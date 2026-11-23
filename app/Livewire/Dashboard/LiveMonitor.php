<?php

namespace App\Livewire\Dashboard;

use App\Core\Contracts\LlmDriverInterface;
use App\Core\Contracts\TtsDriverInterface;
use App\Core\Contracts\VideoRenderDriverInterface;
use App\Core\DTOs\RenderTaskDto;
use App\Jobs\GenerateScriptJob;
use App\Jobs\PublishVideoJob;
use App\Jobs\RenderVideoJob;
use App\Models\ApiKey;
use App\Models\Channel;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\VideoTask;
use App\Services\HumanLikeSchedulerService;
use App\Services\ScriptPromptBuilder;
use App\Services\TokenManagerService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\Process\Process;

#[Layout('layouts.app')]
class LiveMonitor extends Component
{
    public ?string $feedbackMessage = null;
    public bool $isGeneratingTestVideo = false;
    public bool $isGeneratingScript = false;
    public ?int $selectedChannelIdForScript = null;
    public ?array $viewingScriptData = null;
    public ?string $viewingScriptTitle = null;
    public ?string $viewingScriptChannel = null;
    public ?int $viewingVideoTaskId = null;
    public bool $isRenderingVideo = false;
    public bool $isPublishingVideo = false;
    public string $statusFilter = '';
    public string $masterOperationMode = 'auto_pilot';
    public string $channelFilter = '';
    public string $typeFilter = '';
    public string $statusTab = 'all';

    // Error Inspector
    public ?int $inspectingErrorTaskId = null;
    public ?string $inspectingErrorLog = null;
    public ?string $inspectingErrorStep = null;
    public ?string $inspectingErrorTime = null;
    public ?string $inspectingCaptchaScreenshot = null;

    public function mount(): void
    {
        $this->masterOperationMode = (string) SystemSetting::get('master_operation_mode', 'auto_pilot');
    }

    /**
     * Bật / tắt Chế độ Vận hành Master (Auto-Pilot vs Co-Pilot).
     */
    public function toggleMasterOperationMode(): void
    {
        $this->masterOperationMode = $this->masterOperationMode === 'auto_pilot' ? 'co_pilot' : 'auto_pilot';
        SystemSetting::set('master_operation_mode', $this->masterOperationMode, 'Master operation mode for content engine');
        $modeText = $this->masterOperationMode === 'auto_pilot' ? 'Tự hành (Auto-Pilot)' : 'Bán tự động (Co-Pilot)';
        $this->feedbackMessage = "Đã chuyển Chế độ Vận hành sang: {$modeText}.";
    }

    public function deleteTask(int $taskId): void
    {
        $task = VideoTask::query()->find($taskId);

        if ($task !== null) {
            $task->delete();
            $this->feedbackMessage = "Đã xóa tác vụ #{$taskId} thành công.";
        }
    }

    /**
     * Mở trình duyệt Playwright để đăng nhập tài khoản cho kênh chỉ định ngay từ Dashboard.
     */
    public function openBrowserLoginForChannel(int $channelId): void
    {
        $channel = Channel::query()->find($channelId);

        if ($channel === null) {
            $this->feedbackMessage = "Không tìm thấy kênh với ID #{$channelId}.";
            return;
        }

        $platform = strtolower($channel->platform);
        $profilePath = $channel->browser_profile_path;
        if (empty($profilePath)) {
            $profilePath = 'storage/app/browser_profiles/' . $platform . '_' . $channel->id;
            $channel->update(['browser_profile_path' => $profilePath]);
        }

        $absoluteProfilePath = base_path($profilePath);
        if (!File::exists($absoluteProfilePath)) {
            File::makeDirectory($absoluteProfilePath, 0755, true);
        }

        $scriptPath = base_path('scripts/open_browser_login.js');

        if (app()->runningUnitTests()) {
            $this->feedbackMessage = "Đang mở trình duyệt cho kênh {$channel->channel_name}! Hãy hoàn thành đăng nhập trên cửa sổ trình duyệt và đóng lại để lưu phiên. (Hoặc chạy lệnh: php artisan channel:login {$channel->id})";
            return;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = sprintf(
                'start "AffiliateAuto Login" node "%s" --platform %s --profile-path "%s"',
                $scriptPath,
                $platform,
                $absoluteProfilePath
            );
            exec($cmd);
        } else {
            $process = new Process([
                'node',
                $scriptPath,
                '--platform',
                $platform,
                '--profile-path',
                $absoluteProfilePath,
            ]);
            $process->start();
        }

        $this->feedbackMessage = "Đang mở trình duyệt cho kênh {$channel->channel_name}! Hãy hoàn thành đăng nhập trên cửa sổ trình duyệt và đóng lại để lưu phiên. (Hoặc chạy lệnh: php artisan channel:login {$channel->id})";
    }

