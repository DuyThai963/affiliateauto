<div class="space-y-6 max-w-7xl mx-auto">
    <!-- [Design Read]: Apple HIG Dark Mode channels dashboard with Remote Session Manager Modal, Export/Import Zip, and Headless Healthcheck. -->
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-[#F5F5F7]">Kênh Phân Phối</h2>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Cấu hình tài khoản TikTok & Shopee, quản lý session từ xa, kiểm tra trạng thái phiên và điều phối lịch đăng.
            </p>
        </div>
        <button 
            type="button" 
            wire:click="$dispatch('open-channel-modal')" 
            class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-[#FFFFFF] hover:bg-[#EBEBED] text-[#000000] transition-all shadow-apple-btn active:scale-[0.98]">
            <svg class="w-3.5 h-3.5 mr-1.5 text-[#000000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm Kênh Mới
        </button>
    </div>

    <!-- Alert / Toast -->
    @if ($feedbackMessage)
        <div class="p-3.5 rounded-2xl bg-[#242426] border border-[#38383A] text-[#F5F5F7] text-xs flex items-center justify-between shadow-apple-card">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-[#FFFFFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="font-medium">{{ $feedbackMessage }}</span>
            </div>
            <button type="button" wire:click="$set('feedbackMessage', null)" class="text-[#8E8E93] hover:text-[#FFFFFF] text-sm">✕</button>
        </div>
    @endif

    <!-- Search & Filter Bar (macOS Dark Toolbar Style) -->
    <div class="p-3 rounded-2xl bg-[#18181B] border border-[#26262A] shadow-apple-card flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="w-full md:w-80 relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Tìm theo tên kênh hoặc đường dẫn profile..." 
                class="w-full pl-9 pr-4 py-1.5 bg-[#121214] border border-[#26262A] rounded-xl text-xs text-[#F5F5F7] placeholder-[#636366] focus:outline-none focus:border-[#FFFFFF] transition-all">
            <div class="absolute left-3 top-2 text-[#8E8E93]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div class="flex items-center space-x-2 w-full md:w-auto">
            <select wire:model.live="platformFilter" class="px-3 py-1.5 bg-[#121214] border border-[#26262A] rounded-xl text-xs text-[#F5F5F7] focus:outline-none focus:border-[#FFFFFF] shadow-apple-btn">
                <option value="">Tất cả Nền tảng</option>
                <option value="tiktok">TikTok</option>
                <option value="shopee">Shopee</option>
            </select>

            <select wire:model.live="typeFilter" class="px-3 py-1.5 bg-[#121214] border border-[#26262A] rounded-xl text-xs text-[#F5F5F7] focus:outline-none focus:border-[#FFFFFF] shadow-apple-btn">
                <option value="">Tất cả Loại kênh</option>
                <option value="growth">Growth (Kéo view)</option>
                <option value="affiliate">Affiliate (Gắn giỏ)</option>
            </select>
        </div>
    </div>

    <!-- Data Table -->
    <div class="rounded-2xl border border-[#26262A] bg-[#18181B] overflow-hidden shadow-apple-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-[#F5F5F7]">
                <thead class="bg-[#121214] text-[11px] font-semibold text-[#8E8E93] uppercase tracking-wider border-b border-[#26262A]">
                    <tr>
                        <th class="px-6 py-3">Nền tảng</th>
                        <th class="px-6 py-3">Tên Kênh</th>
                        <th class="px-6 py-3">Loại Kênh</th>
                        <th class="px-6 py-3">Giới hạn / Giãn cách</th>
                        <th class="px-6 py-3">Lần đăng cuối</th>
                        <th class="px-6 py-3">Trạng thái Phiên</th>
                        <th class="px-6 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#202024]">
                    @forelse ($channels as $channel)
                        <tr class="hover:bg-[#1C1C1E] transition-colors">
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if ($channel->platform === 'tiktok')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#242426] border border-[#38383A] text-white">
                                        TikTok
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#242426] text-[#FF9F0A] border border-[#FF9F0A]/30">
                                        Shopee
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="font-semibold text-[#F5F5F7]">{{ $channel->channel_name }}</div>
                                <div class="text-[11px] text-[#8E8E93] font-mono truncate max-w-xs" title="{{ $channel->browser_profile_path }}">
                                    {{ $channel->browser_profile_path }}
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if ($channel->type === 'growth')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#242426] text-[#F5F5F7] border border-[#38383A]">
                                        Growth (Kéo View)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#242426] text-[#F5F5F7] border border-[#38383A]">
                                        Affiliate (Gắn Giỏ)
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-[11px] text-[#A1A1A6]">
                                <div><strong class="text-[#F5F5F7]">{{ $channel->daily_post_limit }}</strong> video/ngày</div>
                                <div class="text-[#8E8E93]">Giãn cách: {{ $channel->min_interval_minutes }}p</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-[#8E8E93]">
                                {{ $channel->last_posted_at ? $channel->last_posted_at->diffForHumans() : 'Chưa đăng' }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if ($channel->status === 'active')
                                    <div>
                                        <span class="inline-flex items-center text-[11px] font-medium text-[#30D158]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#30D158] mr-1.5 shadow-[0_0_6px_rgba(48,209,88,0.5)]"></span> Đã kết nối
                                        </span>
                                        @if ($channel->last_healthchecked_at)
                                            <div class="text-[10px] text-[#8E8E93] mt-0.5 font-mono">
                                                Ping: {{ $channel->last_healthchecked_at->diffForHumans() }}
                                            </div>
                                        @endif
                                    </div>
                                @elseif ($channel->status === 'paused')
                                    <span class="inline-flex items-center text-[11px] font-medium text-[#FF9F0A]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#FF9F0A] mr-1.5"></span> Tạm dừng
                                    </span>
                                @else
                                    <div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#242426] text-[#FF453A] border border-[#FF453A]/40 animate-pulse">
                                            Cần Đăng Nhập Lại
                                        </span>
                                        @if ($channel->health_notes)
                                            <div class="text-[10px] text-[#8E8E93] mt-0.5 truncate max-w-[140px]" title="{{ $channel->health_notes }}">
                                                {{ $channel->health_notes }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-right space-x-1.5">
                                <!-- Nút Quản Lý Session (Sprint 6 Modal) -->
                                <button 
                                    type="button" 
                                    wire:click="openSessionModal({{ $channel->id }})" 
                                    title="Quản lý Session: Tải file Zip, nạp Session hoặc kiểm tra sức khỏe phiên"
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#242426] hover:bg-[#2E2E32] text-[#F5F5F7] border border-[#38383A] font-medium transition-colors shadow-apple-btn active:scale-[0.98]">
                                    <svg class="w-3 h-3 mr-1 text-[#8E8E93]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    Session
                                </button>

                                <!-- Nút Mở Trình Duyệt Đăng Nhập Trực Tiếp (Local GUI) -->
                                <button 
                                    type="button" 
                                    wire:click="openBrowserLogin({{ $channel->id }})" 
                                    title="Mở trình duyệt Playwright để đăng nhập tài khoản trực tiếp trên máy"
                                    class="inline-flex items-center px-2 py-1 rounded-lg bg-[#18181B] hover:bg-[#242426] text-[#8E8E93] hover:text-[#F5F5F7] border border-[#2E2E32] transition-colors">
                                    <svg class="w-3 h-3 mr-1 text-[#8E8E93]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                    </svg>
                                    Login GUI
                                </button>

                                <button 
                                    type="button" 
                                    wire:click="$dispatch('open-channel-modal', { id: {{ $channel->id }} })" 
                                    class="px-2 py-1 rounded-lg bg-[#242426] hover:bg-[#2E2E32] text-[#F5F5F7] font-medium transition-colors">
                                    Sửa
                                </button>

                                <button 
                                    type="button" 
                                    wire:confirm="Bạn có chắc chắn muốn xóa kênh này?" 
                                    wire:click="deleteChannel({{ $channel->id }})" 
                                    class="px-2 py-1 rounded-lg bg-[#242426] hover:bg-[#2E2E32] text-[#FF453A] font-medium transition-colors">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-[#8E8E93]">
                                Chưa có kênh nào. Bấm "Thêm Kênh Mới" để bắt đầu!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Quản Lý Session Trình Duyệt (Sprint 6 Modal) -->
    @if ($managingSessionChannelId)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-md transition-opacity" wire:click="closeSessionModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-[#1C1C1E] border border-[#2E2E32] text-left shadow-apple-modal transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-[#2E2E32] flex items-center justify-between bg-[#161618]">
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-sm font-bold text-[#F5F5F7]">
                                    Quản Lý Session: {{ $managingSessionChannelName }}
                                </h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase bg-[#242426] text-[#FFFFFF] border border-[#38383A]">
                                    {{ strtoupper($managingSessionPlatform) }}
                                </span>
                            </div>
                            <div class="text-[11px] text-[#8E8E93] mt-0.5">
                                Trạng thái: <strong class="{{ $managingSessionStatus === 'active' ? 'text-[#30D158]' : 'text-[#FF453A]' }}">{{ $managingSessionStatus }}</strong>
                                • Lần kiểm tra cuối: <span class="font-mono text-[#F5F5F7]">{{ $managingSessionLastChecked }}</span>
                            </div>
                        </div>
                        <button type="button" wire:click="closeSessionModal" class="w-6 h-6 rounded-full bg-[#242426] flex items-center justify-center text-[#8E8E93] hover:text-[#F5F5F7] text-xs">
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body: 3 Sections -->
                    <div class="px-6 py-5 space-y-5 max-h-[75vh] overflow-y-auto">
                        <!-- SECTION 1: KIỂM TRA SỨC KHỎE PHIÊN ĐĂNG NHẬP (HEADLESS HEALTHCHECK) -->
                        <div class="p-4 rounded-xl bg-[#121214] border border-[#2E2E32] space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-bold text-[#F5F5F7] uppercase tracking-wider">1. Kiểm Tra Sức Khỏe Phiên (Headless Healthcheck)</h4>
                                    <p class="text-[11px] text-[#8E8E93] mt-0.5">
                                        Chạy Playwright ngầm không mở màn hình để ping vào trang quản trị Creator và kiểm tra cookies.
                                    </p>
                                </div>
                                <button 
                                    type="button" 
                                    wire:click="runHealthcheck({{ $managingSessionChannelId }})" 
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-[#FFFFFF] hover:bg-[#EBEBED] text-[#000000] transition-all shadow-apple-btn active:scale-[0.98] disabled:opacity-50">
                                    <span wire:loading.remove wire:target="runHealthcheck" class="flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-[#000000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                        Ping Kiểm Tra Ngay
                                    </span>
                                    <span wire:loading wire:target="runHealthcheck" class="flex items-center text-[#000000]">
                                        <svg class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5 text-[#000000]" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        Đang ping Playwright...
                                    </span>
                                </button>
                            </div>

                            @if ($healthcheckResult)
                                <div class="p-3 rounded-lg text-xs {{ $healthcheckResult['is_alive'] ? 'bg-[#30D158]/10 border border-[#30D158]/30 text-[#30D158]' : 'bg-[#FF453A]/10 border border-[#FF453A]/30 text-[#FF453A]' }}">
                                    <div class="font-bold flex items-center space-x-1.5">
                                        <span>{{ $healthcheckResult['is_alive'] ? '✓ PHIÊN HOẠT ĐỘNG BÌNH THƯỜNG' : '✕ PHIÊN KHÔNG KHẢ DỤNG' }}</span>
                                    </div>
                                    <div class="mt-1 text-[11px] opacity-90 leading-relaxed">
                                        {{ $healthcheckResult['message'] }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- SECTION 2: XUẤT SESSION VỀ MÁY (EXPORT ZIP) -->
                        <div class="p-4 rounded-xl bg-[#121214] border border-[#2E2E32] flex items-center justify-between gap-4">
                            <div>
                                <h4 class="text-xs font-bold text-[#F5F5F7] uppercase tracking-wider">2. Xuất Session Về Máy (Export ZIP)</h4>
                                <p class="text-[11px] text-[#8E8E93] mt-0.5">
                                    Đóng gói toàn bộ cookies, session và storage của profile thành file .zip để lưu trữ dự phòng hoặc mang sang server khác.
                                </p>
                            </div>
                            <button 
                                type="button" 
                                wire:click="exportSession({{ $managingSessionChannelId }})" 
                                class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-medium bg-[#242426] hover:bg-[#2E2E32] text-[#F5F5F7] border border-[#38383A] transition-all shadow-apple-btn active:scale-[0.98] whitespace-nowrap">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-[#8E8E93]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Tải File .ZIP
                            </button>
                        </div>

                        <!-- SECTION 3: NẠP SESSION LÊN SERVER (IMPORT ZIP) -->
                        <div class="p-4 rounded-xl bg-[#121214] border border-[#2E2E32] space-y-3">
                            <div>
                                <h4 class="text-xs font-bold text-[#F5F5F7] uppercase tracking-wider">3. Nạp Session Lên Server (Import ZIP)</h4>
                                <p class="text-[11px] text-[#8E8E93] mt-0.5">
                                    Khi chạy trên VPS/Server không có màn hình: Bạn đăng nhập trên máy tính cá nhân, nén thư mục profile thành file .zip và nạp lên đây.
                                </p>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center gap-3">
                                <input 
                                    type="file" 
                                    wire:model="sessionZipUpload" 
                                    accept=".zip" 
                                    class="w-full text-xs text-[#8E8E93] file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border file:border-[#38383A] file:text-xs file:font-medium file:bg-[#242426] file:text-[#F5F5F7] hover:file:bg-[#2E2E32] file:cursor-pointer cursor-pointer">

                                <button 
                                    type="button" 
                                    wire:click="importSession" 
                                    wire:loading.attr="disabled"
                                    @if (!$sessionZipUpload) disabled @endif
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-1.5 rounded-xl text-xs font-semibold bg-[#FFFFFF] hover:bg-[#EBEBED] text-[#000000] transition-all shadow-apple-btn active:scale-[0.98] disabled:opacity-40 whitespace-nowrap">
                                    <span wire:loading.remove wire:target="importSession" class="flex items-center text-[#000000]">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-[#000000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                        </svg>
                                        Giải Nén & Nạp Session
                                    </span>
                                    <span wire:loading wire:target="importSession" class="flex items-center text-[#000000]">
                                        <svg class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5 text-[#000000]" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        Đang giải nén & nạp...
                                    </span>
                                </button>
                            </div>

                            <div wire:loading wire:target="sessionZipUpload" class="text-[11px] text-[#8E8E93] italic">
                                Đang tải file lên bộ đệm...
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-[#2E2E32] flex items-center justify-end bg-[#161618]">
                        <button 
                            type="button" 
                            wire:click="closeSessionModal" 
                            class="px-4 py-2 rounded-xl text-xs font-medium text-[#8E8E93] hover:text-[#F5F5F7] transition-colors">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Form Component -->
    <livewire:channels.channel-form-modal />
</div>
