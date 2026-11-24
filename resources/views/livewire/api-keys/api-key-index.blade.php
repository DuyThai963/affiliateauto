<div x-data="{ activeProvider: 'all' }" class="space-y-6 max-w-7xl mx-auto">
    <!-- [Design Read]: Apple HIG Dark Mode API Key manager with #18181B cards, #26262A borders, macOS segmented control, and battery-style quota meters. Instant zero-latency client switching with smooth skeleton loading frames. -->
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-[#F5F5F7]">Cấu Hình API Keys</h2>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Quản lý xoay vòng hạn mức Token miễn phí hàng ngày (Quota Maximizer) từ Gemini, Claude và OpenAI.
            </p>
        </div>
        <button 
            type="button" 
            wire:click="$dispatch('open-api-key-modal')" 
            class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-[#0A84FF] hover:bg-[#0077ED] text-white transition-all shadow-apple-btn active:scale-[0.98]">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm API Key
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

    <!-- Segmented Filter Control (Instant Zero-Latency macOS Dark Switch) -->
    <div class="inline-flex p-1 rounded-2xl bg-[#18181B] border border-[#26262A] space-x-1 select-none">
        <button 
            type="button" 
            @click="activeProvider = 'all'" 
            :class="activeProvider === 'all' ? 'bg-[#242426] text-[#F5F5F7] font-semibold shadow-apple-card' : 'text-[#8E8E93] hover:text-[#F5F5F7]'"
            class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all">
            Tất cả nhà cung cấp
        </button>
        <button 
            type="button" 
            @click="activeProvider = 'gemini'" 
            :class="activeProvider === 'gemini' ? 'bg-[#242426] text-[#0A84FF] font-semibold shadow-apple-card' : 'text-[#8E8E93] hover:text-[#F5F5F7]'"
            class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all">
            Google Gemini
        </button>
        <button 
            type="button" 
            @click="activeProvider = 'claude'" 
            :class="activeProvider === 'claude' ? 'bg-[#242426] text-[#FF9F0A] font-semibold shadow-apple-card' : 'text-[#8E8E93] hover:text-[#F5F5F7]'"
            class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all">
            Anthropic Claude
        </button>
        <button 
            type="button" 
            @click="activeProvider = 'openai'" 
            :class="activeProvider === 'openai' ? 'bg-[#242426] text-[#30D158] font-semibold shadow-apple-card' : 'text-[#8E8E93] hover:text-[#F5F5F7]'"
            class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all">
            OpenAI GPT
        </button>
    </div>

    <!-- Loading Skeleton Frame (Shows during Livewire background network roundtrips) -->
    <div wire:loading.delay.shortest class="w-full">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div class="rounded-2xl border border-[#26262A] bg-[#18181B]/70 p-5 animate-pulse space-y-4">
                <div class="flex justify-between items-center">
                    <div class="h-4 bg-[#26262A] rounded-full w-20"></div>
                    <div class="h-4 bg-[#26262A] rounded-full w-16"></div>
                </div>
                <div class="h-5 bg-[#26262A] rounded w-36"></div>
                <div class="h-3 bg-[#26262A] rounded w-48"></div>
                <div class="h-2 bg-[#26262A] rounded-full w-full"></div>
            </div>
            <div class="rounded-2xl border border-[#26262A] bg-[#18181B]/70 p-5 animate-pulse space-y-4">
                <div class="flex justify-between items-center">
                    <div class="h-4 bg-[#26262A] rounded-full w-20"></div>
                    <div class="h-4 bg-[#26262A] rounded-full w-16"></div>
                </div>
                <div class="h-5 bg-[#26262A] rounded w-36"></div>
                <div class="h-3 bg-[#26262A] rounded w-48"></div>
                <div class="h-2 bg-[#26262A] rounded-full w-full"></div>
            </div>
        </div>
    </div>

    <!-- Cards Grid -->
    <div wire:loading.remove class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse ($apiKeys as $key)
            @php
                $percentage = $key->daily_limit > 0 ? min(100, round(($key->used_today / $key->daily_limit) * 100)) : 0;
            @endphp
            <div 
                x-show="activeProvider === 'all' || activeProvider === '{{ $key->provider }}'"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-[0.98]"
                x-transition:enter-end="opacity-100 scale-100"
                class="rounded-2xl border border-[#26262A] bg-[#18181B] p-5 shadow-apple-card flex flex-col justify-between hover:border-[#38383A] transition-colors">
                <div>
                    <!-- Top Provider Tag & Status Switch -->
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                            {{ $key->provider === 'gemini' ? 'bg-[#0A84FF]/15 text-[#0A84FF] border border-[#0A84FF]/25' : ($key->provider === 'claude' ? 'bg-[#FF9F0A]/15 text-[#FF9F0A] border border-[#FF9F0A]/25' : 'bg-[#30D158]/15 text-[#30D158] border border-[#30D158]/25') }}">
                            {{ $key->provider }}
                        </span>

                        <button 
                            type="button" 
                            wire:click="toggleActive({{ $key->id }})" 
                            class="text-[11px] font-medium px-2 py-0.5 rounded-full transition-colors {{ $key->is_active ? 'bg-[#30D158]/15 text-[#30D158] border border-[#30D158]/25' : 'bg-[#242426] text-[#8E8E93]' }}">
                            {{ $key->is_active ? '● Đang dùng' : '○ Đã tắt' }}
                        </button>
                    </div>

                    <!-- Key Name -->
                    <h3 class="text-sm font-bold text-[#F5F5F7] mt-3">{{ $key->key_name }}</h3>
                    <div class="text-[11px] font-mono text-[#8E8E93] mt-0.5">
                        {{ Str::mask($key->api_key, '•', 6, -4) }}
                    </div>

                    <!-- Quota Meter (iOS Battery Style) -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-[11px] mb-1.5">
                            <span class="text-[#8E8E93]">Đã dùng hôm nay</span>
                            <span class="font-mono text-[#F5F5F7] font-semibold">{{ $key->used_today }} / {{ $key->daily_limit }}</span>
                        </div>
                        <div class="w-full h-2 bg-[#26262A] rounded-full overflow-hidden">
                            <div 
                                class="h-full rounded-full transition-all {{ $percentage > 85 ? 'bg-[#FF453A]' : ($percentage > 60 ? 'bg-[#FF9F0A]' : 'bg-[#30D158]') }}" 
                                style="width: {{ $percentage }}%">
                            </div>
                        </div>
                    </div>

                    <!-- Meta specs -->
                    <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t border-[#26262A] text-[11px] text-[#8E8E93]">
                        <div>Cooldown: <span class="text-[#F5F5F7] font-mono">{{ $key->cooldown_seconds }}s</span></div>
                        <div class="text-right truncate">
                            Gọi cuối: 
                            <span class="text-[#F5F5F7]">{{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Chưa dùng' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="mt-4 pt-3 border-t border-[#26262A] flex items-center justify-between text-xs">
                    <button 
                        type="button" 
                        wire:click="resetUsage({{ $key->id }})" 
                        class="text-[11px] text-[#8E8E93] hover:text-[#0A84FF] transition-colors">
                        ↺ Reset Quota
                    </button>
                    <div class="space-x-2">
                        <button 
                            type="button" 
                            wire:click="$dispatch('open-api-key-modal', { id: {{ $key->id }} })" 
                            class="px-2 py-1 rounded-lg bg-[#242426] hover:bg-[#2C2C2E] text-[#0A84FF] text-[11px] font-medium transition-colors">
                            Sửa
                        </button>
                        <button 
                            type="button" 
                            wire:confirm="Bạn có chắc chắn muốn xóa API Key này?" 
                            wire:click="deleteKey({{ $key->id }})" 
                            class="px-2 py-1 rounded-lg bg-[#FF453A]/15 hover:bg-[#FF453A]/25 text-[#FF453A] text-[11px] font-medium transition-colors">
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full p-8 text-center bg-[#18181B] border border-[#26262A] rounded-2xl text-[#8E8E93] text-xs shadow-apple-card">
                Chưa có API Key nào. Bấm "Thêm API Key" để bắt đầu nạp key!
            </div>
        @endforelse

        @if ($apiKeys->isNotEmpty())
            @php
                $configuredProviders = $apiKeys->pluck('provider')->unique()->toArray();
            @endphp
            <div 
                x-show="activeProvider !== 'all' && !{{ json_encode($configuredProviders) }}.includes(activeProvider)"
                class="col-span-full p-8 text-center bg-[#18181B] border border-[#26262A] rounded-2xl text-[#8E8E93] text-xs shadow-apple-card">
                Chưa có API Key nào cho nhà cung cấp này. Bấm "Thêm API Key" để cấu hình!
            </div>
        @endif
    </div>

    <!-- Modal Form Component -->
    <livewire:api-keys.api-key-form-modal />
</div>
