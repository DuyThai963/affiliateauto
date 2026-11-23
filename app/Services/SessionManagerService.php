<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class SessionManagerService
{
    /**
     * Xác định đường dẫn tuyệt đối của thư mục Browser Profile của kênh.
     */
    public function resolveProfilePath(Channel $channel): string
    {
        $rawPath = $channel->browser_profile_path;

        if (empty($rawPath)) {
            $platform = strtolower($channel->platform);
            $rawPath = 'storage/app/browser_profiles/' . $platform . '_' . $channel->id;
            $channel->update(['browser_profile_path' => $rawPath]);
        }

        if (str_starts_with($rawPath, 'storage/')) {
            return base_path($rawPath);
        }

        if (File::isAbsolutePath($rawPath)) {
            return $rawPath;
        }

        return storage_path('app/' . ltrim($rawPath, '/\\'));
    }

    /**
     * Nén toàn bộ thư mục profile Playwright thành file zip để người dùng tải về backup / chuyển máy.
     */
    public function exportSessionZip(Channel $channel): ?string
    {
        $profileDir = $this->resolveProfilePath($channel);

        if (!File::exists($profileDir) || !File::isDirectory($profileDir)) {
            Log::warning("SessionManagerService: Thư mục profile của kênh #{$channel->id} không tồn tại ({$profileDir})");
            return null;
        }

        $files = File::allFiles($profileDir);
        if (count($files) === 0) {
            Log::warning("SessionManagerService: Thư mục profile của kênh #{$channel->id} đang rỗng");
            return null;
        }

        $tempDir = storage_path('app/temp_assets');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $platform = strtolower($channel->platform);
        $zipFileName = "session_{$platform}_{$channel->id}_" . time() . ".zip";
        $zipFilePath = $tempDir . DIRECTORY_SEPARATOR . $zipFileName;

        $zip = new ZipArchive();
        $openResult = $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($openResult !== true) {
            Log::error("SessionManagerService: Không thể khởi tạo file zip tại {$zipFilePath}, mã lỗi: {$openResult}");
            return null;
        }

        $realProfileDir = rtrim(realpath($profileDir) ?: $profileDir, DIRECTORY_SEPARATOR);
        $directoryIterator = new RecursiveDirectoryIterator($realProfileDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            $filePath = $item->getRealPath();
            $relativePath = str_replace('\\', '/', substr($filePath, strlen($realProfileDir) + 1));

            if ($item->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($item->isFile()) {
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        Log::info("SessionManagerService: Xuất thành công file session zip cho kênh #{$channel->id} ({$zipFilePath})");

        return $zipFilePath;
    }

    /**
     * Giải nén file zip session trình duyệt vào thư mục profile của kênh với cơ chế chống Zip Slip.
     */
    public function importSessionZip(Channel $channel, string $uploadedZipPath): bool
    {
        if (!File::exists($uploadedZipPath)) {
            Log::error("SessionManagerService: Tệp zip tải lên không tồn tại: {$uploadedZipPath}");
            return false;
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($uploadedZipPath);

        if ($openResult !== true) {
            Log::error("SessionManagerService: Tệp tải lên không phải file zip hợp lệ, mã lỗi: {$openResult}");
            return false;
        }

        $targetDir = $this->resolveProfilePath($channel);

        // Chuẩn bị thư mục đích
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $realTargetBase = realpath($targetDir);
        if ($realTargetBase === false) {
            $realTargetBase = $targetDir;
        }

        // Kiểm tra an toàn chống lỗ hổng Zip Slip (Directory Traversal)
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

            // Chặn các ký tự duyệt thư mục nguy hiểm và đường dẫn tuyệt đối
            if (
                str_contains($entryName, '../')
                || str_contains($entryName, '..\\')
                || str_starts_with($entryName, '/')
                || str_starts_with($entryName, '\\')
                || str_contains($entryName, ':')
            ) {
                Log::error("SessionManagerService: Phát hiện lỗ hổng Zip Slip trong file zip: {$entryName}");
                $zip->close();
                return false;
            }
        }

        // Tiến hành giải nén từng tệp
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            $destinationPath = $targetDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entryName);

            if (str_ends_with($entryName, '/') || str_ends_with($entryName, '\\')) {
                if (!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }
            } else {
                $entryDir = dirname($destinationPath);
                if (!File::exists($entryDir)) {
                    File::makeDirectory($entryDir, 0755, true);
                }

                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    File::put($destinationPath, $content);
                }
            }
        }

        $zip->close();

        // Xóa file zip tạm nếu nằm trong temp_assets hoặc thư mục tạm của hệ thống
        if (File::exists($uploadedZipPath) && (str_contains($uploadedZipPath, 'temp_assets') || str_contains($uploadedZipPath, 'livewire-tmp'))) {
            File::delete($uploadedZipPath);
        }

        // Cập nhật trạng thái kênh sang hoạt động
        $channel->update([
            'status' => 'active',
            'health_notes' => 'Đã nạp session trình duyệt thành công từ file ZIP.',
            'last_healthchecked_at' => now(),
        ]);

        Log::info("SessionManagerService: Nhập session ZIP thành công cho kênh #{$channel->id} vào {$targetDir}");

        return true;
    }
}
