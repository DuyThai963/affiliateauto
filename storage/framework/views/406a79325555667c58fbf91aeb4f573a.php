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
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $tasks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-850/60 transition-colors">
                        <td class="px-5 py-4">
                            <div class="font-semibold text-white max-w-sm truncate">
                                #<?php echo e($task->id); ?> - <?php echo e($task->title ?: 'Chưa có tiêu đề'); ?>

                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($task->product): ?>
                                <div class="text-xs text-slate-400 flex items-center space-x-1 mt-0.5">
                                    <span>🛒</span>
                                    <span><?php echo e($task->product->product_name); ?></span>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($task->video_path): ?>
                                <div class="text-[11px] font-mono text-emerald-400 truncate max-w-xs mt-0.5" title="<?php echo e($task->video_path); ?>">
                                    📁 <?php echo e(basename($task->video_path)); ?>

                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-medium text-slate-200">
                                <?php echo e($task->channel?->channel_name ?: '—'); ?>

                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-xs">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($task->task_type === 'affiliate_original'): ?>
                                <span class="text-purple-300 font-medium">Affiliate Original</span>
                            <?php else: ?>
                                <span class="text-emerald-300 font-medium">Growth Reup Clone</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-xs text-slate-400 max-w-xs truncate">
                            <?php echo e($task->current_step); ?>

                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="w-16 h-1.5 bg-slate-950 rounded-full overflow-hidden border border-slate-800">
                                    <div class="h-full bg-emerald-500" style="width: <?php echo e($task->progress); ?>%"></div>
                                </div>
                                <span class="font-mono text-xs text-slate-300"><?php echo e($task->progress); ?>%</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php switch($task->status):
                                case ('ready_in_vault'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-950 text-blue-300 border border-blue-800">
                                        📦 Ready In Vault
                                    </span>
                                    <?php break; ?>
                                <?php case ('rendering'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-950 text-amber-300 border border-amber-800">
                                        ⚙ Rendering
                                    </span>
                                    <?php break; ?>
                                <?php case ('published'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950 text-emerald-300 border border-emerald-800">
                                        ✓ Published
                                    </span>
                                    <?php break; ?>
                                <?php case ('failed'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-950 text-rose-300 border border-rose-800">
                                        ✕ Failed
                                    </span>
                                    <?php break; ?>
                                <?php default: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-800 text-slate-300">
                                        <?php echo e($task->status); ?>

                                    </span>
                            <?php endswitch; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                            <button 
                                type="button" 
                                wire:confirm="Bạn có chắc chắn muốn xóa tác vụ này?" 
                                wire:click="deleteTask(<?php echo e($task->id); ?>)" 
                                class="px-2 py-1 rounded bg-rose-950/70 hover:bg-rose-900 text-rose-300 transition-colors">
                                Xóa
                            </button>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                            Chưa có tác vụ video nào trong hàng đợi. Hãy bấm nút "Tạo Video Thử Nghiệm" ở trên!
                        </td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /**PATH D:\laragon\laragon\www\AffiliateAuto\resources\views/livewire/dashboard/task-queue-table.blade.php ENDPATH**/ ?>