<?php

namespace App\Jobs;

use App\Core\Contracts\TtsDriverInterface;
use App\Core\Contracts\VideoGenDriverInterface;
use App\Core\Contracts\VideoRenderDriverInterface;
use App\Core\DTOs\RenderTaskDto;
use App\Models\VideoTask;
use App\Services\SubtitleGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class RenderVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $videoTaskId
    ) {
        $this->onQueue('rendering');
    }

    /**
     * Execute the job.
     */
    public function handle(
        TtsDriverInterface $ttsDriver,
        SubtitleGeneratorService $subtitleService,
        VideoRenderDriverInterface $renderDriver
    ): void {
        $videoTask = VideoTask::query()->with(['channel', 'product'])->find($this->videoTaskId);

        if ($videoTask === null) {
            Log::error("RenderVideoJob: Không tìm thấy VideoTask #{$this->videoTaskId}");
            return;
        }

        $tempAudioPath = null;
        $tempSubPath = null;
        $tempBgClipPath = null;

        try {
            $scriptData = $videoTask->script_data ?? [];
            $voiceScript = $scriptData['voice_script'] ?? $videoTask->caption ?? 'Nội dung video ngắn';
            $hookText = $scriptData['hook_text'] ?? null;

            // 1. Chuyển trạng thái rendering
            $videoTask->update([
                'status' => 'rendering',
                'current_step' => 'Đang tạo giọng đọc AI tiếng Việt (TTS)...',
                'progress' => 40,
            ]);

            $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp_assets');
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            $tempAudioPath = $tempDir . DIRECTORY_SEPARATOR . "audio_{$videoTask->id}.mp3";
            $tempSubPath = $tempDir . DIRECTORY_SEPARATOR . "sub_{$videoTask->id}.ass";

            // 2. Sinh audio giọng đọc
            $generatedAudioFile = $ttsDriver->synthesizeAudio(
                text: $voiceScript,
                voiceName: 'vi-VN-HoaiMyNeural',
                outputPath: $tempAudioPath
            );

            // 3. Đồng bộ phụ đề .ass
            $videoTask->update([
                'current_step' => 'Đang đồng bộ phụ đề TikTok Safe Zone...',
                'progress' => 55,
            ]);

            $generatedSubFile = $subtitleService->generateAssSubtitle(
                voiceScript: $voiceScript,
                audioPath: $generatedAudioFile,
                outputPath: $tempSubPath,
                hookText: $hookText
            );

            // 3.5. Gọi AI tạo clip nền (nếu cấu hình VIDEO_GEN_DRIVER=veo3)
            $visualPrompts = $scriptData['visual_prompts'] ?? [];
            $firstVisualPrompt = $this->extractFirstVisualPrompt($visualPrompts);
            $mediaAssets = $visualPrompts;

            if (!empty($firstVisualPrompt)) {
                $videoGenDriver = app(VideoGenDriverInterface::class);
                $candidateBgPath = $tempDir . DIRECTORY_SEPARATOR . "bg_{$videoTask->id}.mp4";
                $generatedBgPath = $videoGenDriver->generateClip($firstVisualPrompt, $candidateBgPath);
                if (!empty($generatedBgPath) && File::exists($generatedBgPath)) {
                    $tempBgClipPath = $generatedBgPath;
                    $mediaAssets['background_video_path'] = $generatedBgPath;
                }
            }

            // 4. Dựng video chuẩn 9:16 bằng FFmpeg
            $videoTask->update([
                'current_step' => 'Đang render video 9:16 (FFmpeg)...',
                'progress' => 60,
            ]);

            $renderTaskDto = new RenderTaskDto(
                taskId: $videoTask->id,
                videoDimensions: '1080x1920',
                audioFilePath: $generatedAudioFile,
                subtitleFilePath: $generatedSubFile,
                mediaAssets: $mediaAssets
            );

            $renderedVideoFile = $renderDriver->renderVideo(
                taskDto: $renderTaskDto,
                onProgressUpdate: function (int $progressPercentage, string $statusMessage) use ($videoTask) {
                    $scaledProgress = 60 + (int) round($progressPercentage * 0.35);
                    $videoTask->update([
                        'current_step' => $statusMessage,
                        'progress' => min(95, $scaledProgress),
                    ]);
                }
            );

            // 5. Cập nhật hoàn thành: phân nhánh Chế độ Vận hành (Auto-Pilot vs Co-Pilot)
            $channel = $videoTask->channel;
            $masterMode = \App\Models\SystemSetting::get('master_operation_mode', 'auto_pilot');
            $channelMode = $channel?->operation_mode ?: $masterMode;
            $effectiveMode = ($masterMode === 'co_pilot' || $channelMode === 'co_pilot') ? 'co_pilot' : 'auto_pilot';

            if ($effectiveMode === 'co_pilot') {
                $videoTask->update([
                    'video_path' => $renderedVideoFile,
                    'status' => 'waiting_approval',
                    'current_step' => 'Video đã dựng xong, chờ duyệt xuất bản (Co-Pilot)',
                    'progress' => 100,
                    'scheduled_at' => null,
                ]);
                Log::info("RenderVideoJob: Task #{$videoTask->id} dựng xong, đang chờ phê duyệt (Co-Pilot Mode)");
                $this->notifyTelegramVaultReady($videoTask, null, true);
            } else {
                $scheduler = app(\App\Services\HumanLikeSchedulerService::class);
                $slot = $channel ? $scheduler->calculateNextPublishSlot($channel) : null;

                $videoTask->update([
                    'video_path' => $renderedVideoFile,
                    'status' => 'ready_in_vault',
                    'current_step' => 'Sẵn sàng trong kho đệm (Buffer Vault). Chờ khung giờ vàng xuất bản!',
                    'progress' => 100,
                    'scheduled_at' => $slot,
                ]);
                Log::info("RenderVideoJob: Task #{$videoTask->id} đã lưu Buffer Vault, lên lịch: " . ($slot ? $slot->toDateTimeString() : 'Chưa xếp slot'));
                $this->notifyTelegramVaultReady($videoTask, $slot, false);
            }

        } catch (\Throwable $throwable) {
            $errorMessage = $throwable->getMessage();
            Log::error("RenderVideoJob: Thất bại khi render Task #{$videoTask->id}: {$errorMessage}");

            $videoTask->update([
                'status' => 'failed',
                'current_step' => 'Lỗi render video: ' . substr($errorMessage, 0, 100),
                'error_log' => $throwable->getTraceAsString(),
            ]);

            throw $throwable;
        } finally {
            // Dọn dẹp file tạm
            if ($tempAudioPath && File::exists($tempAudioPath)) {
                File::delete($tempAudioPath);
            }
            if ($tempSubPath && File::exists($tempSubPath)) {
                File::delete($tempSubPath);
            }
            if ($tempBgClipPath && File::exists($tempBgClipPath)) {
                File::delete($tempBgClipPath);
            }
        }
    }

    /**
     * Trích xuất prompt hình ảnh đầu tiên từ danh sách visual_prompts.
     *
     * @param array<int|string, mixed> $visualPrompts
     */
    protected function extractFirstVisualPrompt(array $visualPrompts): ?string
    {
        if (empty($visualPrompts)) {
            return null;
        }

        $firstItem = reset($visualPrompts);
        if (is_string($firstItem)) {
            return $firstItem;
        }

        if (is_array($firstItem) && !empty($firstItem['visual_query'])) {
            return (string) $firstItem['visual_query'];
        }

        return null;
    }

    /**
     * Gửi thông báo chủ động về Telegram khi video đã dựng xong và sẵn sàng trong kho đệm.
     */
    protected function notifyTelegramVaultReady(VideoTask $videoTask, ?\Carbon\Carbon $slot, bool $isCoPilot): void
    {
        try {
            $adminChatId = (string) env('TELEGRAM_ADMIN_CHAT_ID', '');
            if (empty($adminChatId)) {
                return;
            }

            $bot = app(\App\Core\Contracts\NotificationBotInterface::class);
            $channel = $videoTask->channel;
            $channelName = $channel ? $channel->channel_name : "Kênh #{$videoTask->channel_id}";
            $slotText = $slot ? $slot->format('H:i - d/m/Y') : 'Chưa xếp lịch';

            if ($isCoPilot) {
                $message = "🎬 <b>[CO-PILOT] VIDEO MỚI CẦN DUYỆT!</b>\n\n"
                    . "📺 Kênh: <b>{$channelName}</b>\n"
                    . "📌 Tiêu đề: <i>{$videoTask->title}</i>\n"
                    . "📝 Tác vụ: <b>Task #{$videoTask->id}</b>\n\n"
                    . "👉 Gõ: <code>/approve {$videoTask->id}</code> để duyệt và xếp lịch tự động.\n"
                    . "👉 Gõ: <code>/publish {$videoTask->id}</code> để lấy video đăng ngay.";
            } else {
                $message = "🤖 <b>[AUTO-PILOT] VIDEO MỚI TRONG KHO ĐỆM!</b>\n\n"
                    . "📺 Kênh: <b>{$channelName}</b>\n"
                    . "📌 Tiêu đề: <i>{$videoTask->title}</i>\n"
                    . "⏰ Lịch đăng dự kiến: <b>{$slotText}</b>\n"
                    . "🎯 Trạng thái: Sẵn sàng tự động đăng khi đến giờ hẹn!";
            }

            $bot->sendMessage($adminChatId, $message);
        } catch (\Throwable $throwable) {
            Log::warning('Không thể gửi thông báo Telegram trong RenderVideoJob: ' . $throwable->getMessage());
        }
    }
}

