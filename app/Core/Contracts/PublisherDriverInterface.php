<?php

namespace App\Core\Contracts;

use App\Core\DTOs\PublishPayloadDto;

interface PublisherDriverInterface
{
    /**
     * Publish video to target social platform channel.
     *
     * @return bool True if published successfully, false otherwise
     */
    public function publish(PublishPayloadDto $payloadDto): bool;
}

