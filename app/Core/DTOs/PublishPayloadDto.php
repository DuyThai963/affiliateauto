<?php

namespace App\Core\DTOs;

class PublishPayloadDto
{
    public function __construct(
        public int $taskId,
        public int $channelId,
        public string $videoFilePath,
        public string $caption,
        public string $hashtags,
        public ?string $productName = null
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
            'channel_id' => $this->channelId,
            'video_file_path' => $this->videoFilePath,
            'caption' => $this->caption,
            'hashtags' => $this->hashtags,
            'product_name' => $this->productName,
        ];
    }
}

