<?php

namespace Tests\Feature;

use App\Livewire\Channels\ChannelIndex;
use App\Models\Channel;
use App\Services\SessionHealthcheckService;
use App\Services\SessionManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class Sprint6DoDTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $profilesDir = storage_path('app/browser_profiles');
        if (!File::exists($profilesDir)) {
            File::makeDirectory($profilesDir, 0755, true);
        }

        $tempDir = storage_path('app/temp_assets');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Reset mock result sau mỗi test
        SessionHealthcheckService::setMockResult(null);
        parent::tearDown();
    }

    /**
     * DoD 1: Export Session nén toàn bộ thư mục profile Playwright thành file .zip hợp lệ.
     */
    public function test_export_session_creates_valid_zip_archive(): void
    {
        $sessionManager = app(SessionManagerService::class);

        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@export_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_export_profile',
            'status' => 'active',
        ]);

        // Tạo profile giả lập chứa cookie và local storage
        $profileDir = base_path($channel->browser_profile_path);
        if (File::exists($profileDir)) {
            File::deleteDirectory($profileDir);
        }
        File::makeDirectory($profileDir, 0755, true);

        File::put($profileDir . '/Preferences', '{"profile":{"name":"Default"}}');
        if (!File::exists($profileDir . '/Default')) {
            File::makeDirectory($profileDir . '/Default', 0755, true);
        }
        File::put($profileDir . '/Default/Cookies', 'DUMMY_TIKTOK_SQLITE_COOKIES_DATA');

        // Kích hoạt Export
        $zipPath = $sessionManager->exportSessionZip($channel);

        $this->assertNotNull($zipPath);
        $this->assertFileExists($zipPath);
        $this->assertStringEndsWith('.zip', $zipPath);

        // Kiểm tra nội dung bên trong file zip
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath));
        $this->assertNotFalse($zip->locateName('Preferences'));
        $this->assertNotFalse($zip->locateName('Default/Cookies'));
        $zip->close();

        // Dọn dẹp
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }
        if (File::exists($profileDir)) {
            File::deleteDirectory($profileDir);
        }
    }

    /**
     * DoD 2: Import Session giải nén chính xác, cập nhật kênh active và chặn lỗ hổng Zip Slip.
     */
    public function test_import_session_unpacks_correctly_and_sanitizes_path(): void
    {
        $sessionManager = app(SessionManagerService::class);

        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@import_test',
            'type' => 'growth',
            'browser_profile_path' => 'storage/app/browser_profiles/test_import_dest',
            'status' => 'needs_relogin',
        ]);

        // 1. Tạo file zip hợp lệ
        $validZipPath = storage_path('app/temp_assets/test_valid_import.zip');
        $zip = new ZipArchive();
        $zip->open($validZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('Default/Cookies', 'IMPORTED_SESSION_COOKIE_VALUE');
        $zip->addFromString('session_meta.json', '{"platform":"tiktok"}');
        $zip->close();

        $success = $sessionManager->importSessionZip($channel, $validZipPath);
        $this->assertTrue($success);

        $channel->refresh();
        $this->assertEquals('active', $channel->status);
        $this->assertNotNull($channel->last_healthchecked_at);

        $destDir = base_path($channel->browser_profile_path);
        $this->assertFileExists($destDir . '/Default/Cookies');
        $this->assertEquals('IMPORTED_SESSION_COOKIE_VALUE', File::get($destDir . '/Default/Cookies'));

        // 2. Kiểm tra cơ chế chống Zip Slip (Directory Traversal)
        $maliciousZipPath = storage_path('app/temp_assets/test_malicious_slip.zip');
        $maliciousZip = new ZipArchive();
        $maliciousZip->open($maliciousZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $maliciousZip->addFromString('../../../etc/evil.txt', 'MALICIOUS_CONTENT');
        $maliciousZip->close();

        $slipResult = $sessionManager->importSessionZip($channel, $maliciousZipPath);
        $this->assertFalse($slipResult, 'Hệ thống phải chặn file zip có chứa ký tự Zip Slip ../');

        // Dọn dẹp
        if (File::exists($maliciousZipPath)) {
            File::delete($maliciousZipPath);
        }
        if (File::exists($destDir)) {
            File::deleteDirectory($destDir);
        }
    }

    /**
     * DoD 3: SessionHealthcheckService cập nhật chính xác trạng thái kênh và thời gian ping.
     */
    public function test_session_healthcheck_updates_channel_status(): void
    {
        $healthcheckService = app(SessionHealthcheckService::class);

        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@healthcheck_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_healthcheck',
            'status' => 'needs_relogin',
        ]);

        // Trường hợp 1: Session còn hạn (is_alive = true)
        SessionHealthcheckService::setMockResult([
            'is_alive' => true,
            'message' => 'Phiên TikTok hoạt động bình thường, sẵn sàng xuất bản.',
        ]);

        $resultActive = $healthcheckService->checkChannel($channel);

        $this->assertTrue($resultActive['is_alive']);
        $this->assertEquals('active', $resultActive['status']);

        $channel->refresh();
        $this->assertEquals('active', $channel->status);
        $this->assertNotNull($channel->last_healthchecked_at);
        $this->assertStringContainsString('hoạt động bình thường', $channel->health_notes);

        // Trường hợp 2: Session hết hạn (is_alive = false)
        SessionHealthcheckService::setMockResult([
            'is_alive' => false,
            'message' => 'Phiên TikTok đã hết hạn, vui lòng nạp lại session.',
        ]);

        $resultExpired = $healthcheckService->checkChannel($channel);

        $this->assertFalse($resultExpired['is_alive']);
        $this->assertEquals('needs_relogin', $resultExpired['status']);

        $channel->refresh();
        $this->assertEquals('needs_relogin', $channel->status);
        $this->assertStringContainsString('hết hạn', $channel->health_notes);
    }

    /**
     * DoD 4: Artisan Command channel:healthcheck thực thi quét kênh thành công.
     */
    public function test_check_all_channels_health_command(): void
    {
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@command_health_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_command_health',
            'status' => 'needs_relogin',
        ]);

        SessionHealthcheckService::setMockResult([
            'is_alive' => true,
            'message' => 'Phiên hoạt động tốt',
        ]);

        $exitCode = Artisan::call('channel:healthcheck', [
            'channel_id' => $channel->id,
        ]);

        $this->assertEquals(0, $exitCode);

        $channel->refresh();
        $this->assertEquals('active', $channel->status);
    }

    /**
     * DoD 5: Livewire ChannelIndex Modal Quản Lý Session hoạt động trơn tru.
     */
    public function test_livewire_channel_session_modal_actions(): void
    {
        $channel = Channel::query()->create([
            'platform' => 'tiktok',
            'channel_name' => '@livewire_session_test',
            'type' => 'affiliate',
            'browser_profile_path' => 'storage/app/browser_profiles/test_livewire_session',
            'status' => 'active',
        ]);

        // Tạo profile giả lập để export
        $profileDir = base_path($channel->browser_profile_path);
        if (File::exists($profileDir)) {
            File::deleteDirectory($profileDir);
        }
        File::makeDirectory($profileDir, 0755, true);
        File::put($profileDir . '/session.json', '{"test":true}');

        // Mở Modal
        $component = Livewire::test(ChannelIndex::class)
            ->call('openSessionModal', $channel->id);

        $component->assertSet('managingSessionChannelId', $channel->id);
        $component->assertSet('managingSessionChannelName', $channel->channel_name);

        // Kích hoạt Ping Healthcheck
        SessionHealthcheckService::setMockResult([
            'is_alive' => true,
            'message' => 'Phiên livewire test OK',
        ]);

        $component->call('runHealthcheck', $channel->id);
        $component->assertSet('managingSessionStatus', 'active');
        $this->assertNotNull($component->get('healthcheckResult'));
        $this->assertTrue($component->get('healthcheckResult')['is_alive']);

        // Kích hoạt Export
        $response = $component->call('exportSession', $channel->id);
        $this->assertNotNull($response);

        // Đóng Modal
        $component->call('closeSessionModal');
        $component->assertSet('managingSessionChannelId', null);

        // Dọn dẹp
        if (File::exists($profileDir)) {
            File::deleteDirectory($profileDir);
        }
    }
}
