<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadAttachmentRequest;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

    public function stream(TaskAttachment $attachment): BinaryFileResponse
    {
        if ($this->attachments->isInfected($attachment)) {
            abort(403, 'File is quarantined due to security concerns.');
        }

        $disk = Storage::disk('attachments');

        if (! $disk->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return response()->file($disk->path($attachment->file_path), [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    public function destroy(TaskAttachment $attachment): JsonResponse
    {
        $this->attachments->delete($attachment);

        return response()->json(null, 204);
    }

    public function scan(TaskAttachment $attachment): JsonResponse
    {
        $result = $this->attachments->scan($attachment, auth('api')->id());

        return response()->json($result);
    }

    public function versions(TaskAttachment $attachment): JsonResponse
    {
        $versions = $this->attachments->getVersions($attachment);

        return response()->json($versions);
    }

    public function restoreVersion(TaskAttachment $attachment, int $version): JsonResponse
    {
        $result = $this->attachments->restoreVersion($attachment, $version, auth('api')->id());

        return response()->json($result, 201);
    }
}
