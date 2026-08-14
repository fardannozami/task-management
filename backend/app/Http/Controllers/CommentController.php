<?php

namespace App\Http\Controllers;

use App\Events\TaskCommentCreated;
use App\Events\TaskCommentDeleted;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function index(Task $task): JsonResponse
    {
        $comments = $task->comments()
            ->with('user')
            ->latest('id')
            ->get();

        return response()->json($comments);
    }

    public function store(StoreCommentRequest $request, Task $task): JsonResponse
    {
        $comment = $task->comments()->create([
            'user_id' => auth('api')->id(),
            'comment' => $request->validated('comment'),
        ]);

        $comment->load('user');

        broadcast(new TaskCommentCreated($comment));

        return response()->json($comment, 201);
    }

    public function destroy(TaskComment $comment): JsonResponse
    {
        $user = auth('api')->user();

        if ($comment->user_id !== $user->id && ! in_array($user->role, ['admin', 'manager'], true)) {
            abort(403, 'You can only delete your own comments.');
        }

        $taskId = $comment->task_id;
        $commentId = $comment->id;

        $comment->delete();

        broadcast(new TaskCommentDeleted($taskId, $commentId));

        return response()->json(null, 204);
    }
}
