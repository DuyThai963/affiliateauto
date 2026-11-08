<?php

namespace App\Providers;

use App\Core\Contracts\LlmDriverInterface;
use App\Core\Contracts\PublisherDriverInterface;
use App\Core\Contracts\TtsDriverInterface;
use App\Core\Contracts\VideoRenderDriverInterface;
use App\Drivers\LLM\MockLlmDriver;
use App\Drivers\Publisher\MockPublisherDriver;
use App\Drivers\Render\MockRenderDriver;
use App\Drivers\TTS\MockTtsDriver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LlmDriverInterface::class, MockLlmDriver::class);
        $this->app->bind(TtsDriverInterface::class, MockTtsDriver::class);
        $this->app->bind(VideoRenderDriverInterface::class, MockRenderDriver::class);
        $this->app->bind(PublisherDriverInterface::class, MockPublisherDriver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
