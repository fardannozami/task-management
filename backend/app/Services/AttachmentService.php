<?php

namespace App\Services;

use App\Jobs\GenerateThumbnail;
use App\Jobs\ScanAttachment;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskAttachmentVersion;
use App\Models\VirusScanResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt',
        'mp4', 'mov', 'avi',
    ];

    private const MAX_FILE_SIZE = 51200;

    private AttachmentVersionService $versionService;

    public function __construct()
    {
        $this->versionService = new AttachmentVersionService;
    }

    public function upload(Task $task, UploadedFile $file, ?string $changeDescription = null): TaskAttachment
    {
        $this->validateExtension($file);
        $this->validateMimeType($file);

        $extension = $file->getClientOriginalExtension();
        $randomName = Str::random(40).'.'.$extension;
        $path = $file->storeAs('', $randomName, 'attachments');

        Storage::disk('attachments')->setVisibility($path, 'private');

        if (function_exists('chmod')) {
            @chmod(storage_path('app/attachments/'.$path), 0640);
        }

        $sanitizedFileName = $this->sanitizeFileName($file->getClientOriginalName());
        $existingAttachment = TaskAttachment::where('task_id', $task->id)
            ->where('file_name', $sanitizedFileName)
            ->orderByDesc('created_at')
            ->first();

        if ($existingAttachment) {
            $this->versionService->createVersion(
                $existingAttachment,
                $path,
                $sanitizedFileName,
                $file->getSize(),
                $file->getMimeType(),
                auth('api')->id(),
                $changeDescription
            );

            $existingAttachment->update([
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_at' => now(),
                'thumbnail_path' => null,
                'thumbnail_size' => null,
            ]);

            dispatch(new GenerateThumbnail($existingAttachment->id, $existingAttachment->file_path, $existingAttachment->mime_type));
            dispatch(new ScanAttachment($existingAttachment->id, auth('api')->id()));

            return $existingAttachment->fresh();
        }

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'file_name' => $sanitizedFileName,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now(),
            'thumbnail_path' => null,
            'thumbnail_size' => null,
        ]);

        $this->versionService->createVersion(
            $attachment,
            $path,
            $sanitizedFileName,
            $file->getSize(),
            $file->getMimeType(),
            auth('api')->id(),
            $changeDescription ?? 'Initial version'
        );

        dispatch(new GenerateThumbnail($attachment->id, $attachment->file_path, $attachment->mime_type));
        dispatch(new ScanAttachment($attachment->id, auth('api')->id()));

        return $attachment;
    }

    public function download(TaskAttachment $attachment)
    {
        if ($this->isInfected($attachment)) {
            abort(403, 'File is quarantined due to security concerns.');
        }

        $disk = Storage::disk('attachments');

        if (! $disk->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return $disk->download($attachment->file_path, $attachment->file_name);
    }

    public function downloadThumbnail(TaskAttachment $attachment)
    {
        if (empty($attachment->thumbnail_path)) {
            abort(404, 'Thumbnail not found');
        }

        $disk = Storage::disk('attachments');

        if (! $disk->exists($attachment->thumbnail_path)) {
            abort(404, 'Thumbnail file not found');
        }

        $thumbnailName = pathinfo($attachment->file_name, PATHINFO_FILENAME).'_thumbnail.jpg';

        return $disk->download($attachment->thumbnail_path, $thumbnailName);
    }

    public function delete(TaskAttachment $attachment): void
    {
        Storage::disk('attachments')->delete($attachment->file_path);

        if ($attachment->thumbnail_path) {
            Storage::disk('attachments')->delete($attachment->thumbnail_path);
        }

        $attachment->delete();
    }

    public function scan(TaskAttachment $attachment, ?int $userId = null): VirusScanResult
    {
        return app(VirusScanService::class)->scan($attachment, $userId);
    }

    public function getScanResult(TaskAttachment $attachment): ?VirusScanResult
    {
        return app(VirusScanService::class)->getScanResult($attachment);
    }

    public function isClean(TaskAttachment $attachment): bool
    {
        return app(VirusScanService::class)->isClean($attachment);
    }

    public function isInfected(TaskAttachment $attachment): bool
    {
        return app(VirusScanService::class)->isInfected($attachment);
    }

    public function getVersions(TaskAttachment $attachment)
    {
        return $this->versionService->getVersions($attachment);
    }

    public function restoreVersion(TaskAttachment $attachment, int $version, ?int $userId = null): TaskAttachmentVersion
    {
        return $this->versionService->restoreVersion($attachment, $version, $userId);
    }

    private function validateExtension(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS)) {
            abort(422, 'Unsupported file extension: .'.$extension);
        }
    }

    private function validateMimeType(UploadedFile $file): void
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file->getRealPath());

        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'video/mp4', 'video/quicktime', 'video/x-msvideo',
        ];

        if (! in_array($mimeType, $allowedMimes)) {
            abort(422, 'Unsupported file content type detected.');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE * 1024) {
            abort(422, 'File size exceeds maximum limit of 50MB.');
        }
    }

    private function sanitizeFileName(string $fileName): string
    {
        $fileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fileName);
        $fileName = preg_replace('/_{2,}/', '_', $fileName);
        $fileName = trim($fileName, '_');

        if (empty($fileName)) {
            $fileName = 'file_'.time();
        }

        return $fileName;
    }
}
