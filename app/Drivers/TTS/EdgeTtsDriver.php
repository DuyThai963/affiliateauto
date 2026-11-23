<?php

namespace App\Drivers\TTS;

use App\Core\Contracts\TtsDriverInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

class EdgeTtsDriver implements TtsDriverInterface
{
    /**
     * Sinh giọng đọc tiếng Việt bằng edge-tts.
     */
    public function synthesizeAudio(string $text, string $voiceName, string $outputPath): string
    {
        $directory = dirname($outputPath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $cleanVoice = !empty($voiceName) ? $voiceName : 'vi-VN-HoaiMyNeural';
        $scriptPath = base_path('scripts' . DIRECTORY_SEPARATOR . 'tts_generator.py');
        $pythonBinary = $this->getPythonBinary();

        // Tạo file tạm cho text để tránh lỗi escape tiếng Việt trên Windows CLI
        $tempTextFile = storage_path('app' . DIRECTORY_SEPARATOR . 'temp_assets' . DIRECTORY_SEPARATOR . 'tts_input_' . uniqid() . '.txt');
        File::put($tempTextFile, $text);

        $process = new Process([
            $pythonBinary,
            $scriptPath,
            '--text-file',
            $tempTextFile,
            '--voice',
            $cleanVoice,
            '--rate',
            '+5%',
            '--output',
            $outputPath,
        ]);

        $process->setTimeout(60);

        try {
            Log::info("EdgeTtsDriver: Bắt đầu gọi TTS cho file: {$outputPath} (Giọng: {$cleanVoice})");
            $process->run();

            if (!$process->isSuccessful() || !File::exists($outputPath)) {
                $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
                Log::error("EdgeTtsDriver: Thất bại khi chạy edge-tts: {$errorOutput}");
                throw new RuntimeException("Lỗi sinh âm thanh từ Edge-TTS: {$errorOutput}");
            }

            Log::info("EdgeTtsDriver: Sinh file audio thành công: {$outputPath} (Size: " . File::size($outputPath) . " bytes)");
            return $outputPath;

        } finally {
            if (File::exists($tempTextFile)) {
                File::delete($tempTextFile);
            }
        }
    }

    /**
     * Xác định binary Python khả dụng theo cấu hình môi trường hoặc hệ điều hành.
     */
    protected function getPythonBinary(): string
    {
        $configuredBinary = env('PYTHON_BIN');
        if (!empty($configuredBinary)) {
            return (string) $configuredBinary;
        }

        return PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
    }
}

