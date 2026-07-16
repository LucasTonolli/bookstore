<?php

use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\V1\Auth\RegisterController;
use App\Http\Controllers\V1\AuthorController;
use App\Http\Controllers\V1\BookController;
use App\Http\Controllers\V1\EmailVerificationController;
use App\Http\Controllers\V1\GenreController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::apiResource('authors', AuthorController::class);
    Route::apiResource('genres', GenreController::class);
    Route::apiResource('books', BookController::class);
    Route::apiResource('users', UserController::class);

    Route::prefix('/auth')->group(function () {
        Route::post('/register', RegisterController::class);
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::delete('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'forgotPassword'])->middleware('throttle:1,1');
        Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->middleware('throttle:10,1');
    });

    Route::prefix('/profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->middleware('auth:sanctum');
        Route::put('/', [ProfileController::class, 'update'])->middleware('auth:sanctum');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->middleware('auth:sanctum');
    });

    Route::prefix('/email')->group(function () {
        Route::post('/verification-notification', [EmailVerificationController::class, 'send'])->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');
        Route::get('/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['auth:sanctum', 'signed'])->name('verification.verify');
    });
});
