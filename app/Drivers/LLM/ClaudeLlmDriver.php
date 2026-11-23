<?php

namespace App\Drivers\LLM;

use App\Core\Contracts\LlmDriverInterface;
use App\Core\DTOs\ScriptDataDto;
use App\Services\TokenManagerService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ClaudeLlmDriver implements LlmDriverInterface
{
    public function __construct(
        protected TokenManagerService $tokenManager
    ) {
    }

    /**
     * Sinh kịch bản video có cấu trúc bằng Anthropic Claude Messages API.
     *
     * @param array<string, mixed> $options
     */
    public function generateScript(string $prompt, array $options = []): ScriptDataDto
    {
        $preferredModel = $options['model'] ?? env('CLAUDE_MODEL', 'claude-3-5-sonnet-latest');
        $maxAttempts = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $apiKey = $this->tokenManager->getAvailableKey('claude');

            if ($apiKey === null) {
                if ($lastException !== null) {
                    throw $lastException;
                }

                Log::error('ClaudeLlmDriver: Không có API Key Claude nào khả dụng trong hệ thống.');
                throw new RuntimeException('Không có API Key Claude nào khả dụng (đã chạm giới hạn ngày hoặc đang cooldown).');
            }

            try {
                Log::info("ClaudeLlmDriver: Gửi request tới {$preferredModel} (lần {$attempt}/{$maxAttempts}) sử dụng Key #{$apiKey->id} ({$apiKey->key_name})");

                $response = Http::timeout(45)
                    ->withHeaders([
                        'x-api-key' => $apiKey->api_key,
                        'anthropic-version' => '2023-06-01',
                        'content-type' => 'application/json',
                    ])
                    ->post('https://api.anthropic.com/v1/messages', [
                        'model' => $preferredModel,
                        'max_tokens' => 2048,
                        'temperature' => 0.7,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                    ]);

                if ($response->status() === 429 || $response->status() === 529 || $response->status() === 503) {
                    $statusCode = $response->status();
                    $this->tokenManager->markCooldown($apiKey, 60);
                    $lastException = new RuntimeException("Claude API Rate Limit/Overloaded ({$statusCode}) trên Key #{$apiKey->id}. Đã đưa vào cooldown 60s.");
                    Log::warning("ClaudeLlmDriver: {$lastException->getMessage()}");
                    continue;
                }

                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorMessage = $response->body();
                    Log::error("ClaudeLlmDriver: Lỗi HTTP {$statusCode}: {$errorMessage}");

                    if ($statusCode >= 500) {
                        $this->tokenManager->markCooldown($apiKey, 30);
                    }

                    throw new RuntimeException("Claude API request thất bại với HTTP status {$statusCode}: {$errorMessage}");
                }

                $responseData = $response->json();
                $rawContent = $responseData['content'][0]['text'] ?? null;

                if (empty($rawContent)) {
                    throw new RuntimeException('Claude API trả về nội dung rỗng.');
                }

                $cleanJsonString = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawContent));
                $parsedData = json_decode($cleanJsonString, true);

                if (!is_array($parsedData)) {
                    Log::error("ClaudeLlmDriver: Không thể parse JSON từ Claude. Raw: {$rawContent}");
                    throw new RuntimeException('Phản hồi từ Claude API không đúng định dạng JSON hợp lệ.');
                }

                $this->tokenManager->recordUsage($apiKey, 1);

                return ScriptDataDto::fromArray($parsedData);
            } catch (RequestException $requestException) {
                $this->tokenManager->markCooldown($apiKey, 60);
                Log::error('ClaudeLlmDriver: Lỗi mạng khi gọi Claude API: ' . $requestException->getMessage());
                $lastException = $requestException;
                continue;
            } catch (\Throwable $throwable) {
                Log::error('ClaudeLlmDriver: Ngoại lệ không mong muốn: ' . $throwable->getMessage());
                throw $throwable;
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new RuntimeException('Claude API thất bại sau khi thử tối đa các API Key khả dụng.');
    }
}
