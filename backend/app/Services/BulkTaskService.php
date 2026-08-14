<?php

namespace App\Services;

use App\Jobs\SendTaskAssignmentEmail;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class BulkTaskService
{
    public function updateStatus(array $taskIds, string $status, ?int $updatedBy = null): int
    {
        $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];

        if (! in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('Invalid status value.');
        }

        $updated = 0;

        DB::transaction(function () use ($taskIds, $status, &$updated) {
            $tasks = Task::whereIn('id', $taskIds)->get();

            foreach ($tasks as $task) {
                $task->status = $status;
                $task->save();
                $updated++;
            }
        });

        return $updated;
    }

    public function updatePriority(array $taskIds, string $priority, ?int $updatedBy = null): int
    {
        $validPriorities = ['low', 'medium', 'high', 'urgent'];

        if (! in_array($priority, $validPriorities)) {
            throw new \InvalidArgumentException('Invalid priority value.');
        }

        $updated = 0;

        DB::transaction(function () use ($taskIds, $priority, &$updated) {
            $tasks = Task::whereIn('id', $taskIds)->get();

            foreach ($tasks as $task) {
                $task->priority = $priority;
                $task->save();
                $updated++;
            }
        });

        return $updated;
    }

    public function assignTasks(array $taskIds, ?int $assignedUserId, ?int $updatedBy = null): int
    {
        $updated = 0;

        DB::transaction(function () use ($taskIds, $assignedUserId, &$updated) {
            $tasks = Task::whereIn('id', $taskIds)->get();

            foreach ($tasks as $task) {
                $previousAssignedUserId = $task->assigned_user_id;

                $task->assigned_user_id = $assignedUserId;
                $task->save();

                if ($assignedUserId !== null && $assignedUserId !== $previousAssignedUserId) {
                    $this->dispatchAssignmentNotification($task);
                }

                $updated++;
            }
        });

        return $updated;
    }

    private function dispatchAssignmentNotification(Task $task): void
    {
        if (empty($task->assigned_user_id)) {
            return;
        }

        $assignedUser = $task->assignedUser;
        $creator = $task->creator;

        if (! $assignedUser || ! $creator) {
            return;
        }

        SendTaskAssignmentEmail::dispatch(
            $task,
            $assignedUser->email,
            $assignedUser->name,
            $creator->name
        );
    }
}
