<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('v1')->group(function () {
    Route::apiResource('authors', \App\Http\Controllers\V1\AuthorController::class);
    Route::apiResource('genres', \App\Http\Controllers\V1\GenreController::class);
    Route::apiResource('books', \App\Http\Controllers\V1\BookController::class);
    Route::apiResource('users', \App\Http\Controllers\V1\UserController::class);

    Route::prefix('/auth')->group(function () {
        Route::post('/register', \App\Http\Controllers\V1\Auth\RegisterController::class);
        Route::post('/login', [\App\Http\Controllers\V1\Auth\AuthController::class, 'login'])->name('login');
        Route::delete('/logout', [\App\Http\Controllers\V1\Auth\AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    Route::prefix('/profile')->group(function () {
        Route::get('/', [\App\Http\Controllers\V1\ProfileController::class, 'show'])->middleware('auth:sanctum');
        Route::put('/', [\App\Http\Controllers\V1\ProfileController::class, 'update'])->middleware('auth:sanctum');
        Route::put('/password', [\App\Http\Controllers\V1\ProfileController::class, 'updatePassword'])->middleware('auth:sanctum');
    });

    Route::prefix('/email')->group(function () {
        Route::post('/verification-notification', [\App\Http\Controllers\V1\EmailVerificationController::class, 'send'])->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');
        Route::get('/verify/{id}/{hash}', [\App\Http\Controllers\V1\EmailVerificationController::class, 'verify'])->middleware(['auth:sanctum', 'signed'])->name('verification.verify');
    });
});
