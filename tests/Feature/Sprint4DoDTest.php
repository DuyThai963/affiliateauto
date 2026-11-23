<?php

namespace Tests\Feature;

use App\Core\Contracts\PublisherDriverInterface;
use App\Core\DTOs\PublishPayloadDto;
use App\Drivers\Publisher\TikTokPlaywrightDriver;
use App\Jobs\PublishVideoJob;
use App\Livewire\Channels\ChannelIndex;
use App\Livewire\Dashboard\LiveMonitor;
use App\Models\Channel;
use App\Models\Product;
use App\Models\VideoTask;
use App\Services\HumanLikeSchedulerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class Sprint4DoDTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $profilesDir = storage_path('app/browser_profiles');
        if (!File::exists($profilesDir)) {
            File::makeDirectory($profilesDir, 0755, true);
        }
    }

    /**
     * DoD 1: Thư mục browser_profiles sẵn sàng lưu phiên Playwright.
     */
    public function test_browser_profiles_directory_ready(): void
    {
        $profilesDir = storage_path('app/browser_profiles');
        $this->assertDirectoryExists($profilesDir);
    }

    /**
     * DoD 2: HumanLikeSchedulerService điều phối lịch tự nhiên và Jitter.
     */
    public function test_human_like_scheduler_service_natural_window_and_jitter(): void
    {
        $scheduler = app(HumanLikeSchedulerService::class);

        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@scheduler_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_channel',
            'daily_post_limit' => 2,
            'min_interval_minutes' => 180,
            'status' => 'active',
        ]);

        // Trường hợp 1: Kênh chưa đạt hạn mức -> tính slot tiếp theo
        $slot = $scheduler->calculateNextPublishSlot($channel);
        $this->assertNotNull($slot);
        
        // Slot phải nằm trong khung giờ 07:30 - 22:30
        $hour = (int) $slot->format('H');
        $minute = (int) $slot->format('i');
        $totalMinutes = $hour * 60 + $minute;

        $this->assertGreaterThanOrEqual(7 * 60 + 30, $totalMinutes, "Giờ đăng không được trước 07:30 sáng");
        $this->assertLessThanOrEqual(23 * 60, $totalMinutes, "Giờ đăng không được sau 22:30 đêm");

        // Trường hợp 2: Đã đạt daily_post_limit -> trả về null
        VideoTask::query()->create([
            'channel_id' => $channel->id,
            'status' => 'published',
            'published_at' => Carbon::today()->setTime(8, 0),
        ]);
        VideoTask::query()->create([
            'channel_id' => $channel->id,
            'status' => 'published',
            'published_at' => Carbon::today()->setTime(14, 0),
        ]);

        $slotAfterLimit = $scheduler->calculateNextPublishSlot($channel);
        $this->assertNull($slotAfterLimit, "Kênh đã đăng 2/2 video hôm nay nên phải trả về null");

        // Trường hợp 3: shouldPublishNow
        $readyTask = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'status' => 'ready_in_vault',
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);
        $this->assertTrue($scheduler->shouldPublishNow($readyTask));

        $futureTask = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'status' => 'ready_in_vault',
            'scheduled_at' => Carbon::now()->addHours(2),
        ]);
        $this->assertFalse($scheduler->shouldPublishNow($futureTask));
    }

    /**
     * DoD 3: DispatchScheduledPostsCommand quét kho đệm và dispatch job.
     */
    public function test_dispatch_scheduled_posts_command(): void
    {
        Queue::fake();

        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@dispatch_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_dispatch',
            'status' => 'active',
            'daily_post_limit' => 5,
        ]);

        $task = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'status' => 'ready_in_vault',
            'scheduled_at' => Carbon::now()->subMinute(),
        ]);

        Artisan::call('schedule:dispatch-posts');

        Queue::assertPushed(PublishVideoJob::class, function ($job) use ($task) {
            return $job->videoTaskId === $task->id;
        });
    }

    /**
     * DoD 4: PublishVideoJob xuất bản thành công (phân nhánh Growth vs Affiliate).
     */
    public function test_publish_video_job_successful_flow(): void
    {
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@growth_viral',
            'type' => 'growth',
            'browser_profile_path' => 'storage/app/browser_profiles/test_growth',
            'status' => 'active',
            'daily_post_limit' => 5,
        ]);

        $product = Product::query()->create([
            'platform' => 'tiktok',
            'product_name' => 'Sản phẩm thử nghiệm',
            'external_product_id' => 'PRD-SP4',
            'affiliate_url' => 'https://vt.tiktok.com/sp4',
            'is_active' => true,
        ]);

        $task = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'product_id' => $product->id,
            'task_type' => 'growth_reup_clone',
            'title' => 'Video viral test Sprint 4',
            'caption' => 'Caption test #viral',
            'hashtags' => '#viral #trending',
            'video_path' => 'storage/app/buffer_vault/test_video.mp4',
            'status' => 'ready_in_vault',
        ]);

        // Mock Driver xuất bản thành công
        $mockDriver = new class implements PublisherDriverInterface {
            public ?PublishPayloadDto $receivedPayload = null;

            public function publish(PublishPayloadDto $payloadDto): bool
            {
                $this->receivedPayload = $payloadDto;
                return true;
            }
        };

        $job = new PublishVideoJob($task->id);
        $job->handle($mockDriver);

        $task->refresh();
        $channel->refresh();

        $this->assertEquals('published', $task->status);
        $this->assertEquals(100, $task->progress);
        $this->assertNotNull($task->published_at);
        $this->assertNotNull($channel->last_posted_at);

        // Kênh growth: không truyền productName vào payload
        $this->assertNull($mockDriver->receivedPayload->productName);
    }

    /**
     * DoD 5: PublishVideoJob bắt Captcha an toàn và chuyển kênh sang needs_relogin.
     */
    public function test_publish_video_job_captcha_handling(): void
    {
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@captcha_channel',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_captcha',
            'status' => 'active',
            'daily_post_limit' => 5,
        ]);

        $task = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'status' => 'ready_in_vault',
            'video_path' => 'storage/app/buffer_vault/video_test.mp4',
        ]);

        // Mock Driver báo lỗi Captcha
        $captchaDriver = new class implements PublisherDriverInterface {
            public function publish(PublishPayloadDto $payloadDto): bool
            {
                throw new \RuntimeException("Phát hiện Captcha yêu cầu xác thực thủ công!");
            }
        };

        $job = new PublishVideoJob($task->id);
        $job->handle($captchaDriver);

        $task->refresh();
        $channel->refresh();

        $this->assertEquals('failed', $task->status);
        $this->assertStringContainsString('Captcha', $task->error_log);
        $this->assertEquals('needs_relogin', $channel->status, "Kênh phải tự động chuyển sang needs_relogin khi gặp Captcha");
    }

    /**
     * DoD 6: Livewire Actions: openBrowserLogin và forcePublishNow.
     */
    public function test_livewire_channel_and_monitor_publish_actions(): void
    {
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@livewire_publish_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_livewire_publish',
            'status' => 'active',
            'daily_post_limit' => 5,
        ]);

        $task = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'status' => 'ready_in_vault',
            'video_path' => 'storage/app/buffer_vault/video_livewire.mp4',
        ]);

        // Kiểm tra ChannelIndex openBrowserLogin
        Livewire::test(ChannelIndex::class)
            ->call('openBrowserLogin', $channel->id)
            ->assertSet('feedbackMessage', function ($msg) use ($channel) {
                return str_contains($msg, $channel->channel_name);
            });

        // Kiểm tra LiveMonitor forcePublishNow
        Livewire::test(LiveMonitor::class)
            ->call('forcePublishNow', $task->id);

        $task->refresh();
        $this->assertEquals('published', $task->status);
    }
}
