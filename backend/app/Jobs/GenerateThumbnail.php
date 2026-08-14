<?php

namespace App\Jobs;

use App\Models\TaskAttachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class GenerateThumbnail implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private const THUMBNAIL_WIDTH = 300;

    private const THUMBNAIL_HEIGHT = 300;

    private const THUMBNAIL_QUALITY = 80;

    public function __construct(
        public int $attachmentId,
        public string $filePath,
        public string $mimeType
    ) {}

    public function handle(): void
    {
        $attachment = TaskAttachment::find($this->attachmentId);

        if (! $attachment) {
            return;
        }

        if (! str_starts_with($this->mimeType, 'image/')) {
            return;
        }

        $disk = Storage::disk('attachments');

        if (! $disk->exists($this->filePath)) {
            return;
        }

        $imageManager = new ImageManager(new Driver);
        $image = $imageManager->read($disk->path($this->filePath));

        $image->scaleDown(width: self::THUMBNAIL_WIDTH, height: self::THUMBNAIL_HEIGHT);

        if ($image->width() > self::THUMBNAIL_WIDTH || $image->height() > self::THUMBNAIL_HEIGHT) {
            $image->resize(self::THUMBNAIL_WIDTH, self::THUMBNAIL_HEIGHT, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $thumbnailRandomName = Str::random(40).'.jpg';
        $thumbnailPath = 'thumbnails/'.$thumbnailRandomName;

        $disk->put($thumbnailPath, $image->toJpeg(self::THUMBNAIL_QUALITY));

        $attachment->update([
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_size' => $disk->size($thumbnailPath),
        ]);
    }
}
