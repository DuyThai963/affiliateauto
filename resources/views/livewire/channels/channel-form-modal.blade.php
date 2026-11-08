<!-- [Design Read]: Apple HIG inspired modal sheet with a clean white surface, frosted backdrop, rounded-2xl geometry, and segmented option cards. -->
<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/25 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-[#FFFFFF] border border-[#E5E5EA] text-left shadow-apple-modal transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-[#E5E5EA] flex items-center justify-between bg-[#FBFBFD]">
                        <h3 class="text-sm font-bold text-[#1D1D1F]">
                            {{ $channelId ? 'Cập Nhật Kênh Phân Phối' : 'Thêm Kênh Phân Phối Mới' }}
                        </h3>
                        <button type="button" wire:click="closeModal" class="w-6 h-6 rounded-full bg-[#E5E5EA]/60 flex items-center justify-center text-[#86868B] hover:text-[#1D1D1F] text-xs">
                            ✕
                        </button>
                    </div>

                    <!-- Form -->
                    <form wire:submit="save">
                        <div class="px-6 py-5 space-y-4">
                            <!-- Platform -->
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                    Nền Tảng
                                </label>
                                <select wire:model.live="platform" class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                    <option value="tiktok">TikTok (Playwright Automation)</option>
                                    <option value="shopee">Shopee Video (Playwright Automation)</option>
                                </select>
                                @error('platform') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Channel Name -->
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                    Tên Kênh (Username hoặc ID)
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="channel_name" 
                                    placeholder="@review_gia_dung" 
                                    class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] placeholder-[#86868B] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                @error('channel_name') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Channel Type Cards -->
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-2">
                                    Phân Loại Kênh (Bắt Buộc)
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <!-- Growth Option -->
                                    <label class="cursor-pointer border rounded-2xl p-3 flex flex-col justify-between transition-all {{ $type === 'growth' ? 'bg-[#E8F5E9]/50 border-[#34C759] shadow-apple-card' : 'bg-[#FFFFFF] border-[#E5E5EA] hover:border-[#AEAEB2]' }}">
                                        <div class="flex items-center space-x-2">
                                            <input type="radio" wire:model.live="type" value="growth" class="text-[#34C759] focus:ring-[#34C759]">
                                            <span class="font-bold text-xs text-[#1D1D1F]">🌱 Growth</span>
                                        </div>
                                        <p class="text-[11px] text-[#86868B] mt-2 leading-relaxed">
                                            Xây kênh kéo view. <strong class="text-[#FF3B30]">Tuyệt đối không gắn giỏ hàng.</strong>
                                        </p>
                                    </label>

                                    <!-- Affiliate Option -->
                                    <label class="cursor-pointer border rounded-2xl p-3 flex flex-col justify-between transition-all {{ $type === 'affiliate' ? 'bg-[#F3E5F5]/50 border-[#AF52DE] shadow-apple-card' : 'bg-[#FFFFFF] border-[#E5E5EA] hover:border-[#AEAEB2]' }}">
                                        <div class="flex items-center space-x-2">
                                            <input type="radio" wire:model.live="type" value="affiliate" class="text-[#AF52DE] focus:ring-[#AF52DE]">
                                            <span class="font-bold text-xs text-[#1D1D1F]">🛒 Affiliate</span>
                                        </div>
                                        <p class="text-[11px] text-[#86868B] mt-2 leading-relaxed">
                                            Kênh kiếm tiền AIDA. <strong class="text-[#0071E3]">Bắt buộc ghim link giỏ hàng.</strong>
                                        </p>
                                    </label>
                                </div>
                                @error('type') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Browser Profile Path -->
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                    Profile Trình Duyệt Playwright
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="browser_profile_path" 
                                    class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs font-mono text-[#1D1D1F] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                <span class="text-[10px] text-[#86868B] mt-1 block">Lưu trữ session cookie riêng biệt cho từng kênh để chống checkpoint.</span>
                                @error('browser_profile_path') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Limits & Intervals -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                        Giới Hạn / Ngày (Video)
                                    </label>
                                    <input 
                                        type="number" 
                                        wire:model="daily_post_limit" 
                                        min="1" 
                                        max="50" 
                                        class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                    @error('daily_post_limit') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                        Giãn Cách Tối Thiểu (Phút)
                                    </label>
                                    <input 
                                        type="number" 
                                        wire:model="min_interval_minutes" 
                                        min="30" 
                                        max="1440" 
                                        class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                    @error('min_interval_minutes') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                    Trạng Thái Kênh
                                </label>
                                <select wire:model="status" class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                    <option value="active">Hoạt động bình thường (Active)</option>
                                    <option value="paused">Tạm dừng đăng (Paused)</option>
                                    <option value="needs_relogin">Cần đăng nhập lại (Needs Relogin)</option>
                                </select>
                                @error('status') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-[#E5E5EA] flex items-center justify-end space-x-3 bg-[#FBFBFD]">
                            <button 
                                type="button" 
                                wire:click="closeModal" 
                                class="px-4 py-2 rounded-xl text-xs font-medium bg-[#E5E5EA] hover:bg-[#D1D1D6] text-[#1D1D1F] transition-colors">
                                Hủy Bỏ
                            </button>
                            <button 
                                type="submit" 
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#0071E3] hover:bg-[#0077ED] text-white transition-all shadow-apple-btn">
                                Lưu Kênh
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
