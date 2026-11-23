<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SessionHealthcheckService
{
    /**
     * Dành riêng cho testing giả lập kết quả.
     */
    protected static ?array $mockResult = null;

    public function __construct(
        protected SessionManagerService $sessionManager
    ) {
    }

    /**
     * Thiết lập kết quả giả lập phục vụ cho Unit/Feature Test.
     */
    public static function setMockResult(?array $result): void
    {
        self::$mockResult = $result;
    }

    /**
     * Kiểm tra sức khỏe phiên đăng nhập của kênh mà không cần mở giao diện trình duyệt.
     *
     * @return array{is_alive: bool, status: string, message: string, checked_at: \Carbon\Carbon}
     */
    public function checkChannel(Channel $channel): array
    {
        $now = now();

        // 1. Kiểm tra nếu có mock test kết quả
        if (self::$mockResult !== null) {
            $isAlive = (bool) (self::$mockResult['is_alive'] ?? false);
            $message = (string) (self::$mockResult['message'] ?? ($isAlive ? 'Phiên hoạt động tốt' : 'Phiên hết hạn'));
            $newStatus = $isAlive ? 'active' : 'needs_relogin';

            $channel->update([
                'status' => $newStatus,
                'health_notes' => $message,
                'last_healthchecked_at' => $now,
            ]);

            return [
                'is_alive' => $isAlive,
                'status' => $newStatus,
                'message' => $message,
                'checked_at' => $now,
            ];
        }

        $profileDir = $this->sessionManager->resolveProfilePath($channel);

        // 2. Nếu thư mục profile chưa có trên ổ đĩa
        if (!File::exists($profileDir)) {
            $message = 'Thư mục phiên trình duyệt chưa được khởi tạo. Cần đăng nhập hoặc nạp file ZIP.';
            $channel->update([
                'status' => 'needs_relogin',
                'health_notes' => $message,
                'last_healthchecked_at' => $now,
            ]);

            return [
                'is_alive' => false,
                'status' => 'needs_relogin',
                'message' => $message,
                'checked_at' => $now,
            ];
        }

        $platform = strtolower($channel->platform);
        $scriptPath = base_path('scripts/check_session_health.js');

        // 3. Thực thi script Playwright ở chế độ Headless
        $process = new Process([
            'node',
            $scriptPath,
            '--platform',
            $platform,
            '--profile-path',
            $profileDir,
        ]);
        $process->setTimeout(45);

        try {
            $process->run();
            $output = trim($process->getOutput());

            $decoded = json_decode($output, true);

            if (!is_array($decoded)) {
                // Parse dòng JSON cuối cùng nếu stdout có output thừa
                $lines = explode("\n", $output);
                foreach (array_reverse($lines) as $line) {
                    $tryJson = json_decode(trim($line), true);
                    if (is_array($tryJson) && isset($tryJson['is_alive'])) {
                        $decoded = $tryJson;
                        break;
                    }
                }
            }

            $isAlive = is_array($decoded) && !empty($decoded['is_alive']);
            $message = is_array($decoded) ? ($decoded['message'] ?? 'Đã hoàn tất kiểm tra.') : 'Không thể đọc phản hồi từ Playwright.';
            $newStatus = $isAlive ? 'active' : 'needs_relogin';

            $channel->update([
                'status' => $newStatus,
                'health_notes' => $message,
                'last_healthchecked_at' => $now,
            ]);

            Log::info("SessionHealthcheckService: Kênh #{$channel->id} ({$channel->channel_name}) - Alive: " . ($isAlive ? 'YES' : 'NO') . " - {$message}");

            return [
                'is_alive' => $isAlive,
                'status' => $newStatus,
                'message' => $message,
                'checked_at' => $now,
            ];

        } catch (\Throwable $throwable) {
            $errorMsg = 'Lỗi tiến trình kiểm tra: ' . $throwable->getMessage();
            Log::error("SessionHealthcheckService: Lỗi kênh #{$channel->id}: {$errorMsg}");

            $channel->update([
                'status' => 'needs_relogin',
                'health_notes' => $errorMsg,
                'last_healthchecked_at' => $now,
            ]);

            return [
                'is_alive' => false,
                'status' => 'needs_relogin',
                'message' => $errorMsg,
                'checked_at' => $now,
            ];
        }
    }
}

