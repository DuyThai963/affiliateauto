<?php

namespace App\Jobs;

use App\Models\VideoTask;
use App\Services\CloudStorageSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ArchiveVideoToCloudJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $videoTaskId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(CloudStorageSyncService $cloudStorageService): void
    {
        $videoTask = VideoTask::query()->with(['channel', 'product'])->find($this->videoTaskId);

        if ($videoTask === null) {
            Log::error("ArchiveVideoToCloudJob: Không tìm thấy VideoTask #{$this->videoTaskId}");
            return;
        }

        if ($videoTask->status !== 'published') {
            Log::warning("ArchiveVideoToCloudJob: Task #{$this->videoTaskId} chưa ở trạng thái 'published'. Bỏ qua archive.");
            return;
        }

        $cloudStorageService->archivePublishedVideo($videoTask);
    }
}

