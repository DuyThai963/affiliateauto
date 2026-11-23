<?php

namespace App\Drivers\Notification;

use App\Core\Contracts\NotificationBotInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotDriver implements NotificationBotInterface
{
    protected function getBotToken(): string
    {
        return (string) env('TELEGRAM_BOT_TOKEN', '');
    }

    /**
     * Gửi tin nhắn văn bản qua Telegram Bot API.
     *
     * @param array<string, mixed> $options
     */
    public function sendMessage(string $chatId, string $text, array $options = []): bool
    {
        $token = $this->getBotToken();
        if (empty($token) || empty($chatId)) {
            Log::warning('TelegramBotDriver: Thiếu TELEGRAM_BOT_TOKEN hoặc chat_id.');
            return false;
        }

        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $options);

        $response = Http::timeout(20)->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

        if ($response->failed()) {
            Log::error('TelegramBotDriver: Gửi tin nhắn thất bại: ' . $response->body());
            return false;
        }

        return true;
    }

    /**
     * Gửi video (.mp4 cục bộ hoặc URL) kèm caption qua Telegram Bot API.
     */
    public function sendVideo(string $chatId, string $videoPathOrUrl, string $caption = ''): bool
    {
        $token = $this->getBotToken();
        if (empty($token) || empty($chatId)) {
            Log::warning('TelegramBotDriver: Thiếu TELEGRAM_BOT_TOKEN hoặc chat_id.');
            return false;
        }

        $endpoint = "https://api.telegram.org/bot{$token}/sendVideo";
        $trimmedCaption = mb_substr($caption, 0, 1024);

        if (File::exists($videoPathOrUrl)) {
            $stream = fopen($videoPathOrUrl, 'r');
            try {
                $response = Http::timeout(120)
                    ->attach('video', $stream, basename($videoPathOrUrl))
                    ->post($endpoint, [
                        'chat_id' => $chatId,
                        'caption' => $trimmedCaption,
                        'parse_mode' => 'HTML',
                    ]);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        } else {
            $response = Http::timeout(60)->post($endpoint, [
                'chat_id' => $chatId,
                'video' => $videoPathOrUrl,
                'caption' => $trimmedCaption,
                'parse_mode' => 'HTML',
            ]);
        }

        if ($response->failed()) {
            Log::error('TelegramBotDriver: Gửi video thất bại: ' . $response->body());
            return false;
        }

        return true;
    }
}
