<?php

use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController as UserPostController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/api-test', function () {
    return response()->json(['message' => 'Welcome to the API!']);
});

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json(new UserResource($request->user()));
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('admin')->prefix('/admin')->group(function () {
        Route::apiResource('/posts', AdminPostController::class)->parameters([
            'posts' => 'post:slug',
        ]);
        Route::post('/posts/{post:slug}/publish', [AdminPostController::class, 'publish']);
        Route::post('/posts/{post:slug}/unpublish', [AdminPostController::class, 'unpublish']);
    });
});

Route::apiResource('/posts', UserPostController::class)->only(['index', 'show'])->parameters([
    'posts' => 'post:slug',
]);
