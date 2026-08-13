<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AttachmentService
{
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt',
        'mp4', 'mov', 'avi',
    ];

    private const MAX_FILE_SIZE = 51200;

    private const THUMBNAIL_WIDTH = 300;
    private const THUMBNAIL_HEIGHT = 300;
    private const THUMBNAIL_QUALITY = 80;

    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    public function upload(Task $task, UploadedFile $file): TaskAttachment
    {
        $this->validateExtension($file);
        $this->validateMimeType($file);

        $extension = $file->getClientOriginalExtension();
        $randomName = Str::random(40) . '.' . $extension;
        $path = $file->storeAs('', $randomName, 'attachments');

        Storage::disk('attachments')->setVisibility($path, 'private');

        if (function_exists('chmod')) {
            @chmod(storage_path('app/attachments/' . $path), 0640);
        }

        $thumbnailPath = null;
        $thumbnailSize = null;

        if (str_starts_with($file->getMimeType(), 'image/')) {
            $thumbnail = $this->generateThumbnail($file->getRealPath());
            $thumbnailRandomName = Str::random(40) . '.jpg';
            $thumbnailPath = 'thumbnails/' . $thumbnailRandomName;
            Storage::disk('attachments')->put($thumbnailPath, $thumbnail->toJpeg(self::THUMBNAIL_QUALITY));
            $thumbnailSize = Storage::disk('attachments')->size($thumbnailPath);
        }

        return TaskAttachment::create([
            'task_id' => $task->id,
            'file_name' => $this->sanitizeFileName($file->getClientOriginalName()),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now(),
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_size' => $thumbnailSize,
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

    public function downloadThumbnail(TaskAttachment $attachment)
    {
        if (empty($attachment->thumbnail_path)) {
            abort(404, 'Thumbnail not found');
        }

        $disk = Storage::disk('attachments');

        if (!$disk->exists($attachment->thumbnail_path)) {
            abort(404, 'Thumbnail file not found');
        }

        $thumbnailName = pathinfo($attachment->file_name, PATHINFO_FILENAME) . '_thumbnail.jpg';

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

    private function generateThumbnail(string $filePath): \Intervention\Image\Image
    {
        $image = $this->imageManager->read($filePath);

        $image->scaleDown(width: self::THUMBNAIL_WIDTH, height: self::THUMBNAIL_HEIGHT);

        if ($image->width() > self::THUMBNAIL_WIDTH || $image->height() > self::THUMBNAIL_HEIGHT) {
            $image->resize(self::THUMBNAIL_WIDTH, self::THUMBNAIL_HEIGHT, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        return $image;
    }

    private function validateExtension(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            abort(422, 'Unsupported file extension: .' . $extension);
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

        if (!in_array($mimeType, $allowedMimes)) {
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
            $fileName = 'file_' . time();
        }

        return $fileName;
    }
}
