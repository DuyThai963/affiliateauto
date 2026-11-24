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

Route::get('/', function () {
    return redirect()->route('dashboard');
});

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
            return response()->json(['status' => 'unauthorized'], 403);
        }
    }

    $todayDate = Carbon::today()->toDateString();
    $lastResetDate = (string) SystemSetting::get('last_api_usage_reset_date', '');
    if ($lastResetDate !== $todayDate) {
        Artisan::call('app:reset-daily-api-usage');
        SystemSetting::set('last_api_usage_reset_date', $todayDate, 'Last date API usage was reset');
    }

    if (!Cache::has('cron_produce_buffer_lock')) {
        Artisan::call('app:produce-buffer-content');
        Cache::put('cron_produce_buffer_lock', true, now()->addMinutes(14));
    }

    Artisan::call('schedule:dispatch-posts');

    return response()->json([
        'status' => 'ok',
        'timestamp' => Carbon::now()->toIso8601String(),
    ]);
})->name('api.cron.tick');
