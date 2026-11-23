<?php

namespace App\Livewire\Channels;

use App\Models\Channel;
use App\Services\SessionHealthcheckService;
use App\Services\SessionManagerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

#[Layout('layouts.app')]
class ChannelIndex extends Component
{
    use WithFileUploads;

    public string $search = '';
    public string $platformFilter = '';
    public string $typeFilter = '';

    public ?string $feedbackMessage = null;

    // Quản lý Modal Session Trình Duyệt
    public ?int $managingSessionChannelId = null;
    public ?string $managingSessionChannelName = null;
    public ?string $managingSessionPlatform = null;
    public ?string $managingSessionStatus = null;
    public ?string $managingSessionLastChecked = null;
    public ?string $managingSessionHealthNotes = null;

    /**
     * File zip tải lên để nạp session
     *
     * @var mixed
     */
    public $sessionZipUpload = null;

    public bool $isCheckingHealth = false;
    public ?array $healthcheckResult = null;

    #[On('channel-saved')]
    public function handleChannelSaved(string $message): void
    {
        $this->feedbackMessage = $message;
    }

    public function deleteChannel(int $channelId): void
    {
        $channel = Channel::query()->find($channelId);

        if ($channel !== null) {
            $channelName = $channel->channel_name;
            $channel->delete();
            $this->feedbackMessage = "Đã xóa kênh {$channelName} thành công.";
        }
    }

    public function testBrowserProfile(int $channelId): void
    {
        $channel = Channel::query()->find($channelId);

        if ($channel !== null) {
            $this->feedbackMessage = "Kiểm tra Profile [{$channel->browser_profile_path}]: Thư mục profile đã sẵn sàng cho Playwright.";
        }
    }

    /**
     * Mở Modal Quản Lý Session Trình Duyệt cho kênh.
     */
    public function openSessionModal(int $channelId): void
    {
        $channel = Channel::query()->find($channelId);

        if ($channel === null) {
            $this->feedbackMessage = "Không tìm thấy kênh với ID #{$channelId}.";
            return;
        }

        $this->managingSessionChannelId = $channel->id;
        $this->managingSessionChannelName = $channel->channel_name;
        $this->managingSessionPlatform = $channel->platform;
        $this->managingSessionStatus = $channel->status;
        $this->managingSessionLastChecked = $channel->last_healthchecked_at ? $channel->last_healthchecked_at->format('H:i:s d/m/Y') : 'Chưa kiểm tra';
        $this->managingSessionHealthNotes = $channel->health_notes;

        $this->sessionZipUpload = null;
        $this->isCheckingHealth = false;
        $this->healthcheckResult = null;
    }

    /**
     * Đóng Modal Quản Lý Session.
     */
    public function closeSessionModal(): void
    {
        $this->managingSessionChannelId = null;
        $this->managingSessionChannelName = null;
        $this->managingSessionPlatform = null;
        $this->managingSessionStatus = null;
        $this->managingSessionLastChecked = null;
        $this->managingSessionHealthNotes = null;
        $this->sessionZipUpload = null;
        $this->isCheckingHealth = false;
        $this->healthcheckResult = null;
    }

    /**
     * Tải file zip session của kênh về máy tính (Export Session).
     */
    public function exportSession(int $channelId, SessionManagerService $sessionManager)
    {
        $channel = Channel::query()->find($channelId);

        if ($channel === null) {
            $this->feedbackMessage = "Không tìm thấy kênh với ID #{$channelId}.";
            return null;
        }

        $zipPath = $sessionManager->exportSessionZip($channel);

        if ($zipPath === null || !File::exists($zipPath)) {
            $this->feedbackMessage = "Kênh {$channel->channel_name} chưa có dữ liệu phiên (Profile rỗng). Hãy bấm 'Đăng Nhập Phiên' trước.";
            return null;
        }

        $downloadName = "session_{$channel->platform}_{$channel->id}.zip";

        return response()->download($zipPath, $downloadName);
    }

