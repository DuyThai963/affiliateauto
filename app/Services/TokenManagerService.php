<?php

namespace App\Services;

use App\Models\ApiKey;
use Carbon\Carbon;
use Exception;

class TokenManagerService
{
    /**
     * Get an available API Key for the given provider.
     *
     * @throws Exception If no available API Key is found
     */
    public function getAvailableKey(string $provider): ApiKey
    {
        $availableKey = ApiKey::query()
            ->where('provider', $provider)
            ->where('is_active', true)
            ->whereColumn('used_today', '<', 'daily_limit')
            ->orderBy('last_used_at', 'asc')
            ->first();

        if ($availableKey === null) {
            throw new Exception("Hết hạn mức API Key cho nhà cung cấp: {$provider}. Vui lòng bổ sung hoặc chờ reset.");
        }

        return $availableKey;
    }

    /**
     * Record usage for an API Key and set cooldown.
     */
    public function recordUsage(ApiKey $apiKey, int $tokensCount = 1): void
    {
        $apiKey->increment('used_today', $tokensCount);
        $apiKey->update([
            'last_used_at' => Carbon::now(),
        ]);
    }

    /**
     * Reset daily usage counter for keys whose reset interval has arrived.
     */
    public function resetDailyQuota(): void
    {
        ApiKey::query()->update([
            'used_today' => 0,
            'reset_at' => Carbon::now(),
        ]);
    }
}

