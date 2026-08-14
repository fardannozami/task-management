<?php

namespace App\Http\Controllers;

use App\Http\Requests\InitChunkedUploadRequest;
use App\Models\ChunkedUpload;
use App\Models\Task;
use App\Services\ChunkedUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChunkedUploadController extends Controller
{
    public function __construct(private ChunkedUploadService $chunkedUploads) {}

    public function init(InitChunkedUploadRequest $request, Task $task): JsonResponse
    {
        $data = $request->validated();

        $upload = $this->chunkedUploads->init(
            $task,
            $data['file_name'],
            $data['total_size'],
            $data['mime_type'] ?? null
        );

        return response()->json($upload, 201);
    }

    public function uploadChunk(Request $request, ChunkedUpload $chunkedUpload): JsonResponse
    {
        $request->validate([
            'chunk' => 'required|file',
            'chunk_index' => 'required|integer|min:0',
        ]);

        if ((int) $request->input('chunk_index') >= $chunkedUpload->total_chunks) {
            return response()->json(['message' => 'Invalid chunk index.'], 422);
        }

        $upload = $this->chunkedUploads->uploadChunk($chunkedUpload, $request->file('chunk'), (int) $request->input('chunk_index'));

        return response()->json($upload);
    }

    public function merge(ChunkedUpload $chunkedUpload): JsonResponse
    {
        $attachment = $this->chunkedUploads->merge($chunkedUpload);

        return response()->json($attachment, 201);
    }

    public function cancel(ChunkedUpload $chunkedUpload): JsonResponse
    {
        $this->chunkedUploads->cancel($chunkedUpload);

        return response()->json(null, 204);
    }
}
