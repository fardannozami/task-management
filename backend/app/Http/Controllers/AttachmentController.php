<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadAttachmentRequest;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(private AttachmentService $attachments) {}

    public function upload(UploadAttachmentRequest $request, Task $task): JsonResponse
    {
        $attachment = $this->attachments->upload($task, $request->file('file'));

        return response()->json($attachment, 201);
    }

    public function download(TaskAttachment $attachment): StreamedResponse
    {
        return $this->attachments->download($attachment);
    }

    public function downloadThumbnail(TaskAttachment $attachment): StreamedResponse
    {
        return $this->attachments->downloadThumbnail($attachment);
    }

    public function destroy(TaskAttachment $attachment): JsonResponse
    {
        $this->attachments->delete($attachment);

        return response()->json(null, 204);
    }
}