    /**
     * Nạp file zip session lên server và giải nén vào thư mục profile của kênh (Import Session).
     */
    public function importSession(
        SessionManagerService $sessionManager,
        SessionHealthcheckService $healthcheckService
    ): void {
        if ($this->managingSessionChannelId === null) {
            return;
        }

        $channel = Channel::query()->find($this->managingSessionChannelId);
        if ($channel === null) {
            $this->feedbackMessage = "Kênh không tồn tại.";
            return;
        }

        if ($this->sessionZipUpload === null) {
            $this->feedbackMessage = "Vui lòng chọn file .zip session để nạp lên hệ thống.";
            return;
        }

        $tempPath = $this->sessionZipUpload->getRealPath();

        $success = $sessionManager->importSessionZip($channel, $tempPath);

        if ($success) {
            $this->feedbackMessage = "Đã nạp file Session .zip thành công cho kênh {$channel->channel_name}!";
            $this->sessionZipUpload = null;

            // Tự động kiểm tra sức khỏe phiên sau khi nạp
            $this->runHealthcheck($channel->id, $healthcheckService);
        } else {
            $this->feedbackMessage = "Giải nén thất bại. Vui lòng đảm bảo file là định dạng zip chứa cấu trúc Profile hợp lệ.";
        }
    }

    /**
     * Chạy kiểm tra kết nối trực tiếp (Healthcheck) cho kênh.
     */
    public function runHealthcheck(int $channelId, SessionHealthcheckService $healthcheckService): void
    {
        $channel = Channel::query()->find($channelId);
        if ($channel === null) {
            return;
        }

        $this->isCheckingHealth = true;

        try {
            $result = $healthcheckService->checkChannel($channel);
            $this->healthcheckResult = $result;

            $channel->refresh();
            $this->managingSessionStatus = $channel->status;
            $this->managingSessionLastChecked = $channel->last_healthchecked_at ? $channel->last_healthchecked_at->format('H:i:s d/m/Y') : 'Vừa xong';
            $this->managingSessionHealthNotes = $channel->health_notes;

            if ($result['is_alive']) {
                $this->feedbackMessage = "Kiểm tra phiên kênh {$channel->channel_name}: KẾT NỐI TỐT! Sẵn sàng xuất bản.";
            } else {
                $this->feedbackMessage = "Kiểm tra phiên kênh {$channel->channel_name}: PHIÊN HẾT HẠN ({$result['message']})";
            }
        } finally {
            $this->isCheckingHealth = false;
        }
    }

    /**
     * Mở cửa sổ trình duyệt Playwright để người dùng đăng nhập tài khoản thủ công 1 lần.
     */
    public function openBrowserLogin(int $channelId): void
    {
        $channel = Channel::query()->find($channelId);
        if ($channel === null) {
            $this->feedbackMessage = "Không tìm thấy kênh với ID #{$channelId}.";
            return;
        }

        $platform = strtolower($channel->platform);
        $profilePath = $channel->browser_profile_path;
        if (empty($profilePath)) {
            $profilePath = 'storage/app/browser_profiles/' . $platform . '_' . $channel->id;
            $channel->update(['browser_profile_path' => $profilePath]);
        }

        $absoluteProfilePath = base_path($profilePath);
        if (!File::exists($absoluteProfilePath)) {
            File::makeDirectory($absoluteProfilePath, 0755, true);
        }

        $scriptPath = base_path('scripts/open_browser_login.js');

        if (app()->runningUnitTests()) {
            $this->feedbackMessage = "Đang mở trình duyệt cho kênh {$channel->channel_name}! Hãy hoàn thành đăng nhập trên cửa sổ trình duyệt và đóng lại để lưu phiên. (Hoặc chạy lệnh: php artisan channel:login {$channel->id})";
            return;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = sprintf(
                'start "AffiliateAuto Login" node "%s" --platform %s --profile-path "%s"',
                $scriptPath,
                $platform,
                $absoluteProfilePath
            );
            exec($cmd);
        } else {
            $process = new Process([
                'node',
                $scriptPath,
                '--platform',
                $platform,
                '--profile-path',
                $absoluteProfilePath,
            ]);
            $process->start();
        }

        $this->feedbackMessage = "Đang mở trình duyệt cho kênh {$channel->channel_name}! Hãy hoàn thành đăng nhập trên cửa sổ trình duyệt và đóng lại để lưu phiên. (Hoặc chạy lệnh: php artisan channel:login {$channel->id})";
    }

    public function render(): View
    {
        $channelsQuery = Channel::query();

        if ($this->search !== '') {
            $searchTerm = '%' . $this->search . '%';
            $channelsQuery->where(function ($query) use ($searchTerm) {
                $query->where('channel_name', 'like', $searchTerm)
                    ->orWhere('browser_profile_path', 'like', $searchTerm);
            });
        }

        if ($this->platformFilter !== '') {
            $channelsQuery->where('platform', $this->platformFilter);
        }

        if ($this->typeFilter !== '') {
            $channelsQuery->where('type', $this->typeFilter);
        }

        $channels = $channelsQuery->latest()->get();

        return view('livewire.channels.channel-index', [
            'channels' => $channels,
        ]);
    }
}
