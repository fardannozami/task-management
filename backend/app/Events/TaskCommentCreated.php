<?php

namespace App\Events;

use App\Models\TaskComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCommentCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TaskComment $comment) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('task-comments.' . $this->comment->task_id)];
    }

    public function broadcastAs(): string
    {
        return 'comment.created';
    }

    public function broadcastWith(): array
    {
        $user = $this->comment->user;

        return [
            'id' => $this->comment->id,
            'task_id' => $this->comment->task_id,
            'user_id' => $this->comment->user_id,
            'comment' => $this->comment->comment,
            'created_at' => $this->comment->created_at?->toIso8601String(),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
        ];
    }
}
