<?php

use App\Livewire\ApiKeys\ApiKeyIndex;
use App\Livewire\Channels\ChannelIndex;
use App\Livewire\Dashboard\LiveMonitor;
use App\Livewire\Logs\SystemLogViewer;
use App\Livewire\Products\ProductIndex;
use App\Models\SystemSetting;
use App\Models\VideoTask;
use App\Services\TelegramCommandService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', LiveMonitor::class)->name('home');
Route::get('/dashboard', LiveMonitor::class)->name('dashboard');
Route::get('/channels', ChannelIndex::class)->name('channels.index');
Route::get('/api-keys', ApiKeyIndex::class)->name('api-keys.index');
Route::get('/products', ProductIndex::class)->name('products.index');
Route::get('/system-logs', SystemLogViewer::class)->name('system-logs.index');

// Stream video trong Buffer Vault phục vụ Modal xem trước (Preview)
Route::get('/vault/video/{task}', function (VideoTask $task) {
    if (!$task->video_path || !File::exists($task->video_path)) {
        abort(404, 'Không tìm thấy tệp video trong Buffer Vault.');
    }

    return response()->file($task->video_path, [
        'Content-Type' => 'video/mp4',
        'Accept-Ranges' => 'bytes',
    ]);
})->name('vault.video.stream');

// Webhook nhận lệnh từ Telegram Bot
Route::post('/api/telegram/webhook', function (Request $request, TelegramCommandService $telegramService) {
    $expectedSecret = (string) env('TELEGRAM_WEBHOOK_SECRET', '');
    if (!empty($expectedSecret)) {
        $providedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
        if (!hash_equals($expectedSecret, $providedSecret)) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 403);
        }
    }

    $telegramService->handleUpdate($request->all());

    return response()->json(['ok' => true]);
})->name('api.telegram.webhook');

// Endpoint Cron HTTP cho Render / cron-job.org (chạy mỗi 5 phút)
Route::match(['get', 'post'], '/api/cron/tick', function (Request $request) {
    $expectedToken = (string) env('CRON_SECRET_TOKEN', '');
    if (!empty($expectedToken)) {
        $providedToken = (string) ($request->query('token') ?: $request->header('X-Cron-Token', ''));
        if (!hash_equals($expectedToken, $providedToken)) {
            Log::warning('[CRON TICK REJECTED] Sai Secret Token: ' . $providedToken);
            return response()->json(['status' => 'unauthorized'], 403);
        }
    }

    $now = Carbon::now();
    $nowString = $now->format('H:i:s d/m/Y');

    // 1. Cập nhật mốc thời gian Cron Tick gần nhất để Dashboard hiển thị ngay
    SystemSetting::set('last_cron_tick_at', $nowString, 'Thời điểm chạy cron gần nhất từ cron-job.org');

    Log::info("--- [CRON TICK NHỊP TIM] Nhận tín hiệu từ cron-job.org lúc {$nowString} ---");

    $todayDate = $now->toDateString();
    $lastResetDate = (string) SystemSetting::get('last_api_usage_reset_date', '');
    if ($lastResetDate !== $todayDate) {
        Artisan::call('app:reset-daily-api-usage');
        SystemSetting::set('last_api_usage_reset_date', $todayDate, 'Last date API usage was reset');
    }

    // 2. Chạy lệnh nạp đệm (sinh kịch bản)
    if (!Cache::has('cron_produce_buffer_lock')) {
        Artisan::call('app:produce-buffer-content');
        Cache::put('cron_produce_buffer_lock', true, now()->addMinutes(14));
    }

    // 3. Chạy lệnh xuất bản video đúng giờ vàng
    Artisan::call('schedule:dispatch-posts');

    Log::info("--- [CRON TICK HOÀN TẤT] Đã quét kho đệm và lịch trình đăng bài ---");

    return response()->json([
        'status' => 'ok',
        'timestamp' => $now->toIso8601String(),
    ]);
})->name('api.cron.tick');
