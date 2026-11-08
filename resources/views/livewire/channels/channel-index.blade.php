<!-- [Design Read]: Apple HIG style channels dashboard with clean macOS list view, subtle platform pills, and unified action controls. -->
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-[#1D1D1F]">Kênh Phân Phối</h2>
            <p class="text-xs text-[#86868B] mt-0.5">
                Cấu hình tài khoản TikTok & Shopee, phân loại kênh Kéo View (Growth) hoặc Gắn Giỏ (Affiliate).
            </p>
        </div>
        <button 
            type="button" 
            wire:click="$dispatch('open-channel-modal')" 
            class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-[#0071E3] hover:bg-[#0077ED] text-white transition-all shadow-apple-btn active:scale-[0.98]">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm Kênh Mới
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

    <!-- Search & Filter Bar (macOS Toolbar Style) -->
    <div class="p-3 rounded-2xl bg-[#FFFFFF] border border-[#E5E5EA] shadow-apple-card flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="w-full md:w-80 relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Tìm theo tên kênh hoặc đường dẫn profile..." 
                class="w-full pl-9 pr-4 py-1.5 bg-[#F5F5F7] border border-transparent rounded-xl text-xs text-[#1D1D1F] placeholder-[#86868B] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3] transition-all">
            <div class="absolute left-3 top-2 text-[#86868B]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div class="flex items-center space-x-2 w-full md:w-auto">
            <select wire:model.live="platformFilter" class="px-3 py-1.5 bg-[#FFFFFF] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] focus:outline-none focus:border-[#0071E3] shadow-apple-btn">
                <option value="">Tất cả Nền tảng</option>
                <option value="tiktok">TikTok</option>
                <option value="shopee">Shopee</option>
            </select>

            <select wire:model.live="typeFilter" class="px-3 py-1.5 bg-[#FFFFFF] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] focus:outline-none focus:border-[#0071E3] shadow-apple-btn">
                <option value="">Tất cả Loại kênh</option>
                <option value="growth">Growth (Kéo view)</option>
                <option value="affiliate">Affiliate (Gắn giỏ)</option>
            </select>
        </div>
    </div>

    <!-- Data Table -->
    <div class="rounded-2xl border border-[#E5E5EA] bg-[#FFFFFF] overflow-hidden shadow-apple-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-[#1D1D1F]">
                <thead class="bg-[#F5F5F7]/60 text-[11px] font-semibold text-[#86868B] uppercase tracking-wider border-b border-[#E5E5EA]">
                    <tr>
                        <th class="px-6 py-3">Nền tảng</th>
                        <th class="px-6 py-3">Tên Kênh</th>
                        <th class="px-6 py-3">Loại Kênh</th>
                        <th class="px-6 py-3">Giới hạn / Giãn cách</th>
                        <th class="px-6 py-3">Lần đăng cuối</th>
                        <th class="px-6 py-3">Trạng thái</th>
                        <th class="px-6 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F5F5F7]">
                    @forelse ($channels as $channel)
                        <tr class="hover:bg-[#FBFBFD] transition-colors">
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if ($channel->platform === 'tiktok')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#000000] text-white">
                                        TikTok
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#FFF3E0] text-[#E65100]">
                                        Shopee
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="font-semibold text-[#1D1D1F]">{{ $channel->channel_name }}</div>
                                <div class="text-[11px] text-[#86868B] font-mono truncate max-w-xs" title="{{ $channel->browser_profile_path }}">
                                    {{ $channel->browser_profile_path }}
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if ($channel->type === 'growth')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#E8F5E9] text-[#2E7D32]">
                                        🌱 Growth (Kéo View)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#F3E5F5] text-[#7B1FA2]">
                                        🛒 Affiliate (Gắn Giỏ)
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-mono text-[11px] text-[#515154]">
                                <div><strong class="text-[#1D1D1F]">{{ $channel->daily_post_limit }}</strong> video/ngày</div>
                                <div class="text-[#86868B]">Giãn cách: {{ $channel->min_interval_minutes }}p</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-[#86868B]">
                                {{ $channel->last_posted_at ? $channel->last_posted_at->diffForHumans() : 'Chưa đăng' }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if ($channel->status === 'active')
                                    <span class="inline-flex items-center text-[11px] font-medium text-[#2E7D32]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#34C759] mr-1.5"></span> Hoạt động
                                    </span>
                                @elseif ($channel->status === 'paused')
                                    <span class="inline-flex items-center text-[11px] font-medium text-[#FF9500]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#FF9500] mr-1.5"></span> Tạm dừng
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-[11px] font-medium text-[#FF3B30]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#FF3B30] mr-1.5"></span> Cần Login lại
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-right space-x-2">
                                <button 
                                    type="button" 
                                    wire:click="testBrowserProfile({{ $channel->id }})" 
                                    class="px-2 py-1 rounded-lg bg-[#F5F5F7] hover:bg-[#E5E5EA] text-[#515154] font-medium transition-colors">
                                    Test Profile
                                </button>
                                <button 
                                    type="button" 
                                    wire:click="$dispatch('open-channel-modal', { id: {{ $channel->id }} })" 
                                    class="px-2 py-1 rounded-lg bg-[#F5F5F7] hover:bg-[#E5E5EA] text-[#0071E3] font-medium transition-colors">
                                    Sửa
                                </button>
                                <button 
                                    type="button" 
                                    wire:confirm="Bạn có chắc chắn muốn xóa kênh này?" 
                                    wire:click="deleteChannel({{ $channel->id }})" 
                                    class="px-2 py-1 rounded-lg bg-[#FFEBEE] hover:bg-[#FFCDD2] text-[#C62828] font-medium transition-colors">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-[#86868B]">
                                Chưa có kênh nào. Bấm "Thêm Kênh Mới" để bắt đầu!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Component -->
    <livewire:channels.channel-form-modal />
</div>
