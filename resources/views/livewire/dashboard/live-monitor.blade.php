<!-- [Design Read]: Operational mission control dashboard with real-time polling metric tiles, live execution step monitor, and primary action to trigger mock pipelines. -->
<div wire:poll.3s class="space-y-8">
    <!-- Top Bar Actions & Status -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-white">Trung Tâm Điều Hành Sản Xuất</h2>
            <p class="text-sm text-slate-400 mt-1">
                Theo dõi tiến độ sản xuất kịch bản, render video và trạng thái kho đệm Buffer Vault theo thời gian thực.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <button 
                type="button" 
                wire:click="generateTestVideo" 
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-colors shadow-sm disabled:opacity-50">
                <span wire:loading.remove wire:target="generateTestVideo" class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tạo Video Thử Nghiệm (Mock Driver)
                </span>
                <span wire:loading wire:target="generateTestVideo" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-slate-950" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Đang chạy Mock Driver...
                </span>
            </button>
        </div>
    </div>

    <!-- Alert / Feedback Message -->
    @if ($feedbackMessage)
        <div class="p-4 rounded-lg bg-emerald-950/80 border border-emerald-800/80 text-emerald-200 text-sm flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span>✓</span>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button type="button" wire:click="$set('feedbackMessage', null)" class="text-emerald-400 hover:text-emerald-200 text-sm">✕</button>
        </div>
    @endif

    <!-- Top Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Metric 1: Kênh hoạt động -->
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Kênh Hoạt Động</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-sm">
                    📡
                </span>
            </div>
            <div class="mt-3 flex items-baseline space-x-2">
                <span class="text-3xl font-bold tracking-tight text-white">{{ $totalActiveChannels }}</span>
                <span class="text-xs text-slate-400">kênh</span>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                <span class="text-emerald-400">🌱 {{ $growthChannelsCount }} Growth</span>
                <span class="text-purple-400">🛒 {{ $affiliateChannelsCount }} Affiliate</span>
            </div>
        </div>

        <!-- Metric 2: Kho đệm Buffer Vault -->
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Kho Đệm (Buffer Vault)</span>
                <span class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 text-sm">
                    📦
                </span>
            </div>
            <div class="mt-3 flex items-baseline space-x-2">
                <span class="text-3xl font-bold tracking-tight text-white">{{ $vaultTasksCount }}</span>
                <span class="text-xs text-slate-400">video sẵn sàng</span>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-800/80 text-xs text-slate-400 flex items-center justify-between">
                <span>Trạng thái kho:</span>
                <span class="text-emerald-400 font-mono">storage/vault OK</span>
            </div>
        </div>

        <!-- Metric 3: Tiến độ xuất bản hôm nay -->
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Đã Đăng Hôm Nay</span>
                <span class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 text-sm">
                    🚀
                </span>
            </div>
            <div class="mt-3 flex items-baseline space-x-2">
                <span class="text-3xl font-bold tracking-tight text-white">{{ $publishedTodayCount }}</span>
                <span class="text-xs text-slate-400">/ {{ $totalDailyQuota }} mục tiêu</span>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-800/80 text-xs text-slate-400 flex items-center justify-between">
                <span>Khung giờ vàng:</span>
                <span class="text-slate-200 font-mono text-[11px]">07:30 - 22:30</span>
            </div>
        </div>

        <!-- Metric 4: Token tiêu thụ -->
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Quota LLM Tiêu Thụ</span>
                <span class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 text-sm">
                    ⚡
                </span>
            </div>
            <div class="mt-3 flex items-baseline space-x-2">
                <span class="text-3xl font-bold tracking-tight text-white">{{ $tokensConsumedToday }}</span>
                <span class="text-xs text-slate-400">/ {{ $totalTokensLimit > 0 ? $totalTokensLimit : '—' }} reqs</span>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-800/80 text-xs text-slate-400 flex items-center justify-between">
                <span>Cơ chế xoay vòng:</span>
                <span class="text-emerald-400 font-mono text-[11px]">Auto Rotate</span>
            </div>
        </div>
    </div>

    <!-- Live Task Execution Monitor -->
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-3">
                <span class="relative flex h-3 w-3">
                    @if ($activeTask)
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    @else
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-slate-600"></span>
                    @endif
                </span>
                <h3 class="text-base font-bold text-white">Giám Sát Tác Vụ Đang Xử Lý (Live Queue Monitor)</h3>
            </div>
            <div class="text-xs font-mono text-slate-500 flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>Auto-polling (3s)</span>
            </div>
        </div>

        @if ($activeTask)
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800/80 space-y-3">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-white text-base">#{{ $activeTask->id }} - {{ $activeTask->title ?: 'Đang khởi tạo...' }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase tracking-wider bg-emerald-950 text-emerald-300 border border-emerald-800">
                                {{ $activeTask->status }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-400 mt-1 flex items-center space-x-3">
                            <span>Kênh đích: <strong class="text-slate-200">{{ $activeTask->channel?->channel_name ?: 'Chưa gán' }}</strong></span>
                            @if ($activeTask->product)
                                <span>• Sản phẩm: <strong class="text-slate-200">{{ $activeTask->product->product_name }}</strong></span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="font-mono text-2xl font-bold text-emerald-400">{{ $activeTask->progress }}%</span>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="w-full h-3 bg-slate-900 rounded-full overflow-hidden border border-slate-800">
                    <div 
                        class="h-full bg-emerald-500 transition-all duration-300" 
                        style="width: {{ $activeTask->progress }}%">
                    </div>
                </div>

                <!-- Step Description -->
                <div class="flex items-center justify-between text-xs text-slate-400 pt-1">
                    <span class="flex items-center">
                        <svg class="animate-spin -ml-0.5 mr-2 h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        {{ $activeTask->current_step }}
                    </span>
                    <span class="font-mono text-slate-500">Concurrency: 1</span>
                </div>
            </div>
        @else
            <div class="py-8 text-center bg-slate-950/60 border border-slate-800/80 rounded-xl">
                <div class="text-slate-500 text-sm">Hàng đợi đang rảnh (Idle). Sẵn sàng nhận tác vụ mới.</div>
                <button 
                    type="button" 
                    wire:click="generateTestVideo" 
                    class="mt-3 text-xs font-medium text-emerald-400 hover:text-emerald-300 underline">
                    Bấm vào đây để chạy thử quy trình Mock Video Task
                </button>
            </div>
        @endif
    </div>

    <!-- Task Queue Table & Vault Timeline Component -->
    <livewire:dashboard.task-queue-table />
</div>

