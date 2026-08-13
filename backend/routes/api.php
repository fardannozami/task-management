<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AttachmentController;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('jwt');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('jwt');

Route::middleware('jwt')->prefix('tasks')->group(function () {
    Route::get('/', [TaskController::class, 'index']);
    Route::post('/', [TaskController::class, 'store']);
    Route::get('/{task}', [TaskController::class, 'show']);
    Route::put('/{task}', [TaskController::class, 'update']);
    Route::delete('/{task}', [TaskController::class, 'destroy']);

    Route::post('/{task}/attachments', [AttachmentController::class, 'upload']);
});

Route::middleware('jwt')->prefix('attachments')->group(function () {
    Route::get('/{attachment}/download', [AttachmentController::class, 'download']);
    Route::delete('/{attachment}', [AttachmentController::class, 'destroy']);
});
