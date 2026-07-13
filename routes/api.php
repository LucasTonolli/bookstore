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

    Route::prefix('/auth')->group(function () {
        Route::post('/register', \App\Http\Controllers\V1\Auth\RegisterController::class);
        Route::post('/login', [\App\Http\Controllers\V1\Auth\AuthController::class, 'login']);
    });
});
