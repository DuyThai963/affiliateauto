<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reset hạn mức API Keys mỗi ngày vào lúc 00:00
Schedule::command('app:reset-daily-api-usage')->dailyAt('00:00');

// Tự động sản xuất kịch bản dự trữ cho kho đệm (Buffer Vault) mỗi 15 phút
Schedule::command('app:produce-buffer-content')->everyFifteenMinutes();

// Quét kho đệm và kích hoạt đăng video theo lịch tự nhiên mỗi 5 phút
Schedule::command('schedule:dispatch-posts')->everyFiveMinutes();
