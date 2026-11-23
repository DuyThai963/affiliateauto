<?php

namespace App\Drivers\VideoGen;

use App\Core\Contracts\VideoGenDriverInterface;
use App\Services\TokenManagerService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiVeo3Driver implements VideoGenDriverInterface
{
    public function __construct(
        protected TokenManagerService $tokenManager
    ) {
    }

    /**
     * Gọi Google Gemini Veo 3 API để sinh video clip dọc 9:16 từ prompt.
     *
     * @param array<string, mixed> $options
     */
    public function generateClip(string $prompt, string $outputPath, array $options = []): ?string
    {
        $apiKey = $this->tokenManager->getAvailableKey('gemini');
        if ($apiKey === null) {
            Log::warning('GeminiVeo3Driver: Không có API Key Gemini khả dụng để gọi Veo 3. Bỏ qua bước sinh clip AI.');
            return null;
        }

        $model = $options['model'] ?? env('VEO_MODEL', 'veo-3.0-generate-preview');
        $aspectRatio = $options['aspect_ratio'] ?? '9:16';
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:predictLongRunning";

        try {
            Log::info("GeminiVeo3Driver: Bắt đầu gọi {$model} với Key #{$apiKey->id}");

            $startResponse = Http::timeout(45)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey->api_key,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'instances' => [
                        [
                            'prompt' => $prompt,
                        ],
                    ],
                    'parameters' => [
                        'aspectRatio' => $aspectRatio,
                    ],
                ]);

            if ($startResponse->status() === 429 || $startResponse->status() === 503) {
                $this->tokenManager->markCooldown($apiKey, 60);
                throw new RuntimeException("Veo 3 API Rate Limit/Unavailable ({$startResponse->status()}).");
            }

            if ($startResponse->failed()) {
                Log::warning("GeminiVeo3Driver: Không thể khởi tạo Veo 3 job (HTTP {$startResponse->status()}): " . $startResponse->body());
                return null;
            }

            $operationName = $startResponse->json('name');
            if (empty($operationName)) {
                Log::warning('GeminiVeo3Driver: Phản hồi không có operation name.');
                return null;
            }

            $maxPollAttempts = (int) ($options['max_poll_attempts'] ?? 24);
            $pollIntervalSeconds = (int) ($options['poll_interval_seconds'] ?? 5);
            $videoUri = null;

            for ($poll = 1; $poll <= $maxPollAttempts; $poll++) {
                if ($pollIntervalSeconds > 0 && !app()->runningUnitTests()) {
                    sleep($pollIntervalSeconds);
                }

                $pollUrl = "https://generativelanguage.googleapis.com/v1beta/{$operationName}";
                $pollResponse = Http::timeout(30)
                    ->withHeaders(['x-goog-api-key' => $apiKey->api_key])
                    ->get($pollUrl);

                if ($pollResponse->failed()) {
                    continue;
                }

                $pollJson = $pollResponse->json();
                if (!empty($pollJson['done'])) {
                    $videoUri = $pollJson['response']['generateVideoResponse']['generatedSamples'][0]['video']['uri'] ?? null;
                    break;
                }
            }

            if (empty($videoUri)) {
                Log::warning('GeminiVeo3Driver: Quá thời gian chờ hoặc không nhận được URI video từ Veo 3.');
                return null;
            }

            $directory = dirname($outputPath);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $downloadResponse = Http::timeout(120)
                ->withHeaders(['x-goog-api-key' => $apiKey->api_key])
                ->get($videoUri);

            if ($downloadResponse->failed() || empty($downloadResponse->body())) {
                Log::warning("GeminiVeo3Driver: Tải clip từ Veo 3 thất bại (HTTP {$downloadResponse->status()}).");
                return null;
            }

            File::put($outputPath, $downloadResponse->body());
            $this->tokenManager->recordUsage($apiKey, 1);

            Log::info("GeminiVeo3Driver: Đã tạo clip Veo 3 thành công tại {$outputPath}");
            return $outputPath;
        } catch (\Throwable $throwable) {
            Log::warning('GeminiVeo3Driver: Lỗi khi tạo video Veo 3, chuyển sang nền dự phòng: ' . $throwable->getMessage());
            return null;
        }
    }
}
