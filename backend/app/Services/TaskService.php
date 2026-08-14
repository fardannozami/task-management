<?php

namespace App\Services;

use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskUpdated;
use App\Jobs\SendTaskAssignmentEmail;
use App\Models\Task;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class TaskService
{
    public function getTasks(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Task::query()
            ->with(['assignedUser', 'creator'])
            ->withCount('attachments');

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

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function createTask(array $data): Task
    {
        $data['created_by'] = Auth::id();

        $task = Task::create($data);

        if (! empty($task->assigned_user_id)) {
            $this->dispatchAssignmentNotification($task);
        }

        broadcast(new TaskCreated($task->fresh(['assignedUser', 'creator'])));

        return $task;
    }

    public function updateTask(Task $task, array $data): Task
    {
        $previousAssignedUserId = $task->assigned_user_id;

        $task->update($data);

        $task->refresh();

        if (! empty($task->assigned_user_id) && $task->assigned_user_id !== $previousAssignedUserId) {
            $this->dispatchAssignmentNotification($task);
        }

        $task = $task->fresh(['assignedUser', 'creator']);

        broadcast(new TaskUpdated($task));

        return $task;
    }

    public function deleteTask(Task $task): void
    {
        broadcast(new TaskDeleted($task->id));
        $task->delete();
    }

    private function dispatchAssignmentNotification(Task $task): void
    {
        $assignedUser = User::find($task->assigned_user_id);
        $creator = User::find($task->created_by);

        if (! $assignedUser || ! $creator) {
            return;
        }

        dispatch(new SendTaskAssignmentEmail(
            $task,
            $assignedUser->email,
            $assignedUser->name,
            $creator->name
        ));
    }
}
