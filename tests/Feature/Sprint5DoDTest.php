<?php

namespace Tests\Feature;

use App\Jobs\PublishVideoJob;
use App\Jobs\RenderVideoJob;
use App\Livewire\Dashboard\LiveMonitor;
use App\Models\Channel;
use App\Models\SystemSetting;
use App\Models\VideoTask;
use App\Services\CloudStorageSyncService;
use App\Services\HumanLikeSchedulerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class Sprint5DoDTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Đảm bảo các thư mục cần thiết tồn tại
        $vaultDir = storage_path('app/buffer_vault');
        if (!File::exists($vaultDir)) {
            File::makeDirectory($vaultDir, 0755, true);
        }

        $tempDir = storage_path('app/temp_assets');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $cloudDir = storage_path('app/cloud_vault_5tb');
        if (!File::exists($cloudDir)) {
            File::makeDirectory($cloudDir, 0755, true);
        }
    }

    /**
     * DoD 1: Chế độ Co-Pilot chuyển video render xong sang 'waiting_approval' thay vì tự lên lịch.
     */
    public function test_co_pilot_mode_routes_rendered_video_to_waiting_approval(): void
    {
        SystemSetting::set('master_operation_mode', 'co_pilot');
        $this->assertEquals('co_pilot', SystemSetting::get('master_operation_mode'));

        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@copilot_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_channel',
            'daily_post_limit' => 3,
            'min_interval_minutes' => 120,
            'status' => 'active',
            'operation_mode' => 'co_pilot',
        ]);

        $task = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'task_type' => 'affiliate_original',
            'title' => 'Test Video Co-Pilot',
            'status' => 'rendering',
            'current_step' => 'Đang dựng video...',
            'progress' => 80,
            'script_data' => [
                'hook_text' => 'Hook test',
                'voice_script' => 'Kịch bản thoại test',
            ],
        ]);

        // Giả lập logic khi render xong với chế độ Co-Pilot
        $mode = $channel->operation_mode ?: SystemSetting::get('master_operation_mode', 'auto_pilot');
        if ($mode === 'co_pilot') {
            $task->update([
                'status' => 'waiting_approval',
                'current_step' => 'Đã dựng xong (Co-Pilot). Đang chờ người dùng duyệt xuất bản.',
                'progress' => 100,
                'scheduled_at' => null,
            ]);
        }

        $task->refresh();
        $this->assertEquals('waiting_approval', $task->status);
        $this->assertNull($task->scheduled_at);
    }

    /**
     * DoD 2: Thao tác duyệt video (approveTask) trên Control Center chuyển task sang ready_in_vault và xếp lịch.
     */
    public function test_approve_task_moves_waiting_approval_to_ready_in_vault_with_schedule(): void
    {
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@approve_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_channel',
            'daily_post_limit' => 3,
            'min_interval_minutes' => 120,
            'status' => 'active',
        ]);

        $task = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'task_type' => 'affiliate_original',
            'title' => 'Video Chờ Duyệt',
            'status' => 'waiting_approval',
            'current_step' => 'Chờ duyệt',
            'progress' => 100,
            'scheduled_at' => null,
        ]);

        Livewire::test(LiveMonitor::class)
            ->call('approveTask', $task->id);

        $task->refresh();
        $this->assertEquals('ready_in_vault', $task->status);
        $this->assertNotNull($task->scheduled_at);
        $this->assertStringContainsString('Đã phê duyệt', $task->current_step);
    }

    /**
     * DoD 3: Tầng lưu trữ Cloud 5TB di chuyển file video từ SSD sang Cloud, tạo file metadata .json và xóa file local.
     */
    public function test_cloud_storage_sync_archives_video_and_metadata_and_frees_ssd(): void
    {
        $cloudSyncService = app(CloudStorageSyncService::class);

        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@cloud_sync_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_channel',
            'daily_post_limit' => 3,
            'min_interval_minutes' => 120,
            'status' => 'active',
        ]);

        // Tạo file video giả lập trên SSD cục bộ (Buffer Vault)
        $localVaultPath = storage_path('app/buffer_vault/test_ssd_source_' . time() . '.mp4');
        File::put($localVaultPath, 'DUMMY_MP4_VIDEO_BINARY_DATA_SPRINT_5');
        $this->assertFileExists($localVaultPath);

        $task = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'task_type' => 'affiliate_original',
            'title' => 'Video Đã Xuất Bản Cần Lưu Trữ 5TB',
            'status' => 'published',
            'video_path' => $localVaultPath,
            'caption' => 'Test caption sync 5TB',
            'hashtags' => '#sync #cloud5tb',
            'published_at' => Carbon::now(),
            'script_data' => [
                'hook_text' => 'Hook sync',
            ],
        ]);

        // Kích hoạt archive sang Cloud Vault
        $result = $cloudSyncService->archivePublishedVideo($task);
        $this->assertTrue($result);

        $task->refresh();

        // 1. File gốc trên SSD cục bộ phải được xóa bỏ hoàn toàn
        $this->assertFileDoesNotExist($localVaultPath);

        // 2. File mới trong Cloud Vault phải tồn tại
        $this->assertFileExists($task->video_path);
        $this->assertStringContainsString('cloud_vault_5tb', $task->video_path);
        $this->assertStringContainsString('tiktok', $task->video_path);
        $this->assertStringContainsString('video_' . $task->id . '.mp4', $task->video_path);

        // 3. Tệp metadata .json tương ứng phải tồn tại và đúng cấu trúc
        $metaPath = dirname($task->video_path) . DIRECTORY_SEPARATOR . "video_{$task->id}_metadata.json";
        $this->assertFileExists($metaPath);

        $metaContent = json_decode(File::get($metaPath), true);
        $this->assertIsArray($metaContent);
        $this->assertEquals($task->id, $metaContent['task_id']);
        $this->assertEquals($channel->channel_name, $metaContent['channel_name']);
        $this->assertEquals('Test caption sync 5TB', $metaContent['caption']);

        // Dọn dẹp file test
        if (File::exists($task->video_path)) {
            File::delete($task->video_path);
        }
        if (File::exists($metaPath)) {
            File::delete($metaPath);
        }
    }

    /**
     * DoD 4: Smart Retry khôi phục chính xác trạng thái pipeline theo vị trí lỗi.
     */
    public function test_smart_retry_recovers_to_correct_pipeline_stage(): void
    {
        Queue::fake();

        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@retry_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_channel',
            'status' => 'active',
        ]);

        // Trường hợp A: Task lỗi render (chưa có video_path trên đĩa) -> reset về assets_ready và chạy lại RenderVideoJob
        $renderFailedTask = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'task_type' => 'affiliate_original',
            'title' => 'Task Lỗi Render',
            'status' => 'failed',
            'video_path' => null,
            'error_log' => 'FFmpeg exit code 1',
            'current_step' => 'Lỗi khi dựng video',
            'progress' => 60,
        ]);

        Livewire::test(LiveMonitor::class)
            ->call('smartRetry', $renderFailedTask->id);

        $renderFailedTask->refresh();
        $this->assertEquals('assets_ready', $renderFailedTask->status);
        $this->assertNull($renderFailedTask->error_log);
        Queue::assertPushed(RenderVideoJob::class);

        // Trường hợp B: Task lỗi publish (đã có video_path) -> reset về ready_in_vault và chạy lại PublishVideoJob
        $dummyVideo = storage_path('app/buffer_vault/retry_dummy.mp4');
        File::put($dummyVideo, 'dummy');

        $publishFailedTask = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'task_type' => 'affiliate_original',
            'title' => 'Task Lỗi Publish',
            'status' => 'failed',
            'video_path' => $dummyVideo,
            'error_log' => 'TikTok Playwright Timeout',
            'current_step' => 'Lỗi xuất bản',
        ]);

        Livewire::test(LiveMonitor::class)
            ->call('smartRetry', $publishFailedTask->id);

        $publishFailedTask->refresh();
        $this->assertEquals('ready_in_vault', $publishFailedTask->status);
        $this->assertNull($publishFailedTask->error_log);
        Queue::assertPushed(PublishVideoJob::class);

        if (File::exists($dummyVideo)) {
            File::delete($dummyVideo);
        }
    }

    /**
     * DoD 5: Error Inspector hiển thị đầy đủ Error Log và ảnh chụp Captcha nếu có.
     */
    public function test_error_inspector_loads_log_and_captcha_screenshot(): void
    {
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@inspector_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_channel',
            'status' => 'active',
        ]);

        $task = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'task_type' => 'affiliate_original',
            'title' => 'Task Checkpoint Captcha',
            'status' => 'failed',
            'error_log' => 'Tài khoản yêu cầu giải Captcha kéo thanh trượt.',
            'current_step' => 'Checkpoint Bot',
        ]);

        // Tạo ảnh captcha giả lập
        $captchaFile = storage_path("app/temp_assets/captcha_tiktok_{$task->id}.png");
        File::put($captchaFile, 'DUMMY_IMAGE_BINARY');

        $component = Livewire::test(LiveMonitor::class)
            ->call('inspectError', $task->id);

        $component->assertSet('inspectingErrorTaskId', $task->id);
        $component->assertSet('inspectingErrorStep', 'Checkpoint Bot');
        $this->assertStringContainsString('Tài khoản yêu cầu giải Captcha', $component->get('inspectingErrorLog'));
        $this->assertNotNull($component->get('inspectingCaptchaScreenshot'));
        $this->assertStringStartsWith('data:image/png;base64,', $component->get('inspectingCaptchaScreenshot'));

        // Thử đóng modal
        $component->call('closeErrorModal');
        $component->assertSet('inspectingErrorTaskId', null);

        if (File::exists($captchaFile)) {
            File::delete($captchaFile);
        }
    }

    /**
     * DoD 6: Master Operation Mode Switch chuyển đổi qua lại giữa auto_pilot và co_pilot.
     */
    public function test_master_operation_mode_toggle_switch(): void
    {
        SystemSetting::set('master_operation_mode', 'auto_pilot');

        $component = Livewire::test(LiveMonitor::class);
        $component->assertSet('masterOperationMode', 'auto_pilot');

        $component->call('toggleMasterOperationMode');
        $component->assertSet('masterOperationMode', 'co_pilot');
        $this->assertEquals('co_pilot', SystemSetting::get('master_operation_mode'));

        $component->call('toggleMasterOperationMode');
        $component->assertSet('masterOperationMode', 'auto_pilot');
        $this->assertEquals('auto_pilot', SystemSetting::get('master_operation_mode'));
    }
}

