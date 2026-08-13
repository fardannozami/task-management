<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('jwt');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('jwt');
