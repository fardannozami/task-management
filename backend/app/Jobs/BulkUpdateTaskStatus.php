<?php

namespace App\Jobs;

use App\Services\BulkTaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkUpdateTaskStatus implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $taskIds,
        public string $status,
        public ?int $updatedBy = null
    ) {}

    public function handle(BulkTaskService $bulkTaskService): void
    {
        $bulkTaskService->updateStatus($this->taskIds, $this->status, $this->updatedBy);
    }
}
