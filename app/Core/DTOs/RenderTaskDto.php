<?php

namespace App\Core\DTOs;

class RenderTaskDto
{
    /**
     * @param array<int, string> $mediaAssets
     */
    public function __construct(
        public int $taskId,
        public string $videoDimensions = '1080x1920',
        public ?string $audioFilePath = null,
        public ?string $subtitleFilePath = null,
        public array $mediaAssets = []
    ) {
    }

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'task_id' => $this->taskId,
            'video_dimensions' => $this->videoDimensions,
            'audio_file_path' => $this->audioFilePath,
            'subtitle_file_path' => $this->subtitleFilePath,
            'media_assets' => $this->mediaAssets,
        ];
    }
}