    /**
     * Mở trình duyệt đăng nhập cho kênh đang chọn trên dropdown Top Bar của Dashboard.
     */
    public function openBrowserLoginForSelectedChannel(): void
    {
        $channelId = $this->selectedChannelIdForScript;
        if (!$channelId) {
            $channel = Channel::query()->first();
            $channelId = $channel?->id;
        }

        if (!$channelId) {
            $this->feedbackMessage = "Chưa có kênh nào trong hệ thống. Vui lòng tạo kênh trước.";
            return;
        }

        $this->openBrowserLoginForChannel($channelId);
    }

    /**
     * Kích hoạt sinh kịch bản AI ngay lập tức cho kênh được chọn.
     */
    public function generateScriptNow(
        LlmDriverInterface $llmDriver,
        ScriptPromptBuilder $promptBuilder,
        TokenManagerService $tokenManager
    ): void {
        $this->isGeneratingScript = true;

        try {
            // 1. Xác định kênh
            $channelQuery = Channel::query()->where('status', 'active');
            if ($this->selectedChannelIdForScript) {
                $channelQuery->where('id', $this->selectedChannelIdForScript);
            }
            $channel = $channelQuery->first();

            if ($channel === null) {
                $channel = Channel::query()->create([
                    'platform' => 'tiktok',
                    'channel_name' => '@kenh_test_ai',
                    'type' => 'affiliate',
                    'browser_profile_path' => 'storage/browser_profiles/test_ai_channel',
                    'daily_post_limit' => 5,
                    'min_interval_minutes' => 200,
                    'status' => 'active',
                ]);
            }

            // 2. Xác định sản phẩm (nếu là kênh affiliate)
            $product = null;
            if ($channel->type === 'affiliate') {
                $product = Product::query()->where('is_active', true)->first();
                if ($product === null) {
                    $product = Product::query()->create([
                        'platform' => 'tiktok',
                        'product_name' => 'Bình giữ nhiệt hiển thị nhiệt độ thông minh',
                        'external_product_id' => 'PRD-AI-9021',
                        'affiliate_url' => 'https://vt.tiktok.com/deal-binh-giu-nhiet',
                        'notes' => 'Inox 316 cao cấp, giữ nóng 12h, nắp cảm ứng LED báo nhiệt',
                        'is_active' => true,
                    ]);
                }
            }

            // 3. Tạo VideoTask ở trạng thái pending
            $videoTask = VideoTask::query()->create([
                'channel_id' => $channel->id,
                'product_id' => $product?->id,
                'task_type' => $channel->type === 'growth' ? 'growth_reup_clone' : 'affiliate_original',
                'status' => 'pending',
                'current_step' => 'Bắt đầu gọi AI sinh kịch bản...',
                'progress' => 5,
            ]);

            // 4. Xử lý kịch bản trực tiếp bằng GenerateScriptJob
            GenerateScriptJob::dispatchSync($videoTask->id);

            $videoTask->refresh();

            if ($videoTask->status === 'failed') {
                $this->feedbackMessage = "Lỗi khi sinh kịch bản: {$videoTask->current_step}. Vui lòng kiểm tra lại API Key.";
            } else {
                $this->feedbackMessage = "Sinh kịch bản AI thành công cho kênh {$channel->channel_name}! Tiêu đề: \"{$videoTask->title}\"";
            }

            $this->dispatch('task-queue-updated');

        } catch (\Throwable $throwable) {
            Log::error("LiveMonitor: Lỗi sinh kịch bản tức thì: " . $throwable->getMessage());
            $this->feedbackMessage = "Lỗi: " . $throwable->getMessage();
        } finally {
            $this->isGeneratingScript = false;
        }
    }

    /**
     * Mở modal xem kịch bản JSON chi tiết.
     */
    public function viewScript(int $taskId): void
    {
        $task = VideoTask::query()->with('channel')->find($taskId);

        if ($task !== null && !empty($task->script_data)) {
            $this->viewingScriptData = $task->script_data;
            $this->viewingScriptTitle = $task->title ?: "Kịch bản #{$task->id}";
            $this->viewingScriptChannel = $task->channel?->channel_name ?: 'Kênh phân phối';
        }
    }

