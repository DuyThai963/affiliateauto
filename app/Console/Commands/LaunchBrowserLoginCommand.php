<?php

namespace App\Console\Commands;

use App\Models\Channel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class LaunchBrowserLoginCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channel:login {channel_id : ID của kênh cần đăng nhập}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mở trình duyệt Playwright để người dùng đăng nhập tài khoản TikTok/Shopee thủ công và lưu phiên';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $channelId = (int) $this->argument('channel_id');
        $channel = Channel::query()->find($channelId);

        if ($channel === null) {
            $this->error("Không tìm thấy kênh với ID: {$channelId}");
            return Command::FAILURE;
        }

        $platform = strtolower($channel->platform);
        $profilePath = $channel->browser_profile_path;

        if (empty($profilePath)) {
            $profilePath = 'storage/app/browser_profiles/' . $platform . '_' . $channel->id;
            $channel->update(['browser_profile_path' => $profilePath]);
        }

        $absoluteProfilePath = base_path($profilePath);
        if (!File::exists($absoluteProfilePath)) {
            File::makeDirectory($absoluteProfilePath, 0755, true);
        }

        $this->info("==================================================================");
        $this->info(" ĐĂNG NHẬP PHIÊN TRÌNH DUYỆT TỰ ĐỘNG HÓA (AFFILIATEAUTO)");
        $this->info(" Kênh: {$channel->channel_name} (#{$channel->id})");
        $this->info(" Nền tảng: " . strtoupper($platform));
        $this->info(" Thư mục lưu phiên: {$absoluteProfilePath}");
        $this->info("==================================================================");
        $this->comment("Đang khởi chạy trình duyệt... Hãy thao tác đăng nhập trực tiếp trên cửa sổ vừa mở.");
        $this->comment("Sau khi đăng nhập và hoàn thành OTP/Captcha, HÃY ĐÓNG TRÌNH DUYỆT để lưu phiên.");

        $scriptPath = base_path('scripts/open_browser_login.js');
        $process = new Process([
            'node',
            $scriptPath,
            '--platform',
            $platform,
            '--profile-path',
            $absoluteProfilePath,
        ]);

        // Cho phép người dùng đăng nhập thoải mái (timeout 30 phút)
        $process->setTimeout(1800);

        $process->run(function (string $type, string $buffer) {
            $this->output->write($buffer);
        });

        if ($process->isSuccessful()) {
            // Cập nhật trạng thái kênh thành active nếu trước đó là needs_relogin
            if ($channel->status === 'needs_relogin') {
                $channel->update(['status' => 'active']);
            }
            $this->info("Đã lưu phiên đăng nhập thành công cho kênh: {$channel->channel_name}!");
            return Command::SUCCESS;
        }

        $this->error("Quá trình đăng nhập bị lỗi hoặc bị hủy: " . $process->getErrorOutput());
        return Command::FAILURE;
    }
}

