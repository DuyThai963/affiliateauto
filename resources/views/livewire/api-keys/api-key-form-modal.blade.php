<!-- [Design Read]: Apple HIG inspired modal dialog for API key provisioning with clean input fields, discrete focus borders, and accessible labels. -->
<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/25 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-[#FFFFFF] border border-[#E5E5EA] text-left shadow-apple-modal transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-[#E5E5EA] flex items-center justify-between bg-[#FBFBFD]">
                        <h3 class="text-sm font-bold text-[#1D1D1F]">
                            {{ $keyId ? 'Cập Nhật API Key' : 'Thêm API Key Mới' }}
                        </h3>
                        <button type="button" wire:click="closeModal" class="w-6 h-6 rounded-full bg-[#E5E5EA]/60 flex items-center justify-center text-[#86868B] hover:text-[#1D1D1F] text-xs">
                            ✕
                        </button>
                    </div>

                    <!-- Form -->
                    <form wire:submit="save">
                        <div class="px-6 py-5 space-y-4">
                            <!-- Provider -->
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                    Nhà Cung Cấp LLM
                                </label>
                                <select wire:model="provider" class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                    <option value="gemini">Google Gemini (Gemini 1.5 Flash / Pro)</option>
                                    <option value="claude">Anthropic Claude (Claude 3.5 Sonnet / Haiku)</option>
                                    <option value="openai">OpenAI (GPT-4o / GPT-4o-mini)</option>
                                </select>
                                @error('provider') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Key Name -->
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                    Tên Gợi Nhớ Key
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="key_name" 
                                    placeholder="Gemini Flash 01" 
                                    class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] placeholder-[#86868B] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                @error('key_name') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- API Key Secret -->
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                    Mã Khóa (API Key Token)
                                </label>
                                <input 
                                    type="password" 
                                    wire:model="api_key" 
                                    placeholder="AIzaSy..." 
                                    class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs font-mono text-[#1D1D1F] placeholder-[#86868B] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                @error('api_key') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Limits & Cooldown -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                        Hạn Mức Ngày (Requests)
                                    </label>
                                    <input 
                                        type="number" 
                                        wire:model="daily_limit" 
                                        min="1" 
                                        class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                    @error('daily_limit') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#86868B] mb-1">
                                        Cooldown (Giây)
                                    </label>
                                    <input 
                                        type="number" 
                                        wire:model="cooldown_seconds" 
                                        min="0" 
                                        class="w-full px-3 py-2 bg-[#F5F5F7] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] focus:outline-none focus:bg-[#FFFFFF] focus:border-[#0071E3]">
                                    @error('cooldown_seconds') <span class="text-xs text-[#FF3B30] mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Active Switch Checkbox -->
                            <div class="pt-1">
                                <label class="flex items-center space-x-2.5 cursor-pointer">
                                    <input type="checkbox" wire:model="is_active" class="w-4 h-4 rounded text-[#0071E3] border-[#E5E5EA] focus:ring-[#0071E3]">
                                    <span class="text-xs font-medium text-[#1D1D1F]">Kích hoạt Key này vào vòng quay tự động</span>
                                </label>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-[#E5E5EA] flex items-center justify-end space-x-3 bg-[#FBFBFD]">
                            <button 
                                type="button" 
                                wire:click="closeModal" 
                                class="px-4 py-2 rounded-xl text-xs font-medium bg-[#E5E5EA] hover:bg-[#D1D1D6] text-[#1D1D1F] transition-colors">
                                Hủy Bỏ
                            </button>
                            <button 
                                type="submit" 
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#0071E3] hover:bg-[#0077ED] text-white transition-all shadow-apple-btn">
                                Lưu API Key
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
