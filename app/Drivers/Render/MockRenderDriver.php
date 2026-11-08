<?php

namespace App\Drivers\Render;

use App\Core\Contracts\VideoRenderDriverInterface;
use App\Core\DTOs\RenderTaskDto;
use Illuminate\Support\Facades\File;

class MockRenderDriver implements VideoRenderDriverInterface
{
    /**
     * Render mock video and report progress milestones.
     *
     * @param callable(int $progressPercentage, string $statusMessage): void $onProgressUpdate
     */
    public function renderVideo(RenderTaskDto $taskDto, callable $onProgressUpdate): string
    {
        $milestones = [
            ['progress' => 25, 'message' => 'Đang phân tích kịch bản và chuẩn bị khung hình...'],
            ['progress' => 50, 'message' => 'Đang ghép âm thanh giọng đọc và hiệu ứng hình ảnh...'],
            ['progress' => 75, 'message' => 'Đang nén video chuẩn 1080x1920 (9:16)...'],
            ['progress' => 100, 'message' => 'Render video hoàn tất!'],
        ];

        foreach ($milestones as $milestone) {
            $progress = $milestone['progress'];
            $message = $milestone['message'];

            // Gọi callback cập nhật tiến trình
            call_user_func($onProgressUpdate, $progress, $message);
            usleep(150000); // 150ms giả lập thời gian xử lý
        }

        $vaultDirectory = storage_path('app/buffer_vault');

        if (!File::exists($vaultDirectory)) {
            File::makeDirectory($vaultDirectory, 0755, true);
        }

        $renderedVideoPath = $vaultDirectory . DIRECTORY_SEPARATOR . 'mock_video_' . $taskDto->taskId . '.mp4';
        
        $mockVideoContent = "ftypmp42isommp42...[MOCK MP4 VIDEO RENDERED FOR TASK #{$taskDto->taskId}] Dims: {$taskDto->videoDimensions}";
        File::put($renderedVideoPath, $mockVideoContent);

        return $renderedVideoPath;
    }
}

