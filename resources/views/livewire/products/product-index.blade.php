<!-- [Design Read]: Apple HIG style product inventory catalogue with crisp macOS table styling, minimalist metadata tags, and discreet action buttons. -->
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-[#1D1D1F]">Sản Phẩm Giỏ Hàng (Showcase)</h2>
            <p class="text-xs text-[#86868B] mt-0.5">
                Kho sản phẩm mẫu để bot Playwright tự động tìm kiếm và ghim link mua hàng cho kênh Affiliate.
            </p>
        </div>
        <button 
            type="button" 
            wire:click="$dispatch('open-product-modal')" 
            class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-[#0071E3] hover:bg-[#0077ED] text-white transition-all shadow-apple-btn active:scale-[0.98]">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm Sản Phẩm
        </button>
    </div>

    <!-- Alert / Toast -->
    @if ($feedbackMessage)
        <div class="p-3.5 rounded-2xl bg-[#E8F5E9] border border-[#C8E6C9] text-[#2E7D32] text-xs flex items-center justify-between shadow-apple-card">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-[#34C759]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="font-medium">{{ $feedbackMessage }}</span>
            </div>
            <button type="button" wire:click="$set('feedbackMessage', null)" class="text-[#2E7D32]/70 hover:text-[#2E7D32] text-sm">✕</button>
        </div>
    @endif

    <!-- Search & Filter Toolbar -->
    <div class="p-3 rounded-2xl bg-[#FFFFFF] border border-[#E5E5EA] shadow-apple-card flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="w-full md:w-80 relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Tìm tên sản phẩm, mã ID hoặc ghi chú..." 
                class="w-full pl-9 pr-4 py-1.5 bg-[#F5F5F7] border border-transparent rounded-xl text-xs text-[#1D1D1F] placeholder-[#86868B] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3] transition-all">
            <div class="absolute left-3 top-2 text-[#86868B]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div>
            <select wire:model.live="platformFilter" class="px-3 py-1.5 bg-[#FFFFFF] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] focus:outline-none focus:border-[#0071E3] shadow-apple-btn">
                <option value="">Tất cả Nền tảng</option>
                <option value="tiktok">TikTok Shop</option>
                <option value="shopee">Shopee</option>
            </select>
        </div>
    </div>

    <!-- Products Table -->
    <div class="rounded-2xl border border-[#E5E5EA] bg-[#FFFFFF] overflow-hidden shadow-apple-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-[#1D1D1F]">
                <thead class="bg-[#F5F5F7]/60 text-[11px] font-semibold text-[#86868B] uppercase tracking-wider border-b border-[#E5E5EA]">
                    <tr>
                        <th class="px-6 py-3">Nền tảng</th>
                        <th class="px-6 py-3">Tên Sản Phẩm (Showcase)</th>
                        <th class="px-6 py-3">External ID</th>
                        <th class="px-6 py-3">Link Tiếp Thị</th>
                        <th class="px-6 py-3">Ghi chú</th>
                        <th class="px-6 py-3">Trạng thái</th>
                        <th class="px-6 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F5F5F7]">
                    @forelse ($products as $product)
                        <tr class="hover:bg-[#FBFBFD] transition-colors">
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if ($product->platform === 'tiktok')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#000000] text-white">
                                        TikTok
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#FFF3E0] text-[#E65100]">
                                        Shopee
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="font-semibold text-[#1D1D1F] max-w-sm">{{ $product->product_name }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-[11px] text-[#86868B]">
                                {{ $product->external_product_id ?: '—' }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-xs">
                                @if ($product->affiliate_url)
                                    <a href="{{ $product->affiliate_url }}" target="_blank" class="text-[#0071E3] hover:underline flex items-center text-[11px] font-medium">
                                        <span>Xem link</span>
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-[#AEAEB2]">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-[11px] text-[#86868B] max-w-xs truncate">
                                {{ $product->notes ?: '—' }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <button 
                                    type="button" 
                                    wire:click="toggleActive({{ $product->id }})" 
                                    class="text-[11px] font-medium px-2 py-0.5 rounded-full transition-colors {{ $product->is_active ? 'bg-[#E8F5E9] text-[#2E7D32]' : 'bg-[#F5F5F7] text-[#86868B]' }}">
                                    {{ $product->is_active ? '● Đang bán' : '○ Tạm dừng' }}
                                </button>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-right space-x-2">
                                <button 
                                    type="button" 
                                    wire:click="$dispatch('open-product-modal', { id: {{ $product->id }} })" 
                                    class="px-2 py-1 rounded-lg bg-[#F5F5F7] hover:bg-[#E5E5EA] text-[#0071E3] text-[11px] font-medium transition-colors">
                                    Sửa
                                </button>
                                <button 
                                    type="button" 
                                    wire:confirm="Bạn có chắc chắn muốn xóa sản phẩm này?" 
                                    wire:click="deleteProduct({{ $product->id }})" 
                                    class="px-2 py-1 rounded-lg bg-[#FFEBEE] hover:bg-[#FFCDD2] text-[#C62828] text-[11px] font-medium transition-colors">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-[#86868B]">
                                Chưa có sản phẩm nào. Bấm "Thêm Sản Phẩm" để thêm vào giỏ hàng Showcase!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Component -->
    <livewire:products.product-form-modal />
</div>
