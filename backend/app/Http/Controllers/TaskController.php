<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private TaskService $tasks) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'priority',
            'assigned_user_id',
            'created_by',
            'due_date_from',
            'due_date_to',
            'search',
            'sort_by',
            'sort_dir',
        ]);

        $tasks = $this->tasks->getTasks($filters, (int) $request->query('per_page', 10));

        return response()->json($tasks);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->tasks->createTask($request->validated());

        return response()->json($task->load(['assignedUser', 'creator']), 201);
    }

    public function show(Task $task): JsonResponse
    {
        $task->load(['assignedUser', 'creator', 'attachments', 'comments.user']);

        return response()->json($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task = $this->tasks->updateTask($task, $request->validated());

        return response()->json($task);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->tasks->deleteTask($task);

        return response()->json(null, 204);
    }
}
