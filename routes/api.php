<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\HomeController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/telegram', [AuthController::class, 'login'])
    ->middleware('throttle:20,1');

Route::get('/files/{file}', [FileController::class, 'show'])
    ->name('files.show');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/home', [HomeController::class, 'index']);
});
