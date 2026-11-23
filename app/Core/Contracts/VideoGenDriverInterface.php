<?php

namespace App\Core\Contracts;

interface VideoGenDriverInterface
{
    /**
     * Generate a short vertical video clip from a text prompt.
     *
     * @param array<string, mixed> $options
     * @return string|null Path to generated MP4 file, or null if skipped/unavailable
     */
    public function generateClip(string $prompt, string $outputPath, array $options = []): ?string;
}
