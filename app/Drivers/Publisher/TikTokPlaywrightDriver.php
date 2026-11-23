<?php

namespace App\Drivers\Publisher;

use App\Core\Contracts\PublisherDriverInterface;
use App\Core\DTOs\PublishPayloadDto;
use App\Models\Channel;
use App\Models\VideoTask;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

class TikTokPlaywrightDriver implements PublisherDriverInterface
{
    /**
     * Xuất bản video lên TikTok bằng Playwright.
     */
    public function publish(PublishPayloadDto $payloadDto): bool
    {
        $task = VideoTask::query()->with(['channel', 'product'])->find($payloadDto->taskId);
        $channel = $task?->channel ?? Channel::query()->find($payloadDto->channelId);

        if ($channel === null) {
            throw new RuntimeException("Không tìm thấy kênh hợp lệ cho Task #{$payloadDto->taskId}");
        }

        $profilePath = $channel->browser_profile_path;
        if (empty($profilePath)) {
            $profilePath = 'storage/app/browser_profiles/tiktok_' . $channel->id;
            $channel->update(['browser_profile_path' => $profilePath]);
        }

        $absoluteProfilePath = base_path($profilePath);
        if (!File::exists($absoluteProfilePath)) {
            File::makeDirectory($absoluteProfilePath, 0755, true);
        }

        $scriptPath = base_path('scripts/tiktok_publisher.js');
        $videoFilePath = $payloadDto->videoFilePath;

        if (!File::exists($videoFilePath)) {
            throw new RuntimeException("File video không tồn tại trên đĩa: {$videoFilePath}");
        }

        $command = [
            'node',
            $scriptPath,
            '--profile-path',
            $absoluteProfilePath,
            '--video-path',
            $videoFilePath,
            '--caption',
            $payloadDto->caption,
            '--hashtags',
            $payloadDto->hashtags,
            '--channel-type',
            $channel->type,
            '--task-id',
            (string) $payloadDto->taskId,
        ];

        // Nếu là kênh affiliate và có sản phẩm, truyền tên sản phẩm
        if ($channel->type === 'affiliate' && !empty($payloadDto->productName)) {
            $command[] = '--product-name';
            $command[] = $payloadDto->productName;
        }

        Log::info("TikTokPlaywrightDriver: Bắt đầu chạy tiến trình đăng video cho Task #{$payloadDto->taskId} (Kênh: {$channel->channel_name})");

        $process = new Process($command);
        $process->setTimeout(300); // 5 phút tối đa

        $process->run();

        $output = trim($process->getOutput());
        $errorOutput = trim($process->getErrorOutput());

        Log::info("TikTokPlaywrightDriver: Output từ Playwright: {$output}");

        // Tìm chuỗi JSON trong output
        $resultJson = null;
        if (preg_match('/\{.*"status".*\}/s', $output, $matches)) {
            $resultJson = json_decode($matches[0], true);
        }

        if ($resultJson === null) {
            Log::error("TikTokPlaywrightDriver: Không parse được JSON từ script. Raw output: {$output} | Error: {$errorOutput}");
            throw new RuntimeException("Tiến trình Playwright thất bại mà không trả về trạng thái hợp lệ: " . substr($errorOutput ?: $output, 0, 200));
        }

        $status = $resultJson['status'] ?? 'failed';

        if ($status === 'captcha_detected') {
            $screenshot = $resultJson['screenshot'] ?? null;
            $channel->update(['status' => 'needs_relogin']);
            throw new RuntimeException("Phát hiện Captcha yêu cầu xác thực thủ công! Ảnh chụp màn hình: {$screenshot}");
        }

        if ($status === 'needs_relogin') {
            $channel->update(['status' => 'needs_relogin']);
            throw new RuntimeException("Phiên đăng nhập TikTok của kênh {$channel->channel_name} đã hết hạn. Vui lòng bấm 'Đăng Nhập Lại'.");
        }

        if ($status === 'success') {
            Log::info("TikTokPlaywrightDriver: Xuất bản video thành công cho Task #{$payloadDto->taskId}!");
            return true;
        }

        $errorMessage = $resultJson['error'] ?? 'Lỗi không xác định khi đăng tải video.';
        throw new RuntimeException("Lỗi đăng video TikTok: {$errorMessage}");
    }
}

