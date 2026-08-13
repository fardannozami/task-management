<?php

namespace App\Services;

use App\Models\TaskAttachment;
use App\Models\TaskAttachmentVersion;
use App\Models\VirusScanResult;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentVersionService
{
    private const DISK = 'attachments';

    public function createVersion(TaskAttachment $attachment, string $newFilePath, string $newFileName, int $newFileSize, ?string $newMimeType, ?int $userId = null, ?string $changeDescription = null): TaskAttachmentVersion
    {
        $latestVersion = $attachment->versions()->max('version') ?? 0;
        $newVersion = $latestVersion + 1;

        return TaskAttachmentVersion::create([
            'task_attachment_id' => $attachment->id,
            'user_id' => $userId,
            'file_path' => $newFilePath,
            'file_name' => $newFileName,
            'file_size' => $newFileSize,
            'mime_type' => $newMimeType,
            'version' => $newVersion,
            'change_description' => $changeDescription,
            'uploaded_at' => now(),
        ]);
    }

    public function getVersions(TaskAttachment $attachment)
    {
        return $attachment->versions()->with('user')->orderByDesc('version')->get();
    }

    public function getVersion(TaskAttachment $attachment, int $version): ?TaskAttachmentVersion
    {
        return $attachment->versions()->where('version', $version)->first();
    }

    public function restoreVersion(TaskAttachment $attachment, int $version, ?int $userId = null): TaskAttachmentVersion
    {
        $sourceVersion = $this->getVersion($attachment, $version);

        if (!$sourceVersion) {
            abort(404, 'Version not found.');
        }

        $disk = Storage::disk(self::DISK);

        if (!$disk->exists($sourceVersion->file_path)) {
            abort(404, 'Version file not found on disk.');
        }

        $extension = pathinfo($sourceVersion->file_name, PATHINFO_EXTENSION);
        $randomName = Str::random(40) . '.' . $extension;
        $newPath = $randomName;

        $disk->copy($sourceVersion->file_path, $newPath);

        $attachment->update([
            'file_path' => $newPath,
            'file_size' => $sourceVersion->file_size,
            'mime_type' => $sourceVersion->mime_type,
            'file_name' => $sourceVersion->file_name,
        ]);

        $version = $this->createVersion(
            $attachment,
            $newPath,
            $sourceVersion->file_name,
            $sourceVersion->file_size,
            $sourceVersion->mime_type,
            $userId,
            "Restored from version {$version}"
        );

        if (function_exists('chmod')) {
            @chmod(storage_path('app/attachments/' . $newPath), 0640);
        }

        return $version;
    }

    public function deleteOldVersions(TaskAttachment $attachment, int $keepVersions = 5): void
    {
        $versions = $attachment->versions()
            ->orderByDesc('version')
            ->skip($keepVersions)
            ->get();

        $disk = Storage::disk(self::DISK);

        foreach ($versions as $version) {
            if ($version->file_path && $disk->exists($version->file_path)) {
                $disk->delete($version->file_path);
            }

            $version->delete();
        }
    }
}
