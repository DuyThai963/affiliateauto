<?php

namespace App\Jobs;

use App\Core\Contracts\LlmDriverInterface;
use App\Models\VideoTask;
use App\Services\ScriptPromptBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateScriptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $videoTaskId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(
        LlmDriverInterface $llmDriver,
        ScriptPromptBuilder $promptBuilder
    ): void {
        $videoTask = VideoTask::query()->with(['channel', 'product'])->find($this->videoTaskId);

        if ($videoTask === null) {
            Log::error("GenerateScriptJob: Không tìm thấy VideoTask #{$this->videoTaskId}");
            return;
        }

        try {
            // 1. Cập nhật trạng thái scripting
            $videoTask->update([
                'status' => 'scripting',
                'current_step' => 'Đang gọi AI sinh kịch bản JSON...',
                'progress' => 10,
            ]);

            // 2. Xây dựng prompt chuyên biệt theo loại kênh (Growth vs Affiliate)
            $prompt = $promptBuilder->buildPrompt($videoTask->channel, $videoTask->product);

            // 3. Gọi LLM Driver
            $scriptDataDto = $llmDriver->generateScript($prompt, [
                'channel_type' => $videoTask->channel?->type ?? 'growth',
                'product_name' => $videoTask->product?->product_name ?? null,
            ]);

            // 4. Lưu kịch bản có cấu trúc và cập nhật trạng thái assets_ready
            $videoTask->update([
                'title' => $scriptDataDto->title,
                'caption' => $scriptDataDto->caption,
                'hashtags' => $scriptDataDto->hashtags,
                'script_data' => $scriptDataDto->toArray(),
                'status' => 'assets_ready',
                'current_step' => 'Kịch bản hoàn tất, sẵn sàng dựng media',
                'progress' => 25,
            ]);

            Log::info("GenerateScriptJob: Đã sinh kịch bản thành công cho Task #{$videoTask->id} - Tiêu đề: {$scriptDataDto->title}");

        } catch (\Throwable $throwable) {
            $errorMessage = $throwable->getMessage();
            Log::error("GenerateScriptJob: Thất bại khi sinh kịch bản cho Task #{$videoTask->id}: {$errorMessage}");

            $videoTask->update([
                'status' => 'failed',
                'current_step' => 'Lỗi sinh kịch bản AI: ' . substr($errorMessage, 0, 100),
                'error_log' => $throwable->getTraceAsString(),
            ]);

            throw $throwable;
        }
    }
}

