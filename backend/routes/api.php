<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ChunkedUploadController;
use App\Http\Controllers\BulkTaskController;
use App\Http\Controllers\ExportController;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('jwt');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('jwt');

Route::middleware('jwt')->get('/users', [UserController::class, 'index']);

Route::middleware('jwt')->prefix('tasks')->group(function () {
    Route::get('/', [TaskController::class, 'index']);
    Route::post('/', [TaskController::class, 'store']);
    Route::get('/{task}', [TaskController::class, 'show']);
    Route::put('/{task}', [TaskController::class, 'update']);
    Route::delete('/{task}', [TaskController::class, 'destroy']);

    Route::post('/{task}/attachments', [AttachmentController::class, 'upload'])->middleware('throttle:10,1');
});

Route::middleware('jwt')->prefix('attachments')->group(function () {
    Route::get('/{attachment}/download', [AttachmentController::class, 'download']);
    Route::get('/{attachment}/thumbnail', [AttachmentController::class, 'downloadThumbnail']);
    Route::delete('/{attachment}', [AttachmentController::class, 'destroy']);
    Route::post('/{attachment}/scan', [AttachmentController::class, 'scan']);
    Route::get('/{attachment}/versions', [AttachmentController::class, 'versions']);
    Route::post('/{attachment}/versions/{version}/restore', [AttachmentController::class, 'restoreVersion']);
});

Route::middleware('jwt')->prefix('chunked-uploads')->group(function () {
    Route::post('/tasks/{task}/init', [ChunkedUploadController::class, 'init']);
    Route::post('/{chunkedUpload}/chunk', [ChunkedUploadController::class, 'uploadChunk']);
    Route::post('/{chunkedUpload}/merge', [ChunkedUploadController::class, 'merge']);
    Route::delete('/{chunkedUpload}', [ChunkedUploadController::class, 'cancel']);
});

Route::middleware('jwt')->prefix('bulk')->group(function () {
    Route::post('/tasks/status', [BulkTaskController::class, 'updateStatus']);
});

Route::middleware('jwt')->prefix('exports')->group(function () {
    Route::post('/tasks/csv', [ExportController::class, 'exportCsv']);
    Route::post('/tasks/pdf', [ExportController::class, 'exportPdf']);
    Route::get('/tasks/csv/download', [ExportController::class, 'downloadCsv']);
    Route::get('/tasks/pdf/download', [ExportController::class, 'downloadPdf']);
});
