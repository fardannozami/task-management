<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkUpdateTaskStatusRequest;
use App\Jobs\BulkUpdateTaskStatus;
use App\Services\BulkTaskService;
use Illuminate\Http\JsonResponse;

class BulkTaskController extends Controller
{
    public function __construct(private BulkTaskService $bulkTasks) {}

    public function updateStatus(BulkUpdateTaskStatusRequest $request): JsonResponse
    {
        $data = $request->validated();

        dispatch(new BulkUpdateTaskStatus(
            $data['task_ids'],
            $data['status'],
            auth('api')->id()
        ));

        return response()->json([
            'message' => 'Bulk status update queued.',
            'task_ids' => $data['task_ids'],
            'status' => $data['status'],
        ], 202);
    }
}
