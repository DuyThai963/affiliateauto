<?php

namespace App\Jobs;

use App\Core\Contracts\PublisherDriverInterface;
use App\Core\DTOs\PublishPayloadDto;
use App\Drivers\Publisher\ShopeePlaywrightDriver;
use App\Drivers\Publisher\TikTokPlaywrightDriver;
use App\Jobs\ArchiveVideoToCloudJob;
use App\Models\VideoTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tác vụ xuất bản chạy tuần tự để không xung đột trình duyệt.
     */
    public int $timeout = 360;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $videoTaskId
    ) {
        $this->onQueue('publishing');
    }

    /**
     * Execute the job.
     */
    public function handle(?PublisherDriverInterface $customDriver = null): void
    {
        $videoTask = VideoTask::query()->with(['channel', 'product'])->find($this->videoTaskId);

        if ($videoTask === null) {
            Log::error("PublishVideoJob: Không tìm thấy VideoTask #{$this->videoTaskId}");
            return;
        }

        $channel = $videoTask->channel;
        if ($channel === null) {
            Log::error("PublishVideoJob: VideoTask #{$this->videoTaskId} không có kênh đích.");
            $videoTask->update([
                'status' => 'failed',
                'current_step' => 'Không tìm thấy kênh đích để xuất bản.',
                'error_log' => 'Channel is null',
            ]);
            return;
        }

        try {
            // 1. Chuyển trạng thái publishing
            $videoTask->update([
                'status' => 'publishing',
                'current_step' => 'Đang khởi chạy trình duyệt tự động hóa (Playwright)...',
                'progress' => 80,
            ]);

            // 2. Chọn Driver phù hợp
            $driver = $customDriver;
            if ($driver === null) {
                $configuredDriver = (string) env('PUBLISHER_DRIVER', 'tiktok');
                if (in_array($configuredDriver, ['mock', 'telegram_copilot'], true)) {
                    $driver = app(PublisherDriverInterface::class);
                } else {
                    $platform = strtolower($channel->platform);
                    if ($platform === 'shopee') {
                        $driver = app(ShopeePlaywrightDriver::class);
                    } else {
                        $driver = app(TikTokPlaywrightDriver::class);
                    }
                }
            }

            // 3. Chuẩn bị Payload
            $payload = new PublishPayloadDto(
                taskId: $videoTask->id,
                channelId: $channel->id,
                videoFilePath: $videoTask->video_path ?: '',
                caption: $videoTask->caption ?: ($videoTask->title ?: 'Video xuất bản tự động'),
                hashtags: $videoTask->hashtags ?: '#affiliate #trending',
                productName: $channel->type === 'affiliate' ? $videoTask->product?->product_name : null
            );

            $videoTask->update([
                'current_step' => "Đang tải video lên cổng {$channel->platform} và gắn thông tin...",
                'progress' => 90,
            ]);

            // 4. Thực thi xuất bản qua Driver
            $isSuccess = $driver->publish($payload);

            if ($isSuccess) {
                $now = now();
                $videoTask->update([
                    'status' => 'published',
                    'current_step' => 'Đã xuất bản thành công lên kênh! Đang đồng bộ kho Cloud 5TB...',
                    'progress' => 100,
                    'published_at' => $now,
                ]);

                $channel->update([
                    'last_posted_at' => $now,
                ]);

                // Tự động archive sang Kho Cloud 5TB
                ArchiveVideoToCloudJob::dispatch($videoTask->id);

                Log::info("PublishVideoJob: Task #{$videoTask->id} xuất bản thành công lên kênh {$channel->channel_name} ({$channel->platform})");
            } else {
                $videoTask->update([
                    'status' => 'failed',
                    'current_step' => 'Driver trả về kết quả xuất bản không thành công.',
                ]);
            }

        } catch (\Throwable $throwable) {
            $errorMessage = $throwable->getMessage();
            Log::error("PublishVideoJob: Lỗi khi xuất bản Task #{$videoTask->id}: {$errorMessage}");

            $isCaptchaOrAuth = str_contains($errorMessage, 'Captcha') || str_contains($errorMessage, 'hết hạn');
            if ($isCaptchaOrAuth) {
                $channel->update(['status' => 'needs_relogin']);
            }

            $videoTask->update([
                'status' => 'failed',
                'current_step' => "Thất bại: {$errorMessage}",
                'error_log' => $errorMessage,
            ]);
        }
    }
}

