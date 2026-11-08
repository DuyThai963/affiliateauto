<!-- [Design Read]: Compact modal dialog for registering showcase affiliate merchandise, external IDs, and affiliate URLs. -->
<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-slate-900 border border-slate-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">
                            {{ $productId ? 'Cập Nhật Sản Phẩm Giỏ Hàng' : 'Thêm Sản Phẩm Mới Vào Giỏ Hàng' }}
                        </h3>
                        <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-200">
                            ✕
                        </button>
                    </div>

                    <!-- Form -->
                    <form wire:submit="save">
                        <div class="px-6 py-5 space-y-4">
                            <!-- Platform -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                    Nền Tảng Thương Mại
                                </label>
                                <select wire:model="platform" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-emerald-500">
                                    <option value="tiktok">TikTok Shop Showcase</option>
                                    <option value="shopee">Shopee Video</option>
                                </select>
                                @error('platform') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Product Name -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                    Tên Sản Phẩm (Tên chính xác như trong Showcase)
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="product_name" 
                                    placeholder="Nồi chiên không dầu Lock&Lock 5.2L" 
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-emerald-500">
                                <span class="text-[11px] text-slate-500 mt-1 block">Bot Playwright sẽ gõ tên này vào ô tìm kiếm sản phẩm giỏ hàng để ghim.</span>
                                @error('product_name') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- External ID -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                    External Product ID (Mã sản phẩm trên sàn - Tùy chọn)
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="external_product_id" 
                                    placeholder="17294829104" 
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 font-mono placeholder-slate-600 focus:outline-none focus:border-emerald-500">
                                @error('external_product_id') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Affiliate URL -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                    Link Affiliate Tiếp Thị (Dự phòng)
                                </label>
                                <input 
                                    type="url" 
                                    wire:model="affiliate_url" 
                                    placeholder="https://vt.tiktok.com/..." 
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-emerald-500">
                                @error('affiliate_url') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                    Ghi Chú Đơn Hàng / Hoa Hồng
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="notes" 
                                    placeholder="Hoa hồng 15%, bán kèm mã giảm giá 50k" 
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-emerald-500">
                                @error('notes') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Active Checkbox -->
                            <div class="pt-2">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" wire:model="is_active" class="w-4 h-4 rounded text-emerald-500 bg-slate-950 border-slate-800 focus:ring-emerald-500">
                                    <span class="text-sm font-medium text-slate-200">Sẵn sàng để đưa vào kịch bản affiliate tự động</span>
                                </label>
                            </div>
                        </div>

                        <!-- Footer -->
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
                                Lưu Sản Phẩm
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

