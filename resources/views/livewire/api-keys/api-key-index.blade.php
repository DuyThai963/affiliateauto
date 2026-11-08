<!-- [Design Read]: Apple HIG inspired API Key quota manager with macOS segmented tab controls, minimal quota meters, and refined action sheets. -->
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-[#1D1D1F]">Cấu Hình API Keys</h2>
            <p class="text-xs text-[#86868B] mt-0.5">
                Quản lý xoay vòng hạn mức Token miễn phí hàng ngày (Quota Maximizer) từ Gemini, Claude và OpenAI.
            </p>
        </div>
        <button 
            type="button" 
            wire:click="$dispatch('open-api-key-modal')" 
            class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-[#0071E3] hover:bg-[#0077ED] text-white transition-all shadow-apple-btn active:scale-[0.98]">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm API Key
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

    <!-- Segmented Filter Control (macOS Style) -->
    <div class="inline-flex p-1 rounded-2xl bg-[#E5E5EA]/70 space-x-1">
        <button 
            type="button" 
            wire:click="$set('providerFilter', '')" 
            class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all {{ $providerFilter === '' ? 'bg-[#FFFFFF] text-[#1D1D1F] font-semibold shadow-apple-card' : 'text-[#515154] hover:text-[#1D1D1F]' }}">
            Tất cả nhà cung cấp
        </button>
        <button 
            type="button" 
            wire:click="$set('providerFilter', 'gemini')" 
            class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all {{ $providerFilter === 'gemini' ? 'bg-[#FFFFFF] text-[#0071E3] font-semibold shadow-apple-card' : 'text-[#515154] hover:text-[#1D1D1F]' }}">
            Google Gemini
        </button>
        <button 
            type="button" 
            wire:click="$set('providerFilter', 'claude')" 
            class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all {{ $providerFilter === 'claude' ? 'bg-[#FFFFFF] text-[#FF9500] font-semibold shadow-apple-card' : 'text-[#515154] hover:text-[#1D1D1F]' }}">
            Anthropic Claude
        </button>
        <button 
            type="button" 
            wire:click="$set('providerFilter', 'openai')" 
            class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all {{ $providerFilter === 'openai' ? 'bg-[#FFFFFF] text-[#34C759] font-semibold shadow-apple-card' : 'text-[#515154] hover:text-[#1D1D1F]' }}">
            OpenAI GPT
        </button>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse ($apiKeys as $key)
            @php
                $percentage = $key->daily_limit > 0 ? min(100, round(($key->used_today / $key->daily_limit) * 100)) : 0;
            @endphp
            <div class="rounded-2xl border border-[#E5E5EA] bg-[#FFFFFF] p-5 shadow-apple-card flex flex-col justify-between hover:border-[#AEAEB2] transition-colors">
                <div>
                    <!-- Top Provider Tag & Status Switch -->
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                            {{ $key->provider === 'gemini' ? 'bg-[#E3F2FD] text-[#0071E3]' : ($key->provider === 'claude' ? 'bg-[#FFF3E0] text-[#E65100]' : 'bg-[#E8F5E9] text-[#2E7D32]') }}">
                            {{ $key->provider }}
                        </span>

                        <button 
                            type="button" 
                            wire:click="toggleActive({{ $key->id }})" 
                            class="text-[11px] font-medium px-2 py-0.5 rounded-full transition-colors {{ $key->is_active ? 'bg-[#E8F5E9] text-[#2E7D32]' : 'bg-[#F5F5F7] text-[#86868B]' }}">
                            {{ $key->is_active ? '● Đang dùng' : '○ Đã tắt' }}
                        </button>
                    </div>

                    <!-- Key Name -->
                    <h3 class="text-sm font-bold text-[#1D1D1F] mt-3">{{ $key->key_name }}</h3>
                    <div class="text-[11px] font-mono text-[#86868B] mt-0.5">
                        {{ Str::mask($key->api_key, '•', 6, -4) }}
                    </div>

                    <!-- Quota Meter (iOS Battery Style) -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-[11px] mb-1.5">
                            <span class="text-[#86868B]">Đã dùng hôm nay</span>
                            <span class="font-mono text-[#1D1D1F] font-semibold">{{ $key->used_today }} / {{ $key->daily_limit }}</span>
                        </div>
                        <div class="w-full h-2 bg-[#E5E5EA] rounded-full overflow-hidden">
                            <div 
                                class="h-full rounded-full transition-all {{ $percentage > 85 ? 'bg-[#FF3B30]' : ($percentage > 60 ? 'bg-[#FF9500]' : 'bg-[#34C759]') }}" 
                                style="width: {{ $percentage }}%">
                            </div>
                        </div>
                    </div>

                    <!-- Meta specs -->
                    <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t border-[#F5F5F7] text-[11px] text-[#86868B]">
                        <div>Cooldown: <span class="text-[#1D1D1F] font-mono">{{ $key->cooldown_seconds }}s</span></div>
                        <div class="text-right truncate">
                            Gọi cuối: 
                            <span class="text-[#1D1D1F]">{{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Chưa dùng' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="mt-4 pt-3 border-t border-[#F5F5F7] flex items-center justify-between text-xs">
                    <button 
                        type="button" 
                        wire:click="resetUsage({{ $key->id }})" 
                        class="text-[11px] text-[#86868B] hover:text-[#0071E3] transition-colors">
                        ↺ Reset Quota
                    </button>
                    <div class="space-x-2">
                        <button 
                            type="button" 
                            wire:click="$dispatch('open-api-key-modal', { id: {{ $key->id }} })" 
                            class="px-2 py-1 rounded-lg bg-[#F5F5F7] hover:bg-[#E5E5EA] text-[#0071E3] text-[11px] font-medium transition-colors">
                            Sửa
                        </button>
                        <button 
                            type="button" 
                            wire:confirm="Bạn có chắc chắn muốn xóa API Key này?" 
                            wire:click="deleteKey({{ $key->id }})" 
                            class="px-2 py-1 rounded-lg bg-[#FFEBEE] hover:bg-[#FFCDD2] text-[#C62828] text-[11px] font-medium transition-colors">
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full p-8 text-center bg-[#FFFFFF] border border-[#E5E5EA] rounded-2xl text-[#86868B] text-xs shadow-apple-card">
                Chưa có API Key nào. Bấm "Thêm API Key" để bắt đầu nạp key!
            </div>
        @endforelse
    </div>

    <!-- Modal Form Component -->
    <livewire:api-keys.api-key-form-modal />
</div>
