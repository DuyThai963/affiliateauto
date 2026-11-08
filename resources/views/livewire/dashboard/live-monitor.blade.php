<!-- [Design Read]: Apple HIG inspired operational control center featuring frosted cards, minimalist typography metrics, subtle progress meters, and an Apple-blue pill action button. -->
<div wire:poll.3s class="space-y-6 max-w-7xl mx-auto">
    <!-- Top Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-[#1D1D1F]">Tổng Quan Điều Hành</h2>
            <p class="text-xs text-[#86868B] mt-0.5">
                Giám sát kho đệm Buffer Vault, tiến độ render kịch bản và xuất bản video tự động.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <button 
                type="button" 
                wire:click="generateTestVideo" 
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-[#0071E3] hover:bg-[#0077ED] text-white transition-all shadow-apple-btn active:scale-[0.98] disabled:opacity-50">
                <span wire:loading.remove wire:target="generateTestVideo" class="flex items-center">
                    <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    </svg>
                    Tạo Video Thử Nghiệm
                </span>
                <span wire:loading wire:target="generateTestVideo" class="flex items-center">
                    <svg class="animate-spin -ml-0.5 mr-2 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Đang chạy Mock Pipeline...
                </span>
            </button>
        </div>
    </div>

    <!-- Notification Toast / Alert -->
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

    <!-- Top KPI Metric Cards (Apple Cards Style) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Kênh hoạt động -->
        <div class="rounded-2xl border border-[#E5E5EA] bg-[#FFFFFF] p-5 shadow-apple-card flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#86868B]">
                <span class="text-[11px] font-semibold uppercase tracking-wider">Kênh Hoạt Động</span>
                <span class="w-7 h-7 rounded-lg bg-[#E5E5EA]/50 flex items-center justify-center text-[#1D1D1F]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-bold tracking-tight text-[#1D1D1F]">{{ $totalActiveChannels }}</span>
                <span class="text-xs text-[#86868B] ml-1">kênh</span>
            </div>
            <div class="mt-3 pt-3 border-t border-[#F5F5F7] flex items-center justify-between text-[11px] font-medium text-[#86868B]">
                <span class="text-[#2E7D32]">🌱 {{ $growthChannelsCount }} Growth</span>
                <span class="text-[#8E24AA]">🛒 {{ $affiliateChannelsCount }} Affiliate</span>
            </div>
        </div>

        <!-- Metric 2: Buffer Vault -->
        <div class="rounded-2xl border border-[#E5E5EA] bg-[#FFFFFF] p-5 shadow-apple-card flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#86868B]">
                <span class="text-[11px] font-semibold uppercase tracking-wider">Kho Đệm Vault</span>
                <span class="w-7 h-7 rounded-lg bg-[#E5E5EA]/50 flex items-center justify-center text-[#1D1D1F]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-bold tracking-tight text-[#1D1D1F]">{{ $vaultTasksCount }}</span>
                <span class="text-xs text-[#86868B] ml-1">video sẵn sàng</span>
            </div>
            <div class="mt-3 pt-3 border-t border-[#F5F5F7] flex items-center justify-between text-[11px] text-[#86868B]">
                <span>Trữ lượng:</span>
                <span class="font-mono text-[#0071E3] font-medium">storage/vault</span>
            </div>
        </div>

        <!-- Metric 3: Đã xuất bản hôm nay -->
        <div class="rounded-2xl border border-[#E5E5EA] bg-[#FFFFFF] p-5 shadow-apple-card flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#86868B]">
                <span class="text-[11px] font-semibold uppercase tracking-wider">Đã Đăng Hôm Nay</span>
                <span class="w-7 h-7 rounded-lg bg-[#E5E5EA]/50 flex items-center justify-center text-[#1D1D1F]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-bold tracking-tight text-[#1D1D1F]">{{ $publishedTodayCount }}</span>
                <span class="text-xs text-[#86868B] ml-1">/ {{ $totalDailyQuota }} video</span>
            </div>
            <div class="mt-3 pt-3 border-t border-[#F5F5F7] flex items-center justify-between text-[11px] text-[#86868B]">
                <span>Khung giờ vàng:</span>
                <span class="font-mono text-[#1D1D1F]">07:30 - 22:30</span>
            </div>
        </div>

        <!-- Metric 4: Token tiêu thụ -->
        <div class="rounded-2xl border border-[#E5E5EA] bg-[#FFFFFF] p-5 shadow-apple-card flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#86868B]">
                <span class="text-[11px] font-semibold uppercase tracking-wider">Hạn Mức LLM</span>
                <span class="w-7 h-7 rounded-lg bg-[#E5E5EA]/50 flex items-center justify-center text-[#1D1D1F]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-bold tracking-tight text-[#1D1D1F]">{{ $tokensConsumedToday }}</span>
                <span class="text-xs text-[#86868B] ml-1">/ {{ $totalTokensLimit > 0 ? $totalTokensLimit : '—' }} reqs</span>
            </div>
            <div class="mt-3 pt-3 border-t border-[#F5F5F7] flex items-center justify-between text-[11px] text-[#86868B]">
                <span>Xoay vòng:</span>
                <span class="text-[#2E7D32] font-medium">Auto Rotating</span>
            </div>
        </div>
    </div>

    <!-- Live Execution Status (macOS Window Style) -->
    <div class="rounded-2xl border border-[#E5E5EA] bg-[#FFFFFF] p-6 shadow-apple-card">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full {{ $activeTask ? 'bg-[#34C759] animate-pulse' : 'bg-[#AEAEB2]' }}"></span>
                <h3 class="text-sm font-bold text-[#1D1D1F]">Trạng Thái Xử Lý Trực Tiếp (Live Task Monitor)</h3>
            </div>
            <div class="text-[11px] font-mono text-[#86868B]">
                Auto-refresh: 3s
            </div>
        </div>

        @if ($activeTask)
            <div class="p-4 rounded-xl bg-[#F5F5F7] border border-[#E5E5EA] space-y-3">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-[#1D1D1F] text-sm">#{{ $activeTask->id }} - {{ $activeTask->title ?: 'Đang tạo...' }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#E8F5E9] text-[#2E7D32]">
                                {{ $activeTask->status }}
                            </span>
                        </div>
                        <div class="text-xs text-[#86868B] mt-1 flex items-center space-x-2">
                            <span>Kênh đích: <strong class="text-[#1D1D1F]">{{ $activeTask->channel?->channel_name ?: '—' }}</strong></span>
                            @if ($activeTask->product)
                                <span>• Sản phẩm: <strong class="text-[#1D1D1F]">{{ $activeTask->product->product_name }}</strong></span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="font-mono text-xl font-bold text-[#0071E3]">{{ $activeTask->progress }}%</span>
                    </div>
                </div>

                <!-- Minimal Apple Progress Bar -->
                <div class="w-full h-2 bg-[#E5E5EA] rounded-full overflow-hidden">
                    <div 
                        class="h-full bg-[#0071E3] transition-all duration-300 rounded-full" 
                        style="width: {{ $activeTask->progress }}%">
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-[#86868B] pt-0.5">
                    <span class="flex items-center">
                        <svg class="animate-spin -ml-0.5 mr-1.5 h-3 w-3 text-[#0071E3]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        {{ $activeTask->current_step }}
                    </span>
                    <span class="font-mono">Concurrency: 1</span>
                </div>
            </div>
        @else
            <div class="py-8 text-center bg-[#FBFBFD] border border-dashed border-[#E5E5EA] rounded-xl">
                <p class="text-xs text-[#86868B]">Hàng đợi nhàn rỗi (Queue Idle). Không có video nào đang render.</p>
                <button 
                    type="button" 
                    wire:click="generateTestVideo" 
                    class="mt-2 text-xs font-semibold text-[#0071E3] hover:underline">
                    Bấm để tạo video mẫu kiểm tra
                </button>
            </div>
        @endif
    </div>

    <!-- Task Queue Table & Timeline -->
    <livewire:dashboard.task-queue-table />
</div>
