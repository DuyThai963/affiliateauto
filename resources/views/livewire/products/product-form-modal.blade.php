<!-- [Design Read]: Apple HIG style modal sheet for showcase product registration with smooth curved inputs, calm neutral surface, and clear primary CTA. -->
<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/25 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-[#FFFFFF] border border-[#E5E5EA] text-left shadow-apple-modal transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-[#E5E5EA] flex items-center justify-between bg-[#FBFBFD]">
                        <h3 class="text-sm font-bold text-[#1D1D1F]">
                            {{ $productId ? 'Cập Nhật Sản Phẩm Giỏ Hàng' : 'Thêm Sản Phẩm Mới' }}
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
                                <select wire:model="platform" class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                    <option value="tiktok">TikTok Shop Showcase</option>
                                    <option value="shopee">Shopee Video</option>
                                </select>
                                @error('platform') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Product Name -->
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                    Tên Sản Phẩm (Hiển thị trong Showcase)
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="product_name" 
                                    placeholder="Nồi chiên không dầu Lock&Lock 5.2L" 
                                    class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] placeholder-[#86868B] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                <span class="text-[10px] text-[#86868B] mt-1 block">Bot Playwright sẽ tìm chính xác tên này trong giỏ hàng để ghim.</span>
                                @error('product_name') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- External ID -->
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                    External Product ID (Mã sàn - Tùy chọn)
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="external_product_id" 
                                    placeholder="17294829104" 
                                    class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs font-mono text-[#1D1D1F] placeholder-[#86868B] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                @error('external_product_id') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Affiliate URL -->
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                    Link Affiliate (Dự phòng)
                                </label>
                                <input 
                                    type="url" 
                                    wire:model="affiliate_url" 
                                    placeholder="https://vt.tiktok.com/..." 
                                    class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] placeholder-[#86868B] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                @error('affiliate_url') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                    Ghi Chú Đơn Hàng / Hoa Hồng
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="notes" 
                                    placeholder="Hoa hồng 15%" 
                                    class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] placeholder-[#86868B] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                @error('notes') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Active Switch Checkbox -->
                            <div class="pt-1">
                                <label class="flex items-center space-x-2.5 cursor-pointer">
                                    <input type="checkbox" wire:model="is_active" class="w-4 h-4 rounded text-[#0071E3] border-[#E5E5EA] focus:ring-[#0071E3]">
                                    <span class="text-xs font-medium text-[#1D1D1F]">Sẵn sàng đưa vào kịch bản affiliate tự động</span>
                                </label>
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
                                Lưu Sản Phẩm
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
