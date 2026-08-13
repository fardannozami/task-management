<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_attachment_id', 'user_id', 'file_path', 'file_name', 'file_size', 'mime_type', 'version', 'change_description', 'uploaded_at'])]
class TaskAttachmentVersion extends Model
{
    protected $table = 'attachment_versions';
    public $timestamps = false;
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'file_size' => 'integer',
            'version' => 'integer',
        ];
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(TaskAttachment::class, 'task_attachment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
