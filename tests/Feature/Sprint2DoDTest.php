<?php

namespace Tests\Feature;

use App\Core\Contracts\LlmDriverInterface;
use App\Core\DTOs\ScriptDataDto;
use App\Drivers\LLM\GeminiLlmDriver;
use App\Jobs\GenerateScriptJob;
use App\Models\ApiKey;
use App\Models\Channel;
use App\Models\Product;
use App\Models\VideoTask;
use App\Services\ScriptPromptBuilder;
use App\Services\TokenManagerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class Sprint2DoDTest extends TestCase
{
    use RefreshDatabase;

    /**
     * DoD 1: TokenManagerService quản lý Round-Robin, Cooldown và Provider Fallback.
     */
    public function test_token_manager_service_round_robin_and_cooldown(): void
    {
        $tokenManager = app(TokenManagerService::class);

        // Tạo 2 key Gemini
        $key1 = ApiKey::query()->create([
            'provider' => 'gemini',
            'key_name' => 'Gemini Key 01',
            'api_key' => 'AIzaSyKeyOne111',
            'daily_limit' => 100,
            'used_today' => 10,
            'cooldown_seconds' => 10,
            'is_active' => true,
            'last_used_at' => Carbon::now()->subSeconds(30),
        ]);

        $key2 = ApiKey::query()->create([
            'provider' => 'gemini',
            'key_name' => 'Gemini Key 02',
            'api_key' => 'AIzaSyKeyTwo222',
            'daily_limit' => 100,
            'used_today' => 5,
            'cooldown_seconds' => 10,
            'is_active' => true,
            'last_used_at' => Carbon::now()->subSeconds(60), // Lâu hơn key 1
        ]);

        // Key 2 có last_used_at cũ hơn nên được ưu tiên
        $selectedKey = $tokenManager->getAvailableKey('gemini');
        $this->assertNotNull($selectedKey);
        $this->assertEquals($key2->id, $selectedKey->id);

        // Ghi nhận sử dụng
        $tokenManager->recordUsage($selectedKey, 1);
        $this->assertEquals(6, $key2->fresh()->used_today);

        // Đánh dấu Cooldown key 2 -> getAvailableKey phải trả về key 1
        $tokenManager->markCooldown($key2, 60);
        $nextKey = $tokenManager->getAvailableKey('gemini');
        $this->assertNotNull($nextKey);
        $this->assertEquals($key1->id, $nextKey->id);

        // Thử nghiệm Fallback sang OpenAI khi Gemini hết hạn mức
        $key1->update(['used_today' => 100]); // Chạm trần
        $key2->update(['used_today' => 100]); // Chạm trần

        $openAiKey = ApiKey::query()->create([
            'provider' => 'openai',
            'key_name' => 'OpenAI Backup Key',
            'api_key' => 'sk-proj-demo999',
            'daily_limit' => 50,
            'used_today' => 0,
            'cooldown_seconds' => 5,
            'is_active' => true,
        ]);

        $fallbackKey = $tokenManager->getAvailableKey('gemini');
        $this->assertNotNull($fallbackKey);
        $this->assertEquals('openai', $fallbackKey->provider);
        $this->assertEquals($openAiKey->id, $fallbackKey->id);
    }

    /**
     * DoD 2: GeminiLlmDriver kết nối Structured JSON Output & xử lý Rate Limit 429.
     */
    public function test_gemini_llm_driver_generates_structured_json(): void
    {
        $tokenManager = app(TokenManagerService::class);

        $apiKey = ApiKey::query()->create([
            'provider' => 'gemini',
            'key_name' => 'Gemini Real Test Key',
            'api_key' => 'AIzaSyMockedApiSecret123',
            'daily_limit' => 1000,
            'used_today' => 0,
            'cooldown_seconds' => 5,
            'is_active' => true,
        ]);

        $mockGeminiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'title' => 'Top 3 mẹo tiết kiệm thời gian đỉnh cao',
                                    'hook_text' => 'Dừng lại 3 giây nếu bạn không muốn phí hoài thanh xuân!',
                                    'voice_script' => 'Đây là bí mật giúp bạn giải quyết 90% công việc chỉ trong 15 phút mỗi sáng.',
                                    'visual_prompts' => [
                                        ['start_sec' => 0, 'end_sec' => 3, 'visual_query' => 'Đồng hồ đếm ngược'],
                                    ],
                                    'caption' => 'Mẹo hay mỗi ngày #viral',
                                    'hashtags' => '#fyp #viral #meohay',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockGeminiResponse, 200),
        ]);

        $geminiDriver = new GeminiLlmDriver($tokenManager);
        $scriptDto = $geminiDriver->generateScript('Prompt thử nghiệm kịch bản');

        $this->assertInstanceOf(ScriptDataDto::class, $scriptDto);
        $this->assertEquals('Top 3 mẹo tiết kiệm thời gian đỉnh cao', $scriptDto->title);
        $this->assertEquals(1, $apiKey->fresh()->used_today);

        // Test trường hợp lỗi 429 Rate Limit
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'Rate limit exceeded'], 429),
        ]);

        $this->expectException(\RuntimeException::class);
        $geminiDriver->generateScript('Prompt thử nghiệm bị 429');
    }

    /**
     * DoD 3: ScriptPromptBuilder phân nhánh đúng cho Kênh Growth vs Affiliate.
     */
    public function test_script_prompt_builder_rules(): void
    {
        $promptBuilder = new ScriptPromptBuilder();

        // 1. Kênh Growth
        $growthChannel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@kenh_growth_demo',
            'type' => 'growth',
            'browser_profile_path' => 'storage/profiles/growth',
            'daily_post_limit' => 5,
            'min_interval_minutes' => 180,
            'status' => 'active',
        ]);

        $growthPrompt = $promptBuilder->buildPrompt($growthChannel);
        $this->assertStringContainsString('KHÔNG PHẢI KÊNH BÁN HÀNG', $growthPrompt);
        $this->assertStringContainsString('#fyp #viral #kienthuc #xuhuong', $growthPrompt);
        $this->assertStringContainsString('TUYỆT ĐỐI CẤM', $growthPrompt);

        // 2. Kênh Affiliate
        $affiliateChannel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@kenh_affiliate_demo',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/profiles/affiliate',
            'daily_post_limit' => 4,
            'min_interval_minutes' => 240,
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'platform' => 'tiktok',
            'product_name' => 'Nồi chiên không dầu Magic Korea 6L',
            'notes' => 'Dung tích lớn, giảm 85% lượng dầu mỡ thừa',
            'is_active' => true,
        ]);

        $affiliatePrompt = $promptBuilder->buildPrompt($affiliateChannel, $product);
        $this->assertStringContainsString('Nồi chiên không dầu Magic Korea 6L', $affiliatePrompt);
        $this->assertStringContainsString('MÔ HÌNH AIDA THỰC CHIẾN', $affiliatePrompt);
        $this->assertStringContainsString('Giỏ Hàng màu vàng ở góc trái', $affiliatePrompt);
    }

    /**
     * DoD 4: GenerateScriptJob thực thi lưu trữ script_data và cập nhật assets_ready.
     */
    public function test_generate_script_job_execution(): void
    {
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@kenh_job_test',
            'type' => 'growth',
            'browser_profile_path' => 'storage/profiles/job',
            'daily_post_limit' => 5,
            'min_interval_minutes' => 180,
            'status' => 'active',
        ]);

        $videoTask = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'task_type' => 'growth_reup_clone',
            'status' => 'pending',
            'current_step' => 'Chờ xử lý',
            'progress' => 0,
        ]);

        // Tạo instance fake LlmDriver trả về kịch bản hợp lệ
        $fakeLlmDriver = new class implements LlmDriverInterface {
            public function generateScript(string $prompt, array $options = []): ScriptDataDto
            {
                return new ScriptDataDto(
                    title: 'Bí mật giấc ngủ REM',
                    hookText: 'Bạn đang ngủ sai cách suốt 20 năm qua!',
                    voiceScript: 'Ngủ đủ 8 tiếng chưa chắc đã tốt nếu bạn bỏ qua chu kỳ 90 phút...',
                    visualPrompts: [['start_sec' => 0, 'end_sec' => 3, 'visual_query' => 'Giường ngủ']],
                    caption: 'Ngủ sâu mỗi đêm #suckhoe',
                    hashtags: '#fyp #suckhoe #viral'
                );
            }
        };

        $job = new GenerateScriptJob($videoTask->id);
        $job->handle($fakeLlmDriver, new ScriptPromptBuilder());

        $videoTask->refresh();
        $this->assertEquals('assets_ready', $videoTask->status);
        $this->assertEquals(25, $videoTask->progress);
        $this->assertEquals('Bí mật giấc ngủ REM', $videoTask->title);
        $this->assertIsArray($videoTask->script_data);
        $this->assertEquals('Bạn đang ngủ sai cách suốt 20 năm qua!', $videoTask->script_data['hook_text']);
    }

    /**
     * DoD 5: ProduceBufferContentCommand và ResetDailyApiUsageCommand hoạt động chính xác.
     */
    public function test_produce_buffer_content_and_reset_quota_commands(): void
    {
        Queue::fake();

        // 1. Tạo kênh active
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@channel_buffer_test',
            'type' => 'growth',
            'browser_profile_path' => 'storage/profiles/buf',
            'daily_post_limit' => 5,
            'min_interval_minutes' => 180,
            'status' => 'active',
        ]);

        // Tạo key khả dụng
        $key = ApiKey::query()->create([
            'provider' => 'gemini',
            'key_name' => 'Buffer Key 01',
            'api_key' => 'AIzaSyKeyForBuffer',
            'daily_limit' => 500,
            'used_today' => 45,
            'cooldown_seconds' => 5,
            'is_active' => true,
        ]);

        // Chạy command quét kho đệm
        Artisan::call('app:produce-buffer-content', ['--channel-id' => $channel->id]);

        Queue::assertPushed(GenerateScriptJob::class);

        // 2. Chạy command Reset hạn mức 00:00
        Artisan::call('app:reset-daily-api-usage');
        $this->assertEquals(0, $key->fresh()->used_today);
    }
}

