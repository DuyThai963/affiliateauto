<?php

namespace App\Drivers\Publisher;

use App\Core\Contracts\PublisherDriverInterface;
use App\Core\DTOs\PublishPayloadDto;
use Illuminate\Support\Facades\Log;

class MockPublisherDriver implements PublisherDriverInterface
{
    /**
     * Simulate publishing video to channel.
     */
    public function publish(PublishPayloadDto $payloadDto): bool
    {
        Log::info('Đã xuất bản giả lập thành công lên kênh', [
            'task_id' => $payloadDto->taskId,
            'channel_id' => $payloadDto->channelId,
            'video_file_path' => $payloadDto->videoFilePath,
            'caption' => $payloadDto->caption,
            'hashtags' => $payloadDto->hashtags,
            'product_name' => $payloadDto->productName,
        ]);

        return true;
    }
}

