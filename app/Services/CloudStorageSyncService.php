<?php

namespace App\Services;

use App\Models\VideoTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudStorageSyncService
{
    /**
     * Lấy đường dẫn gốc của Kho lưu trữ Cloud 5TB (chế độ local/mount).
     */
    public function getCloudVaultPath(): string
    {
        $customPath = env('CLOUD_STORAGE_VAULT_PATH');

        if (!empty($customPath)) {
            return rtrim(str_replace('/', DIRECTORY_SEPARATOR, $customPath), DIRECTORY_SEPARATOR);
        }

        return storage_path('app' . DIRECTORY_SEPARATOR . 'cloud_vault_5tb');
    }

    /**
     * Di chuyển video đã xuất bản thành công sang Kho Cloud 5TB và giải phóng SSD cục bộ.
     */
    public function archivePublishedVideo(VideoTask $videoTask): bool
    {
        $sourceVideoPath = $videoTask->video_path;

        if (empty($sourceVideoPath) || !File::exists($sourceVideoPath)) {
            Log::warning("CloudStorageSyncService: Không tìm thấy file video nguồn để archive cho Task #{$videoTask->id}");
            return false;
        }

        $channel = $videoTask->channel;
        $platform = $channel?->platform ? strtolower($channel->platform) : 'general';
        $rawChannelName = $channel?->channel_name ?: 'channel_' . $videoTask->channel_id;
        $cleanChannelName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $rawChannelName);

        $publishDate = $videoTask->published_at ? Carbon::parse($videoTask->published_at) : Carbon::now();
        $yearMonth = $publishDate->format('Y-m');

        $metadata = [
            'task_id' => $videoTask->id,
            'title' => $videoTask->title,
            'caption' => $videoTask->caption,
            'hashtags' => $videoTask->hashtags,
            'channel_id' => $videoTask->channel_id,
            'channel_name' => $channel?->channel_name,
            'platform' => $channel?->platform,
            'task_type' => $videoTask->task_type,
            'product_name' => $videoTask->product?->product_name,
            'published_at' => $publishDate->toIso8601String(),
            'archived_at' => Carbon::now()->toIso8601String(),
            'script_data' => $videoTask->script_data,
        ];

        $driver = (string) env('CLOUD_STORAGE_DRIVER', 'local');
        if ($driver === 'gdrive_api') {
            return $this->archiveToGoogleDriveApi(
                $videoTask,
                $sourceVideoPath,
                [$platform, $cleanChannelName, $yearMonth],
                $metadata
            );
        }

        return $this->archiveToLocalMount(
            $videoTask,
            $sourceVideoPath,
            $platform,
            $cleanChannelName,
            $yearMonth,
            $metadata
        );
    }

    /**
     * Lưu trữ sang thư mục cục bộ hoặc ổ đĩa mạng mount (CLOUD_STORAGE_VAULT_PATH).
     *
     * @param array<string, mixed> $metadata
     */
    protected function archiveToLocalMount(
        VideoTask $videoTask,
        string $sourceVideoPath,
        string $platform,
        string $cleanChannelName,
        string $yearMonth,
        array $metadata
    ): bool {
        $cloudVaultBase = $this->getCloudVaultPath();
        $targetDirectory = $cloudVaultBase . DIRECTORY_SEPARATOR . $platform . DIRECTORY_SEPARATOR . $cleanChannelName . DIRECTORY_SEPARATOR . $yearMonth;

        if (!File::exists($targetDirectory)) {
            File::makeDirectory($targetDirectory, 0755, true);
        }

        $targetVideoName = "video_{$videoTask->id}.mp4";
        $targetVideoPath = $targetDirectory . DIRECTORY_SEPARATOR . $targetVideoName;

        // 1. Sao chép file video sang Kho Cloud
        File::copy($sourceVideoPath, $targetVideoPath);

        if (!File::exists($targetVideoPath) || File::size($targetVideoPath) <= 0) {
            Log::error("CloudStorageSyncService: Xác minh file đích thất bại (không tồn tại hoặc 0 byte) tại {$targetVideoPath}. Hủy xóa file gốc trên SSD.");
            return false;
        }

        // 2. Xuất bản tệp Metadata .json lưu trữ kèm theo
        $targetMetaPath = $targetDirectory . DIRECTORY_SEPARATOR . "video_{$videoTask->id}_metadata.json";
        File::put($targetMetaPath, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 3. Xóa bỏ file video gốc trên SSD cục bộ (Buffer Vault) để giải phóng bộ nhớ
        if (File::exists($sourceVideoPath) && realpath($sourceVideoPath) !== realpath($targetVideoPath)) {
            File::delete($sourceVideoPath);
        }

        // 4. Cập nhật lại đường dẫn mới vào database
        $videoTask->update([
            'video_path' => $targetVideoPath,
            'current_step' => 'Đã lưu trữ an toàn vào Kho Cloud 5TB',
        ]);

        Log::info("CloudStorageSyncService: Archive thành công Task #{$videoTask->id} sang {$targetVideoPath} (Metadata: {$targetMetaPath})");

        return true;
    }

    /**
     * Đẩy trực tiếp video và metadata JSON lên Google Drive 5TB qua Google Drive REST API v3.
     *
     * @param array<int, string> $folderSegments
     * @param array<string, mixed> $metadata
     */
    protected function archiveToGoogleDriveApi(
        VideoTask $videoTask,
        string $sourceVideoPath,
        array $folderSegments,
        array $metadata
    ): bool {
        $accessToken = $this->fetchGoogleDriveAccessToken();
        if (empty($accessToken)) {
            Log::error('CloudStorageSyncService: Không lấy được Google Drive Access Token. Giữ nguyên file local.');
            return false;
        }

        $parentFolderId = (string) env('GOOGLE_DRIVE_FOLDER_ID', 'root');
        foreach ($folderSegments as $segment) {
            $parentFolderId = $this->ensureGoogleDriveFolder($accessToken, $segment, $parentFolderId);
            if (empty($parentFolderId)) {
                Log::error("CloudStorageSyncService: Không thể tạo/tìm thư mục '{$segment}' trên Google Drive.");
                return false;
            }
        }

        $videoFileName = "video_{$videoTask->id}.mp4";
        $videoContent = File::get($sourceVideoPath);
        $uploadedVideo = $this->uploadMultipartToDrive(
            $accessToken,
            $videoFileName,
            $parentFolderId,
            'video/mp4',
            $videoContent
        );

        if (empty($uploadedVideo['id']) || ((int) ($uploadedVideo['size'] ?? 0)) <= 0) {
            Log::error("CloudStorageSyncService: Upload video lên Google Drive thất bại cho Task #{$videoTask->id}. Giữ nguyên file local.");
            return false;
        }

        $metaFileName = "video_{$videoTask->id}_metadata.json";
        $metaContent = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->uploadMultipartToDrive(
            $accessToken,
            $metaFileName,
            $parentFolderId,
            'application/json',
            $metaContent ?: '{}'
        );

        $driveFileId = $uploadedVideo['id'];
        $driveUrl = "https://drive.google.com/file/d/{$driveFileId}/view";

        if (File::exists($sourceVideoPath)) {
            File::delete($sourceVideoPath);
        }

        $videoTask->update([
            'video_path' => $driveUrl,
            'current_step' => 'Đã lưu trữ an toàn lên Google Drive 5TB (API)',
        ]);

        Log::info("CloudStorageSyncService: Đã upload Task #{$videoTask->id} lên Google Drive ID: {$driveFileId}");

        return true;
    }

    /**
     * Đổi Refresh Token lấy Google OAuth2 Access Token.
     */
    protected function fetchGoogleDriveAccessToken(): ?string
    {
        $clientId = env('GOOGLE_DRIVE_CLIENT_ID');
        $clientSecret = env('GOOGLE_DRIVE_CLIENT_SECRET');
        $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            Log::warning('CloudStorageSyncService: Thiếu cấu hình GOOGLE_DRIVE_CLIENT_ID/SECRET/REFRESH_TOKEN.');
            return null;
        }

        $response = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            Log::error('CloudStorageSyncService: Lỗi làm mới Google OAuth2 token: ' . $response->body());
            return null;
        }

        return $response->json('access_token');
    }

    /**
     * Tìm hoặc tạo thư mục trên Google Drive.
     */
    protected function ensureGoogleDriveFolder(string $accessToken, string $folderName, string $parentId): ?string
    {
        $escapedName = str_replace("'", "\\'", $folderName);
        $query = "name = '{$escapedName}' and '{$parentId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false";

        $listResponse = Http::withToken($accessToken)
            ->timeout(20)
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q' => $query,
                'fields' => 'files(id, name)',
                'supportsAllDrives' => 'true',
                'includeItemsFromAllDrives' => 'true',
            ]);

        if ($listResponse->successful()) {
            $existingId = $listResponse->json('files.0.id');
            if (!empty($existingId)) {
                return $existingId;
            }
        }

        $createResponse = Http::withToken($accessToken)
            ->timeout(20)
            ->post('https://www.googleapis.com/drive/v3/files?supportsAllDrives=true', [
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentId],
            ]);

        if ($createResponse->failed()) {
            Log::error('CloudStorageSyncService: Tạo thư mục Drive thất bại: ' . $createResponse->body());
            return null;
        }

        return $createResponse->json('id');
    }

    /**
     * Upload file lên Google Drive bằng multipart upload.
     *
     * @return array<string, mixed>|null
     */
    protected function uploadMultipartToDrive(
        string $accessToken,
        string $fileName,
        string $parentId,
        string $mimeType,
        string $binaryContent
    ): ?array {
        $boundary = 'affiliate_auto_' . bin2hex(random_bytes(8));
        $metadataJson = json_encode([
            'name' => $fileName,
            'parents' => [$parentId],
        ]);

        $body = "--{$boundary}\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . "{$metadataJson}\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: {$mimeType}\r\n\r\n"
            . $binaryContent . "\r\n"
            . "--{$boundary}--";

        $response = Http::withToken($accessToken)
            ->timeout(180)
            ->withBody($body, "multipart/related; boundary={$boundary}")
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,size&supportsAllDrives=true');

        if ($response->failed()) {
            Log::error("CloudStorageSyncService: Upload '{$fileName}' thất bại: " . $response->body());
            return null;
        }

        return $response->json();
    }

    /**
     * Dọn dẹp định kỳ các file tạm, log captcha cũ hơn $daysOld ngày trong temp_assets.
     */
    public function pruneOldTempAssets(int $daysOld = 3): void
    {
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp_assets');

        if (!File::exists($tempDir)) {
            return;
        }

        $thresholdTimestamp = Carbon::now()->subDays($daysOld)->getTimestamp();
        $files = File::files($tempDir);

        foreach ($files as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }

            if ($file->getMTime() < $thresholdTimestamp) {
                File::delete($file->getPathname());
            }
        }
    }
}
