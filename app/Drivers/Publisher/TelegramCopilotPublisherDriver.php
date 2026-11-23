<?php

namespace App\Drivers\Publisher;

use App\Core\Contracts\NotificationBotInterface;
use App\Core\Contracts\PublisherDriverInterface;
use App\Core\DTOs\PublishPayloadDto;
use App\Models\Channel;
use Illuminate\Support\Facades\Log;

class TelegramCopilotPublisherDriver implements PublisherDriverInterface
{
    public function __construct(
        protected NotificationBotInterface $notificationBot
    ) {
    }

    /**
     * Gửi video + caption + sản phẩm về Telegram để người dùng bấm đăng thủ công trên điện thoại.
     */
    public function publish(PublishPayloadDto $payloadDto): bool
    {
        $chatId = (string) env('TELEGRAM_ADMIN_CHAT_ID', '');
        if (empty($chatId)) {
            Log::warning('TelegramCopilotPublisherDriver: Chưa cấu hình TELEGRAM_ADMIN_CHAT_ID.');
            return false;
        }

        $channel = Channel::query()->find($payloadDto->channelId);
        $channelLabel = $channel ? "{$channel->channel_name} (" . strtoupper($channel->platform) . ")" : "Kênh #{$payloadDto->channelId}";

        $lines = [
            "🚀 <b>ĐẾN GIỜ ĐĂNG VIDEO #{$payloadDto->taskId}</b>",
            "📺 Kênh: <b>{$channelLabel}</b>",
            "",
            "📝 <b>Caption & Hashtags:</b>",
            trim($payloadDto->caption . ' ' . $payloadDto->hashtags),
        ];

        if (!empty($payloadDto->productName)) {
            $lines[] = "";
            $lines[] = "🛒 <b>Sản phẩm ghim giỏ hàng:</b> {$payloadDto->productName}";
        }

        $message = implode("\n", $lines);

        if (!empty($payloadDto->videoFilePath)) {
            return $this->notificationBot->sendVideo($chatId, $payloadDto->videoFilePath, $message);
        }

        return $this->notificationBot->sendMessage($chatId, $message);
    }
}
