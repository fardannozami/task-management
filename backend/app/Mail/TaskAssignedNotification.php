<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public string $assignedUserName,
        public string $assignedByName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Task Assigned: '.$this->task->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.task-assigned',
            with: [
                'taskTitle' => $this->task->title,
                'taskDescription' => $this->task->description,
                'taskStatus' => $this->task->status,
                'taskPriority' => $this->task->priority,
                'dueDate' => $this->task->due_date,
                'assignedUserName' => $this->assignedUserName,
                'assignedByName' => $this->assignedByName,
            ],
        );
    }
}
