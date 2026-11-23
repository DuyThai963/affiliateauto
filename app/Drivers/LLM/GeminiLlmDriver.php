<?php

namespace App\Drivers\LLM;

use App\Core\Contracts\LlmDriverInterface;
use App\Core\DTOs\ScriptDataDto;
use App\Services\TokenManagerService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiLlmDriver implements LlmDriverInterface
{
    public function __construct(
        protected TokenManagerService $tokenManager
    ) {
    }

    /**
     * Sinh kịch bản video có cấu trúc bằng Google Gemini API.
     *
     * @param array<string, mixed> $options
     */
    public function generateScript(string $prompt, array $options = []): ScriptDataDto
    {
        $preferredModel = $options['model'] ?? 'gemini-2.5-flash';
        $maxAttempts = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            // 1. Rút Key khả dụng từ TokenManagerService
            $apiKey = $this->tokenManager->getAvailableKey('gemini');

            if ($apiKey === null) {
                if ($lastException !== null) {
                    throw $lastException;
                }

                Log::error('GeminiLlmDriver: Không có API Key nào khả dụng trong hệ thống.');
                throw new RuntimeException('Không có API Key nào khả dụng (đã chạm giới hạn ngày hoặc đang cooldown).');
            }

            $endpointUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$preferredModel}:generateContent?key={$apiKey->api_key}";

            try {
                Log::info("GeminiLlmDriver: Gửi request tới {$preferredModel} (lần {$attempt}/{$maxAttempts}) sử dụng Key #{$apiKey->id} ({$apiKey->key_name})");

                $response = Http::timeout(45)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->post($endpointUrl, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.7,
                            'responseMimeType' => 'application/json',
                        ],
                    ]);

                if ($response->status() === 429 || $response->status() === 503) {
                    $statusCode = $response->status();
                    $this->tokenManager->markCooldown($apiKey, 60);
                    $lastException = new RuntimeException("Gemini API Rate Limit/Unavailable ({$statusCode}) trên Key #{$apiKey->id}. Đã đưa vào cooldown 60s.");
                    Log::warning("GeminiLlmDriver: {$lastException->getMessage()} Đang thử fallback sang key khác...");
                    continue;
                }

                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorMessage = $response->body();
                    Log::error("GeminiLlmDriver: Lỗi HTTP {$statusCode} từ Gemini API: {$errorMessage}");

                    if ($statusCode >= 500) {
                        $this->tokenManager->markCooldown($apiKey, 30);
                    }

                    throw new RuntimeException("Gemini API request thất bại với HTTP status {$statusCode}: {$errorMessage}");
                }

                $responseData = $response->json();
                $rawContent = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (empty($rawContent)) {
                    throw new RuntimeException('Gemini API trả về nội dung rỗng (không có candidate text).');
                }

                // Gỡ bỏ markdown code block nếu có
                $cleanJsonString = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawContent));
                $parsedData = json_decode($cleanJsonString, true);

                if (!is_array($parsedData)) {
                    Log::error("GeminiLlmDriver: Không thể parse JSON từ Gemini. Raw: {$rawContent}");
                    throw new RuntimeException('Phản hồi từ Gemini API không đúng định dạng JSON hợp lệ.');
                }

                // 2. Ghi nhận đã tiêu thụ 1 lượt quota
                $this->tokenManager->recordUsage($apiKey, 1);

                // 3. Mapping vào ScriptDataDto
                return ScriptDataDto::fromArray($parsedData);

            } catch (RequestException $requestException) {
                $this->tokenManager->markCooldown($apiKey, 60);
                Log::error("GeminiLlmDriver: Lỗi mạng khi gọi Gemini API: " . $requestException->getMessage());
                $lastException = $requestException;
                continue;
            } catch (\Throwable $throwable) {
                Log::error("GeminiLlmDriver: Ngoại lệ không mong muốn: " . $throwable->getMessage());
                throw $throwable;
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new RuntimeException('Gemini API thất bại sau khi đã thử tối đa các API Key khả dụng.');
    }
}

