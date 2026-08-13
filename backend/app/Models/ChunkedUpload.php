<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id', 'user_id', 'original_file_name', 'temp_path', 'final_path',
    'mime_type', 'total_size', 'chunk_size', 'total_chunks', 'uploaded_chunks',
    'status', 'completed_at', 'expires_at',
])]
class ChunkedUpload extends Model
{
    protected function casts(): array
    {
        return [
            'total_size' => 'integer',
            'chunk_size' => 'integer',
            'total_chunks' => 'integer',
            'uploaded_chunks' => 'integer',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
