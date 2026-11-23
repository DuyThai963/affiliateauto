<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Services\SessionHealthcheckService;
use Illuminate\Console\Command;

class CheckAllChannelsHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channel:healthcheck {channel_id? : ID kênh cần kiểm tra cụ thể}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra sức khỏe phiên đăng nhập (Healthcheck) của các kênh TikTok / Shopee bằng Playwright ngầm';

    /**
     * Execute the console command.
     */
    public function handle(SessionHealthcheckService $healthcheckService): int
    {
        $channelId = $this->argument('channel_id');

        if ($channelId) {
            $channel = Channel::query()->find($channelId);

            if ($channel === null) {
                $this->error("Không tìm thấy kênh với ID #{$channelId}.");
                return Command::FAILURE;
            }

            $channels = collect([$channel]);
        } else {
            $channels = Channel::query()->get();
        }

        if ($channels->isEmpty()) {
            $this->warn('Hệ thống chưa có kênh phân phối nào.');
            return Command::SUCCESS;
        }

        $this->info("Bắt đầu kiểm tra sức khỏe phiên đăng nhập cho {$channels->count()} kênh...");

        foreach ($channels as $channel) {
            $this->line("-> Đang kiểm tra kênh #{$channel->id}: {$channel->channel_name} ({$channel->platform})...");

            $result = $healthcheckService->checkChannel($channel);

            if ($result['is_alive']) {
                $this->info("   [OK] Phiên hoạt động tốt: {$result['message']}");
            } else {
                $this->warn("   [FAIL] Phiên không khả dụng: {$result['message']}");
            }
        }

        $this->info('Hoàn tất quét sức khỏe phiên đăng nhập.');
        return Command::SUCCESS;
    }
}

