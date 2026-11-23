<?php

namespace App\Console\Commands;

use App\Jobs\GenerateScriptJob;
use App\Models\Channel;
use App\Models\Product;
use App\Models\VideoTask;
use App\Services\TokenManagerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProduceBufferContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:produce-buffer-content {--channel-id= : Chỉ định ID kênh cần nạp đệm}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra kho đệm và tự động kích hoạt AI sản xuất kịch bản video dự trữ';

    /**
     * Execute the console command.
     */
    public function handle(TokenManagerService $tokenManager): int
    {
        $this->info('--- BẮT ĐẦU QUÉT KHO ĐỆM BUFFER VAULT ---');

        $channelIdOption = $this->option('channel-id');
        $channelsQuery = Channel::query()->where('status', 'active');

        if ($channelIdOption !== null) {
            $channelsQuery->where('id', (int) $channelIdOption);
        }

        $activeChannels = $channelsQuery->get();

        if ($activeChannels->isEmpty()) {
            $this->warn('Không có kênh nào đang ở trạng thái active.');
            return Command::SUCCESS;
        }

        $tasksProducedCount = 0;

        foreach ($activeChannels as $channel) {
            // Đếm số lượng video/kịch bản đang có sẵn trong kho đệm
            $currentBufferCount = VideoTask::query()
                ->where('channel_id', $channel->id)
                ->whereIn('status', ['ready_in_vault', 'assets_ready'])
                ->count();

            $this->line("Kênh [{$channel->channel_name}] (Type: {$channel->type}) - Đệm hiện tại: {$currentBufferCount}/10");

            if ($currentBufferCount < 10) {
                // Kiểm tra xem hệ thống còn API Key khả dụng không
                $availableKey = $tokenManager->getAvailableKey('gemini');

                if ($availableKey === null) {
                    $this->warn("Tạm hoãn sinh video cho kênh [{$channel->channel_name}] vì toàn bộ API Keys đều hết quota hoặc cooldown.");
                    break;
                }

                // Nếu là kênh affiliate: Tự động chọn 1 sản phẩm đang bán theo đúng nền tảng của kênh
                $assignedProduct = null;
                if ($channel->type === 'affiliate') {
                    $assignedProduct = Product::query()
                        ->where('is_active', true)
                        ->where('platform', $channel->platform)
                        ->inRandomOrder()
                        ->first();
                    if ($assignedProduct === null) {
                        $this->warn("Kênh [{$channel->channel_name}] là affiliate nhưng chưa có sản phẩm nào active. Bỏ qua.");
                        continue;
                    }
                }

                // Khởi tạo VideoTask ở trạng thái pending
                $videoTask = VideoTask::query()->create([
                    'channel_id' => $channel->id,
                    'product_id' => $assignedProduct?->id,
                    'task_type' => $channel->type === 'growth' ? 'growth_reup_clone' : 'affiliate_original',
                    'status' => 'pending',
                    'current_step' => 'Khởi tạo tác vụ kịch bản dự trữ',
                    'progress' => 0,
                ]);

                // Dispatch Job sản xuất kịch bản
                GenerateScriptJob::dispatch($videoTask->id);

                $tasksProducedCount++;
                $this->info("✔ Đã dispatch GenerateScriptJob cho Task #{$videoTask->id} (Kênh: {$channel->channel_name})");
                Log::info("ProduceBufferContentCommand: Đã dispatch GenerateScriptJob cho Task #{$videoTask->id}");
            }
        }

        $this->info(">>> HOÀN TẤT: Đã kích hoạt sản xuất {$tasksProducedCount} kịch bản mới cho kho đệm! <<<");
        return Command::SUCCESS;
    }
}

