<!-- [Design Read]: Apple HIG macOS Console & Terminal Dark Mode interface with deep #0D0D0E canvas, studio-grade #18181B cards, JetBrains Mono log stream, colored semantic tags, and real-time schedule stats. -->
<div class="space-y-6">
    <!-- Top Stats Cards: Giám sát lịch trình thời gian thực -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Card 1: Trạng thái Kênh -->
        <div class="p-5 rounded-2xl bg-[#18181B] border border-[#26262A] shadow-apple-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#8E8E93]">Kênh Đang Hoạt Động</span>
                <span class="w-2 h-2 rounded-full bg-[#30D158] shadow-[0_0_8px_rgba(48,209,88,0.5)]"></span>
            </div>
            <div class="flex items-baseline space-x-2">
                <span class="text-2xl font-bold text-[#F5F5F7] tracking-tight">{{ $activeChannelsCount }}</span>
                <span class="text-xs text-[#8E8E93]">/ {{ $totalChannelsCount }} kênh</span>
            </div>
            <div class="mt-2 text-[11px] text-[#8E8E93]">
                Kênh có trạng thái <span class="text-[#30D158] font-medium">Active</span> mới được quét sinh video.
            </div>
            @if ($activeChannelsCount === 0 && $totalChannelsCount > 0)
                <div class="mt-2.5">
                    <button 
                        type="button" 
                        wire:click="activateAllChannels"
                        title="Bấm để kích hoạt trạng thái Active cho kênh thử nghiệm ngay"
                        class="w-full py-1.5 px-2 rounded-xl bg-[#30D158]/20 hover:bg-[#30D158]/30 text-[#30D158] border border-[#30D158]/40 text-[11px] font-semibold transition-all shadow-apple-btn active:scale-[0.98]">
                        ⚡ Kích Hoạt Kênh Test Ngay
                    </button>
                </div>
            @endif
        </div>

        <!-- Card 2: Kho đệm Buffer Vault -->
        <div class="p-5 rounded-2xl bg-[#18181B] border border-[#26262A] shadow-apple-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#8E8E93]">Trong Kho Đệm</span>
                <span class="w-2 h-2 rounded-full bg-[#0A84FF]"></span>
            </div>
            <div class="flex items-baseline space-x-2">
                <span class="text-2xl font-bold text-[#F5F5F7] tracking-tight">{{ $readyInVaultCount }}</span>
                <span class="text-xs text-[#8E8E93]">video sẵn sàng</span>
            </div>
            <div class="mt-2 text-[11px] text-[#8E8E93]">
                Mục tiêu: Duy trì 10 video đệm/kênh để không đứt lịch.
            </div>
        </div>

        <!-- Card 3: Lịch xuất bản hôm nay -->
        <div class="p-5 rounded-2xl bg-[#18181B] border border-[#26262A] shadow-apple-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#8E8E93]">Lịch Hẹn Hôm Nay</span>
                <span class="w-2 h-2 rounded-full bg-[#FF9F0A]"></span>
            </div>
            <div class="flex items-baseline space-x-2">
                <span class="text-2xl font-bold text-[#F5F5F7] tracking-tight">{{ $scheduledTodayCount }}</span>
                <span class="text-xs text-[#8E8E93]">video chờ giờ vàng</span>
            </div>
            <div class="mt-2 text-[11px] text-[#8E8E93]">
                Khung giờ vàng tự nhiên: 07:30 - 22:30 (±Jitter).
            </div>
        </div>

        <!-- Card 4: Lần Cron chạy gần nhất -->
        <div class="p-5 rounded-2xl bg-[#18181B] border border-[#26262A] shadow-apple-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#8E8E93]">Cronjob Tick Gần Nhất</span>
                <svg class="w-4 h-4 text-[#8E8E93]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="text-sm font-mono font-medium text-[#F5F5F7] truncate">
                {{ $lastCronTick }}
            </div>
            <div class="mt-2 text-[11px] text-[#8E8E93]">
                Được gọi tự động mỗi 5 phút từ <span class="text-[#F5F5F7]">cron-job.org</span>.
            </div>
        </div>
    </div>

    <!-- Thông báo kết quả thao tác nhanh (nếu có) -->
    @if (!empty($lastTickMessage))
        <div class="p-4 rounded-xl bg-[#1C1C1E] border border-[#30D158]/30 flex items-center justify-between text-xs text-[#F5F5F7] shadow-apple-card">
            <div class="flex items-center space-x-2">
                <span>{{ $lastTickMessage }}</span>
            </div>
            <button type="button" wire:click="$set('lastTickMessage', '')" class="text-[#8E8E93] hover:text-[#FFFFFF]">&times;</button>
        </div>
    @endif

    @if (!empty($simulationStatus))
        <div class="p-4 rounded-xl bg-[#1C1C1E] border border-[#0A84FF]/30 flex items-center justify-between text-xs text-[#F5F5F7] shadow-apple-card">
            <div class="flex items-center space-x-2">
                <span>{{ $simulationStatus }}</span>
            </div>
            <button type="button" wire:click="$set('simulationStatus', '')" class="text-[#8E8E93] hover:text-[#FFFFFF]">&times;</button>
        </div>
    @endif

    <!-- Action Toolbar (Điều khiển kiểm thử & Tiện ích Log) -->
    <div class="p-4 rounded-2xl bg-[#18181B] border border-[#26262A] shadow-apple-card flex flex-wrap items-center justify-between gap-4">
        <!-- Nhóm nút kích hoạt kiểm thử -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- Nút 0: Bật/Tắt Sandbox Test Mode (Chế độ chạy thử tự động) -->
            <button 
                type="button" 
                wire:click="toggleSandboxMode"
                title="Bật/Tắt Chế Độ Thử Nghiệm: Cho phép hệ thống tự chạy từ A-Z mà không cần nhập key AI & không cần đăng nhập TikTok/Shopee"
                class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-semibold transition-all active:scale-[0.98] border shadow-apple-btn {{ $sandboxMode ? 'bg-[#30D158]/15 border-[#30D158]/40 text-[#30D158]' : 'bg-[#242426] border-[#38383A] text-[#8E8E93]' }}">
                <span class="w-2 h-2 rounded-full mr-2 {{ $sandboxMode ? 'bg-[#30D158] shadow-[0_0_8px_rgba(48,209,88,0.6)] animate-pulse' : 'bg-[#8E8E93]' }}"></span>
                <span>Sandbox Mode: {{ $sandboxMode ? 'BẬT (Tự chạy không cần Key)' : 'TẮT (Yêu cầu Key & Cookie)' }}</span>
            </button>

            <!-- Nút 1: Kích hoạt Cron Tick thủ công -->
            <button 
                type="button" 
                wire:click="triggerCronTick" 
                wire:loading.attr="disabled"
                class="inline-flex items-center px-4 py-2 rounded-xl bg-[#FFFFFF] hover:bg-[#EBEBED] text-[#000000] text-xs font-semibold shadow-apple-btn transition-all active:scale-[0.98] disabled:opacity-50">
                <svg wire:loading.remove wire:target="triggerCronTick" class="w-4 h-4 mr-1.5 text-[#000000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span wire:loading wire:target="triggerCronTick" class="inline-block w-4 h-4 mr-1.5 border-2 border-[#000000] border-t-transparent rounded-full animate-spin"></span>
                <span>Chạy Thử Cron Ngay (Trigger Tick)</span>
            </button>

            <!-- Nút 2: Mô phỏng toàn bộ luồng tạo video & Báo cáo Telegram -->
            <button 
                type="button" 
                wire:click="simulateTestPipeline" 
                wire:loading.attr="disabled"
                title="Tạo 1 video test mô phỏng để kiểm tra toàn bộ luồng thông báo về Telegram"
                class="inline-flex items-center px-4 py-2 rounded-xl bg-[#242426] hover:bg-[#2E2E32] text-[#F5F5F7] border border-[#38383A] text-xs font-medium transition-all active:scale-[0.98] disabled:opacity-50">
                <svg wire:loading.remove wire:target="simulateTestPipeline" class="w-4 h-4 mr-1.5 text-[#0A84FF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                <span wire:loading wire:target="simulateTestPipeline" class="inline-block w-4 h-4 mr-1.5 border-2 border-[#0A84FF] border-t-transparent rounded-full animate-spin"></span>
                <span>Chạy Thử Mô Phỏng Luồng (Test Pipeline)</span>
            </button>
        </div>

        <!-- Nhóm công cụ Log: Copy gọn tức thì, Xem lỗi, Xóa log, Lọc -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Nút 3: Sao chép riêng lỗi gọn gàng (Instant Synchronous Copy + Preview Modal) -->
            <div x-data="{ 
                copied: false,
                showModal: false,
                copyText() {
                    const el = this.$refs.cleanSource;
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(el.value).then(() => {
                            this.copied = true;
                            setTimeout(() => { this.copied = false; }, 3000);
                        }).catch(() => {
                            this.fallbackCopy();
                        });
                    } else {
                        this.fallbackCopy();
                    }
                },
                fallbackCopy() {
                    const el = this.$refs.cleanSource;
                    el.focus();
                    el.select();
                    try {
                        document.execCommand('copy');
                        this.copied = true;
                        setTimeout(() => { this.copied = false; }, 3000);
                    } catch (e) {
                        console.error('Không thể copy:', e);
                    }
                }
            }" class="relative">
                <!-- Textarea ẩn chứa sẵn toàn bộ lỗi sạch đã trích xuất từ server -->
                <textarea x-ref="cleanSource" class="sr-only" readonly>{{ $cleanErrorsText }}</textarea>

                <div class="inline-flex items-center rounded-xl bg-[#1C1C1E] border border-[#38383A] shadow-apple-btn overflow-hidden">
                    <!-- Nút bấm Copy Ngay -->
                    <button 
                        type="button" 
                        @click="copyText()"
                        title="Sao chép toàn bộ thông báo lỗi sạch vào clipboard (đã lọc sạch stack trace rác)"
                        class="inline-flex items-center px-3.5 py-2 text-xs font-medium transition-all active:scale-[0.98]"
                        :class="copied ? 'bg-[#30D158]/20 text-[#30D158]' : 'text-[#F5F5F7] hover:bg-[#2C2C2E]'">
                        
                        <template x-if="!copied">
                            <span class="inline-flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-[#BF5AF2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                </svg>
                                <span>Copy Log Lỗi Gọn</span>
                            </span>
                        </template>

                        <template x-if="copied">
                            <span class="inline-flex items-center text-[#30D158] font-semibold">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-[#30D158]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>✓ Đã Copy Lỗi Gọn!</span>
                            </span>
                        </template>
                    </button>

                    <!-- Nút Mắt Xem Trước Lỗi Gọn -->
                    <button 
                        type="button" 
                        @click="showModal = true"
                        title="Xem nhanh toàn bộ nội dung lỗi sạch trên màn hình"
                        class="px-2.5 py-2 border-l border-[#38383A] text-[#8E8E93] hover:text-[#FFFFFF] hover:bg-[#2C2C2E] transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>

                <!-- Modal Bảng Xem Lỗi Gọn Trực Tiếp -->
                <div 
                    x-show="showModal" 
                    x-cloak
                    style="display: none;"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                    <div @click.away="showModal = false" class="w-full max-w-2xl bg-[#18181B] border border-[#26262A] rounded-2xl shadow-apple-modal overflow-hidden flex flex-col max-h-[85vh]">
                        <div class="px-5 py-4 border-b border-[#26262A] flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 rounded-full bg-[#BF5AF2]"></span>
                                <h3 class="text-sm font-semibold text-[#F5F5F7]">Nội Dung Lỗi Đã Làm Sạch (Không Còn Rác)</h3>
                            </div>
                            <button type="button" @click="showModal = false" class="text-[#8E8E93] hover:text-[#F5F5F7] text-lg font-bold">&times;</button>
                        </div>
                        <div class="p-5 overflow-y-auto flex-1 font-mono text-xs leading-relaxed text-[#F5F5F7] bg-[#121214] select-all scrollbar-thin">
                            <pre class="whitespace-pre-wrap">{{ $cleanErrorsText }}</pre>
                        </div>
                        <div class="px-5 py-3 border-t border-[#26262A] bg-[#18181B] flex items-center justify-between">
                            <span class="text-xs text-[#8E8E93]">Nhấn nút bên phải hoặc bôi đen văn bản để copy.</span>
                            <div class="flex items-center space-x-2">
                                <button 
                                    type="button" 
                                    @click="copyText()" 
                                    class="px-4 py-2 rounded-xl bg-[#FFFFFF] hover:bg-[#EBEBED] text-black text-xs font-semibold shadow-apple-btn transition-all">
                                    <span x-text="copied ? '✓ Đã Copy!' : 'Sao Chép Lỗi'"></span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="showModal = false" 
                                    class="px-4 py-2 rounded-xl bg-[#242426] hover:bg-[#2C2C2E] text-[#F5F5F7] text-xs font-medium border border-[#38383A] transition-colors">
                                    Đóng
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nút 4: Xóa Log Sạch Sẽ -->
            <button 
                type="button" 
                wire:confirm="Bạn có chắc chắn muốn làm sạch toàn bộ file nhật ký laravel.log hiện tại?"
                wire:click="clearLogs" 
                wire:loading.attr="disabled"
                title="Làm sạch toàn bộ file nhật ký hệ thống"
                class="inline-flex items-center px-3.5 py-2 rounded-xl bg-[#242426] hover:bg-[#FF453A]/20 hover:text-[#FF453A] hover:border-[#FF453A]/40 text-[#8E8E93] border border-[#38383A] text-xs font-medium transition-all active:scale-[0.98]">
                <svg wire:loading.remove wire:target="clearLogs" class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span wire:loading wire:target="clearLogs" class="inline-block w-3.5 h-3.5 mr-1.5 border-2 border-[#FF453A] border-t-transparent rounded-full animate-spin"></span>
                <span>Xóa Log</span>
            </button>

            <!-- Bộ lọc mức log -->
            <div class="flex items-center space-x-1.5 pl-1">
                <span class="text-xs text-[#8E8E93]">Lọc:</span>
                <select wire:model.live="filterLevel" class="px-2.5 py-1.5 rounded-xl bg-[#242426] border border-[#38383A] text-xs text-[#F5F5F7] focus:outline-none focus:border-[#0A84FF]">
                    <option value="ALL">Tất cả (All)</option>
                    <option value="CRON">Chỉ tác vụ Cron</option>
                    <option value="ERROR">Chỉ báo lỗi (Errors)</option>
                    <option value="WARNING">Cảnh báo (Warnings)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Màn hình Console Terminal (macOS Dark Mode Log Stream) -->
    <div class="rounded-2xl bg-[#121214] border border-[#26262A] shadow-apple-card overflow-hidden">
        <!-- Header Thanh Console -->
        <div class="h-10 px-4 bg-[#18181B] border-b border-[#26262A] flex items-center justify-between select-none">
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full bg-[#FF5F56]/80"></span>
                <span class="w-3 h-3 rounded-full bg-[#FFBD2E]/80"></span>
                <span class="w-3 h-3 rounded-full bg-[#27C93F]/80"></span>
                <span class="ml-3 font-mono text-xs text-[#8E8E93]">storage/logs/laravel.log (250 dòng gần nhất)</span>
            </div>
            <div class="text-[11px] font-mono text-[#636366]">
                {{ count($logs) }} dòng hiển thị
            </div>
        </div>

        <!-- Log Content Box -->
        <div class="p-4 font-mono text-xs leading-relaxed max-h-[600px] overflow-y-auto space-y-1 scrollbar-thin">
            @forelse ($logs as $logItem)
                <div class="flex items-start space-x-2.5 py-0.5 hover:bg-[#1C1C1E]/50 px-2 rounded transition-colors">
                    @if ($logItem['level'] === 'ERROR')
                        <span class="shrink-0 px-1.5 py-0.2 rounded text-[10px] font-bold bg-[#FF453A]/20 text-[#FF453A] border border-[#FF453A]/30">ERR</span>
                    @elseif ($logItem['level'] === 'WARNING')
                        <span class="shrink-0 px-1.5 py-0.2 rounded text-[10px] font-bold bg-[#FF9F0A]/20 text-[#FF9F0A] border border-[#FF9F0A]/30">WARN</span>
                    @elseif ($logItem['level'] === 'DEBUG')
                        <span class="shrink-0 px-1.5 py-0.2 rounded text-[10px] font-bold bg-[#8E8E93]/20 text-[#8E8E93]">DBG</span>
                    @else
                        <span class="shrink-0 px-1.5 py-0.2 rounded text-[10px] font-bold bg-[#0A84FF]/20 text-[#0A84FF]">INFO</span>
                    @endif

                    <div class="text-[#F5F5F7] break-all">
                        {{ $logItem['raw'] }}
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-[#8E8E93]">
                    <svg class="w-8 h-8 mx-auto mb-2 text-[#636366]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Chưa có nhật ký nào hoặc bộ lọc không khớp. Hãy bấm nút "Chạy Thử Cron Ngay" để tạo log!
                </div>
            @endforelse
        </div>
    </div>
</div>
