<!-- [Design Read]: Modal dialog overlay with focused input controls for API credentials, quota values, and safety validation warnings. -->
<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-slate-900 border border-slate-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">
                            {{ $keyId ? 'Cập Nhật API Key' : 'Thêm API Key Mới' }}
                        </h3>
                        <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-200">
                            ✕
                        </button>
                    </div>

                    <!-- Form -->
                    <form wire:submit="save">
                        <div class="px-6 py-5 space-y-4">
                            <!-- Provider -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                    Nhà Cung Cấp LLM
                                </label>
                                <select wire:model="provider" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-emerald-500">
                                    <option value="gemini">Google Gemini (Gemini 1.5 Flash / Pro)</option>
                                    <option value="claude">Anthropic Claude (Claude 3.5 Sonnet / Haiku)</option>
                                    <option value="openai">OpenAI (GPT-4o / GPT-4o-mini)</option>
                                </select>
                                @error('provider') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Key Name -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                    Tên Gợi Nhớ Key
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="key_name" 
                                    placeholder="Gemini Pro Key 01" 
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-emerald-500">
                                @error('key_name') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- API Key Secret -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                    Chuỗi Khóa Bí Mật (API Key Token)
                                </label>
                                <input 
                                    type="password" 
                                    wire:model="api_key" 
                                    placeholder="AIzaSy..." 
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 font-mono placeholder-slate-600 focus:outline-none focus:border-emerald-500">
                                @error('api_key') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Limits & Cooldown -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                        Hạn Mức Ngày (Requests)
                                    </label>
                                    <input 
                                        type="number" 
                                        wire:model="daily_limit" 
                                        min="1" 
                                        class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-emerald-500">
                                    @error('daily_limit') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                        Cooldown (Giây)
                                    </label>
                                    <input 
                                        type="number" 
                                        wire:model="cooldown_seconds" 
                                        min="0" 
                                        class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-emerald-500">
                                    @error('cooldown_seconds') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Active Checkbox -->
                            <div class="pt-2">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" wire:model="is_active" class="w-4 h-4 rounded text-emerald-500 bg-slate-950 border-slate-800 focus:ring-emerald-500">
                                    <span class="text-sm font-medium text-slate-200">Kích hoạt Key này ngay lập tức vào vòng quay</span>
                                </label>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-slate-800 flex items-center justify-end space-x-3 bg-slate-950/40">
                            <button 
                                type="button" 
                                wire:click="closeModal" 
                                class="px-4 py-2 rounded-lg text-sm font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                                Hủy Bỏ
                            </button>
                            <button 
                                type="submit" 
                                class="px-4 py-2 rounded-lg text-sm font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-colors">
                                Lưu API Key
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

