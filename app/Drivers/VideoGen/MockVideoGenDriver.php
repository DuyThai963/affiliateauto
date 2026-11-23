<?php

namespace App\Drivers\VideoGen;

use App\Core\Contracts\VideoGenDriverInterface;

class MockVideoGenDriver implements VideoGenDriverInterface
{
    /**
     * Mock driver skips external video generation during local testing.
     *
     * @param array<string, mixed> $options
     */
    public function generateClip(string $prompt, string $outputPath, array $options = []): ?string
    {
        return null;
    }
}
