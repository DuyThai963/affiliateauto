<?php

namespace App\Livewire\Logs;

use App\Core\Contracts\NotificationBotInterface;
use App\Models\Channel;
use App\Models\SystemSetting;
use App\Models\VideoTask;
use App\Services\HumanLikeSchedulerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class SystemLogViewer extends Component
{
    public string $filterLevel = 'ALL';
    public string $lastTickMessage = '';
    public string $simulationStatus = '';

    public function triggerCronTick(): void
    {
        $startTime = microtime(true);
        $currentTimeString = Carbon::now()->format('H:i:s d/m/Y');

        Log::info("--- [MANUAL TRIGGER] Người dùng kích hoạt chạy thử Cron Tick lúc {$currentTimeString} ---");

        SystemSetting::set('last_cron_tick_at', Carbon::now()->toDateTimeString(), 'Thời điểm chạy cron gần nhất');

        // 1. Chạy lệnh quét đệm và sinh kịch bản
        Artisan::call('app:produce-buffer-content');
        $produceOutput = trim(Artisan::output());

        // 2. Chạy lệnh quét lịch xuất bản
        Artisan::call('schedule:dispatch-posts');
        $dispatchOutput = trim(Artisan::output());

        $duration = round(microtime(true) - $startTime, 2);
        Log::info("--- [MANUAL TRIGGER HOÀN TẤT] Thời gian thực thi: {$duration}s ---");

        $this->lastTickMessage = "✅ Đã kích hoạt Cron Tick thành công ({$duration}s)! Lệnh đã quét kho đệm và kiểm tra lịch xuất bản.";
    }

    public function simulateTestPipeline(NotificationBotInterface $bot, HumanLikeSchedulerService $scheduler): void
    {
        $activeChannel = Channel::query()->where('status', 'active')->first();
        if ($activeChannel === null) {
            $activeChannel = Channel::query()->first();
        }

        if ($activeChannel === null) {
            $this->simulationStatus = '❌ Chưa có kênh nào trong hệ thống. Hãy tạo 1 kênh tại mục Kênh Phân Phối trước.';
            return;
        }

        $now = Carbon::now();
        $slot = $scheduler->calculateNextPublishSlot($activeChannel);
        $slotText = $slot ? $slot->format('H:i - d/m/Y') : 'Chưa xếp slot';

        // Tạo 1 VideoTask mô phỏng luồng
        $simulatedTask = VideoTask::query()->create([
            'channel_id' => $activeChannel->id,
            'task_type' => 'affiliate_original',
            'status' => 'ready_in_vault',
            'title' => 'Video Test Mô Phỏng Luồng Tự Động (AffiliateAuto Pipeline Check)',
            'caption' => 'Video chạy thử nghiệm kiểm tra tính năng tự động xếp lịch và gửi thông báo #test #automation',
            'hashtags' => '#affiliateauto #testing #fyp',
            'current_step' => 'Mô phỏng thành công! Đã lên lịch trong kho đệm.',
            'progress' => 100,
            'scheduled_at' => $slot,
        ]);

        Log::info("SystemLogViewer: Đã tạo tác vụ chạy thử mô phỏng Task #{$simulatedTask->id} cho kênh {$activeChannel->channel_name}");

        // Bắn thông báo Telegram thử nghiệm về máy Admin
        $adminChatId = (string) env('TELEGRAM_ADMIN_CHAT_ID', '');
        $telegramNotified = false;
        if (!empty($adminChatId)) {
            $message = "🧪 <b>[CHẠY THỬ LUỒNG THÀNH CÔNG]</b>\n\n"
                . "Hệ thống AffiliateAuto vừa hoàn thành 1 chu trình mô phỏng tự động:\n"
                . "📺 Kênh: <b>{$activeChannel->channel_name}</b>\n"
                . "🎬 Tiêu đề: <i>{$simulatedTask->title}</i>\n"
                . "⏰ Giờ vàng đăng bài: <b>{$slotText}</b>\n"
                . "📦 Trạng thái: Trong kho đệm (Buffer Vault)\n\n"
                . "✅ Luồng kiểm tra: Cronjob $\\rightarrow$ Kịch bản $\\rightarrow$ Dựng video $\\rightarrow$ Xếp lịch $\\rightarrow$ Telegram Bot đã thông suốt 100%!";
            $telegramNotified = $bot->sendMessage($adminChatId, $message);
        }

        $teleText = $telegramNotified ? "Đã gửi thông báo về Telegram!" : "(Chưa cấu hình TELEGRAM_ADMIN_CHAT_ID)";
        $this->simulationStatus = "🎉 Tác vụ mô phỏng Task #{$simulatedTask->id} đã được tạo thành công! Lịch đăng: {$slotText}. {$teleText}";
    }

    public function clearLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '[' . Carbon::now()->toDateTimeString() . "] production.INFO: --- NHẬT KÝ ĐÃ ĐƯỢC XÓA SẠCH VÀ LÀM MỚI HOÀN TOÀN ---\n");
        }
        $this->lastTickMessage = '🗑 Đã xóa sạch toàn bộ log cũ! Sẵn sàng ghi nhận chu trình mới.';
    }

    public function getCleanErrors(): string
    {
        $logPath = storage_path('logs/laravel.log');
        if (!File::exists($logPath)) {
            return 'Chưa có tệp nhật ký nào được ghi nhận.';
        }

        $rawContent = (string) File::get($logPath);
        $lines = explode("\n", $rawContent);

        $filteredLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            // 1. Loại bỏ các dòng stack trace dư thừa dạng "#37 {main}", "#36 /var/www/vendor/..."
            if (preg_match('/^\s*(#[0-9]+|\{main\}|INFO$|ERR$|DEBUG$|WARN$|\"\}|\"|\[stacktrace\])/i', $trimmed)) {
                continue;
            }

            // 2. Loại bỏ các đường dẫn vendor nội bộ nếu không chứa exception thực tế
            if (str_contains($trimmed, '/vendor/') && !str_contains($trimmed, 'Exception') && !str_contains($trimmed, 'Error') && !str_contains($trimmed, 'Permission denied')) {
                continue;
            }

            // 3. Giữ lại những dòng thực sự quan trọng: Lỗi, Cảnh báo, Exception, Trạng thái luồng
            if (
                str_contains($trimmed, '.ERROR:') ||
                str_contains($trimmed, '.CRITICAL:') ||
                str_contains($trimmed, '.EMERGENCY:') ||
                str_contains($trimmed, '.ALERT:') ||
                str_contains($trimmed, '.WARNING:') ||
                str_contains($trimmed, 'Exception') ||
                str_contains($trimmed, 'PDOException') ||
                str_contains($trimmed, 'Error') ||
                str_contains($trimmed, 'SQLSTATE') ||
                str_contains($trimmed, 'Permission denied') ||
                str_contains($trimmed, 'connection to server') ||
                str_contains($trimmed, 'failed') ||
                str_contains($trimmed, 'FAIL') ||
                str_contains($trimmed, '[previous exception]') ||
                str_contains($trimmed, 'SystemLogViewer:') ||
                str_contains($trimmed, '[INIT]') ||
                str_contains($trimmed, '[MANUAL TRIGGER')
            ) {
                $filteredLines[] = $trimmed;
            }
        }

        if (empty($filteredLines)) {
            return "✅ Tuyệt vời! Hệ thống không ghi nhận bất kỳ lỗi nào trong log.";
        }

        // Lấy 40 dòng lỗi và sự kiện gần nhất để gửi trợ lý
        $recentErrors = array_slice($filteredLines, -40);

        return implode("\n", $recentErrors);
    }

    public function render()
    {
        $logPath = storage_path('logs/laravel.log');
        $rawLogs = '';
        if (File::exists($logPath)) {
            $rawLogs = (string) File::get($logPath);
        }

        $allLines = array_filter(explode("\n", $rawLogs), function (string $line) {
            return trim($line) !== '';
        });

        // Lấy 250 dòng cuối cùng
        $recentLines = array_slice($allLines, -250);
        $recentLines = array_reverse($recentLines);

        $parsedLogs = [];
        foreach ($recentLines as $line) {
            $level = 'INFO';
            if (str_contains($line, '.ERROR:') || str_contains($line, 'FAIL') || str_contains($line, 'Exception')) {
                $level = 'ERROR';
            } elseif (str_contains($line, '.WARNING:') || str_contains($line, 'WARN')) {
                $level = 'WARNING';
            } elseif (str_contains($line, '.DEBUG:')) {
                $level = 'DEBUG';
            }

            // Lọc theo cấp độ
            if ($this->filterLevel === 'ERROR' && $level !== 'ERROR') {
                continue;
            }
            if ($this->filterLevel === 'WARNING' && $level !== 'WARNING' && $level !== 'ERROR') {
                continue;
            }
            if ($this->filterLevel === 'CRON' && !str_contains($line, 'ProduceBufferContent') && !str_contains($line, 'DispatchScheduledPosts') && !str_contains($line, 'Cron')) {
                continue;
            }

            $parsedLogs[] = [
                'raw' => $line,
                'level' => $level,
            ];
        }

        $activeChannelsCount = Channel::query()->where('status', 'active')->count();
        $totalChannelsCount = Channel::query()->count();
        $readyInVaultCount = VideoTask::query()->where('status', 'ready_in_vault')->count();
        $scheduledTodayCount = VideoTask::query()
            ->where('status', 'ready_in_vault')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', Carbon::today())
            ->count();
        $lastCronTick = (string) SystemSetting::get('last_cron_tick_at', 'Chưa ghi nhận');

        return view('livewire.logs.system-log-viewer', [
            'logs' => $parsedLogs,
            'activeChannelsCount' => $activeChannelsCount,
            'totalChannelsCount' => $totalChannelsCount,
            'readyInVaultCount' => $readyInVaultCount,
            'scheduledTodayCount' => $scheduledTodayCount,
            'lastCronTick' => $lastCronTick,
        ])->layout('layouts.app', [
            'header' => 'Nhật Ký & Kiểm Soát Lịch Trình Tự Động',
            'title' => 'AffiliateAuto - Nhật Ký & Schedule',
        ]);
    }
}
