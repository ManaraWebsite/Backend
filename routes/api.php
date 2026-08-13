<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/api-test', function () {
    return response()->json(['message' => 'Welcome to the API!']);
});

Route::get('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json(new UserResource($request->user()));
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::get('/posts', [PostController::class, 'index']);
