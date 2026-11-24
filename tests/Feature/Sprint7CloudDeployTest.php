<?php

namespace Tests\Feature;

use App\Core\Contracts\NotificationBotInterface;
use App\Core\DTOs\PublishPayloadDto;
use App\Core\DTOs\ScriptDataDto;
use App\Drivers\LLM\ClaudeLlmDriver;
use App\Drivers\Publisher\TelegramCopilotPublisherDriver;
use App\Drivers\VideoGen\GeminiVeo3Driver;
use App\Models\ApiKey;
use App\Models\Channel;
use App\Models\SystemSetting;
use App\Models\VideoTask;
use App\Services\CloudStorageSyncService;
use App\Services\TelegramCommandService;
use App\Services\TokenManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Sprint7CloudDeployTest extends TestCase
{
    use RefreshDatabase;

    public function test_claude_llm_driver_generates_structured_json(): void
    {
        $apiKey = ApiKey::query()->create([
            'provider' => 'claude',
            'key_name' => 'Claude Test Key',
            'api_key' => 'sk-ant-test-key',
            'daily_limit' => 100,
            'used_today' => 0,
            'cooldown_seconds' => 5,
            'is_active' => true,
        ]);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode([
                            'title' => 'Review nhanh bàn phím cơ mini',
                            'hook_text' => 'Gõ sướng tay không tưởng!',
                            'voice_script' => 'Chiếc bàn phím cơ nhỏ gọn này giúp góc làm việc gọn hẳn.',
                            'visual_prompts' => [
                                ['start_sec' => 0, 'end_sec' => 5, 'visual_query' => 'Close up mechanical keyboard'],
                            ],
                            'caption' => 'Bàn phím cơ mini giá học sinh',
                            'hashtags' => '#review #banphimco',
                        ]),
                    ],
                ],
            ], 200),
        ]);

        $driver = new ClaudeLlmDriver(app(TokenManagerService::class));
        $scriptDto = $driver->generateScript('Viết kịch bản bàn phím cơ');

        $this->assertInstanceOf(ScriptDataDto::class, $scriptDto);
        $this->assertEquals('Review nhanh bàn phím cơ mini', $scriptDto->title);
        $this->assertEquals(1, $apiKey->fresh()->used_today);
    }

    public function test_gemini_veo3_driver_generates_and_downloads_clip(): void
    {
        $apiKey = ApiKey::query()->create([
            'provider' => 'gemini',
            'key_name' => 'Gemini Veo Key',
            'api_key' => 'AIzaSyVeoTestKey',
            'daily_limit' => 50,
            'used_today' => 0,
            'cooldown_seconds' => 5,
            'is_active' => true,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/*:predictLongRunning' => Http::response([
                'name' => 'operations/veo-op-123',
            ], 200),
            'generativelanguage.googleapis.com/v1beta/operations/veo-op-123' => Http::response([
                'done' => true,
                'response' => [
                    'generateVideoResponse' => [
                        'generatedSamples' => [
                            [
                                'video' => [
                                    'uri' => 'https://generativelanguage.googleapis.com/v1beta/files/veo-clip-123:download',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
            'generativelanguage.googleapis.com/v1beta/files/veo-clip-123:download' => Http::response('FAKE_VEO3_MP4_BYTES', 200),
        ]);

        $outputPath = storage_path('app/temp_assets/test_veo3_clip.mp4');
        $driver = new GeminiVeo3Driver(app(TokenManagerService::class));
        $resultPath = $driver->generateClip('Aesthetic product shot', $outputPath, [
            'max_poll_attempts' => 2,
            'poll_interval_seconds' => 0,
        ]);

        $this->assertEquals($outputPath, $resultPath);
        $this->assertFileExists($outputPath);
        $this->assertEquals('FAKE_VEO3_MP4_BYTES', File::get($outputPath));
        $this->assertEquals(1, $apiKey->fresh()->used_today);

        File::delete($outputPath);
    }

    public function test_cloud_storage_sync_uploads_to_google_drive_api_and_deletes_local(): void
    {
        putenv('CLOUD_STORAGE_DRIVER=gdrive_api');
        putenv('GOOGLE_DRIVE_CLIENT_ID=test-client-id');
        putenv('GOOGLE_DRIVE_CLIENT_SECRET=test-client-secret');
        putenv('GOOGLE_DRIVE_REFRESH_TOKEN=test-refresh-token');
        putenv('GOOGLE_DRIVE_FOLDER_ID=root-5tb-folder');

        $_ENV['CLOUD_STORAGE_DRIVER'] = 'gdrive_api';
        $_ENV['GOOGLE_DRIVE_CLIENT_ID'] = 'test-client-id';
        $_ENV['GOOGLE_DRIVE_CLIENT_SECRET'] = 'test-client-secret';
        $_ENV['GOOGLE_DRIVE_REFRESH_TOKEN'] = 'test-refresh-token';
        $_ENV['GOOGLE_DRIVE_FOLDER_ID'] = 'root-5tb-folder';

        try {
            Http::fake([
                'oauth2.googleapis.com/token' => Http::response([
                    'access_token' => 'ya29.mock-access-token',
                ], 200),
                'www.googleapis.com/drive/v3/files*' => Http::response([
                    'files' => [
                        ['id' => 'folder-123', 'name' => 'existing'],
                    ],
                ], 200),
                'www.googleapis.com/upload/drive/v3/files*' => Http::response([
                    'id' => 'gdrive-file-999',
                    'size' => '1024',
                ], 200),
            ]);

            $channel = Channel::query()->create([
                'platform' => 'tiktok',
                'channel_name' => '@drive_api_test',
                'type' => 'affiliate',
                'browser_profile_path' => 'storage/app/browser_profiles/drive_test',
                'status' => 'active',
            ]);

            $localFile = storage_path('app/buffer_vault/test_gdrive_upload.mp4');
            File::ensureDirectoryExists(dirname($localFile));
            File::put($localFile, 'DUMMY_MP4_FOR_DRIVE');

            $task = VideoTask::query()->create([
                'channel_id' => $channel->id,
                'task_type' => 'affiliate_original',
                'title' => 'Drive API Sync Test',
                'status' => 'published',
                'video_path' => $localFile,
                'published_at' => now(),
            ]);

            $syncService = app(CloudStorageSyncService::class);
            $result = $syncService->archivePublishedVideo($task);

            $this->assertTrue($result);
            $this->assertFileDoesNotExist($localFile);
            $task->refresh();
            $this->assertEquals('https://drive.google.com/file/d/gdrive-file-999/view', $task->video_path);
        } finally {
            putenv('CLOUD_STORAGE_DRIVER=local');
            $_ENV['CLOUD_STORAGE_DRIVER'] = 'local';
        }
    }

    public function test_telegram_webhook_and_copilot_publisher_and_cron_tick(): void
    {
        Queue::fake();

        putenv('TELEGRAM_ADMIN_CHAT_ID=123456789');
        putenv('TELEGRAM_BOT_TOKEN=bot-token-demo');
        putenv('TELEGRAM_WEBHOOK_SECRET=secret-tg-123');
        putenv('CRON_SECRET_TOKEN=secret-cron-123');

        $_ENV['TELEGRAM_ADMIN_CHAT_ID'] = '123456789';
        $_ENV['TELEGRAM_BOT_TOKEN'] = 'bot-token-demo';
        $_ENV['TELEGRAM_WEBHOOK_SECRET'] = 'secret-tg-123';
        $_ENV['CRON_SECRET_TOKEN'] = 'secret-cron-123';

        $_SERVER['TELEGRAM_ADMIN_CHAT_ID'] = '123456789';
        $_SERVER['TELEGRAM_BOT_TOKEN'] = 'bot-token-demo';
        $_SERVER['TELEGRAM_WEBHOOK_SECRET'] = 'secret-tg-123';
        $_SERVER['CRON_SECRET_TOKEN'] = 'secret-cron-123';

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        // 1. Test Telegram /mode command via Webhook
        SystemSetting::set('master_operation_mode', 'auto_pilot');
        $webhookResponse = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'secret-tg-123',
        ])->postJson(route('api.telegram.webhook'), [
            'message' => [
                'chat' => ['id' => '123456789'],
                'text' => '/mode',
            ],
        ]);

        $webhookResponse->assertStatus(200);
        $this->assertEquals('co_pilot', SystemSetting::get('master_operation_mode'));

        // 2. Test TelegramCopilotPublisherDriver
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@tg_copilot',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/tg_copilot',
            'status' => 'active',
        ]);

        $copilotDriver = new TelegramCopilotPublisherDriver(app(NotificationBotInterface::class));
        $published = $copilotDriver->publish(new PublishPayloadDto(
            taskId: 101,
            channelId: $channel->id,
            videoFilePath: 'https://drive.google.com/file/d/demo/view',
            caption: 'Deal hot hôm nay',
            hashtags: '#dealhot',
            productName: 'Tai nghe Bluetooth'
        ));
        $this->assertTrue($published);

        // 3. Test Cron Tick Endpoint (unauthorized vs authorized)
        $this->getJson(route('api.cron.tick'))->assertStatus(403);

        $this->getJson(route('api.cron.tick', ['token' => 'secret-cron-123']))
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_system_log_viewer_can_clear_logs_and_extract_clean_errors(): void
    {
        $logPath = storage_path('logs/laravel.log');
        $dummyLog = implode("\n", [
            '[2026-11-24 07:19:13] production.INFO: SystemLogViewer: Đã tạo tác vụ chạy thử',
            'INFO',
            '"}',
            '#37 {main}',
            '#36 /var/www/artisan(13): Illuminate\Foundation\Application->handleCommand()',
            '#35 /var/www/vendor/laravel/framework/Application.php(1235)',
            '[stacktrace]',
            'ERR',
            '[previous exception] [object] (PDOException(code: 7): SQLSTATE[08006] could not open certificate file "/root/.postgresql/postgresql.crt": Permission denied at Connector.php:67)',
            '#0 /var/www/vendor/laravel/framework/Connector.php(67): PDO::connect()',
        ]);
        File::put($logPath, $dummyLog);

        $viewer = new \App\Livewire\Logs\SystemLogViewer();
        $cleanErrors = $viewer->getCleanErrors();

        // Must contain the real error line and vital info
        $this->assertStringContainsString('could not open certificate file', $cleanErrors);
        $this->assertStringContainsString('Permission denied', $cleanErrors);
        $this->assertStringContainsString('SystemLogViewer:', $cleanErrors);

        // Must NOT contain redundant stack trace lines
        $this->assertStringNotContainsString('#37 {main}', $cleanErrors);
        $this->assertStringNotContainsString('#36 /var/www/artisan', $cleanErrors);
        $this->assertStringNotContainsString('[stacktrace]', $cleanErrors);

        // Test clear logs
        $viewer->clearLogs();
        $this->assertStringContainsString('NHẬT KÝ ĐÃ ĐƯỢC XÓA SẠCH', (string) File::get($logPath));
    }
}
