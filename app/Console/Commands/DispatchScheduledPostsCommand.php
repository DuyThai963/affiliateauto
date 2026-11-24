<?php

namespace App\Console\Commands;

use App\Jobs\PublishVideoJob;
use App\Models\VideoTask;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchScheduledPostsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:dispatch-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quét kho đệm (Buffer Vault) và kích hoạt tác vụ xuất bản cho các video đã đến lịch hẹn';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();
        $isSandbox = \App\Models\SystemSetting::get('sandbox_test_mode', 'true') === 'true';

        $query = VideoTask::query()
            ->where('status', 'ready_in_vault')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now);

        if (!$isSandbox) {
            $query->whereHas('channel', function ($subQuery) {
                $subQuery->where('status', 'active');
            });
        }

        $readyTasks = $query->get();

        $count = $readyTasks->count();
        if ($count > 0) {
            $this->info("Tìm thấy {$count} video đã đến lịch hẹn xuất bản.");
            Log::info("DispatchScheduledPostsCommand: Phát hiện {$count} video đã đến giờ hẹn xuất bản!");
        } else {
            Log::info("DispatchScheduledPostsCommand: Quét lịch đăng lúc {$now->format('H:i:s')} - Chưa có video nào đến hạn xuất bản.");
        }

        foreach ($readyTasks as $videoTask) {
            $this->comment("Kích hoạt xuất bản cho Video Task #{$videoTask->id} (Kênh: {$videoTask->channel_id})...");
            
            // Dispatch vào queue
            PublishVideoJob::dispatch($videoTask->id);

            Log::info("DispatchScheduledPostsCommand: Đã dispatch PublishVideoJob cho Task #{$videoTask->id}");
        }

        return Command::SUCCESS;
    }
}

