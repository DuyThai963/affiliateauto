<?php

namespace App\Providers;

use App\Core\Contracts\LlmDriverInterface;
use App\Core\Contracts\NotificationBotInterface;
use App\Core\Contracts\PublisherDriverInterface;
use App\Core\Contracts\TtsDriverInterface;
use App\Core\Contracts\VideoGenDriverInterface;
use App\Core\Contracts\VideoRenderDriverInterface;
use App\Drivers\LLM\ClaudeLlmDriver;
use App\Drivers\LLM\GeminiLlmDriver;
use App\Drivers\LLM\MockLlmDriver;
use App\Drivers\Notification\TelegramBotDriver;
use App\Drivers\Publisher\MockPublisherDriver;
use App\Drivers\Publisher\ShopeePlaywrightDriver;
use App\Drivers\Publisher\TelegramCopilotPublisherDriver;
use App\Drivers\Publisher\TikTokPlaywrightDriver;
use App\Drivers\Render\FFmpegRenderDriver;
use App\Drivers\Render\MockRenderDriver;
use App\Drivers\TTS\EdgeTtsDriver;
use App\Drivers\TTS\MockTtsDriver;
use App\Drivers\VideoGen\GeminiVeo3Driver;
use App\Drivers\VideoGen\MockVideoGenDriver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LlmDriverInterface::class, function ($app) {
            $driver = env('LLM_DRIVER', 'gemini');
            if ($driver === 'mock') {
                return $app->make(MockLlmDriver::class);
            }
            if ($driver === 'claude') {
                return $app->make(ClaudeLlmDriver::class);
            }

            // Tự động chuyển sang Mock AI nếu chưa cấu hình API Key thật nào trong hệ thống
            $tokenManager = $app->make(TokenManagerService::class);
            if ($tokenManager->getAvailableKey('gemini') === null) {
                return $app->make(MockLlmDriver::class);
            }

            return $app->make(GeminiLlmDriver::class);
        });

        $this->app->bind(VideoGenDriverInterface::class, function ($app) {
            $driver = env('VIDEO_GEN_DRIVER', 'mock');
            if ($driver === 'veo3') {
                return $app->make(GeminiVeo3Driver::class);
            }

            return $app->make(MockVideoGenDriver::class);
        });

        $this->app->bind(NotificationBotInterface::class, function ($app) {
            return $app->make(TelegramBotDriver::class);
        });

        $this->app->bind(TtsDriverInterface::class, function ($app) {
            $driver = env('TTS_DRIVER', 'edge');
            if ($driver === 'mock') {
                return $app->make(MockTtsDriver::class);
            }

            return $app->make(EdgeTtsDriver::class);
        });

        $this->app->bind(VideoRenderDriverInterface::class, function ($app) {
            $driver = env('RENDER_DRIVER', 'ffmpeg');
            if ($driver === 'mock') {
                return $app->make(MockRenderDriver::class);
            }

            return $app->make(FFmpegRenderDriver::class);
        });

        $this->app->bind(PublisherDriverInterface::class, function ($app) {
            $driver = env('PUBLISHER_DRIVER', 'tiktok');
            if ($driver === 'mock') {
                return $app->make(MockPublisherDriver::class);
            }
            if ($driver === 'telegram_copilot') {
                return $app->make(TelegramCopilotPublisherDriver::class);
            }
            if ($driver === 'shopee') {
                return $app->make(ShopeePlaywrightDriver::class);
            }

            return $app->make(TikTokPlaywrightDriver::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Tự động làm sạch và giải phóng transaction bị gián đoạn trước mỗi vòng lặp của Queue Worker
        // Ngăn chặn hoàn toàn lỗi 25P02 "current transaction is aborted" trên PostgreSQL
        Queue::looping(function () {
            try {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
            } catch (\Throwable $e) {
                DB::purge();
            }
        });
    }
}
