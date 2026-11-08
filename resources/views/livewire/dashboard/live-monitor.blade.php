<div class="space-y-6 max-w-7xl mx-auto" @if ($activeTask) wire:poll.2s @else wire:poll.30s @endif>
    <!-- [Design Read]: Unified Apple HIG Dark Mode operational dashboard with consolidated single-component architecture, eliminating nested morphdom flicker and wire:id desync. -->
    
    <!-- Top Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-[#F5F5F7]">Tổng Quan Điều Hành</h2>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Giám sát kho đệm Buffer Vault, tiến độ render kịch bản và xuất bản video tự động.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <!-- Nút Hành Động Đơn Sắc Trắng/Đen Chuẩn Apple Dark Mode -->
            <button 
                type="button" 
                wire:click="generateTestVideo" 
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-[#FFFFFF] hover:bg-[#EBEBED] text-[#000000] transition-all shadow-apple-btn active:scale-[0.98] disabled:opacity-50">
                <span wire:loading.remove wire:target="generateTestVideo" class="flex items-center text-[#000000]">
                    <!-- Icon đơn sắc đen thanh mảnh -->
                    <svg class="w-3.5 h-3.5 mr-2 text-[#000000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Tạo Video Thử Nghiệm
                </span>
                <span wire:loading wire:target="generateTestVideo" class="flex items-center text-[#000000]">
                    <svg class="animate-spin -ml-0.5 mr-2 h-3.5 w-3.5 text-[#000000]" fill="none" viewBox="0 0 24 24">
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
        <div class="p-3.5 rounded-2xl bg-[#242426] border border-[#38383A] text-[#F5F5F7] text-xs flex items-center justify-between shadow-apple-card">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-[#FFFFFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="font-medium">{{ $feedbackMessage }}</span>
            </div>
            <button type="button" wire:click="$set('feedbackMessage', null)" class="text-[#8E8E93] hover:text-[#FFFFFF] text-sm">✕</button>
        </div>
    @endif

    <!-- Top KPI Metric Cards (Đơn sắc tối giản chuẩn Apple) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Kênh hoạt động -->
        <div class="rounded-2xl border border-[#26262A] bg-[#18181B] p-5 shadow-apple-card flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#8E8E93]">
                <span class="text-[11px] font-semibold uppercase tracking-wider">Kênh Hoạt Động</span>
                <span class="w-7 h-7 rounded-lg bg-[#242426] border border-[#2E2E32] flex items-center justify-center text-[#FFFFFF]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-bold tracking-tight text-[#F5F5F7]">{{ $totalActiveChannels }}</span>
                <span class="text-xs text-[#8E8E93] ml-1">kênh</span>
            </div>
            <div class="mt-3 pt-3 border-t border-[#26262A] flex items-center justify-between text-[11px] font-medium text-[#8E8E93]">
                <span><strong class="text-[#F5F5F7]">{{ $growthChannelsCount }}</strong> Growth</span>
                <span><strong class="text-[#F5F5F7]">{{ $affiliateChannelsCount }}</strong> Affiliate</span>
            </div>
        </div>

        <!-- Metric 2: Buffer Vault -->
        <div class="rounded-2xl border border-[#26262A] bg-[#18181B] p-5 shadow-apple-card flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#8E8E93]">
                <span class="text-[11px] font-semibold uppercase tracking-wider">Kho Đệm Vault</span>
                <span class="w-7 h-7 rounded-lg bg-[#242426] border border-[#2E2E32] flex items-center justify-center text-[#FFFFFF]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-bold tracking-tight text-[#F5F5F7]">{{ $vaultTasksCount }}</span>
                <span class="text-xs text-[#8E8E93] ml-1">video sẵn sàng</span>
            </div>
            <div class="mt-3 pt-3 border-t border-[#26262A] flex items-center justify-between text-[11px] text-[#8E8E93]">
                <span>Trữ lượng:</span>
                <span class="font-mono text-[#F5F5F7]">storage/vault</span>
            </div>
        </div>

        <!-- Metric 3: Đã xuất bản hôm nay -->
        <div class="rounded-2xl border border-[#26262A] bg-[#18181B] p-5 shadow-apple-card flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#8E8E93]">
                <span class="text-[11px] font-semibold uppercase tracking-wider">Đã Đăng Hôm Nay</span>
                <span class="w-7 h-7 rounded-lg bg-[#242426] border border-[#2E2E32] flex items-center justify-center text-[#FFFFFF]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-bold tracking-tight text-[#F5F5F7]">{{ $publishedTodayCount }}</span>
                <span class="text-xs text-[#8E8E93] ml-1">/ {{ $totalDailyQuota }} video</span>
            </div>
            <div class="mt-3 pt-3 border-t border-[#26262A] flex items-center justify-between text-[11px] text-[#8E8E93]">
                <span>Khung giờ vàng:</span>
                <span class="font-mono text-[#F5F5F7]">07:30 - 22:30</span>
            </div>
        </div>

        <!-- Metric 4: Token tiêu thụ -->
        <div class="rounded-2xl border border-[#26262A] bg-[#18181B] p-5 shadow-apple-card flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#8E8E93]">
                <span class="text-[11px] font-semibold uppercase tracking-wider">Hạn Mức LLM</span>
                <span class="w-7 h-7 rounded-lg bg-[#242426] border border-[#2E2E32] flex items-center justify-center text-[#FFFFFF]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-bold tracking-tight text-[#F5F5F7]">{{ $tokensConsumedToday }}</span>
                <span class="text-xs text-[#8E8E93] ml-1">/ {{ $totalTokensLimit > 0 ? $totalTokensLimit : '—' }} reqs</span>
            </div>
            <div class="mt-3 pt-3 border-t border-[#26262A] flex items-center justify-between text-[11px] text-[#8E8E93]">
                <span>Xoay vòng:</span>
                <span class="text-[#F5F5F7] font-medium">Auto Rotating</span>
            </div>
        </div>
    </div>

    <!-- Live Execution Status (macOS Window Dark Style) -->
    <div class="rounded-2xl border border-[#26262A] bg-[#18181B] p-6 shadow-apple-card">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full {{ $activeTask ? 'bg-[#30D158] animate-pulse shadow-[0_0_8px_rgba(48,209,88,0.6)]' : 'bg-[#636366]' }}"></span>
                <h3 class="text-sm font-bold text-[#F5F5F7]">Trạng Thái Xử Lý Trực Tiếp (Live Task Monitor)</h3>
            </div>
            <div class="text-[11px] font-mono text-[#8E8E93]">
                {{ $activeTask ? 'Đang chạy (Live: 2s)' : 'Hàng đợi nhàn rỗi (30s)' }}
            </div>
        </div>

        @if ($activeTask)
            <div class="p-4 rounded-xl bg-[#121214] border border-[#26262A] space-y-3">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-[#F5F5F7] text-sm">#{{ $activeTask->id }} - {{ $activeTask->title ?: 'Đang tạo...' }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#242426] border border-[#38383A] text-[#F5F5F7]">
                                {{ $activeTask->status }}
                            </span>
                        </div>
                        <div class="text-xs text-[#8E8E93] mt-1 flex items-center space-x-2">
                            <span>Kênh đích: <strong class="text-[#F5F5F7]">{{ $activeTask->channel?->channel_name ?: '—' }}</strong></span>
                            @if ($activeTask->product)
                                <span>• Sản phẩm: <strong class="text-[#F5F5F7]">{{ $activeTask->product->product_name }}</strong></span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="font-mono text-xl font-bold text-[#FFFFFF]">{{ $activeTask->progress }}%</span>
                    </div>
                </div>

                <!-- Minimal Apple Progress Bar -->
                <div class="w-full h-2 bg-[#242426] rounded-full overflow-hidden">
                    <div 
                        class="h-full bg-[#FFFFFF] transition-all duration-300 rounded-full" 
                        style="width: {{ $activeTask->progress }}%">
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-[#8E8E93] pt-0.5">
                    <span class="flex items-center">
                        <svg class="animate-spin -ml-0.5 mr-1.5 h-3 w-3 text-[#FFFFFF]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        {{ $activeTask->current_step }}
                    </span>
                    <span class="font-mono">Concurrency: 1</span>
                </div>
            </div>
        @else
            <div class="py-8 text-center bg-[#141416] border border-dashed border-[#26262A] rounded-xl">
                <p class="text-xs text-[#8E8E93]">Hàng đợi nhàn rỗi (Queue Idle). Không có video nào đang render.</p>
                <button 
                    type="button" 
                    wire:click="generateTestVideo" 
                    class="mt-2 text-xs font-semibold text-[#FFFFFF] hover:underline">
                    Bấm để tạo video mẫu kiểm tra
                </button>
            </div>
        @endif
    </div>

    <!-- Task Queue Table & Buffer Vault (Tích hợp trực tiếp, không dùng component con để triệt tiêu hoàn toàn giật wire:id) -->
    <div class="rounded-2xl border border-[#26262A] bg-[#18181B] overflow-hidden shadow-apple-card">
        <!-- Header with Filter -->
        <div class="px-6 py-4 border-b border-[#26262A] flex flex-col md:flex-row md:items-center justify-between gap-3 bg-[#141416]">
            <div>
                <h3 class="text-sm font-bold text-[#F5F5F7]">Hàng Đợi Xuất Bản & Kho Đệm (Buffer Vault)</h3>
                <p class="text-[11px] text-[#8E8E93] mt-0.5">Vòng đời: pending → scripting → assets_ready → rendering → ready_in_vault → published.</p>
            </div>

            <div>
                <select wire:model.live="statusFilter" class="px-3 py-1.5 bg-[#18181B] border border-[#2E2E32] rounded-xl text-xs text-[#F5F5F7] focus:outline-none focus:border-[#FFFFFF] shadow-apple-btn">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ready_in_vault">Ready In Vault (Sẵn sàng)</option>
                    <option value="rendering">Rendering (Đang render)</option>
                    <option value="published">Published (Đã xuất bản)</option>
                    <option value="failed">Failed (Thất bại)</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-[#F5F5F7]">
                <thead class="bg-[#121214] text-[11px] font-semibold text-[#8E8E93] uppercase tracking-wider border-b border-[#26262A]">
                    <tr>
                        <th class="px-6 py-3">Video / Tiêu đề</th>
                        <th class="px-6 py-3">Kênh Đích</th>
                        <th class="px-6 py-3">Loại Kịch Bản</th>
                        <th class="px-6 py-3">Bước Hiện Tại</th>
                        <th class="px-6 py-3">Tiến Độ</th>
                        <th class="px-6 py-3">Trạng Thái</th>
                        <th class="px-6 py-3 text-right">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#202024]">
                    @forelse ($tasks as $task)
                        <tr wire:key="task-row-{{ $task->id }}" class="hover:bg-[#1C1C1E] transition-colors">
                            <td class="px-6 py-3.5">
                                <div class="font-semibold text-[#F5F5F7] max-w-sm truncate">
                                    #{{ $task->id }} - {{ $task->title ?: 'Chưa có tiêu đề' }}
                                </div>
                                @if ($task->product)
                                    <div class="text-[11px] text-[#8E8E93] flex items-center space-x-1 mt-0.5">
                                        <span>🛒</span>
                                        <span>{{ $task->product->product_name }}</span>
                                    </div>
                                @endif
                                @if ($task->video_path)
                                    <div class="text-[11px] font-mono text-[#FFFFFF] truncate max-w-xs mt-0.5" title="{{ $task->video_path }}">
                                        📁 {{ basename($task->video_path) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-[#A1A1A6] font-medium">
                                {{ $task->channel?->channel_name ?: '—' }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if ($task->task_type === 'affiliate_original')
                                    <span class="text-[#F5F5F7] font-medium">Affiliate Original</span>
                                @else
                                    <span class="text-[#8E8E93] font-medium">Growth Reup Clone</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-[#8E8E93] max-w-xs truncate text-[11px]">
                                {{ $task->current_step }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <div class="w-14 h-1.5 bg-[#26262A] rounded-full overflow-hidden">
                                        <div class="h-full bg-[#FFFFFF] rounded-full" style="width: {{ $task->progress }}%"></div>
                                    </div>
                                    <span class="font-mono text-[11px] text-[#8E8E93]">{{ $task->progress }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @switch($task->status)
                                    @case('ready_in_vault')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#242426] text-[#FFFFFF] border border-[#38383A]">
                                            📦 In Vault
                                        </span>
                                        @break
                                    @case('rendering')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#242426] text-[#FFFFFF] border border-[#38383A]">
                                            ⚙ Rendering
                                        </span>
                                        @break
                                    @case('published')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#30D158]/15 text-[#30D158] border border-[#30D158]/25">
                                            ✓ Published
                                        </span>
                                        @break
                                    @case('failed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#FF453A]/15 text-[#FF453A] border border-[#FF453A]/25">
                                            ✕ Failed
                                        </span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#242426] text-[#8E8E93]">
                                            {{ $task->status }}
                                        </span>
                                @endswitch
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-right">
                                <button 
                                    type="button" 
                                    wire:confirm="Bạn có chắc chắn muốn xóa tác vụ này?" 
                                    wire:click="deleteTask({{ $task->id }})" 
                                    class="text-[11px] font-medium text-[#FF453A] hover:underline">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-[#8E8E93]">
                                Chưa có tác vụ video nào. Hãy bấm "Tạo Video Thử Nghiệm" ở trên!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
