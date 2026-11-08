<!-- [Design Read]: Dense administrative table on dark slate with crisp semantic color chips (emerald for growth, violet for affiliate) and immediate search/filter controls. -->
<div class="space-y-6">
    <!-- Page Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-white">Quản lý Kênh Phân Phối</h2>
            <p class="text-sm text-slate-400 mt-1">Cấu hình các kênh TikTok & Shopee, phân loại kênh Kéo View (Growth) hoặc Gắn Giỏ (Affiliate).</p>
        </div>
        <button 
            type="button" 
            wire:click="$dispatch('open-channel-modal')" 
            class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm Kênh Mới
        </button>
    </div>

    <!-- Notification Toast/Alert -->
    @if ($feedbackMessage)
        <div class="p-4 rounded-lg bg-emerald-950/80 border border-emerald-800/80 text-emerald-200 text-sm flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span>✓</span>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button type="button" wire:click="$set('feedbackMessage', null)" class="text-emerald-400 hover:text-emerald-200 text-sm">✕</button>
        </div>
    @endif

    <!-- Filter & Search Controls -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="w-full md:w-96 relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Tìm theo tên kênh hoặc đường dẫn profile..." 
                class="w-full pl-10 pr-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
            <div class="absolute left-3 top-2.5 text-slate-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div class="flex items-center space-x-3 w-full md:w-auto">
            <select wire:model.live="platformFilter" class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-300 focus:outline-none focus:border-emerald-500">
                <option value="">Tất cả Nền tảng</option>
                <option value="tiktok">TikTok</option>
                <option value="shopee">Shopee</option>
            </select>

            <select wire:model.live="typeFilter" class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-300 focus:outline-none focus:border-emerald-500">
                <option value="">Tất cả Loại kênh</option>
                <option value="growth">Growth (Kéo view)</option>
                <option value="affiliate">Affiliate (Gắn giỏ)</option>
            </select>
        </div>
    </div>

    <!-- Channels Data Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/70 text-xs uppercase font-semibold text-slate-400 tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">Nền tảng</th>
                        <th class="px-5 py-3.5">Tên Kênh</th>
                        <th class="px-5 py-3.5">Loại Kênh</th>
                        <th class="px-5 py-3.5">Chỉ tiêu / Giãn cách</th>
                        <th class="px-5 py-3.5">Lần đăng cuối</th>
                        <th class="px-5 py-3.5">Trạng thái</th>
                        <th class="px-5 py-3.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse ($channels as $channel)
                        <tr class="hover:bg-slate-850/60 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if ($channel->platform === 'tiktok')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-950 text-rose-300 border border-rose-800">
                                        TikTok
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-950 text-amber-300 border border-amber-800">
                                        Shopee
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-semibold text-white">{{ $channel->channel_name }}</div>
                                <div class="text-xs text-slate-500 font-mono mt-0.5 truncate max-w-xs" title="{{ $channel->browser_profile_path }}">
                                    {{ $channel->browser_profile_path }}
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if ($channel->type === 'growth')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-950 text-emerald-300 border border-emerald-800">
                                        🌱 Growth (Kéo View)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-950 text-purple-300 border border-purple-800">
                                        🛒 Affiliate (Gắn Giỏ)
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap font-mono text-xs text-slate-300">
                                <div><span class="text-white font-semibold">{{ $channel->daily_post_limit }}</span> video/ngày</div>
                                <div class="text-slate-500">Giãn cách: {{ $channel->min_interval_minutes }}p</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-400">
                                {{ $channel->last_posted_at ? $channel->last_posted_at->diffForHumans() : 'Chưa đăng lần nào' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if ($channel->status === 'active')
                                    <span class="inline-flex items-center text-xs font-medium text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5"></span> Hoạt động
                                    </span>
                                @elseif ($channel->status === 'paused')
                                    <span class="inline-flex items-center text-xs font-medium text-amber-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5"></span> Tạm dừng
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-xs font-medium text-rose-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 mr-1.5"></span> Cần Login lại
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right space-x-2 text-xs">
                                <button 
                                    type="button" 
                                    wire:click="testBrowserProfile({{ $channel->id }})" 
                                    class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                                    Test Profile
                                </button>
                                <button 
                                    type="button" 
                                    wire:click="$dispatch('open-channel-modal', { id: {{ $channel->id }} })" 
                                    class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 transition-colors">
                                    Sửa
                                </button>
                                <button 
                                    type="button" 
                                    wire:confirm="Bạn có chắc chắn muốn xóa kênh này?" 
                                    wire:click="deleteChannel({{ $channel->id }})" 
                                    class="px-2 py-1 rounded bg-rose-950/70 hover:bg-rose-900 text-rose-300 transition-colors">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                                Chưa có kênh nào được cấu hình. Bấm "Thêm Kênh Mới" để bắt đầu!
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

