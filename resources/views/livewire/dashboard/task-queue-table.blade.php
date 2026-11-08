<div class="rounded-2xl border border-[#26262A] bg-[#18181B] overflow-hidden shadow-apple-card">
    <!-- [Design Read]: Apple HIG Dark Mode standalone task queue table component. -->
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
                    <tr wire:key="sub-task-row-{{ $task->id }}" class="hover:bg-[#1C1C1E] transition-colors">
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
