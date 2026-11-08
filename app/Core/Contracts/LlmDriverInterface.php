<?php

namespace App\Core\Contracts;

use App\Core\DTOs\ScriptDataDto;

interface LlmDriverInterface
{
    /**
     * Generate structured video script from prompt.
     *
     * @param array<string, mixed> $options
     */
    public function generateScript(string $prompt, array $options = []): ScriptDataDto;
}

