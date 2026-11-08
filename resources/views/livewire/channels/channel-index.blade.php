<!-- [Design Read]: Apple HIG Dark Mode channels dashboard featuring studio-gray #18181B cards, #26262A borders, luminous platform pills, and clean action controls. -->
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-[#F5F5F7]">Kênh Phân Phối</h2>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Cấu hình tài khoản TikTok & Shopee, phân loại kênh Kéo View (Growth) hoặc Gắn Giỏ (Affiliate).
            </p>
        </div>
        <button 
            type="button" 
            wire:click="$dispatch('open-channel-modal')" 
            class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-[#0A84FF] hover:bg-[#0077ED] text-white transition-all shadow-apple-btn active:scale-[0.98]">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm Kênh Mới
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

    <!-- Search & Filter Bar (macOS Dark Toolbar Style) -->
    <div class="p-3 rounded-2xl bg-[#18181B] border border-[#26262A] shadow-apple-card flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="w-full md:w-80 relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Tìm theo tên kênh hoặc đường dẫn profile..." 
                class="w-full pl-9 pr-4 py-1.5 bg-[#121214] border border-[#26262A] rounded-xl text-xs text-[#F5F5F7] placeholder-[#636366] focus:outline-none focus:border-[#0A84FF] transition-all">
            <div class="absolute left-3 top-2 text-[#8E8E93]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div class="flex items-center space-x-2 w-full md:w-auto">
            <select wire:model.live="platformFilter" class="px-3 py-1.5 bg-[#121214] border border-[#26262A] rounded-xl text-xs text-[#F5F5F7] focus:outline-none focus:border-[#0A84FF] shadow-apple-btn">
                <option value="">Tất cả Nền tảng</option>
                <option value="tiktok">TikTok</option>
                <option value="shopee">Shopee</option>
            </select>

            <select wire:model.live="typeFilter" class="px-3 py-1.5 bg-[#121214] border border-[#26262A] rounded-xl text-xs text-[#F5F5F7] focus:outline-none focus:border-[#0A84FF] shadow-apple-btn">
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
                        <th class="px-6 py-3">Trạng thái</th>
                        <th class="px-6 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#202024]">
                    @forelse ($channels as $channel)
                        <tr class="hover:bg-[#1C1C1E] transition-colors">
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if ($channel->platform === 'tiktok')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#000000] border border-[#38383A] text-white">
                                        TikTok
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#FF9F0A]/15 text-[#FF9F0A] border border-[#FF9F0A]/25">
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#30D158]/15 text-[#30D158] border border-[#30D158]/25">
                                        🌱 Growth (Kéo View)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#BF5AF2]/15 text-[#BF5AF2] border border-[#BF5AF2]/25">
                                        🛒 Affiliate (Gắn Giỏ)
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
                                    <span class="inline-flex items-center text-[11px] font-medium text-[#30D158]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#30D158] mr-1.5 shadow-[0_0_6px_rgba(48,209,88,0.5)]"></span> Hoạt động
                                    </span>
                                @elseif ($channel->status === 'paused')
                                    <span class="inline-flex items-center text-[11px] font-medium text-[#FF9F0A]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#FF9F0A] mr-1.5"></span> Tạm dừng
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-[11px] font-medium text-[#FF453A]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#FF453A] mr-1.5"></span> Cần Login lại
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-right space-x-2">
                                <button 
                                    type="button" 
                                    wire:click="testBrowserProfile({{ $channel->id }})" 
                                    class="px-2 py-1 rounded-lg bg-[#242426] hover:bg-[#2C2C2E] text-[#A1A1A6] font-medium transition-colors">
                                    Test Profile
                                </button>
                                <button 
                                    type="button" 
                                    wire:click="$dispatch('open-channel-modal', { id: {{ $channel->id }} })" 
                                    class="px-2 py-1 rounded-lg bg-[#0A84FF]/15 hover:bg-[#0A84FF]/25 text-[#0A84FF] font-medium transition-colors">
                                    Sửa
                                </button>
                                <button 
                                    type="button" 
                                    wire:confirm="Bạn có chắc chắn muốn xóa kênh này?" 
                                    wire:click="deleteChannel({{ $channel->id }})" 
                                    class="px-2 py-1 rounded-lg bg-[#FF453A]/15 hover:bg-[#FF453A]/25 text-[#FF453A] font-medium transition-colors">
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

    <!-- Modal Form Component -->
    <livewire:channels.channel-form-modal />
</div>
