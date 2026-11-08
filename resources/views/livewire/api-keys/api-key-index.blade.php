<!-- [Design Read]: Modular card grid showcasing AI Provider key quotas with progress bars, status badges, and quick toggle actions. -->
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-white">Quản Lý & Xoay Vòng API Keys</h2>
            <p class="text-sm text-slate-400 mt-1">
                Tối ưu hóa hạn mức Token miễn phí hàng ngày (Quota Maximizer) từ Gemini, Claude và OpenAI.
            </p>
        </div>
        <button 
            type="button" 
            wire:click="$dispatch('open-api-key-modal')" 
            class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm API Key
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

    <!-- Provider Filter Tabs -->
    <div class="flex items-center space-x-2 border-b border-slate-800 pb-3">
        <button 
            type="button" 
            wire:click="$set('providerFilter', '')" 
            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $providerFilter === '' ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900' }}">
            Tất cả nhà cung cấp
        </button>
        <button 
            type="button" 
            wire:click="$set('providerFilter', 'gemini')" 
            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $providerFilter === 'gemini' ? 'bg-blue-950 text-blue-300 border border-blue-800' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900' }}">
            Google Gemini
        </button>
        <button 
            type="button" 
            wire:click="$set('providerFilter', 'claude')" 
            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $providerFilter === 'claude' ? 'bg-amber-950 text-amber-300 border border-amber-800' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900' }}">
            Anthropic Claude
        </button>
        <button 
            type="button" 
            wire:click="$set('providerFilter', 'openai')" 
            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $providerFilter === 'openai' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900' }}">
            OpenAI GPT
        </button>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse ($apiKeys as $key)
            @php
                $percentage = $key->daily_limit > 0 ? min(100, round(($key->used_today / $key->daily_limit) * 100)) : 0;
            @endphp
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-5 flex flex-col justify-between hover:border-slate-700 transition-colors">
                <div>
                    <!-- Top Provider Badge & Toggle -->
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold uppercase tracking-wider
                            {{ $key->provider === 'gemini' ? 'bg-blue-950 text-blue-300 border border-blue-800' : ($key->provider === 'claude' ? 'bg-amber-950 text-amber-300 border border-amber-800' : 'bg-emerald-950 text-emerald-300 border border-emerald-800') }}">
                            {{ $key->provider }}
                        </span>

                        <button 
                            type="button" 
                            wire:click="toggleActive({{ $key->id }})" 
                            class="text-xs font-medium px-2 py-0.5 rounded transition-colors {{ $key->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-slate-800 text-slate-500' }}">
                            {{ $key->is_active ? '● Đang dùng' : '○ Tắt' }}
                        </button>
                    </div>

                    <!-- Key Name -->
                    <h3 class="text-base font-bold text-white mt-3">{{ $key->key_name }}</h3>
                    <div class="text-xs font-mono text-slate-500 mt-0.5">
                        {{ Str::mask($key->api_key, '*', 6, -4) }}
                    </div>

                    <!-- Quota Progress Bar -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-slate-400">Đã tiêu thụ hôm nay</span>
                            <span class="font-mono text-white font-semibold">{{ $key->used_today }} / {{ $key->daily_limit }}</span>
                        </div>
                        <div class="w-full h-2 bg-slate-950 rounded-full overflow-hidden border border-slate-800">
                            <div 
                                class="h-full transition-all {{ $percentage > 85 ? 'bg-rose-500' : ($percentage > 60 ? 'bg-amber-500' : 'bg-emerald-500') }}" 
                                style="width: {{ $percentage }}%">
                            </div>
                        </div>
                    </div>

                    <!-- Meta specs -->
                    <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t border-slate-800/80 text-xs text-slate-400">
                        <div>Cooldown: <span class="text-slate-200 font-mono">{{ $key->cooldown_seconds }}s</span></div>
                        <div class="text-right truncate">
                            Gọi cuối: 
                            <span class="text-slate-200">{{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Chưa dùng' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card Actions Footer -->
                <div class="mt-5 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                    <button 
                        type="button" 
                        wire:click="resetUsage({{ $key->id }})" 
                        class="text-slate-400 hover:text-slate-200 transition-colors">
                        ↺ Reset Quota
                    </button>
                    <div class="space-x-2">
                        <button 
                            type="button" 
                            wire:click="$dispatch('open-api-key-modal', { id: {{ $key->id }} })" 
                            class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 transition-colors">
                            Sửa
                        </button>
                        <button 
                            type="button" 
                            wire:confirm="Bạn có chắc chắn muốn xóa API Key này?" 
                            wire:click="deleteKey({{ $key->id }})" 
                            class="px-2.5 py-1 rounded bg-rose-950/70 hover:bg-rose-900 text-rose-300 transition-colors">
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full p-8 text-center bg-slate-900 border border-slate-800 rounded-xl text-slate-500 text-sm">
                Chưa có API Key nào. Bấm "Thêm API Key" để kích hoạt cơ chế vắt sạch hạn mức!
            </div>
        @endforelse
    </div>

    <!-- Modal Form Component -->
    <livewire:api-keys.api-key-form-modal />
</div>

