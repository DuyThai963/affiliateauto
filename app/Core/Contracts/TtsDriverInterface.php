<?php

namespace App\Core\Contracts;

interface TtsDriverInterface
{
    /**
     * Synthesize audio from text and return generated file path.
     */
    public function synthesizeAudio(string $text, string $voiceName, string $outputPath): string;
}

