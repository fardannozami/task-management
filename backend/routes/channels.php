<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tasks', fn ($user) => $user !== null, ['guards' => ['api']]);

Broadcast::channel('task-comments.{taskId}', fn ($user, $taskId) => $user !== null, ['guards' => ['api']]);
