<!-- [Design Read]: E-commerce showcase inventory table on dark slate with platform markers, direct affiliate links, and quick active state toggles. -->
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-white">Danh Mục Sản Phẩm Giỏ Hàng</h2>
            <p class="text-sm text-slate-400 mt-1">
                Quản lý kho sản phẩm Showcase để bot Playwright tự động tìm kiếm và gắn link mua hàng cho kênh Affiliate.
            </p>
        </div>
        <button 
            type="button" 
            wire:click="$dispatch('open-product-modal')" 
            class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm Sản Phẩm
        </button>
    </div>

    <!-- Alert / Toast -->
    @if ($feedbackMessage)
        <div class="p-4 rounded-lg bg-emerald-950/80 border border-emerald-800/80 text-emerald-200 text-sm flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span>✓</span>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button type="button" wire:click="$set('feedbackMessage', null)" class="text-emerald-400 hover:text-emerald-200 text-sm">✕</button>
        </div>
    @endif

    <!-- Search & Filter -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="w-full md:w-96 relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Tìm tên sản phẩm, mã ID hoặc ghi chú..." 
                class="w-full pl-10 pr-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-emerald-500">
            <div class="absolute left-3 top-2.5 text-slate-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div>
            <select wire:model.live="platformFilter" class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-300 focus:outline-none focus:border-emerald-500">
                <option value="">Tất cả Nền tảng</option>
                <option value="tiktok">TikTok Shop</option>
                <option value="shopee">Shopee</option>
            </select>
        </div>
    </div>

    <!-- Products Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/70 text-xs uppercase font-semibold text-slate-400 tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">Nền tảng</th>
                        <th class="px-5 py-3.5">Tên Sản Phẩm (Showcase Name)</th>
                        <th class="px-5 py-3.5">External ID</th>
                        <th class="px-5 py-3.5">Link Tiếp Thị</th>
                        <th class="px-5 py-3.5">Ghi chú</th>
                        <th class="px-5 py-3.5">Trạng thái</th>
                        <th class="px-5 py-3.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-850/60 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if ($product->platform === 'tiktok')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-950 text-rose-300 border border-rose-800">
                                        TikTok Shop
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-950 text-amber-300 border border-amber-800">
                                        Shopee
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-white max-w-sm">{{ $product->product_name }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap font-mono text-xs text-slate-400">
                                {{ $product->external_product_id ?: '—' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-xs">
                                @if ($product->affiliate_url)
                                    <a href="{{ $product->affiliate_url }}" target="_blank" class="text-emerald-400 hover:underline flex items-center">
                                        <span>Xem link</span>
                                        <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-slate-600">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-400 max-w-xs truncate">
                                {{ $product->notes ?: '—' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <button 
                                    type="button" 
                                    wire:click="toggleActive({{ $product->id }})" 
                                    class="text-xs font-medium px-2.5 py-0.5 rounded transition-colors {{ $product->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-slate-800 text-slate-500' }}">
                                    {{ $product->is_active ? '● Đang bán' : '○ Tạm dừng' }}
                                </button>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right space-x-2 text-xs">
                                <button 
                                    type="button" 
                                    wire:click="$dispatch('open-product-modal', { id: {{ $product->id }} })" 
                                    class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 transition-colors">
                                    Sửa
                                </button>
                                <button 
                                    type="button" 
                                    wire:confirm="Bạn có chắc chắn muốn xóa sản phẩm này?" 
                                    wire:click="deleteProduct({{ $product->id }})" 
                                    class="px-2.5 py-1 rounded bg-rose-950/70 hover:bg-rose-900 text-rose-300 transition-colors">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                                Chưa có sản phẩm nào trong kho giỏ hàng. Thêm sản phẩm để bot tự động ghim link Showcase!
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