    /**
     * Đóng modal xem kịch bản.
     */
    public function closeScriptModal(): void
    {
        $this->viewingScriptData = null;
        $this->viewingScriptTitle = null;
        $this->viewingScriptChannel = null;
    }

    /**
     * Mở modal xem trước video thật (HTML5 player) từ Buffer Vault.
     */
    public function previewVideo(int $taskId): void
    {
        $task = VideoTask::query()->find($taskId);

        if ($task !== null && !empty($task->video_path) && File::exists($task->video_path)) {
            $this->viewingVideoTaskId = $taskId;
        } else {
            $this->feedbackMessage = "Video của tác vụ #{$taskId} chưa sẵn sàng hoặc file không tồn tại trên đĩa.";
        }
    }

    /**
     * Đóng modal xem trước video.
     */
    public function closeVideoModal(): void
    {
        $this->viewingVideoTaskId = null;
    }

    /**
     * Kích hoạt render video thật bằng RenderVideoJob cho tác vụ đã có kịch bản.
     */
    public function renderVideoNow(int $taskId): void
    {
        $this->isRenderingVideo = true;

        try {
            $task = VideoTask::query()->find($taskId);

            if ($task === null) {
                $this->feedbackMessage = "Không tìm thấy tác vụ #{$taskId}.";
                return;
            }

            if (empty($task->script_data)) {
                $this->feedbackMessage = "Tác vụ #{$taskId} chưa có kịch bản. Vui lòng sinh kịch bản trước.";
                return;
            }

            RenderVideoJob::dispatchSync($task->id);

            $task->refresh();

            if ($task->status === 'ready_in_vault') {
                $this->feedbackMessage = "Dựng video thành công cho tác vụ #{$task->id}! Video 9:16 đã được lưu an toàn tại Buffer Vault.";
            } else {
                $this->feedbackMessage = "Dựng video kết thúc với trạng thái: {$task->status} ({$task->current_step})";
            }

            $this->dispatch('task-queue-updated');
        } catch (\Throwable $throwable) {
            Log::error("LiveMonitor: Lỗi khi dựng video #{$taskId}: " . $throwable->getMessage());
            $this->feedbackMessage = "Lỗi khi render video: " . $throwable->getMessage();
        } finally {
            $this->isRenderingVideo = false;
        }
    }

    /**
     * Ép xuất bản video ngay lập tức (Force Publish) cho video đang ở ready_in_vault.
     */
    public function forcePublishNow(int $taskId): void
    {
        $this->isPublishingVideo = true;

        try {
            $task = VideoTask::query()->with(['channel', 'product'])->find($taskId);

            if ($task === null) {
                $this->feedbackMessage = "Không tìm thấy tác vụ #{$taskId}.";
                return;
            }

            if ($task->status !== 'ready_in_vault') {
                $this->feedbackMessage = "Tác vụ #{$taskId} chưa sẵn sàng trong kho đệm để xuất bản.";
                return;
            }

            PublishVideoJob::dispatchSync($task->id);

            $task->refresh();

            if ($task->status === 'published') {
                $this->feedbackMessage = "Xuất bản thành công video #{$task->id} lên kênh {$task->channel?->channel_name}!";
            } else {
                $this->feedbackMessage = "Xuất bản kết thúc với trạng thái: {$task->status} ({$task->current_step})";
            }

            $this->dispatch('task-queue-updated');
        } catch (\Throwable $throwable) {
            Log::error("LiveMonitor: Lỗi khi ép đăng video #{$taskId}: " . $throwable->getMessage());
            $this->feedbackMessage = "Lỗi khi xuất bản: " . $throwable->getMessage();
        } finally {
            $this->isPublishingVideo = false;
        }
    }

    /**
     * Phê duyệt xuất bản video ở chế độ Co-Pilot (waiting_approval -> ready_in_vault).
     */
    public function approveTask(int $taskId, HumanLikeSchedulerService $scheduler): void
    {
        $task = VideoTask::query()->with('channel')->find($taskId);

        if ($task === null) {
            $this->feedbackMessage = "Không tìm thấy tác vụ #{$taskId}.";
            return;
        }

        if ($task->status !== 'waiting_approval') {
            $this->feedbackMessage = "Tác vụ #{$taskId} không ở trạng thái chờ duyệt.";
            return;
        }

        $slot = $task->channel ? $scheduler->calculateNextPublishSlot($task->channel) : now()->addMinutes(10);

        $task->update([
            'status' => 'ready_in_vault',
            'current_step' => 'Đã phê duyệt! Đang xếp lịch xuất bản: ' . ($slot ? $slot->format('H:i d/m') : 'Ngay lập tức'),
            'scheduled_at' => $slot ?: now(),
        ]);

        $this->feedbackMessage = "Đã phê duyệt video #{$taskId}! Lịch xuất bản: " . ($slot ? $slot->format('H:i d/m') : 'Ngay lập tức');
        $this->dispatch('task-queue-updated');
    }

