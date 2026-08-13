<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    public function upload(Task $task, UploadedFile $file): TaskAttachment
    {
        $path = $file->store('', 'attachments');

        return TaskAttachment::create([
            'task_id' => $task->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now(),
        ]);
    }

    public function download(TaskAttachment $attachment)
    {
        $disk = Storage::disk('attachments');

        if (!$disk->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return $disk->download($attachment->file_path, $attachment->file_name);
    }

    public function delete(TaskAttachment $attachment): void
    {
        Storage::disk('attachments')->delete($attachment->file_path);
        $attachment->delete();
    }
}
