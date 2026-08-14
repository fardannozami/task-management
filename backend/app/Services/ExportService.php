<?php

namespace App\Services;

use App\Models\Task;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Writer;

class ExportService
{
    private const EXPORT_DIR = 'exports';

    public function exportTasksCsv(array $filters = []): string
    {
        $query = Task::query()
            ->with(['assignedUser', 'creator']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['assigned_user_id'])) {
            $query->where('assigned_user_id', $filters['assigned_user_id']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (! empty($filters['due_date_from'])) {
            $query->whereDate('due_date', '>=', $filters['due_date_from']);
        }

        if (! empty($filters['due_date_to'])) {
            $query->whereDate('due_date', '<=', $filters['due_date_to']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%'.$filters['search'].'%')
                    ->orWhere('description', 'like', '%'.$filters['search'].'%');
            });
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        $csv = Writer::createFromString('');
        $csv->insertOne([
            'ID',
            'Title',
            'Description',
            'Status',
            'Priority',
            'Assigned To',
            'Created By',
            'Due Date',
            'Created At',
            'Updated At',
        ]);

        foreach ($tasks as $task) {
            $csv->insertOne([
                $task->id,
                $task->title,
                $task->description,
                $task->status,
                $task->priority,
                $task->assignedUser?->name ?? 'Unassigned',
                $task->creator?->name ?? 'Unknown',
                $task->due_date?->format('Y-m-d H:i:s') ?? 'No due date',
                $task->created_at?->format('Y-m-d H:i:s'),
                $task->updated_at?->format('Y-m-d H:i:s'),
            ]);
        }

        $fileName = 'tasks_export_'.now()->format('Y-m-d_H-i-s').'_'.Str::random(8).'.csv';
        $path = self::EXPORT_DIR.'/'.$fileName;

        Storage::disk('local')->put($path, $csv->getContent());

        return $path;
    }

    public function exportTasksPdf(array $filters = []): string
    {
        $query = Task::query()
            ->with(['assignedUser', 'creator']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['assigned_user_id'])) {
            $query->where('assigned_user_id', $filters['assigned_user_id']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (! empty($filters['due_date_from'])) {
            $query->whereDate('due_date', '>=', $filters['due_date_from']);
        }

        if (! empty($filters['due_date_to'])) {
            $query->whereDate('due_date', '<=', $filters['due_date_to']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%'.$filters['search'].'%')
                    ->orWhere('description', 'like', '%'.$filters['search'].'%');
            });
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        $fileName = 'tasks_export_'.now()->format('Y-m-d_H-i-s').'_'.Str::random(8).'.pdf';
        $path = self::EXPORT_DIR.'/'.$fileName;

        $pdf = Pdf::loadView('exports.tasks-pdf', [
            'tasks' => $tasks,
            'exportedAt' => now()->format('Y-m-d H:i:s'),
        ]);

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    public function getExportFullPath(string $path): string
    {
        return Storage::disk('local')->path($path);
    }

    public function deleteExport(string $path): void
    {
        Storage::disk('local')->delete($path);
    }
}
