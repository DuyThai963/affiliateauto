<!-- [Design Read]: Apple HIG Dark Mode product inventory catalogue with #18181B studio cards, #26262A borders, luminous platform pills, and minimalist action controls. -->
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-[#F5F5F7]">Sản Phẩm Giỏ Hàng (Showcase)</h2>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Kho sản phẩm mẫu để bot Playwright tự động tìm kiếm và ghim link mua hàng cho kênh Affiliate.
            </p>
        </div>
        <button 
            type="button" 
            wire:click="$dispatch('open-product-modal')" 
            class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-[#0A84FF] hover:bg-[#0077ED] text-white transition-all shadow-apple-btn active:scale-[0.98]">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm Sản Phẩm
        </button>
    </div>

    <!-- Alert / Toast -->
    @if ($feedbackMessage)
        <div class="p-3.5 rounded-2xl bg-[#30D158]/10 border border-[#30D158]/25 text-[#30D158] text-xs flex items-center justify-between shadow-apple-card">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-[#30D158]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="font-medium">{{ $feedbackMessage }}</span>
            </div>
            <button type="button" wire:click="$set('feedbackMessage', null)" class="text-[#30D158]/70 hover:text-[#30D158] text-sm">✕</button>
        </div>
    @endif

    <!-- Search & Filter Toolbar -->
    <div class="p-3 rounded-2xl bg-[#18181B] border border-[#26262A] shadow-apple-card flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="w-full md:w-80 relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Tìm tên sản phẩm, mã ID hoặc ghi chú..." 
                class="w-full pl-9 pr-4 py-1.5 bg-[#121214] border border-[#26262A] rounded-xl text-xs text-[#F5F5F7] placeholder-[#636366] focus:outline-none focus:border-[#0A84FF] transition-all">
            <div class="absolute left-3 top-2 text-[#8E8E93]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div>
            <select wire:model.live="platformFilter" class="px-3 py-1.5 bg-[#121214] border border-[#26262A] rounded-xl text-xs text-[#F5F5F7] focus:outline-none focus:border-[#0A84FF] shadow-apple-btn">
                <option value="">Tất cả Nền tảng</option>
                <option value="tiktok">TikTok Shop</option>
                <option value="shopee">Shopee</option>
            </select>
        </div>
    </div>

    <!-- Products Table -->
    <div class="rounded-2xl border border-[#26262A] bg-[#18181B] overflow-hidden shadow-apple-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-[#F5F5F7]">
                <thead class="bg-[#121214] text-[11px] font-semibold text-[#8E8E93] uppercase tracking-wider border-b border-[#26262A]">
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
                <tbody class="divide-y divide-[#202024]">
                    @forelse ($products as $product)
                        <tr class="hover:bg-[#1C1C1E] transition-colors">
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if ($product->platform === 'tiktok')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#000000] border border-[#38383A] text-white">
                                        TikTok
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#FF9F0A]/15 text-[#FF9F0A] border border-[#FF9F0A]/25">
                                        Shopee
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="font-semibold text-[#F5F5F7] max-w-sm">{{ $product->product_name }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-[11px] text-[#8E8E93]">
                                {{ $product->external_product_id ?: '—' }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-xs">
                                @if ($product->affiliate_url)
                                    <a href="{{ $product->affiliate_url }}" target="_blank" class="text-[#0A84FF] hover:underline flex items-center text-[11px] font-medium">
                                        <span>Xem link</span>
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-[#636366]">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-[11px] text-[#8E8E93] max-w-xs truncate">
                                {{ $product->notes ?: '—' }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <button 
                                    type="button" 
                                    wire:click="toggleActive({{ $product->id }})" 
                                    class="text-[11px] font-medium px-2 py-0.5 rounded-full transition-colors {{ $product->is_active ? 'bg-[#30D158]/15 text-[#30D158] border border-[#30D158]/25' : 'bg-[#242426] text-[#8E8E93]' }}">
                                    {{ $product->is_active ? '● Đang bán' : '○ Tạm dừng' }}
                                </button>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-right space-x-2">
                                <button 
                                    type="button" 
                                    wire:click="$dispatch('open-product-modal', { id: {{ $product->id }} })" 
                                    class="px-2 py-1 rounded-lg bg-[#242426] hover:bg-[#2C2C2E] text-[#0A84FF] text-[11px] font-medium transition-colors">
                                    Sửa
                                </button>
                                <button 
                                    type="button" 
                                    wire:confirm="Bạn có chắc chắn muốn xóa sản phẩm này?" 
                                    wire:click="deleteProduct({{ $product->id }})" 
                                    class="px-2 py-1 rounded-lg bg-[#FF453A]/15 hover:bg-[#FF453A]/25 text-[#FF453A] text-[11px] font-medium transition-colors">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-[#8E8E93]">
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
