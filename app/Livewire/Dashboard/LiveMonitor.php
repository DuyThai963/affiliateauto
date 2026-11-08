<?php

namespace App\Livewire\Dashboard;

use App\Core\Contracts\LlmDriverInterface;
use App\Core\Contracts\TtsDriverInterface;
use App\Core\Contracts\VideoRenderDriverInterface;
use App\Core\DTOs\RenderTaskDto;
use App\Models\ApiKey;
use App\Models\Channel;
use App\Models\Product;
use App\Models\VideoTask;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LiveMonitor extends Component
{
    public ?string $feedbackMessage = null;
    public bool $isGeneratingTestVideo = false;
    public string $statusFilter = '';

    public function deleteTask(int $taskId): void
    {
        $task = VideoTask::query()->find($taskId);

        if ($task !== null) {
            $task->delete();
            $this->feedbackMessage = "Đã xóa tác vụ #{$taskId} thành công.";
        }
    }

    /**
     * Trigger test video generation pipeline using Mock Drivers.
     */
    public function generateTestVideo(
        LlmDriverInterface $llmDriver,
        TtsDriverInterface $ttsDriver,
        VideoRenderDriverInterface $renderDriver
    ): void {
        $this->isGeneratingTestVideo = true;

        // 1. Lấy hoặc tạo kênh thử nghiệm
        $channel = Channel::query()->first();
        if ($channel === null) {
            $channel = Channel::query()->create([
                'platform' => 'tiktok',
                'channel_name' => '@review_test_channel',
                'type' => 'affiliate',
                'browser_profile_path' => 'storage/browser_profiles/tiktok_channel_demo',
                'daily_post_limit' => 5,
                'min_interval_minutes' => 200,
                'status' => 'active',
            ]);
        }

        // 2. Lấy hoặc tạo sản phẩm thử nghiệm
        $product = Product::query()->first();
        if ($product === null) {
            $product = Product::query()->create([
                'platform' => 'tiktok',
                'product_name' => 'Máy sấy tóc ion âm tốc độ cao',
                'external_product_id' => 'PRD-98213',
                'affiliate_url' => 'https://vt.tiktok.com/demo-deal',
                'notes' => 'Hoa hồng 20%',
                'is_active' => true,
            ]);
        }

        // 3. Khởi tạo bản ghi VideoTask ở trạng thái 'pending'
        $videoTask = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'product_id' => $product->id,
            'task_type' => $channel->type === 'affiliate' ? 'affiliate_original' : 'growth_reup_clone',
            'title' => 'Khởi tạo tác vụ video...',
            'status' => 'pending',
            'current_step' => 'Bắt đầu khởi tạo quy trình sản xuất',
            'progress' => 5,
        ]);

        // 4. Bước 'scripting': Sinh kịch bản qua LLM Driver
        $videoTask->update([
            'status' => 'scripting',
            'current_step' => 'Đang phân tích sản phẩm và biên soạn kịch bản viral (LLM)',
            'progress' => 20,
        ]);

        $scriptDto = $llmDriver->generateScript(
            prompt: 'Viết kịch bản giật tít cho sản phẩm: ' . $product->product_name,
            options: [
                'product_name' => $product->product_name,
                'channel_type' => $channel->type,
            ]
        );

        $videoTask->update([
            'title' => $scriptDto->title,
            'caption' => $scriptDto->caption,
            'hashtags' => $scriptDto->hashtags,
            'script_data' => $scriptDto->toArray(),
            'progress' => 35,
        ]);

        // 5. Bước 'assets_ready': Sinh audio giọng đọc qua TTS Driver
        $videoTask->update([
            'status' => 'assets_ready',
            'current_step' => 'Đang tổng hợp giọng đọc AI thuyết minh (TTS)',
            'progress' => 45,
        ]);

        $audioPath = storage_path('app/temp_assets/audio_task_' . $videoTask->id . '.mp3');
        $ttsDriver->synthesizeAudio(
            text: $scriptDto->voiceScript,
            voiceName: 'vi-VN-Standard-A',
            outputPath: $audioPath
        );

        // 6. Bước 'rendering': Render video qua VideoRenderDriver
        $videoTask->update([
            'status' => 'rendering',
            'current_step' => 'Đang render video chuẩn 9:16 (FFmpeg/Remotion)',
            'progress' => 60,
        ]);

        $renderTaskDto = new RenderTaskDto(
            taskId: $videoTask->id,
            videoDimensions: '1080x1920',
            audioFilePath: $audioPath,
            subtitleFilePath: null,
            mediaAssets: $scriptDto->visualPrompts
        );

        // Render với callback cập nhật tiến độ
        $renderedFilePath = $renderDriver->renderVideo(
            taskDto: $renderTaskDto,
            onProgressUpdate: function (int $progressPercentage, string $statusMessage) use ($videoTask) {
                $videoTask->update([
                    'current_step' => $statusMessage,
                    'progress' => 60 + (int) round($progressPercentage * 0.35),
                ]);
            }
        );

        // 7. Bước 'ready_in_vault': Lưu video vào kho đệm
        $videoTask->update([
            'status' => 'ready_in_vault',
            'current_step' => 'Sẵn sàng trong kho đệm (Buffer Vault). Chờ khung giờ vàng xuất bản!',
            'video_path' => $renderedFilePath,
            'progress' => 100,
        ]);

        $this->isGeneratingTestVideo = false;
        $this->feedbackMessage = "Tạo video thử nghiệm #{$videoTask->id} thành công! Video đã được lưu an toàn tại Buffer Vault.";
        $this->dispatch('task-queue-updated');
    }

    public function render(): View
    {
        // 1. Thống kê số kênh hoạt động
        $totalActiveChannels = Channel::query()->where('status', 'active')->count();
        $growthChannelsCount = Channel::query()->where('status', 'active')->where('type', 'growth')->count();
        $affiliateChannelsCount = Channel::query()->where('status', 'active')->where('type', 'affiliate')->count();

        // 2. Video sẵn sàng trong Buffer Vault
        $vaultTasksCount = VideoTask::query()->where('status', 'ready_in_vault')->count();

        // 3. Số video đã đăng hôm nay / Tổng chỉ tiêu
        $todayStart = Carbon::today();
        $publishedTodayCount = VideoTask::query()
            ->where('status', 'published')
            ->where('published_at', '>=', $todayStart)
            ->count();
        
        $totalDailyQuota = (int) Channel::query()->where('status', 'active')->sum('daily_post_limit');

        // 4. Token API đã tiêu thụ hôm nay
        $tokensConsumedToday = (int) ApiKey::query()->sum('used_today');
        $totalTokensLimit = (int) ApiKey::query()->sum('daily_limit');

        // 5. Tác vụ đang xử lý thời gian thực
        $activeTask = VideoTask::query()
            ->with(['channel', 'product'])
            ->whereIn('status', ['pending', 'scripting', 'assets_ready', 'rendering', 'publishing'])
            ->latest()
            ->first();

        // 6. Danh sách tác vụ trong hàng đợi & Buffer Vault
        $tasksQuery = VideoTask::query()->with(['channel', 'product']);
        if ($this->statusFilter !== '') {
            $tasksQuery->where('status', $this->statusFilter);
        }
        $tasks = $tasksQuery->latest()->take(15)->get();

        return view('livewire.dashboard.live-monitor', [
            'totalActiveChannels' => $totalActiveChannels,
            'growthChannelsCount' => $growthChannelsCount,
            'affiliateChannelsCount' => $affiliateChannelsCount,
            'vaultTasksCount' => $vaultTasksCount,
            'publishedTodayCount' => $publishedTodayCount,
            'totalDailyQuota' => $totalDailyQuota,
            'tokensConsumedToday' => $tokensConsumedToday,
            'totalTokensLimit' => $totalTokensLimit,
            'activeTask' => $activeTask,
            'tasks' => $tasks,
        ]);
    }
}

