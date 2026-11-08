<?php

namespace App\Livewire\Dashboard;

use App\Models\VideoTask;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskQueueTable extends Component
{
    public string $statusFilter = '';

    #[On('task-queue-updated')]
    public function refreshTable(): void
    {
        // Livewire tự động re-render component khi nhận event
    }

    public function deleteTask(int $taskId): void
    {
        $task = VideoTask::query()->find($taskId);

        if ($task !== null) {
            $task->delete();
        }
    }

    public function render(): View
    {
        $tasksQuery = VideoTask::query()->with(['channel', 'product']);

        if ($this->statusFilter !== '') {
            $tasksQuery->where('status', $this->statusFilter);
        }

        $tasks = $tasksQuery->latest()->take(15)->get();

        return view('livewire.dashboard.task-queue-table', [
            'tasks' => $tasks,
        ]);
    }
}

