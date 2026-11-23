<?php

namespace App\Core\Contracts;

interface NotificationBotInterface
{
    /**
     * Send a text message to a chat/channel.
     *
     * @param array<string, mixed> $options
     */
    public function sendMessage(string $chatId, string $text, array $options = []): bool;

    /**
     * Send a video file or URL with caption to a chat/channel.
     */
    public function sendVideo(string $chatId, string $videoPathOrUrl, string $caption = ''): bool;
}