    /**
     * Mở modal kiểm tra chi tiết lỗi và ảnh Captcha (Error Inspector).
     */
    public function inspectError(int $taskId): void
    {
        $task = VideoTask::query()->find($taskId);

        if ($task !== null) {
            $this->inspectingErrorTaskId = $taskId;
            $this->inspectingErrorLog = $task->error_log ?: 'Không có chi tiết stack trace.';
            $this->inspectingErrorStep = $task->current_step;
            $this->inspectingErrorTime = $task->updated_at ? $task->updated_at->format('H:i:s d/m/Y') : null;

            // Tìm ảnh screenshot Captcha nếu có
            $captchaPath = storage_path("app/temp_assets/captcha_tiktok_{$taskId}.png");
            $captchaShopeePath = storage_path("app/temp_assets/captcha_shopee_{$taskId}.png");

            if (File::exists($captchaPath)) {
                $this->inspectingCaptchaScreenshot = 'data:image/png;base64,' . base64_encode(File::get($captchaPath));
            } elseif (File::exists($captchaShopeePath)) {
                $this->inspectingCaptchaScreenshot = 'data:image/png;base64,' . base64_encode(File::get($captchaShopeePath));
            } else {
                $this->inspectingCaptchaScreenshot = null;
            }
        }
    }

    /**
     * Đóng modal kiểm tra lỗi.
     */
    public function closeErrorModal(): void
    {
        $this->inspectingErrorTaskId = null;
        $this->inspectingErrorLog = null;
        $this->inspectingErrorStep = null;
        $this->inspectingErrorTime = null;
        $this->inspectingCaptchaScreenshot = null;
    }

