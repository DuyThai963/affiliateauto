<!-- [Design Read]: Operational timeline and status table listing recent tasks with State Machine pills (scripting, rendering, ready_in_vault, published). -->
<div class="rounded-xl border border-slate-800 bg-slate-900 overflow-hidden shadow-sm">
    <!-- Header with Filter -->
    <div class="p-5 border-b border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-white">Lịch Trình Hàng Đợi & Kho Đệm (Buffer Vault)</h3>
            <p class="text-xs text-slate-400 mt-0.5">Vòng đời trạng thái: pending → scripting → assets_ready → rendering → ready_in_vault → publishing → published.</p>
        </div>

        <div class="flex items-center space-x-2">
            <select wire:model.live="statusFilter" class="px-3 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-xs text-slate-300 focus:outline-none focus:border-emerald-500">
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
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950/70 text-xs uppercase font-semibold text-slate-400 tracking-wider border-b border-slate-800">
                <tr>
                    <th class="px-5 py-3.5">ID / Video</th>
                    <th class="px-5 py-3.5">Kênh Đích</th>
                    <th class="px-5 py-3.5">Loại Tác Vụ</th>
                    <th class="px-5 py-3.5">Bước Hiện Tại</th>
                    <th class="px-5 py-3.5">Tiến Độ</th>
                    <th class="px-5 py-3.5">Trạng Thái</th>
                    <th class="px-5 py-3.5 text-right">Thao Tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
                @forelse ($tasks as $task)
                    <tr class="hover:bg-slate-850/60 transition-colors">
                        <td class="px-5 py-4">
                            <div class="font-semibold text-white max-w-sm truncate">
                                #{{ $task->id }} - {{ $task->title ?: 'Chưa có tiêu đề' }}
                            </div>
                            @if ($task->product)
                                <div class="text-xs text-slate-400 flex items-center space-x-1 mt-0.5">
                                    <span>🛒</span>
                                    <span>{{ $task->product->product_name }}</span>
                                </div>
                            @endif
                            @if ($task->video_path)
                                <div class="text-[11px] font-mono text-emerald-400 truncate max-w-xs mt-0.5" title="{{ $task->video_path }}">
                                    📁 {{ basename($task->video_path) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-medium text-slate-200">
                                {{ $task->channel?->channel_name ?: '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-xs">
                            @if ($task->task_type === 'affiliate_original')
                                <span class="text-purple-300 font-medium">Affiliate Original</span>
                            @else
                                <span class="text-emerald-300 font-medium">Growth Reup Clone</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-xs text-slate-400 max-w-xs truncate">
                            {{ $task->current_step }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="w-16 h-1.5 bg-slate-950 rounded-full overflow-hidden border border-slate-800">
                                    <div class="h-full bg-emerald-500" style="width: {{ $task->progress }}%"></div>
                                </div>
                                <span class="font-mono text-xs text-slate-300">{{ $task->progress }}%</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            @switch($task->status)
                                @case('ready_in_vault')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-950 text-blue-300 border border-blue-800">
                                        📦 Ready In Vault
                                    </span>
                                    @break
                                @case('rendering')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-950 text-amber-300 border border-amber-800">
                                        ⚙ Rendering
                                    </span>
                                    @break
                                @case('published')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950 text-emerald-300 border border-emerald-800">
                                        ✓ Published
                                    </span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-950 text-rose-300 border border-rose-800">
                                        ✕ Failed
                                    </span>
                                    @break
                                @default
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-800 text-slate-300">
                                        {{ $task->status }}
                                    </span>
                            @endswitch
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                            <button 
                                type="button" 
                                wire:confirm="Bạn có chắc chắn muốn xóa tác vụ này?" 
                                wire:click="deleteTask({{ $task->id }})" 
                                class="px-2 py-1 rounded bg-rose-950/70 hover:bg-rose-900 text-rose-300 transition-colors">
                                Xóa
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                            Chưa có tác vụ video nào trong hàng đợi. Hãy bấm nút "Tạo Video Thử Nghiệm" ở trên!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

