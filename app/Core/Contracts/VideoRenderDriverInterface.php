<?php

namespace App\Core\Contracts;

use App\Core\DTOs\RenderTaskDto;

interface VideoRenderDriverInterface
{
    /**
     * Render video from task specifications and report progress via callback.
     *
     * @param callable(int $progressPercentage, string $statusMessage): void $onProgressUpdate
     * @return string Path to rendered video file
     */
    public function renderVideo(RenderTaskDto $taskDto, callable $onProgressUpdate): string;
}

