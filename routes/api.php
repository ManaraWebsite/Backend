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
        Route::get('/posts', [AdminPostController::class, 'index']);
        Route::post('/posts', [AdminPostController::class, 'store']);
        Route::put('/posts/{post:slug}', [AdminPostController::class, 'update']);  //i'm heeeeere
        // Route::delete('/posts/{post:slug}', [AdminPostController::class, 'destroy']);
    });
});

Route::get('/posts', [UserPostController::class, 'index']);
Route::get('/posts/{post:slug}', [UserPostController::class, 'show']);
