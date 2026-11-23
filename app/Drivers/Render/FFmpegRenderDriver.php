<?php

namespace App\Drivers\Render;

use App\Core\Contracts\VideoRenderDriverInterface;
use App\Core\DTOs\RenderTaskDto;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

class FFmpegRenderDriver implements VideoRenderDriverInterface
{
    /**
     * Render video chuẩn dọc 9:16 (1080x1920) qua FFmpeg.
     */
    public function renderVideo(RenderTaskDto $taskDto, callable $onProgressUpdate): string
    {
        $vaultDir = storage_path('app' . DIRECTORY_SEPARATOR . 'buffer_vault');
        if (!File::exists($vaultDir)) {
            File::makeDirectory($vaultDir, 0755, true);
        }

        $outputFilePath = $vaultDir . DIRECTORY_SEPARATOR . "video_{$taskDto->taskId}.mp4";
        $ffmpegBinary = $this->getFfmpegBinary();

        $onProgressUpdate(30, 'Đang chuẩn bị nguyên liệu âm thanh & kịch bản visual...');

        // 1. Kiểm tra audio
        if (!File::exists($taskDto->audioFilePath)) {
            throw new RuntimeException("Không tìm thấy file audio: {$taskDto->audioFilePath}");
        }

        // Lấy thời lượng audio
        $audioDuration = $this->getAudioDuration($taskDto->audioFilePath);

        // 2. Xây dựng filter đồ họa FFmpeg
        $videoFilters = [];
        $hasCustomFootage = false;

        // Kiểm tra xem có clip nền AI (Veo 3) hoặc background asset mẫu không
        $generatedBgVideo = $taskDto->mediaAssets['background_video_path'] ?? null;
        $defaultAssetDir = storage_path('app' . DIRECTORY_SEPARATOR . 'default_assets');
        $sampleBgVideo = $defaultAssetDir . DIRECTORY_SEPARATOR . 'background_sample.mp4';
        $sampleBgImage = $defaultAssetDir . DIRECTORY_SEPARATOR . 'background_sample.jpg';

        $inputArgs = [];

        if (!empty($generatedBgVideo) && File::exists($generatedBgVideo)) {
            $inputArgs[] = '-stream_loop';
            $inputArgs[] = '-1';
            $inputArgs[] = '-i';
            $inputArgs[] = $generatedBgVideo;
            $hasCustomFootage = true;
            $videoFilters[] = 'scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920';
        } elseif (File::exists($sampleBgVideo)) {
            $inputArgs[] = '-stream_loop';
            $inputArgs[] = '-1';
            $inputArgs[] = '-i';
            $inputArgs[] = $sampleBgVideo;
            $hasCustomFootage = true;
            $videoFilters[] = 'scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920';
        } elseif (File::exists($sampleBgImage)) {
            $inputArgs[] = '-loop';
            $inputArgs[] = '1';
            $inputArgs[] = '-i';
            $inputArgs[] = $sampleBgImage;
            $hasCustomFootage = true;
            // Hiệu ứng zoom nhẹ Ken Burns
            $videoFilters[] = 'scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920';
        } else {
            // Nền tối studio chuẩn Apple HIG tạo bằng color lavfi
            $inputArgs[] = '-f';
            $inputArgs[] = 'lavfi';
            $inputArgs[] = '-i';
            $inputArgs[] = 'color=c=0x121216:s=1080x1920:r=30';
            $videoFilters[] = 'drawbox=x=0:y=0:w=1080:h=1920:color=0x18181B@0.8:t=fill';
        }

        // Đưa audio vào input thứ 2
        $inputArgs[] = '-i';
        $inputArgs[] = $taskDto->audioFilePath;

        // Phủ phụ đề nếu có file .ass
        if (!empty($taskDto->subtitleFilePath) && File::exists($taskDto->subtitleFilePath)) {
            $escapedSubPath = str_replace(['\\', ':'], ['/', '\\:'], $taskDto->subtitleFilePath);
            $videoFilters[] = "ass='{$escapedSubPath}'";
        }

        $filterComplex = implode(',', $videoFilters);

        $command = array_merge(
            [$ffmpegBinary, '-y'],
            $inputArgs,
            [
                '-vf', $filterComplex,
                '-r', '30',
                '-c:v', 'libx264',
                '-preset', 'fast',
                '-crf', '22',
                '-c:a', 'aac',
                '-b:a', '192k',
                '-pix_fmt', 'yuv420p',
                '-shortest',
                '-t', (string) $audioDuration,
                $outputFilePath,
            ]
        );

        $onProgressUpdate(50, 'Đang nén video H.264 và phủ phụ đề Safe Zone...');

        Log::info("FFmpegRenderDriver: Bắt đầu chạy FFmpeg cho Task #{$taskDto->taskId}");

        $process = new Process($command);
        $process->setTimeout(300);

        $onProgressUpdate(70, 'Đang đóng gói khung hình 9:16 (1080x1920)...');

        $process->run(function (string $type, string $buffer) use ($onProgressUpdate) {
            // Phân tích tiến độ từ log output của FFmpeg nếu cần
            if (str_contains($buffer, 'frame=')) {
                $onProgressUpdate(85, 'Đang hoàn tất mã hóa luồng MP4...');
            }
        });

        if (!$process->isSuccessful() || !File::exists($outputFilePath)) {
            $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
            Log::error("FFmpegRenderDriver: Lỗi khi render: {$errorOutput}");
            throw new RuntimeException("FFmpeg Render thất bại: " . substr($errorOutput, 0, 300));
        }

        $onProgressUpdate(100, 'Render hoàn tất! Đã lưu video vào Buffer Vault.');
        Log::info("FFmpegRenderDriver: Render thành công file: {$outputFilePath} (" . File::size($outputFilePath) . " bytes)");

        return $outputFilePath;
    }

    /**
     * Đo độ dài audio bằng FFmpeg.
     */
    protected function getAudioDuration(string $audioPath): float
    {
        $ffmpegBinary = $this->getFfmpegBinary();
        $process = new Process([$ffmpegBinary, '-i', $audioPath]);
        $process->run();

        $output = $process->getErrorOutput() . ' ' . $process->getOutput();
        if (preg_match('/Duration:\s*(\d+):(\d+):(\d+\.\d+)/', $output, $matches)) {
            $hours = (int) $matches[1];
            $minutes = (int) $matches[2];
            $seconds = (float) $matches[3];
            return ($hours * 3600) + ($minutes * 60) + $seconds;
        }

        return 15.0;
    }

    /**
     * Lấy binary FFmpeg.
     */
    protected function getFfmpegBinary(): string
    {
        return (string) (env('FFMPEG_BIN') ?: 'ffmpeg');
    }
}

