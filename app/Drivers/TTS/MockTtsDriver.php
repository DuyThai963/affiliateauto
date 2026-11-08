<?php

namespace App\Drivers\TTS;

use App\Core\Contracts\TtsDriverInterface;
use Illuminate\Support\Facades\File;

class MockTtsDriver implements TtsDriverInterface
{
    /**
     * Synthesize mock audio and write placeholder file.
     */
    public function synthesizeAudio(string $text, string $voiceName, string $outputPath): string
    {
        $directory = dirname($outputPath);

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Tạo file audio giả lập kèm thông tin metadata
        $audioPlaceholderContent = "RIFF....WAVEfmt ....data...[Mock Voiceover Audio: {$voiceName}] Text: {$text}";
        File::put($outputPath, $audioPlaceholderContent);

        return $outputPath;
    }
}

