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
        $isSandbox = \App\Models\SystemSetting::get('sandbox_test_mode', 'true') === 'true';

        $channelsQuery = Channel::query();
        if (!$isSandbox) {
            $channelsQuery->where('status', 'active');
        }

        if ($channelIdOption !== null) {
            $channelsQuery->where('id', (int) $channelIdOption);
        }

        $activeChannels = $channelsQuery->get();

        if ($activeChannels->isEmpty()) {
            if ($isSandbox) {
                // Trong chế độ Sandbox: tự tạo 1 kênh demo để người dùng test luồng
                $demoChannel = Channel::query()->create([
                    'platform' => 'tiktok',
                    'channel_name' => '@review_test_channel',
                    'type' => 'affiliate',
                    'browser_profile_path' => 'storage/app/browser_profiles/tiktok_channel_demo',
                    'status' => 'active',
                    'daily_post_limit' => 5,
                    'min_interval_minutes' => 60,
                ]);
                $activeChannels = collect([$demoChannel]);
                Log::info('ProduceBufferContentCommand: [SANDBOX] Tự động khởi tạo kênh demo @review_test_channel');
            } else {
                $msg = 'ProduceBufferContentCommand: Không có kênh nào đang ở trạng thái active (Cần bật trạng thái Active cho kênh).';
                $this->warn($msg);
                Log::info($msg);
                return Command::SUCCESS;
            }
        }

        $tasksProducedCount = 0;

        foreach ($activeChannels as $channel) {
            // Đếm số lượng video/kịch bản đang có sẵn trong kho đệm
            $currentBufferCount = VideoTask::query()
                ->where('channel_id', $channel->id)
                ->whereIn('status', ['ready_in_vault', 'assets_ready'])
                ->count();

            $this->line("Kênh [{$channel->channel_name}] (Type: {$channel->type}) - Đệm hiện tại: {$currentBufferCount}/10");
            Log::info("ProduceBufferContentCommand: Quét kênh [{$channel->channel_name}] - Kho đệm: {$currentBufferCount}/10 video");

            if ($currentBufferCount < 10) {
                // Kiểm tra xem hệ thống còn API Key khả dụng không
                $availableKey = $tokenManager->getAvailableKey('gemini');
                $isSimulation = ($availableKey === null && env('LLM_DRIVER') !== 'claude');

                if ($availableKey === null && !$isSimulation) {
                    $this->warn("Tạm hoãn sinh video cho kênh [{$channel->channel_name}] vì toàn bộ API Keys đều hết quota hoặc cooldown.");
                    break;
                }

                if ($isSimulation) {
                    $this->info("Kênh [{$channel->channel_name}]: Chưa có API Key thật, tự động dùng kịch bản mô phỏng AI (Simulation Mode) để duy trì luồng tự động.");
                    Log::info("ProduceBufferContentCommand: Kênh [{$channel->channel_name}] dùng Mock AI sinh kịch bản vì chưa cấu hình API Key.");
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

        // Tự động quét và kích hoạt RenderVideoJob cho các tác vụ đã có kịch bản (assets_ready)
        $assetsReadyTasks = VideoTask::query()
            ->where('status', 'assets_ready')
            ->get();

        foreach ($assetsReadyTasks as $readyTask) {
            \App\Jobs\RenderVideoJob::dispatch($readyTask->id);
            Log::info("ProduceBufferContentCommand: Tự động kích hoạt RenderVideoJob cho Task #{$readyTask->id}");
        }

        return Command::SUCCESS;
    }
}

