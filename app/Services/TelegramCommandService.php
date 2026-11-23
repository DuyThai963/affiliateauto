<?php

namespace App\Services;

use App\Core\Contracts\NotificationBotInterface;
use App\Jobs\PublishVideoJob;
use App\Jobs\RenderVideoJob;
use App\Models\ApiKey;
use App\Models\Channel;
use App\Models\SystemSetting;
use App\Models\VideoTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TelegramCommandService
{
    public function __construct(
        protected NotificationBotInterface $notificationBot,
        protected HumanLikeSchedulerService $scheduler
    ) {
    }

    /**
     * Xử lý Webhook Update từ Telegram Bot API.
     *
     * @param array<string, mixed> $update
     */
    public function handleUpdate(array $update): void
    {
        $message = $update['message'] ?? null;
        if (!is_array($message)) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = trim((string) ($message['text'] ?? ''));

        if (empty($chatId) || empty($text)) {
            return;
        }

        $adminChatId = (string) env('TELEGRAM_ADMIN_CHAT_ID', '');
        if (!empty($adminChatId) && $chatId !== $adminChatId) {
            Log::warning("TelegramCommandService: Từ chối truy cập trái phép từ chat_id {$chatId}");
            $this->notificationBot->sendMessage($chatId, '⛔ Bạn không có quyền điều khiển hệ thống AffiliateAuto.');
            return;
        }

        $parts = preg_split('/\s+/', $text);
        $command = strtolower($parts[0] ?? '');
        $argument = $parts[1] ?? null;

        match ($command) {
            '/start', '/help' => $this->handleHelp($chatId),
            '/status' => $this->handleStatus($chatId),
            '/produce' => $this->handleProduce($chatId),
            '/mode' => $this->handleToggleMode($chatId),
            '/approve' => $this->handleApprove($chatId, $argument),
            '/retry' => $this->handleRetry($chatId, $argument),
            '/publish' => $this->handleManualPublish($chatId, $argument),
            default => $this->notificationBot->sendMessage($chatId, "❓ Lệnh không hợp lệ. Gõ /help để xem danh sách lệnh."),
        };
    }

    protected function handleHelp(string $chatId): void
    {
        $helpText = implode("\n", [
            "🤖 <b>AFFILIATEAUTO CONTROL BOT</b>",
            "",
            "/status - Xem tình trạng kênh, kho đệm và API quota",
            "/produce - Kích hoạt sinh kịch bản dự trữ ngay",
            "/approve &lt;id&gt; - Duyệt video chờ ở chế độ Co-Pilot",
            "/retry &lt;id&gt; - Thử lại tác vụ bị lỗi",
            "/publish &lt;id&gt; - Gửi video + caption về Telegram để đăng",
            "/mode - Chuyển đổi Auto-Pilot ↔ Co-Pilot",
        ]);

        $this->notificationBot->sendMessage($chatId, $helpText);
    }

    protected function handleStatus(string $chatId): void
    {
        $activeChannels = Channel::query()->where('status', 'active')->count();
        $reloginChannels = Channel::query()->where('status', 'needs_relogin')->count();
        $readyInVault = VideoTask::query()->where('status', 'ready_in_vault')->count();
        $waitingApproval = VideoTask::query()->where('status', 'waiting_approval')->count();
        $failedTasks = VideoTask::query()->where('status', 'failed')->count();
        $publishedToday = VideoTask::query()
            ->where('status', 'published')
            ->where('published_at', '>=', Carbon::today())
            ->count();

        $activeKeys = ApiKey::query()->where('is_active', true)->get();
        $usedToday = (int) $activeKeys->sum('used_today');
        $dailyLimit = (int) $activeKeys->sum('daily_limit');
        $mode = (string) SystemSetting::get('master_operation_mode', 'auto_pilot');

        $statusText = implode("\n", [
            "📊 <b>TRẠNG THÁI HỆ THỐNG AFFILIATEAUTO</b>",
            "• Chế độ: <b>{$mode}</b>",
            "• Kênh hoạt động: <b>{$activeChannels}</b> (Cần đăng nhập lại: {$reloginChannels})",
            "• Kho đệm (Ready in Vault): <b>{$readyInVault}</b>",
            "• Chờ duyệt (Co-Pilot): <b>{$waitingApproval}</b>",
            "• Đã đăng hôm nay: <b>{$publishedToday}</b>",
            "• Tác vụ lỗi: <b>{$failedTasks}</b>",
            "• API Quota hôm nay: <b>{$usedToday} / {$dailyLimit}</b>",
        ]);

        $this->notificationBot->sendMessage($chatId, $statusText);
    }

    protected function handleProduce(string $chatId): void
    {
        Artisan::call('app:produce-buffer-content');
        $output = trim(Artisan::output());
        $summary = mb_substr($output ?: 'Đã kích hoạt lệnh nạp kho đệm.', 0, 800);
        $this->notificationBot->sendMessage($chatId, "⚡ <b>KẾT QUẢ NẠP KHO ĐỆM</b>\n<pre>{$summary}</pre>");
    }

    protected function handleToggleMode(string $chatId): void
    {
        $currentMode = (string) SystemSetting::get('master_operation_mode', 'auto_pilot');
        $nextMode = $currentMode === 'auto_pilot' ? 'co_pilot' : 'auto_pilot';
        SystemSetting::set('master_operation_mode', $nextMode, 'Master operation mode toggled via Telegram');

        $this->notificationBot->sendMessage($chatId, "🔄 Đã chuyển chế độ vận hành sang: <b>{$nextMode}</b>");
    }

    protected function handleApprove(string $chatId, ?string $rawTaskId): void
    {
        $taskId = (int) $rawTaskId;
        if ($taskId <= 0) {
            $this->notificationBot->sendMessage($chatId, '⚠️ Cú pháp: <code>/approve &lt;task_id&gt;</code>');
            return;
        }

        $task = VideoTask::query()->with('channel')->find($taskId);
        if ($task === null || $task->status !== 'waiting_approval') {
            $this->notificationBot->sendMessage($chatId, "⚠️ Không tìm thấy tác vụ #{$taskId} ở trạng thái chờ duyệt.");
            return;
        }

        $slot = $task->channel ? $this->scheduler->calculateNextPublishSlot($task->channel) : Carbon::now()->addMinutes(10);
        $scheduledTime = $slot ?: Carbon::now();

        $task->update([
            'status' => 'ready_in_vault',
            'current_step' => 'Đã phê duyệt qua Telegram! Lịch: ' . $scheduledTime->format('H:i d/m'),
            'scheduled_at' => $scheduledTime,
        ]);

        $this->notificationBot->sendMessage($chatId, "✅ Đã duyệt Task #{$taskId}. Lịch đăng: <b>{$scheduledTime->format('H:i d/m')}</b>");
    }

    protected function handleRetry(string $chatId, ?string $rawTaskId): void
    {
        $taskId = (int) $rawTaskId;
        if ($taskId <= 0) {
            $this->notificationBot->sendMessage($chatId, '⚠️ Cú pháp: <code>/retry &lt;task_id&gt;</code>');
            return;
        }

        $task = VideoTask::query()->find($taskId);
        if ($task === null) {
            $this->notificationBot->sendMessage($chatId, "⚠️ Không tìm thấy tác vụ #{$taskId}.");
            return;
        }

        if (!empty($task->video_path) && File::exists($task->video_path)) {
            $task->update([
                'status' => 'ready_in_vault',
                'current_step' => 'Thử lại xuất bản qua Telegram...',
                'error_log' => null,
            ]);
            PublishVideoJob::dispatch($task->id);
            $this->notificationBot->sendMessage($chatId, "🔁 Đã đưa Task #{$taskId} vào hàng đợi xuất bản.");
            return;
        }

        $task->update([
            'status' => 'assets_ready',
            'current_step' => 'Thử lại dựng video qua Telegram...',
            'error_log' => null,
            'progress' => 30,
        ]);
        RenderVideoJob::dispatch($task->id);
        $this->notificationBot->sendMessage($chatId, "🔁 Đã đưa Task #{$taskId} vào hàng đợi dựng video.");
    }

    protected function handleManualPublish(string $chatId, ?string $rawTaskId): void
    {
        $taskId = (int) $rawTaskId;
        if ($taskId <= 0) {
            $this->notificationBot->sendMessage($chatId, '⚠️ Cú pháp: <code>/publish &lt;task_id&gt;</code>');
            return;
        }

        $task = VideoTask::query()->with(['channel', 'product'])->find($taskId);
        if ($task === null || empty($task->video_path)) {
            $this->notificationBot->sendMessage($chatId, "⚠️ Tác vụ #{$taskId} chưa có video để gửi.");
            return;
        }

        $lines = [
            "🎬 <b>VIDEO #{$task->id} - {$task->title}</b>",
            "📺 Kênh: " . ($task->channel?->channel_name ?? 'N/A'),
            "",
            trim(($task->caption ?? '') . ' ' . ($task->hashtags ?? '')),
        ];

        if ($task->product) {
            $lines[] = "";
            $lines[] = "🛒 Sản phẩm: <b>{$task->product->product_name}</b>";
        }

        $this->notificationBot->sendVideo($chatId, $task->video_path, implode("\n", $lines));
    }
}
