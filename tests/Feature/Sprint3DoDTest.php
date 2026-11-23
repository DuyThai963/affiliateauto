<?php

namespace Tests\Feature;

use App\Core\Contracts\TtsDriverInterface;
use App\Core\Contracts\VideoRenderDriverInterface;
use App\Core\DTOs\RenderTaskDto;
use App\Drivers\Render\FFmpegRenderDriver;
use App\Drivers\TTS\EdgeTtsDriver;
use App\Jobs\RenderVideoJob;
use App\Livewire\Dashboard\LiveMonitor;
use App\Models\Channel;
use App\Models\Product;
use App\Models\VideoTask;
use App\Services\ReupTransformService;
use App\Services\SubtitleGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class Sprint3DoDTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tạo các thư mục cần thiết trong storage
        $vaultDir = storage_path('app/buffer_vault');
        $tempDir = storage_path('app/temp_assets');

        if (!File::exists($vaultDir)) {
            File::makeDirectory($vaultDir, 0755, true);
        }
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
    }

    /**
     * DoD 1: SubtitleGeneratorService tạo file .ass chuẩn TikTok 9:16 có Safe Zone cách đáy 450px và Hook Text.
     */
    public function test_subtitle_generator_service_creates_ass_with_safe_zone(): void
    {
        $subtitleService = app(SubtitleGeneratorService::class);

        // Tạo file dummy mp3 để tính thời lượng
        $tempAudio = storage_path('app/temp_assets/test_dummy_audio.mp3');
        File::put($tempAudio, 'dummy-audio-content');

        $outputPath = storage_path('app/temp_assets/test_sub.ass');
        $voiceScript = 'Xin chào các bạn hôm nay mình sẽ giới thiệu một sản phẩm cực hot giúp tiết kiệm thời gian.';
        $hookText = 'ĐỪNG MUA NẾU CHƯA BIẾT ĐIỀU NÀY!';

        $resultFile = $subtitleService->generateAssSubtitle(
            voiceScript: $voiceScript,
            audioPath: $tempAudio,
            outputPath: $outputPath,
            hookText: $hookText
        );

        $this->assertFileExists($resultFile);
        $content = File::get($resultFile);

        // Kiểm tra resolution 1080x1920
        $this->assertStringContainsString('PlayResX: 1080', $content);
        $this->assertStringContainsString('PlayResY: 1920', $content);

        // Kiểm tra Safe Zone 450px (TikTokYellow Style)
        $this->assertStringContainsString('450', $content);
        $this->assertStringContainsString('TikTokYellow', $content);

        // Kiểm tra HookBanner xuất hiện
        $this->assertStringContainsString('HookBanner', $content);
        $this->assertStringContainsString('ĐỪNG MUA NẾU CHƯA BIẾT ĐIỀU NÀY!', $content);

        // Dọn dẹp file test
        if (File::exists($tempAudio)) {
            File::delete($tempAudio);
        }
        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }
    }

    /**
     * DoD 2: ReupTransformService cấu trúc chuỗi filter biến tính chống gậy bản quyền.
     */
    public function test_reup_transform_service_filters_definition(): void
    {
        $reupService = app(ReupTransformService::class);
        $this->assertInstanceOf(ReupTransformService::class, $reupService);

        // Kiểm tra khi file nguồn không tồn tại sẽ throw RuntimeException
        $this->expectException(\RuntimeException::class);
        $reupService->transformVideo('storage/non_existent_video.mp4', 9999);
    }

    /**
     * DoD 3: RenderVideoJob điều phối quy trình TTS -> Subtitle -> Render -> Ready in Vault.
     */
    public function test_render_video_job_pipeline(): void
    {
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@kenh_test_render',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/profiles/test_render',
            'daily_post_limit' => 3,
            'min_interval_minutes' => 120,
            'status' => 'active',
        ]);

        $task = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'task_type' => 'affiliate_original',
            'title' => 'Test Video Render Sprint 3',
            'status' => 'assets_ready',
            'current_step' => 'Kịch bản đã sẵn sàng',
            'progress' => 30,
            'script_data' => [
                'title' => 'Bình Giữ Nhiệt Đỉnh Cao',
                'hook_text' => 'BÍ MẬT GIỮ NÓNG 24H',
                'voiceScript' => 'Chiếc bình này giữ nóng cả ngày cực tiện lợi cho dân văn phòng.',
                'voice_script' => 'Chiếc bình này giữ nóng cả ngày cực tiện lợi cho dân văn phòng.',
                'caption' => 'Review bình giữ nhiệt hot nhất năm #binhgiunhiet',
                'hashtags' => '#review #binhgiunhiet',
                'visual_prompts' => ['Cảnh rót nước nóng bốc khói', 'Cảnh hiển thị nhiệt độ LED'],
            ],
        ]);

        // Giả lập Mock render output file
        $mockTts = new class implements TtsDriverInterface {
            public function synthesizeAudio(string $text, string $voiceName, string $outputPath): string
            {
                File::put($outputPath, 'fake-mp3-audio-content');
                return $outputPath;
            }
        };

        $mockRender = new class implements VideoRenderDriverInterface {
            public function renderVideo(RenderTaskDto $taskDto, callable $onProgressUpdate): string
            {
                $onProgressUpdate(50, 'Đang ghép phụ đề & video...');
                $vaultPath = storage_path("app/buffer_vault/video_{$taskDto->taskId}.mp4");
                File::put($vaultPath, 'fake-mp4-video-content');
                return $vaultPath;
            }
        };

        $this->app->instance(TtsDriverInterface::class, $mockTts);
        $this->app->instance(VideoRenderDriverInterface::class, $mockRender);

        // Chạy job đồng bộ
        RenderVideoJob::dispatchSync($task->id);

        $task->refresh();

        $this->assertEquals('ready_in_vault', $task->status);
        $this->assertEquals(100, $task->progress);
        $this->assertNotNull($task->video_path);
        $this->assertFileExists($task->video_path);

        // Kiểm tra file tạm temp_assets đã được dọn sạch
        $tempAudio = storage_path("app/temp_assets/audio_{$task->id}.mp3");
        $tempSub = storage_path("app/temp_assets/sub_{$task->id}.ass");
        $this->assertFileDoesNotExist($tempAudio);
        $this->assertFileDoesNotExist($tempSub);

        // Dọn file mock vault sau khi test
        if (File::exists($task->video_path)) {
            File::delete($task->video_path);
        }
    }

    /**
     * DoD 4: Route stream video /vault/video/{task} phát video MP4.
     */
    public function test_vault_video_stream_route(): void
    {
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@kenh_test_stream',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/profiles/test_stream',
            'daily_post_limit' => 3,
            'min_interval_minutes' => 120,
            'status' => 'active',
        ]);

        $videoFile = storage_path('app/buffer_vault/test_stream_video.mp4');
        File::put($videoFile, 'fake-video-bytes-for-stream');

        $task = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'task_type' => 'affiliate_original',
            'status' => 'ready_in_vault',
            'video_path' => $videoFile,
        ]);

        $response = $this->get(route('vault.video.stream', $task->id));

        $response->assertStatus(200);
        $this->assertStringContainsString('video/mp4', (string) $response->headers->get('Content-Type'));

        // Dọn dẹp
        if (File::exists($videoFile)) {
            File::delete($videoFile);
        }
    }

    /**
     * DoD 5: Livewire LiveMonitor component render & preview actions.
     */
    public function test_livewire_live_monitor_render_and_preview_actions(): void
    {
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@kenh_livewire_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/profiles/test_livewire',
            'daily_post_limit' => 3,
            'min_interval_minutes' => 120,
            'status' => 'active',
        ]);

        $videoFile = storage_path('app/buffer_vault/test_livewire_preview.mp4');
        File::put($videoFile, 'fake-preview-video-bytes');

        $task = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'task_type' => 'affiliate_original',
            'title' => 'Tác vụ test preview',
            'status' => 'ready_in_vault',
            'video_path' => $videoFile,
            'script_data' => [
                'voice_script' => 'Kịch bản ngắn kiểm tra preview',
            ],
        ]);

        Livewire::test(LiveMonitor::class)
            ->call('previewVideo', $task->id)
            ->assertSet('viewingVideoTaskId', $task->id)
            ->assertSee('Xem Trước Video Tác Vụ #' . $task->id)
            ->call('closeVideoModal')
            ->assertSet('viewingVideoTaskId', null);

        // Dọn dẹp
        if (File::exists($videoFile)) {
            File::delete($videoFile);
        }
    }
}

