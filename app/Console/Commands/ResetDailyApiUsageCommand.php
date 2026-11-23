<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetDailyApiUsageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-daily-api-usage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset số lượng token đã dùng trong ngày (used_today) về 0 cho toàn bộ API Keys';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Đang tiến hành reset hạn mức API hàng ngày...');

        $updatedCount = ApiKey::query()->update([
            'used_today' => 0,
            'reset_at' => Carbon::now(),
        ]);

        $this->info("Đã reset thành công cho {$updatedCount} API Keys.");
        Log::info("ResetDailyApiUsageCommand: Đã reset hạn mức ngày cho {$updatedCount} API Keys lúc 00:00.");

        return Command::SUCCESS;
    }
}

