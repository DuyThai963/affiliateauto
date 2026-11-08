<?php

namespace App\Core\DTOs;

class ScriptDataDto
{
    public function __construct(
        public string $title,
        public string $hookText,
        public string $voiceScript,
        public array $visualPrompts,
        public string $caption,
        public string $hashtags
    ) {
    }

    /**
     * Convert DTO to array for JSON storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'hook_text' => $this->hookText,
            'voice_script' => $this->voiceScript,
            'visual_prompts' => $this->visualPrompts,
            'caption' => $this->caption,
            'hashtags' => $this->hashtags,
        ];
    }

    /**
     * Create DTO from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? '',
            hookText: $data['hook_text'] ?? '',
            voiceScript: $data['voice_script'] ?? '',
            visualPrompts: $data['visual_prompts'] ?? [],
            caption: $data['caption'] ?? '',
            hashtags: $data['hashtags'] ?? ''
        );
    }
}

