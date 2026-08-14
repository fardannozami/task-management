<?php

namespace App\Jobs;

use App\Mail\TaskAssignedNotification;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTaskAssignmentEmail implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public string $assignedUserEmail,
        public string $assignedUserName,
        public string $assignedByName
    ) {}

    public function handle(): void
    {
        Mail::to($this->assignedUserEmail)->send(
            new TaskAssignedNotification(
                $this->task,
                $this->assignedUserName,
                $this->assignedByName
            )
        );
    }
}
