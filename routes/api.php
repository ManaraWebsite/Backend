<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\PostController as UserPostController;
use App\Http\Controllers\Admin\FormController as AdminFormController;
use App\Http\Controllers\FormController as UserFormController;
use App\Http\Controllers\Admin\FormSubmissionController as AdminFormSubmissionController;
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
        // Admin Posts routes
        Route::apiResource('/posts', AdminPostController::class)->parameters([
            'posts' => 'post:slug',
        ]);
        Route::post('/posts/{post:slug}/publish', [AdminPostController::class, 'publish']);
        Route::post('/posts/{post:slug}/unpublish', [AdminPostController::class, 'unpublish']);

        // Admin Forms routes
        Route::apiResource('/forms', AdminFormController::class)->parameters([
            'forms' => 'form:slug',
        ]);
        Route::post('forms/{form:slug}/duplicate', [AdminFormController::class, 'duplicate'])->name('forms.duplicate');
        Route::get('forms/{form:slug}/submissions', [AdminFormSubmissionController::class, 'index'])->name('forms.submissions');
        Route::get('forms/{form:slug}/submissions/export', [AdminFormSubmissionController::class, 'export'])->name('forms.submissions.export');
    });
});

Route::apiResource('/posts', UserPostController::class)->only(['index', 'show'])->parameters([
    'posts' => 'post:slug',
]);

Route::get('/forms/{form:slug}', [UserFormController::class, 'show'])->name('forms.show');
Route::post('/forms/{form:slug}/submit', [UserFormController::class, 'submit'])->middleware('throttle:5,1')->name('forms.submit');
