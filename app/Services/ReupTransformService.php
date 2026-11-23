<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

class ReupTransformService
{
    /**
     * Biến tính video Reup để thay đổi cấu trúc pixel, mã băm MD5 và làm sạch metadata.
     */
    public function transformVideo(
        string $sourceVideoPath,
        int $taskId,
        ?string $replacementAudioPath = null,
        bool $enableFlip = true
    ): string {
        if (!File::exists($sourceVideoPath)) {
            throw new RuntimeException("Không tìm thấy file video gốc: {$sourceVideoPath}");
        }

        $vaultDir = storage_path('app' . DIRECTORY_SEPARATOR . 'buffer_vault');
        if (!File::exists($vaultDir)) {
            File::makeDirectory($vaultDir, 0755, true);
        }

        $outputFilePath = $vaultDir . DIRECTORY_SEPARATOR . "growth_{$taskId}.mp4";
        $ffmpegBinary = $this->getFfmpegBinary();

        // 1. Xây dựng filter chuỗi biến tính (Zoom 1.03x, Hflip, Grain Noise 1.5%, Speed PTS)
        $zoomFactor = 1.03;
        $videoFilters = [
            "crop=in_w/{$zoomFactor}:in_h/{$zoomFactor}",
            'scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920',
        ];

        if ($enableFlip) {
            $videoFilters[] = 'hflip';
        }

        // Thêm hạt nhiễu nhẹ để đổi hash pixel
        $videoFilters[] = 'noise=alls=1.5:allf=t';

        // Lệch nhẹ nhịp khung hình PTS
        $videoFilters[] = 'setpts=0.99*PTS';

        $filterComplex = implode(',', $videoFilters);

        $command = [
            $ffmpegBinary,
            '-y',
            '-i', $sourceVideoPath,
        ];

        // Nếu có audio giọng đọc mới thì thay thế track audio cũ
        if ($replacementAudioPath && File::exists($replacementAudioPath)) {
            $command[] = '-i';
            $command[] = $replacementAudioPath;
            $command[] = '-map';
            $command[] = '0:v:0';
            $command[] = '-map';
            $command[] = '1:a:0';
            $command[] = '-shortest';
        }

        $command = array_merge($command, [
            '-vf', $filterComplex,
            '-map_metadata', '-1', // Xóa sạch metadata gốc
            '-c:v', 'libx264',
            '-preset', 'fast',
            '-crf', '23',
            '-c:a', 'aac',
            '-b:a', '192k',
            '-pix_fmt', 'yuv420p',
            $outputFilePath,
        ]);

        Log::info("ReupTransformService: Bắt đầu biến tính video Task #{$taskId}");

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful() || !File::exists($outputFilePath)) {
            $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
            Log::error("ReupTransformService: Thất bại khi biến tính video: {$errorOutput}");
            throw new RuntimeException("Lỗi biến tính video Reup: " . substr($errorOutput, 0, 300));
        }

        Log::info("ReupTransformService: Biến tính thành công: {$outputFilePath} (" . File::size($outputFilePath) . " bytes)");
        return $outputFilePath;
    }

    /**
     * Lấy binary FFmpeg.
     */
    protected function getFfmpegBinary(): string
    {
        return (string) (env('FFMPEG_BIN') ?: 'ffmpeg');
    }
}

