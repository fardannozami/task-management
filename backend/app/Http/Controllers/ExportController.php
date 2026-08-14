<?php

namespace App\Http\Controllers;

use App\Jobs\ExportTasksCsv;
use App\Jobs\ExportTasksPdf;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function __construct(private ExportService $exports) {}

    public function exportCsv(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'priority',
            'assigned_user_id',
            'created_by',
            'due_date_from',
            'due_date_to',
            'search',
        ]);

        dispatch(new ExportTasksCsv($filters, auth('api')->id()));

        return response()->json([
            'message' => 'CSV export queued. The file will be available shortly.',
            'format' => 'csv',
        ], 202);
    }

    public function exportPdf(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'priority',
            'assigned_user_id',
            'created_by',
            'due_date_from',
            'due_date_to',
            'search',
        ]);

        dispatch(new ExportTasksPdf($filters, auth('api')->id()));

        return response()->json([
            'message' => 'PDF export queued. The file will be available shortly.',
            'format' => 'pdf',
        ], 202);
    }

    public function downloadCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $request->only([
            'status',
            'priority',
            'assigned_user_id',
            'created_by',
            'due_date_from',
            'due_date_to',
            'search',
        ]);

        $path = $this->exports->exportTasksCsv($filters);

        return Storage::disk('local')->download($path, 'tasks_export_' . now()->format('Y-m-d_H-i-s') . '.csv');
    }

    public function downloadPdf(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $request->only([
            'status',
            'priority',
            'assigned_user_id',
            'created_by',
            'due_date_from',
            'due_date_to',
            'search',
        ]);

        $path = $this->exports->exportTasksPdf($filters);

        return Storage::disk('local')->download($path, 'tasks_export_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }
}
