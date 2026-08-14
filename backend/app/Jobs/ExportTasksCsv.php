<?php

namespace App\Jobs;

use App\Services\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportTasksCsv implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $filters = [],
        public ?int $userId = null
    ) {}

    public function handle(ExportService $exportService): void
    {
        $exportService->exportTasksCsv($this->filters);
    }
}
