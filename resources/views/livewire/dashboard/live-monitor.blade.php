<div class="space-y-6 max-w-7xl mx-auto" @if ($activeTask) wire:poll.3s @endif>
    <!-- [Design Read]: Apple HIG Dark Mode Sprint 5 Control Center with Master Auto-Pilot Switch, Tiered Storage 5TB archive, Smart Retry, and Error Inspector Modal. -->

    <!-- Top Action Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-[#F5F5F7]">Tổng Quan Điều Hành</h2>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Sprint 5: Trạm Điều Khiển Trung Tâm (Control Center), Tự Hành Auto-Pilot & Lưu Trữ 5TB
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Master Operation Mode Switch (Apple Segmented Style) -->
            <button 
                type="button" 
                wire:click="toggleMasterOperationMode"
                title="Chuyển đổi chế độ Tự hành (Auto-Pilot) hoặc Bán tự động (Co-Pilot)"
                class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-medium border transition-all shadow-apple-btn active:scale-[0.98] {{ $masterOperationMode === 'auto_pilot' ? 'bg-[#242426] text-[#F5F5F7] border-[#38383A]' : 'bg-[#18181B] text-[#A1A1A6] border-[#2E2E32]' }}">
                <span class="w-2 h-2 rounded-full mr-2 {{ $masterOperationMode === 'auto_pilot' ? 'bg-[#30D158] shadow-[0_0_6px_rgba(48,209,88,0.5)]' : 'bg-[#FF9F0A]' }}"></span>
                <span>Chế độ: <strong class="text-[#F5F5F7] ml-0.5">{{ $masterOperationMode === 'auto_pilot' ? 'Tự hành (Auto-Pilot)' : 'Chờ duyệt (Co-Pilot)' }}</strong></span>
            </button>

            <!-- Nút Làm Mới Thủ Công (Manual Reload) -->
            <button 
                type="button" 
                wire:click="$refresh" 
                wire:loading.attr="disabled"
                title="Tải lại số liệu mới nhất từ cơ sở dữ liệu"
                class="inline-flex items-center justify-center px-3.5 py-2 rounded-xl text-xs font-medium bg-[#1C1C1E] hover:bg-[#242426] text-[#F5F5F7] border border-[#2E2E32] transition-all shadow-apple-btn active:scale-[0.98]">
                <span wire:loading.remove wire:target="$refresh" class="flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1.5 text-[#8E8E93]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Làm Mới
                </span>
                <span wire:loading wire:target="$refresh" class="flex items-center text-[#8E8E93]">
                    <svg class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5 text-[#FFFFFF]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Đang nạp...
                </span>
            </button>

            <!-- Chọn kênh để sinh kịch bản / đăng nhập -->
            <div class="w-48">
                <select wire:model="selectedChannelIdForScript" class="w-full px-3 py-2 bg-[#18181B] border border-[#2E2E32] rounded-xl text-xs text-[#F5F5F7] focus:outline-none focus:border-[#FFFFFF] shadow-apple-btn">
                    <option value="">Chọn kênh chỉ định...</option>
                    @foreach ($availableChannels as $channelItem)
                        <option value="{{ $channelItem->id }}">
                            {{ $channelItem->channel_name }} ({{ $channelItem->type }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Nút Mở Trình Duyệt Đăng Nhập Phiên (Playwright Login) -->
            <button 
                type="button" 
                wire:click="openBrowserLoginForSelectedChannel" 
                title="Mở cửa sổ trình duyệt Playwright để đăng nhập tài khoản một lần duy nhất cho kênh"
                class="inline-flex items-center justify-center px-3.5 py-2 rounded-xl text-xs font-medium bg-[#242426] hover:bg-[#2E2E32] text-[#F5F5F7] border border-[#38383A] transition-all shadow-apple-btn active:scale-[0.98]">
                <svg class="w-3.5 h-3.5 mr-1.5 text-[#A1A1A6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Đăng Nhập Kênh
            </button>

            <!-- Nút Sinh Kịch Bản Ngay (Content Engine) -->
            <button 
                type="button" 
                wire:click="generateScriptNow" 
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-[#FFFFFF] hover:bg-[#EBEBED] text-[#000000] transition-all shadow-apple-btn active:scale-[0.98] disabled:opacity-50">
                <span wire:loading.remove wire:target="generateScriptNow" class="flex items-center text-[#000000]">
                    <svg class="w-3.5 h-3.5 mr-1.5 text-[#000000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Sinh Kịch Bản Ngay
                </span>
                <span wire:loading wire:target="generateScriptNow" class="flex items-center text-[#000000]">
                    <svg class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5 text-[#000000]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Đang gọi AI LLM...
                </span>
            </button>

            <!-- Nút Tạo Video Mẫu Pipeline -->
            <button 
                type="button" 
                wire:click="generateTestVideo" 
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center px-3.5 py-2 rounded-xl text-xs font-medium bg-[#242426] hover:bg-[#2E2E32] text-[#F5F5F7] border border-[#38383A] transition-all shadow-apple-btn active:scale-[0.98] disabled:opacity-50">
                <span wire:loading.remove wire:target="generateTestVideo" class="flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1.5 text-[#8E8E93]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Dựng Mock Video
                </span>
                <span wire:loading wire:target="generateTestVideo" class="flex items-center">
                    <svg class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5 text-[#FFFFFF]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Đang dựng media...
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
                <span><strong class="text-[#F5F5F7]">{{ $growthChannelsCount }}</strong> Growth • <strong class="text-[#F5F5F7]">{{ $affiliateChannelsCount }}</strong> Affiliate</span>
                <a href="{{ route('channels.index') }}" class="text-[#30D158] hover:underline flex items-center">
                    Quản lý ➔
                </a>
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

    <!-- Section: Telemetry Hạn Mức Token & Quota Manager (Sprint 2 Widget) -->
    <div class="rounded-2xl border border-[#26262A] bg-[#18181B] p-6 shadow-apple-card">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-[#30D158] shadow-[0_0_8px_rgba(48,209,88,0.5)]"></span>
                <h3 class="text-sm font-bold text-[#F5F5F7]">Tình Trạng Token & Điều Phối Hạn Mức (Token & Quota Manager)</h3>
            </div>
            <span class="text-[11px] font-mono text-[#8E8E93]">Round-Robin & Fallback Active</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($activeApiKeys as $apiKeyItem)
                @php
                    $quotaPercent = $apiKeyItem->daily_limit > 0 ? min(100, round(($apiKeyItem->used_today / $apiKeyItem->daily_limit) * 100)) : 0;
                @endphp
                <div class="p-4 rounded-xl bg-[#121214] border border-[#26262A] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase bg-[#242426] text-[#F5F5F7] border border-[#38383A]">
                                {{ $apiKeyItem->provider }}
                            </span>
                            <span class="text-[11px] font-mono text-[#8E8E93]">
                                Cooldown: {{ $apiKeyItem->cooldown_seconds }}s
                            </span>
                        </div>
                        <div class="font-bold text-xs text-[#F5F5F7] mt-2.5 truncate" title="{{ $apiKeyItem->key_name }}">
                            {{ $apiKeyItem->key_name }}
                        </div>
                        <div class="text-[11px] font-mono text-[#8E8E93] mt-0.5">
                            {{ Str::mask($apiKeyItem->api_key, '•', 6, -4) }}
                        </div>
                    </div>

                    <div class="mt-3.5">
                        <div class="flex items-center justify-between text-[11px] mb-1">
                            <span class="text-[#8E8E93]">Đã dùng:</span>
                            <span class="font-mono text-[#F5F5F7]">{{ $apiKeyItem->used_today }} / {{ $apiKeyItem->daily_limit }} ({{ $quotaPercent }}%)</span>
                        </div>
                        <div class="w-full h-1.5 bg-[#26262A] rounded-full overflow-hidden">
                            <div class="h-full bg-[#FFFFFF] rounded-full" style="width: {{ $quotaPercent }}%"></div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-6 text-center text-xs text-[#8E8E93]">
                    Chưa có API Key nào được kích hoạt. Hãy vào menu "Cấu hình API Keys" để nạp Gemini Key!
                </div>
            @endforelse
        </div>
    </div>

    <!-- Section: Kho Kịch Bản Chờ Dựng (Script Vault - Sprint 2) -->
    <div class="rounded-2xl border border-[#26262A] bg-[#18181B] p-6 shadow-apple-card">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-[#FFFFFF]"></span>
                <h3 class="text-sm font-bold text-[#F5F5F7]">Kho Kịch Bản AI Chờ Dựng (Script Vault)</h3>
            </div>
            <span class="text-[11px] font-mono text-[#8E8E93]">Lưu trữ JSON Schema AIDA / Growth</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($scriptVault as $scriptItem)
                <div class="p-4 rounded-xl bg-[#121214] border border-[#26262A] flex flex-col justify-between hover:border-[#38383A] transition-colors">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#242426] text-[#F5F5F7] border border-[#38383A]">
                                {{ $scriptItem->channel?->type === 'growth' ? 'Growth Viral' : 'Affiliate AIDA' }}
                            </span>
                            <span class="text-[11px] text-[#8E8E93]">
                                {{ $scriptItem->created_at->diffForHumans() }}
                            </span>
                        </div>

                        <h4 class="font-bold text-xs text-[#F5F5F7] mt-3 line-clamp-2" title="{{ $scriptItem->title }}">
                            {{ $scriptItem->title ?: 'Kịch bản chưa đặt tên' }}
                        </h4>

                        @if (!empty($scriptItem->script_data['hook_text']))
                            <div class="mt-2 p-2 rounded-lg bg-[#18181B] border border-[#26262A] text-[11px] text-[#8E8E93] italic line-clamp-2">
                                "{{ $scriptItem->script_data['hook_text'] }}"
                            </div>
                        @endif

                        <div class="mt-2 text-[11px] text-[#8E8E93] flex items-center space-x-2">
                            <span>Kênh: <strong class="text-[#F5F5F7]">{{ $scriptItem->channel?->channel_name ?: '—' }}</strong></span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#26262A] flex items-center justify-between gap-2">
                        <span class="text-[11px] font-mono text-[#8E8E93]">#{{ $scriptItem->id }}</span>
                        <div class="flex items-center space-x-2">
                            <button 
                                type="button" 
                                wire:click="viewScript({{ $scriptItem->id }})" 
                                class="px-2.5 py-1 rounded-lg bg-[#242426] hover:bg-[#2E2E32] text-[#F5F5F7] border border-[#38383A] text-[11px] font-medium transition-colors">
                                Xem Kịch Bản
                            </button>
                            <button 
                                type="button" 
                                wire:click="renderVideoNow({{ $scriptItem->id }})" 
                                wire:loading.attr="disabled"
                                class="px-2.5 py-1 rounded-lg bg-[#FFFFFF] hover:bg-[#EBEBED] text-[#000000] text-[11px] font-semibold transition-colors disabled:opacity-50">
                                Dựng Video
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-xs text-[#8E8E93] bg-[#141416] rounded-xl border border-dashed border-[#26262A]">
                    Chưa có kịch bản AI nào trong kho. Bấm "Sinh Kịch Bản Ngay" ở trên để khởi tạo kịch bản đầu tiên!
                </div>
            @endforelse
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
                    Bấm để dựng video mẫu kiểm tra
                </button>
            </div>
        @endif
    </div>

    <!-- Task Queue Table & Buffer Vault -->
    <div class="rounded-2xl border border-[#26262A] bg-[#18181B] overflow-hidden shadow-apple-card">
        <!-- Header with Filter Tabs & Controls -->
        <div class="px-6 py-4 border-b border-[#26262A] flex flex-col gap-3 bg-[#141416]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-[#F5F5F7]">Hàng Đợi Xuất Bản & Kho Đệm (Buffer Vault)</h3>
                    <p class="text-[11px] text-[#8E8E93] mt-0.5">Vòng đời: pending → scripting → assets_ready → rendering → ready_in_vault / waiting_approval → published.</p>
                </div>

                <!-- Dropdown Kênh & Loại -->
                <div class="flex items-center space-x-2">
                    <select wire:model.live="channelFilter" class="px-3 py-1.5 bg-[#18181B] border border-[#2E2E32] rounded-xl text-xs text-[#F5F5F7] focus:outline-none focus:border-[#FFFFFF] shadow-apple-btn">
                        <option value="">Tất cả kênh</option>
                        @foreach ($availableChannels as $channelItem)
                            <option value="{{ $channelItem->id }}">{{ $channelItem->channel_name }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="typeFilter" class="px-3 py-1.5 bg-[#18181B] border border-[#2E2E32] rounded-xl text-xs text-[#F5F5F7] focus:outline-none focus:border-[#FFFFFF] shadow-apple-btn">
                        <option value="">Tất cả loại kịch bản</option>
                        <option value="affiliate_original">Affiliate Original</option>
                        <option value="growth_reup_clone">Growth Reup</option>
                    </select>
                </div>
            </div>

            <!-- Segmented Filter Tabs -->
            <div class="flex items-center space-x-1 pt-1 border-t border-[#202024]">
                <button 
                    type="button" 
                    wire:click="$set('statusTab', 'all')" 
                    class="px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $statusTab === 'all' ? 'bg-[#242426] text-[#FFFFFF]' : 'text-[#8E8E93] hover:text-[#F5F5F7]' }}">
                    Tất Cả
                </button>
                <button 
                    type="button" 
                    wire:click="$set('statusTab', 'vault')" 
                    class="px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $statusTab === 'vault' ? 'bg-[#242426] text-[#FFFFFF]' : 'text-[#8E8E93] hover:text-[#F5F5F7]' }}">
                    Kho Đệm ({{ $vaultTasksCount }})
                </button>
                <button 
                    type="button" 
                    wire:click="$set('statusTab', 'waiting_approval')" 
                    class="px-3 py-1 rounded-lg text-xs font-medium transition-all flex items-center space-x-1.5 {{ $statusTab === 'waiting_approval' ? 'bg-[#242426] text-[#FFFFFF]' : 'text-[#8E8E93] hover:text-[#F5F5F7]' }}">
                    <span>Chờ Duyệt (Co-Pilot)</span>
                    @if ($waitingApprovalCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-[#FF9F0A] text-[#000000]">
                            {{ $waitingApprovalCount }}
                        </span>
                    @endif
                </button>
                <button 
                    type="button" 
                    wire:click="$set('statusTab', 'published')" 
                    class="px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $statusTab === 'published' ? 'bg-[#242426] text-[#FFFFFF]' : 'text-[#8E8E93] hover:text-[#F5F5F7]' }}">
                    Đã Xuất Bản
                </button>
                <button 
                    type="button" 
                    wire:click="$set('statusTab', 'failed')" 
                    class="px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $statusTab === 'failed' ? 'bg-[#242426] text-[#FFFFFF]' : 'text-[#8E8E93] hover:text-[#F5F5F7]' }}">
                    Thất Bại
                </button>
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
                                        <span class="text-[10px] text-[#A1A1A6] font-mono uppercase">SP:</span>
                                        <span>{{ $task->product->product_name }}</span>
                                    </div>
                                @endif
                                @if ($task->video_path)
                                    <div class="text-[11px] font-mono text-[#A1A1A6] truncate max-w-xs mt-0.5" title="{{ $task->video_path }}">
                                        {{ basename($task->video_path) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-[#A1A1A6] font-medium">
                                <div class="flex items-center space-x-2">
                                    <span>{{ $task->channel?->channel_name ?: '—' }}</span>
                                    @if ($task->channel)
                                        @if ($task->channel->status === 'needs_relogin')
                                            <button 
                                                type="button" 
                                                wire:click="openBrowserLoginForChannel({{ $task->channel->id }})" 
                                                title="Phiên đăng nhập hết hạn - Bấm để mở trình duyệt đăng nhập lại"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-[#242426] text-[#FF453A] border border-[#FF453A]/40 hover:bg-[#2C2C2E] transition-colors">
                                                Cần Đăng Nhập Lại
                                            </button>
                                        @else
                                            <button 
                                                type="button" 
                                                wire:click="openBrowserLoginForChannel({{ $task->channel->id }})" 
                                                title="Mở trình duyệt đăng nhập phiên cho kênh này"
                                                class="p-1 rounded-md bg-[#242426] hover:bg-[#2E2E32] border border-[#2E2E32] text-[#8E8E93] hover:text-[#F5F5F7] transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                                </svg>
                                            </button>
                                        @endif
                                    @endif
                                </div>
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
                                    @case('assets_ready')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#242426] text-[#F5F5F7] border border-[#38383A]">
                                            Kịch bản sẵn sàng
                                        </span>
                                        @break
                                    @case('ready_in_vault')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#242426] text-[#FFFFFF] border border-[#38383A]">
                                            Trong kho đệm
                                        </span>
                                        @break
                                    @case('waiting_approval')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#242426] text-[#FF9F0A] border border-[#FF9F0A]/30">
                                            Chờ duyệt (Co-Pilot)
                                        </span>
                                        @break
                                    @case('rendering')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#242426] text-[#FFFFFF] border border-[#38383A]">
                                            Đang render
                                        </span>
                                        @break
                                    @case('published')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#242426] text-[#30D158] border border-[#30D158]/30">
                                            Đã xuất bản
                                        </span>
                                        @break
                                    @case('failed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#242426] text-[#FF453A] border border-[#FF453A]/30">
                                            Thất bại
                                        </span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#242426] text-[#8E8E93]">
                                            {{ $task->status }}
                                        </span>
                                @endswitch
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-right space-x-2">
                                @if ($task->status === 'waiting_approval')
                                    <button 
                                        type="button" 
                                        wire:click="approveTask({{ $task->id }})" 
                                        title="Phê duyệt xuất bản (chuyển sang kho đệm và xếp lịch)"
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#FF9F0A] hover:bg-[#FFB340] text-[#000000] text-[11px] font-semibold transition-all shadow-apple-btn active:scale-[0.98]">
                                        <svg class="w-3 h-3 mr-1 text-[#000000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Phê Duyệt
                                    </button>
                                @endif

                                @if ($task->status === 'ready_in_vault')
                                    <button 
                                        type="button" 
                                        wire:click="forcePublishNow({{ $task->id }})" 
                                        wire:loading.attr="disabled"
                                        title="Xuất bản ngay lập tức bỏ qua lịch hẹn"
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#FFFFFF] hover:bg-[#EBEBED] text-[#000000] text-[11px] font-semibold transition-all shadow-apple-btn active:scale-[0.98] disabled:opacity-50">
                                        <svg class="w-3 h-3 mr-1 text-[#000000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                        </svg>
                                        Đăng Ngay
                                    </button>
                                @endif

                                @if ($task->status === 'failed')
                                    <button 
                                        type="button" 
                                        wire:click="inspectError({{ $task->id }})" 
                                        title="Xem chi tiết lỗi và ảnh chụp Captcha nếu có"
                                        class="inline-flex items-center px-2 py-1 rounded-lg bg-[#242426] hover:bg-[#2E2E32] text-[#FF453A] border border-[#FF453A]/30 text-[11px] font-medium transition-colors">
                                        <svg class="w-3 h-3 mr-1 text-[#FF453A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        Chi Tiết Lỗi
                                    </button>
                                    <button 
                                        type="button" 
                                        wire:click="smartRetry({{ $task->id }})" 
                                        title="Thử lại thông minh từ bước bị lỗi"
                                        class="inline-flex items-center px-2 py-1 rounded-lg bg-[#242426] hover:bg-[#2E2E32] text-[#F5F5F7] border border-[#38383A] text-[11px] font-medium transition-colors">
                                        <svg class="w-3 h-3 mr-1 text-[#8E8E93]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Chạy Lại
                                    </button>
                                @endif

                                @if (!empty($task->video_path))
                                    <button 
                                        type="button" 
                                        wire:click="previewVideo({{ $task->id }})" 
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#242426] hover:bg-[#2E2E32] text-[#F5F5F7] border border-[#38383A] text-[11px] font-medium transition-colors">
                                        <svg class="w-3 h-3 mr-1 text-[#A1A1A6]" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                        Xem Video
                                    </button>
                                @elseif (!empty($task->script_data))
                                    <button 
                                        type="button" 
                                        wire:click="renderVideoNow({{ $task->id }})" 
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#242426] hover:bg-[#2E2E32] text-[#F5F5F7] border border-[#38383A] text-[11px] font-medium transition-colors disabled:opacity-50">
                                        Dựng Video
                                    </button>
                                @endif

                                @if (!empty($task->script_data))
                                    <button 
                                        type="button" 
                                        wire:click="viewScript({{ $task->id }})" 
                                        class="text-[11px] font-medium text-[#8E8E93] hover:text-[#FFFFFF] underline">
                                        Script
                                    </button>
                                @endif
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
                                Chưa có tác vụ video nào. Hãy bấm "Sinh Kịch Bản Ngay" hoặc "Dựng Mock Video"!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Chi Tiết Kịch Bản Video AI (Structured Script Preview) -->
    @if ($viewingScriptData)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/75 backdrop-blur-md transition-opacity" wire:click="closeScriptModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-[#1C1C1E] border border-[#2E2E32] text-left shadow-apple-modal transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-[#2E2E32] flex items-center justify-between bg-[#161618]">
                        <div>
                            <h3 class="text-sm font-bold text-[#F5F5F7]">
                                {{ $viewingScriptTitle }}
                            </h3>
                            <div class="text-[11px] text-[#8E8E93] mt-0.5">
                                Kênh: {{ $viewingScriptChannel }}
                            </div>
                        </div>
                        <button type="button" wire:click="closeScriptModal" class="w-6 h-6 rounded-full bg-[#242426] flex items-center justify-center text-[#8E8E93] hover:text-[#F5F5F7] text-xs">
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
                        <!-- Hook 3 Giây Đầu -->
                        <div class="p-3.5 rounded-xl bg-[#121214] border border-[#2E2E32]">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93] block mb-1">
                                3 GIÂY ĐẦU (HOOK TEXT TRÊN MÀN HÌNH)
                            </span>
                            <div class="text-sm font-bold text-[#F5F5F7]">
                                "{{ $viewingScriptData['hook_text'] ?? 'Chưa có hook' }}"
                            </div>
                        </div>

                        <!-- Lời Thoại Voice Script -->
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93] block mb-1">
                                LỜI THOẠI GIỌNG ĐỌC (VOICE SCRIPT)
                            </span>
                            <div class="p-4 rounded-xl bg-[#121214] border border-[#2E2E32] text-xs text-[#F5F5F7] leading-relaxed whitespace-pre-line">
                                {{ $viewingScriptData['voice_script'] ?? 'Chưa có lời thoại' }}
                            </div>
                        </div>

                        <!-- Phân Cảnh Hình Ảnh (Visual Prompts) -->
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93] block mb-1">
                                GỢI Ý PHÂN CẢNH HÌNH ẢNH / FOOTAGE (VISUAL PROMPTS)
                            </span>
                            <div class="space-y-2">
                                @if (!empty($viewingScriptData['visual_prompts']) && is_array($viewingScriptData['visual_prompts']))
                                    @foreach ($viewingScriptData['visual_prompts'] as $index => $scene)
                                        <div class="p-2.5 rounded-lg bg-[#121214] border border-[#26262A] flex items-start space-x-3 text-xs">
                                            <span class="font-mono text-[10px] font-bold px-2 py-0.5 rounded bg-[#242426] text-[#FFFFFF] whitespace-nowrap">
                                                @if (is_array($scene) && isset($scene['start_sec']))
                                                    {{ $scene['start_sec'] }}s - {{ $scene['end_sec'] }}s
                                                @else
                                                    Cảnh {{ $index + 1 }}
                                                @endif
                                            </span>
                                            <span class="text-[#8E8E93] flex-1">
                                                {{ is_array($scene) ? ($scene['visual_query'] ?? '') : $scene }}
                                            </span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-xs text-[#8E8E93] italic">Không có phân cảnh cụ thể.</div>
                                @endif
                            </div>
                        </div>

                        <!-- Caption & Hashtags -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 border-t border-[#26262A]">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93] block mb-1">CAPTION MÔ TẢ</span>
                                <div class="text-xs text-[#F5F5F7] p-2.5 rounded-lg bg-[#121214] border border-[#26262A]">
                                    {{ $viewingScriptData['caption'] ?? '—' }}
                                </div>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93] block mb-1">HASHTAGS</span>
                                <div class="text-xs font-mono text-[#FFFFFF] p-2.5 rounded-lg bg-[#121214] border border-[#26262A]">
                                    {{ $viewingScriptData['hashtags'] ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-[#2E2E32] flex items-center justify-end bg-[#161618]">
                        <button 
                            type="button" 
                            wire:click="closeScriptModal" 
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#FFFFFF] hover:bg-[#EBEBED] text-[#000000] transition-colors">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Xem Trước Video MP4 (Video Preview Modal - Sprint 3) -->
    @if ($viewingVideoTaskId)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-md transition-opacity" wire:click="closeVideoModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-[#1C1C1E] border border-[#2E2E32] text-left shadow-apple-modal transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-[#2E2E32] flex items-center justify-between bg-[#161618]">
                        <div>
                            <h3 class="text-sm font-bold text-[#F5F5F7]">
                                Xem Trước Video Tác Vụ #{{ $viewingVideoTaskId }}
                            </h3>
                            <div class="text-[11px] text-[#8E8E93] mt-0.5">
                                Định dạng dọc 9:16 (1080x1920) • H.264 / AAC
                            </div>
                        </div>
                        <button type="button" wire:click="closeVideoModal" class="w-6 h-6 rounded-full bg-[#242426] flex items-center justify-center text-[#8E8E93] hover:text-[#F5F5F7] text-xs">
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body: HTML5 Video Player -->
                    <div class="p-6 bg-[#000000] flex flex-col items-center justify-center">
                        <div class="w-full aspect-[9/16] max-h-[65vh] bg-[#0A0A0C] rounded-xl overflow-hidden shadow-2xl flex items-center justify-center border border-[#26262A]">
                            <video 
                                src="{{ route('vault.video.stream', $viewingVideoTaskId) }}" 
                                controls 
                                autoplay 
                                playsinline 
                                class="w-full h-full object-contain">
                                Trình duyệt của bạn không hỗ trợ phát thẻ video HTML5.
                            </video>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-[#2E2E32] flex items-center justify-between bg-[#161618]">
                        <span class="text-[11px] font-mono text-[#8E8E93]">
                            Buffer Vault Storage
                        </span>
                        <button 
                            type="button" 
                            wire:click="closeVideoModal" 
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#FFFFFF] hover:bg-[#EBEBED] text-[#000000] transition-colors">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Kiểm Tra Lỗi & Ảnh Captcha (Error Inspector Modal - Sprint 5) -->
    @if ($inspectingErrorTaskId)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-md transition-opacity" wire:click="closeErrorModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-[#1C1C1E] border border-[#2E2E32] text-left shadow-apple-modal transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-[#2E2E32] flex items-center justify-between bg-[#161618]">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#FF453A]"></span>
                            <div>
                                <h3 class="text-sm font-bold text-[#F5F5F7]">
                                    Chi Tiết Lỗi Tác Vụ #{{ $inspectingErrorTaskId }}
                                </h3>
                                <div class="text-[11px] text-[#8E8E93] mt-0.5">
                                    Thời điểm: {{ $inspectingErrorTime ?: 'Không xác định' }} • Bước: {{ $inspectingErrorStep ?: 'Chưa rõ' }}
                                </div>
                            </div>
                        </div>
                        <button type="button" wire:click="closeErrorModal" class="w-6 h-6 rounded-full bg-[#242426] flex items-center justify-center text-[#8E8E93] hover:text-[#F5F5F7] text-xs">
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
                        <!-- Step Status -->
                        <div class="p-3 rounded-xl bg-[#121214] border border-[#2E2E32] flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93]">VỊ TRÍ PHÁT SINH LỖI</span>
                            <span class="text-xs font-semibold text-[#FF453A]">{{ $inspectingErrorStep ?: 'Hệ thống' }}</span>
                        </div>

                        <!-- Stack Trace / Error Log -->
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93] block mb-1.5">
                                NHẬT KÝ CHI TIẾT (ERROR LOG & STACK TRACE)
                            </span>
                            <pre class="p-4 rounded-xl bg-[#121214] border border-[#2E2E32] text-xs font-mono text-[#FF453A] max-h-60 overflow-y-auto whitespace-pre-wrap leading-relaxed select-all">{{ $inspectingErrorLog }}</pre>
                        </div>

                        <!-- Captcha Screenshot if exists -->
                        @if ($inspectingCaptchaScreenshot)
                            <div class="pt-2 border-t border-[#26262A]">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93] block mb-2">
                                    ẢNH CHỤP MÀN HÌNH BOT / CAPTCHA (PLAYWRIGHT SCREENSHOT)
                                </span>
                                <div class="p-3 bg-[#0A0A0C] rounded-xl border border-[#2E2E32] flex items-center justify-center">
                                    <img 
                                        src="{{ $inspectingCaptchaScreenshot }}" 
                                        alt="Captcha Checkpoint Screenshot" 
                                        class="max-h-80 w-auto rounded-lg shadow-lg border border-[#26262A] object-contain">
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Modal Footer with Smart Retry -->
                    <div class="px-6 py-4 border-t border-[#2E2E32] flex items-center justify-between bg-[#161618]">
                        <button 
                            type="button" 
                            wire:click="closeErrorModal" 
                            class="px-4 py-2 rounded-xl text-xs font-medium text-[#8E8E93] hover:text-[#F5F5F7] transition-colors">
                            Đóng
                        </button>
                        <button 
                            type="button" 
                            wire:click="smartRetry({{ $inspectingErrorTaskId }})" 
                            class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold bg-[#FFFFFF] hover:bg-[#EBEBED] text-[#000000] transition-all shadow-apple-btn active:scale-[0.98]">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-[#000000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Chạy Lại Tác Vụ (Smart Retry)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
