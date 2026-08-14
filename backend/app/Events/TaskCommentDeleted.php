<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TaskCommentDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $taskId,
        public int $commentId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('task-comments.' . $this->taskId)];
    }

    public function broadcastAs(): string
    {
        return 'comment.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->commentId,
            'task_id' => $this->taskId,
        ];
    }
}
