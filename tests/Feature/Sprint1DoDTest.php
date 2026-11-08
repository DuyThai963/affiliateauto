<?php

namespace Tests\Feature;

use App\Core\Contracts\LlmDriverInterface;
use App\Core\Contracts\PublisherDriverInterface;
use App\Core\Contracts\TtsDriverInterface;
use App\Core\Contracts\VideoRenderDriverInterface;
use App\Core\DTOs\PublishPayloadDto;
use App\Core\DTOs\RenderTaskDto;
use App\Livewire\ApiKeys\ApiKeyFormModal;
use App\Livewire\Channels\ChannelFormModal;
use App\Livewire\Dashboard\LiveMonitor;
use App\Livewire\Products\ProductFormModal;
use App\Models\ApiKey;
use App\Models\Channel;
use App\Models\Product;
use App\Models\VideoTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class Sprint1DoDTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test DoD 1 & 2: Tạo, cập nhật và xóa Kênh với 2 phân loại rõ ràng growth & affiliate.
     */
    public function test_can_create_and_manage_growth_and_affiliate_channels(): void
    {
        // 1. Tạo kênh Growth
        Livewire::test(ChannelFormModal::class)
            ->call('openModal')
            ->set('platform', 'tiktok')
            ->set('channel_name', '@review_viral_tips')
            ->set('type', 'growth')
            ->set('browser_profile_path', 'storage/browser_profiles/growth_channel_1')
            ->set('daily_post_limit', 6)
            ->set('min_interval_minutes', 180)
            ->set('status', 'active')
            ->call('save')
            ->assertDispatched('channel-saved');

        $this->assertDatabaseHas('channels', [
            'channel_name' => '@review_viral_tips',
            'type' => 'growth',
        ]);

        // 2. Tạo kênh Affiliate
        Livewire::test(ChannelFormModal::class)
            ->call('openModal')
            ->set('platform', 'shopee')
            ->set('channel_name', '@san_deal_chinh_hang')
            ->set('type', 'affiliate')
            ->set('browser_profile_path', 'storage/browser_profiles/affiliate_channel_1')
            ->set('daily_post_limit', 4)
            ->set('min_interval_minutes', 240)
            ->set('status', 'active')
            ->call('save')
            ->assertDispatched('channel-saved');

        $this->assertDatabaseHas('channels', [
            'channel_name' => '@san_deal_chinh_hang',
            'type' => 'affiliate',
        ]);
    }

    /**
     * Test DoD 3: Lưu trữ và quản lý API Keys cùng Sản phẩm giỏ hàng.
     */
    public function test_can_create_api_keys_and_products(): void
    {
        // Tạo API Key
        Livewire::test(ApiKeyFormModal::class)
            ->call('openModal')
            ->set('provider', 'gemini')
            ->set('key_name', 'Gemini Flash Production 01')
            ->set('api_key', 'AIzaSyDemoSecretKey123')
            ->set('daily_limit', 1500)
            ->set('cooldown_seconds', 4)
            ->set('is_active', true)
            ->call('save')
            ->assertDispatched('api-key-saved');

        $this->assertDatabaseHas('api_keys', [
            'key_name' => 'Gemini Flash Production 01',
            'provider' => 'gemini',
        ]);

        // Tạo Sản phẩm Giỏ hàng
        Livewire::test(ProductFormModal::class)
            ->call('openModal')
            ->set('platform', 'tiktok')
            ->set('product_name', 'Tai nghe chống ồn Bluetooth Pro 5')
            ->set('external_product_id', 'SKU-8849201')
            ->set('affiliate_url', 'https://vt.tiktok.com/ZSexample')
            ->set('notes', 'Hoa hồng 18%')
            ->set('is_active', true)
            ->call('save')
            ->assertDispatched('product-saved');

        $this->assertDatabaseHas('products', [
            'product_name' => 'Tai nghe chống ồn Bluetooth Pro 5',
            'platform' => 'tiktok',
        ]);
    }

    /**
     * Test DoD 4: Bấm nút "Tạo Video Thử Nghiệm" trên Dashboard kích hoạt Mock Drivers.
     */
    public function test_generate_test_video_pipeline_with_mock_drivers(): void
    {
        // Gọi action generateTestVideo trên Livewire LiveMonitor
        Livewire::test(LiveMonitor::class)
            ->call('generateTestVideo')
            ->assertDispatched('task-queue-updated');

        // Kiểm tra task được tạo thành công với trạng thái ready_in_vault
        $latestTask = VideoTask::query()->latest()->first();
        $this->assertNotNull($latestTask);
        $this->assertEquals('ready_in_vault', $latestTask->status);
        $this->assertEquals(100, $latestTask->progress);
        $this->assertNotNull($latestTask->video_path);
        $this->assertFileExists($latestTask->video_path);

        // Kiểm tra DTOs và mock publisher
        $publisherDriver = app(PublisherDriverInterface::class);
        $publishPayload = new PublishPayloadDto(
            taskId: $latestTask->id,
            channelId: $latestTask->channel_id,
            videoFilePath: $latestTask->video_path,
            caption: $latestTask->caption,
            hashtags: $latestTask->hashtags,
            productName: $latestTask->product?->product_name
        );

        $publishResult = $publisherDriver->publish($publishPayload);
        $this->assertTrue($publishResult);
    }
}

