<?php

namespace App\Services;

use App\Jobs\ScanAttachment;
use App\Models\ChunkedUpload;
use App\Models\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkedUploadService
{
    private const DISK = 'attachments';
    private const MAX_CHUNK_SIZE = 5 * 1024 * 1024; // 5MB per chunk

    public function init(Task $task, string $fileName, int $totalSize, ?string $mimeType = null): ChunkedUpload
    {
        $chunkSize = self::MAX_CHUNK_SIZE;
        $totalChunks = (int) ceil($totalSize / $chunkSize);
        $tempPath = 'chunked/' . Str::random(40);

        Storage::disk(self::DISK)->makeDirectory($tempPath);

        return ChunkedUpload::create([
            'task_id' => $task->id,
            'user_id' => auth('api')->id(),
            'original_file_name' => $fileName,
            'temp_path' => $tempPath,
            'mime_type' => $mimeType,
            'total_size' => $totalSize,
            'chunk_size' => $chunkSize,
            'total_chunks' => $totalChunks,
            'uploaded_chunks' => 0,
            'status' => 'initiated',
            'expires_at' => now()->addHours(2),
        ]);
    }

    public function uploadChunk(ChunkedUpload $upload, UploadedFile $chunk, int $chunkIndex): ChunkedUpload
    {
        if ($upload->status === 'completed' || $upload->status === 'cancelled') {
            abort(409, 'Upload is no longer active.');
        }

        $chunkPath = $upload->temp_path . '/' . $chunkIndex;
        $chunk->storeAs('', $chunkPath, self::DISK);

        $upload->increment('uploaded_chunks');

        if ($upload->uploaded_chunks >= $upload->total_chunks) {
            $upload->update(['status' => 'ready_to_merge']);
        }

        return $upload->fresh();
    }

    public function merge(ChunkedUpload $upload): \App\Models\TaskAttachment
    {
        if ($upload->status === 'completed') {
            abort(409, 'Upload already merged.');
        }

        if ($upload->uploaded_chunks < $upload->total_chunks) {
            abort(422, 'Not all chunks have been uploaded.');
        }

        $extension = pathinfo($upload->original_file_name, PATHINFO_EXTENSION);
        $randomName = Str::random(40) . '.' . $extension;
        $finalPath = $randomName;

        $disk = Storage::disk(self::DISK);
        $targetStream = fopen($disk->path($finalPath), 'w');

        for ($i = 0; $i < $upload->total_chunks; $i++) {
            $chunkPath = $upload->temp_path . '/' . $i;
            $chunkStream = fopen($disk->path($chunkPath), 'r');
            stream_copy_to_stream($chunkStream, $targetStream);
            fclose($chunkStream);
        }

        fclose($targetStream);

        $attachment = \App\Models\TaskAttachment::create([
            'task_id' => $upload->task_id,
            'file_name' => $this->sanitizeFileName($upload->original_file_name),
            'file_path' => $finalPath,
            'file_size' => $upload->total_size,
            'mime_type' => $upload->mime_type ?? 'application/octet-stream',
            'uploaded_at' => now(),
        ]);

        dispatch(new ScanAttachment($attachment->id, auth('api')->id()));

        $upload->update([
            'status' => 'completed',
            'final_path' => $finalPath,
            'completed_at' => now(),
        ]);

        $this->cleanupChunks($upload);

        return $attachment;
    }

    public function cancel(ChunkedUpload $upload): void
    {
        if ($upload->status === 'completed') {
            abort(409, 'Cannot cancel completed upload.');
        }

        $this->cleanupChunks($upload);

        $upload->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    private function cleanupChunks(ChunkedUpload $upload): void
    {
        $disk = Storage::disk(self::DISK);

        if ($disk->exists($upload->temp_path)) {
            $disk->deleteDirectory($upload->temp_path);
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
