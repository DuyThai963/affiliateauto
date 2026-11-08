<!-- [Design Read]: Modal dialog overlay with dark slate surface (bg-slate-900), high accessibility contrast inputs, and distinct radio option cards for growth vs affiliate channel types. -->
<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Background Backdrop -->
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-slate-900 border border-slate-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">
                            {{ $channelId ? 'Cập Nhật Kênh Phân Phối' : 'Thêm Kênh Phân Phối Mới' }}
                        </h3>
                        <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-200">
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body / Form -->
                    <form wire:submit="save">
                        <div class="px-6 py-5 space-y-4">
                            <!-- Platform -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                    Nền Tảng
                                </label>
                                <select wire:model.live="platform" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-emerald-500">
                                    <option value="tiktok">TikTok (Playwright Automation)</option>
                                    <option value="shopee">Shopee Video (Playwright Automation)</option>
                                </select>
                                @error('platform') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Channel Name -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                    Tên Kênh (Username hoặc ID)
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="channel_name" 
                                    placeholder="@review_gia_dung" 
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-emerald-500">
                                @error('channel_name') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Channel Type (Radio Selection) -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                                    Phân Loại Kênh (Bắt Buộc)
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <!-- Growth Option -->
                                    <label class="cursor-pointer border rounded-xl p-3 flex flex-col justify-between transition-colors {{ $type === 'growth' ? 'bg-emerald-950/40 border-emerald-500 text-emerald-200' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700' }}">
                                        <div class="flex items-center space-x-2">
                                            <input type="radio" wire:model.live="type" value="growth" class="text-emerald-500 focus:ring-emerald-500">
                                            <span class="font-bold text-sm text-white">🌱 Growth</span>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-2">
                                            Xây kênh kéo view, tương tác cao. <strong class="text-rose-400">Tuyệt đối không gắn giỏ hàng.</strong>
                                        </p>
                                    </label>

                                    <!-- Affiliate Option -->
                                    <label class="cursor-pointer border rounded-xl p-3 flex flex-col justify-between transition-colors {{ $type === 'affiliate' ? 'bg-purple-950/40 border-purple-500 text-purple-200' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700' }}">
                                        <div class="flex items-center space-x-2">
                                            <input type="radio" wire:model.live="type" value="affiliate" class="text-purple-500 focus:ring-purple-500">
                                            <span class="font-bold text-sm text-white">🛒 Affiliate</span>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-2">
                                            Kênh kiếm tiền AIDA. <strong class="text-emerald-400">Bắt buộc tìm kiếm & ghim link giỏ hàng.</strong>
                                        </p>
                                    </label>
                                </div>
                                @error('type') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Browser Profile Path -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                    Đường Dẫn Profile Trình Duyệt (Playwright)
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="browser_profile_path" 
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 font-mono focus:outline-none focus:border-emerald-500">
                                <span class="text-[11px] text-slate-500 mt-1 block">Lưu trữ cookie, localStorage session đăng nhập riêng biệt chống checkpoint.</span>
                                @error('browser_profile_path') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Limits & Intervals -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                        Giới Hạn / Ngày (Video)
                                    </label>
                                    <input 
                                        type="number" 
                                        wire:model="daily_post_limit" 
                                        min="1" 
                                        max="50" 
                                        class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-emerald-500">
                                    @error('daily_post_limit') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                        Giãn Cách Tối Thiểu (Phút)
                                    </label>
                                    <input 
                                        type="number" 
                                        wire:model="min_interval_minutes" 
                                        min="30" 
                                        max="1440" 
                                        class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-emerald-500">
                                    @error('min_interval_minutes') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                    Trạng Thái Kênh
                                </label>
                                <select wire:model="status" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-emerald-500">
                                    <option value="active">Hoạt động bình thường (Active)</option>
                                    <option value="paused">Tạm dừng đăng (Paused)</option>
                                    <option value="needs_relogin">Cần đăng nhập lại (Needs Relogin)</option>
                                </select>
                                @error('status') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 border-t border-slate-800 flex items-center justify-end space-x-3 bg-slate-950/40">
                            <button 
                                type="button" 
                                wire:click="closeModal" 
                                class="px-4 py-2 rounded-lg text-sm font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                                Hủy Bỏ
                            </button>
                            <button 
                                type="submit" 
                                class="px-4 py-2 rounded-lg text-sm font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-colors">
                                Lưu Kênh
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