    /**
     * Thử lại thông minh (Smart Retry): tự phục hồi từ bước bị lỗi.
     */
    public function smartRetry(int $taskId): void
    {
        $task = VideoTask::query()->find($taskId);

        if ($task === null) {
            $this->feedbackMessage = "Không tìm thấy tác vụ #{$taskId}.";
            return;
        }

        // Nếu đã có file video trên đĩa -> Lỗi ở bước xuất bản -> Reset về ready_in_vault
        if (!empty($task->video_path) && File::exists($task->video_path)) {
            $task->update([
                'status' => 'ready_in_vault',
                'current_step' => 'Thử lại xuất bản video...',
                'error_log' => null,
            ]);
            PublishVideoJob::dispatch($task->id);
            $this->feedbackMessage = "Đã đưa lại Video #{$taskId} vào hàng đợi xuất bản!";
        } else {
            // Lỗi ở bước render kịch bản -> Reset về assets_ready và chạy lại RenderVideoJob
            $task->update([
                'status' => 'assets_ready',
                'current_step' => 'Thử lại tiến trình dựng video...',
                'error_log' => null,
                'progress' => 30,
            ]);
            RenderVideoJob::dispatch($task->id);
            $this->feedbackMessage = "Đã đưa lại Video #{$taskId} vào hàng đợi dựng video (Render)!";
        }

        $this->dispatch('task-queue-updated');
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

        // 3. Khởi tạo VideoTask ở trạng thái pending
        $videoTask = VideoTask::query()->create([
            'channel_id' => $channel->id,
            'product_id' => $channel->type === 'affiliate' ? $product->id : null,
            'task_type' => $channel->type === 'affiliate' ? 'affiliate_original' : 'growth_reup_clone',
            'status' => 'pending',
            'current_step' => 'Khởi tạo tác vụ thử nghiệm',
            'progress' => 0,
        ]);

        // 4. Bước 'scripting': Gọi LLM Driver
        $videoTask->update([
            'status' => 'scripting',
            'current_step' => 'Đang gọi AI sinh kịch bản viral (LlmDriver)',
            'progress' => 20,
        ]);

        $scriptDto = $llmDriver->generateScript(
            prompt: 'Tạo kịch bản video ngắn cho sản phẩm ' . $product->product_name,
            options: [
                'channel_type' => $channel->type,
                'product_name' => $product->product_name,
            ]
        );

        $videoTask->update([
            'title' => $scriptDto->title,
            'caption' => $scriptDto->caption,
            'hashtags' => $scriptDto->hashtags,
            'script_data' => $scriptDto->toArray(),
        ]);

        // 5. Bước 'assets_ready': Tạo audio từ kịch bản qua TtsDriver
        $videoTask->update([
            'status' => 'assets_ready',
            'current_step' => 'Đang sinh giọng đọc AI tiếng Việt (TtsDriver)',
            'progress' => 40,
        ]);

        $audioOutputDir = storage_path('app/temp_assets');
        if (!File::exists($audioOutputDir)) {
            File::makeDirectory($audioOutputDir, 0755, true);
        }
        $audioTargetFile = $audioOutputDir . DIRECTORY_SEPARATOR . 'audio_task_' . $videoTask->id . '.mp3';

        $audioPath = $ttsDriver->synthesizeAudio(
            text: $scriptDto->voiceScript,
            voiceName: 'vi-VN-HoaiMyNeural',
            outputPath: $audioTargetFile
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
        // 1. Tối ưu gom nhóm: Lấy danh sách kênh active (tái sử dụng cho thống kê và dropdown)
        $availableChannels = Channel::query()->where('status', 'active')->get();
        $totalActiveChannels = $availableChannels->count();
        $growthChannelsCount = $availableChannels->where('type', 'growth')->count();
        $affiliateChannelsCount = $availableChannels->where('type', 'affiliate')->count();
        $totalDailyQuota = (int) $availableChannels->sum('daily_post_limit');

        // 2. Tối ưu gom nhóm: Lấy danh sách API Keys active (tái sử dụng cho widget và thống kê)
        $activeApiKeys = ApiKey::query()->where('is_active', true)->get();
        $tokensConsumedToday = (int) $activeApiKeys->sum('used_today');
        $totalTokensLimit = (int) $activeApiKeys->sum('daily_limit');

        // 3. Đếm nhanh tác vụ Buffer Vault & Đã đăng hôm nay
        $todayStart = Carbon::today();
        $vaultTasksCount = VideoTask::query()->where('status', 'ready_in_vault')->count();
        $waitingApprovalCount = VideoTask::query()->where('status', 'waiting_approval')->count();
        $publishedTodayCount = VideoTask::query()
            ->where('status', 'published')
            ->where('published_at', '>=', $todayStart)
            ->count();

        // 4. Tác vụ đang xử lý thời gian thực
        $activeTask = VideoTask::query()
            ->with(['channel', 'product'])
            ->whereIn('status', ['pending', 'scripting', 'assets_ready', 'rendering', 'publishing'])
            ->latest()
            ->first();

        // 5. Danh sách tác vụ trong hàng đợi & Buffer Vault với bộ lọc phong phú Sprint 5
        $tasksQuery = VideoTask::query()->with(['channel', 'product']);

        if ($this->channelFilter !== '') {
            $tasksQuery->where('channel_id', (int) $this->channelFilter);
        }

        if ($this->typeFilter !== '') {
            $tasksQuery->where('task_type', $this->typeFilter);
        }

        if ($this->statusTab === 'vault') {
            $tasksQuery->where('status', 'ready_in_vault');
        } elseif ($this->statusTab === 'waiting_approval') {
            $tasksQuery->where('status', 'waiting_approval');
        } elseif ($this->statusTab === 'published') {
            $tasksQuery->where('status', 'published');
        } elseif ($this->statusTab === 'failed') {
            $tasksQuery->where('status', 'failed');
        } elseif ($this->statusFilter !== '') {
            $tasksQuery->where('status', $this->statusFilter);
        }

        $tasks = $tasksQuery->latest()->take(15)->get();

        // 6. Danh sách kịch bản mới sinh (Script Vault)
        $scriptVault = VideoTask::query()
            ->with(['channel', 'product'])
            ->whereNotNull('script_data')
            ->latest()
            ->take(6)
            ->get();

        return view('livewire.dashboard.live-monitor', [
            'totalActiveChannels' => $totalActiveChannels,
            'growthChannelsCount' => $growthChannelsCount,
            'affiliateChannelsCount' => $affiliateChannelsCount,
            'vaultTasksCount' => $vaultTasksCount,
            'waitingApprovalCount' => $waitingApprovalCount,
            'publishedTodayCount' => $publishedTodayCount,
            'totalDailyQuota' => $totalDailyQuota,
            'tokensConsumedToday' => $tokensConsumedToday,
            'totalTokensLimit' => $totalTokensLimit,
            'activeTask' => $activeTask,
            'tasks' => $tasks,
            'activeApiKeys' => $activeApiKeys,
            'scriptVault' => $scriptVault,
            'availableChannels' => $availableChannels,
        ]);
    }
}
