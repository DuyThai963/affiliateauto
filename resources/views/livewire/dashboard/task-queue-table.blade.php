<!-- [Design Read]: Clean Apple-styled data table with subtle hairline borders, soft pastel status pills, and monospaced asset tags. -->
<div class="rounded-2xl border border-[#E5E5EA] bg-[#FFFFFF] overflow-hidden shadow-apple-card">
    <!-- Header with Filter -->
    <div class="px-6 py-4 border-b border-[#E5E5EA] flex flex-col md:flex-row md:items-center justify-between gap-3 bg-[#FBFBFD]">
        <div>
            <h3 class="text-sm font-bold text-[#1D1D1F]">Hàng Đợi Xuất Bản & Kho Đệm (Buffer Vault)</h3>
            <p class="text-[11px] text-[#86868B] mt-0.5">Vòng đời: pending → scripting → assets_ready → rendering → ready_in_vault → published.</p>
        </div>

        <div>
            <select wire:model.live="statusFilter" class="px-3 py-1.5 bg-[#FFFFFF] border border-[#E5E5EA] rounded-xl text-xs text-[#1D1D1F] focus:outline-none focus:border-[#0071E3] shadow-apple-btn">
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
        <table class="w-full text-left text-xs text-[#1D1D1F]">
            <thead class="bg-[#F5F5F7]/60 text-[11px] font-semibold text-[#86868B] uppercase tracking-wider border-b border-[#E5E5EA]">
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
            <tbody class="divide-y divide-[#F5F5F7]">
                @forelse ($tasks as $task)
                    <tr class="hover:bg-[#FBFBFD] transition-colors">
                        <td class="px-6 py-3.5">
                            <div class="font-semibold text-[#1D1D1F] max-w-sm truncate">
                                #{{ $task->id }} - {{ $task->title ?: 'Chưa có tiêu đề' }}
                            </div>
                            @if ($task->product)
                                <div class="text-[11px] text-[#86868B] flex items-center space-x-1 mt-0.5">
                                    <span>🛒</span>
                                    <span>{{ $task->product->product_name }}</span>
                                </div>
                            @endif
                            @if ($task->video_path)
                                <div class="text-[11px] font-mono text-[#0071E3] truncate max-w-xs mt-0.5" title="{{ $task->video_path }}">
                                    📁 {{ basename($task->video_path) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap text-[#515154] font-medium">
                            {{ $task->channel?->channel_name ?: '—' }}
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            @if ($task->task_type === 'affiliate_original')
                                <span class="text-[#8E24AA] font-medium">Affiliate Original</span>
                            @else
                                <span class="text-[#2E7D32] font-medium">Growth Reup Clone</span>
                            @endif
                        </td>
                        <td class="px-6 py-3.5 text-[#86868B] max-w-xs truncate text-[11px]">
                            {{ $task->current_step }}
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="w-14 h-1.5 bg-[#E5E5EA] rounded-full overflow-hidden">
                                    <div class="h-full bg-[#0071E3] rounded-full" style="width: {{ $task->progress }}%"></div>
                                </div>
                                <span class="font-mono text-[11px] text-[#515154]">{{ $task->progress }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            @switch($task->status)
                                @case('ready_in_vault')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#E3F2FD] text-[#1976D2]">
                                        📦 In Vault
                                    </span>
                                    @break
                                @case('rendering')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#FFF3E0] text-[#E65100]">
                                        ⚙ Rendering
                                    </span>
                                    @break
                                @case('published')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#E8F5E9] text-[#2E7D32]">
                                        ✓ Published
                                    </span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#FFEBEE] text-[#C62828]">
                                        ✕ Failed
                                    </span>
                                    @break
                                @default
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#F5F5F7] text-[#515154]">
                                        {{ $task->status }}
                                    </span>
                            @endswitch
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap text-right">
                            <button 
                                type="button" 
                                wire:confirm="Bạn có chắc chắn muốn xóa tác vụ này?" 
                                wire:click="deleteTask({{ $task->id }})" 
                                class="text-[11px] font-medium text-[#FF3B30] hover:underline">
                                Xóa
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-[#86868B]">
                            Chưa có tác vụ video nào. Hãy bấm "Tạo Video Thử Nghiệm" ở trên!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
