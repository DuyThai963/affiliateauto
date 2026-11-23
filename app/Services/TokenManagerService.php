<?php

namespace App\Services;

use App\Models\ApiKey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TokenManagerService
{
    /**
     * Lấy API Key khả dụng (ưu tiên provider chỉ định, tự động fallback khi hết quota/cooldown).
     */
    public function getAvailableKey(string $preferredProvider = 'gemini'): ?ApiKey
    {
        // 1. Thử tìm key của preferredProvider
        $candidateKey = $this->findActiveKeyForProvider($preferredProvider);

        if ($candidateKey !== null) {
            return $candidateKey;
        }

        // 2. Fallback sang các provider khác nếu preferredProvider không khả dụng
        $fallbackProviders = array_values(array_filter(
            ['gemini', 'openai', 'claude'],
            function (string $provider) use ($preferredProvider) {
                return $provider !== $preferredProvider;
            }
        ));

        foreach ($fallbackProviders as $fallbackProvider) {
            $fallbackCandidate = $this->findActiveKeyForProvider($fallbackProvider);
            if ($fallbackCandidate !== null) {
                Log::info("TokenManagerService: Fallback từ {$preferredProvider} sang {$fallbackProvider} (Key ID: {$fallbackCandidate->id})");
                return $fallbackCandidate;
            }
        }

        // 3. Toàn bộ key đều hết hạn mức hoặc đang cooldown
        Log::warning('TokenManagerService: Toàn bộ API Keys trong hệ thống đều hết hạn mức hoặc đang trong thời gian cooldown.');
        return null;
    }

    /**
     * Tìm key phù hợp cho một provider cụ thể.
     */
    protected function findActiveKeyForProvider(string $provider): ?ApiKey
    {
        $now = Carbon::now();

        // Lấy danh sách key đang active và chưa vượt hạn mức ngày
        $activeKeys = ApiKey::query()
            ->where('provider', $provider)
            ->where('is_active', true)
            ->whereColumn('used_today', '<', 'daily_limit')
            ->get();

        if ($activeKeys->isEmpty()) {
            return null;
        }

        // Lọc các key đã hết thời gian cooldown
        $availableKeys = $activeKeys->filter(function (ApiKey $apiKey) {
            if ($apiKey->last_used_at === null) {
                return true;
            }

            return $apiKey->last_used_at->copy()->addSeconds($apiKey->cooldown_seconds)->isPast();
        });

        if ($availableKeys->isEmpty()) {
            return null;
        }

        // Ưu tiên key có thời gian chờ dài nhất (Round-Robin tự nhiên)
        return $availableKeys->sortBy(function (ApiKey $apiKey) {
            return $apiKey->last_used_at === null ? 0 : $apiKey->last_used_at->timestamp;
        })->first();
    }

    /**
     * Ghi nhận lượt sử dụng API Key.
     */
    public function recordUsage(ApiKey $apiKey, int $tokensUsed = 1): void
    {
        $apiKey->used_today += $tokensUsed;
        $apiKey->last_used_at = Carbon::now();
        $apiKey->save();

        Log::info("TokenManagerService: Ghi nhận sử dụng Key #{$apiKey->id} ({$apiKey->key_name}) - Used Today: {$apiKey->used_today}/{$apiKey->daily_limit}");
    }

    /**
     * Đánh dấu key rơi vào trạng thái Cooldown khi gặp lỗi (429 Rate Limit hoặc lỗi mạng).
     */
    public function markCooldown(ApiKey $apiKey, int $seconds = 60): void
    {
        $apiKey->cooldown_seconds = max($apiKey->cooldown_seconds, $seconds);
        $apiKey->last_used_at = Carbon::now();
        $apiKey->save();

        Log::warning("TokenManagerService: Đánh dấu Key #{$apiKey->id} ({$apiKey->key_name}) Cooldown trong {$seconds}s do Rate Limit hoặc lỗi dịch vụ.");
    }
}
