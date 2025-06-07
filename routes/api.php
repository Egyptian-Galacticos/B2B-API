<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::get('me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('refresh/{token}', [AuthController::class, 'refresh'])->name('auth.refresh');
})->middleware('is_email_verified');

Route::prefix('auth')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::post('email/send-verification', [EmailVerificationController::class, 'send']);
        Route::post('email/resend-verification', [EmailVerificationController::class, 'resend']);
        Route::get('email/status', [EmailVerificationController::class, 'status']);
    });

    Route::post('email/verify', [EmailVerificationController::class, 'verify']);
});
Route::post('/password/forgot', [AuthController::class, 'sendResetLink'])->name('api.password.forgot');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('api.password.reset');
Route::resource('products', ProductController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);
Route::middleware('auth:api')->group(function () {
    Route::resource('users', UserController::class)->only(['destroy']);
    Route::patch('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('users/{id}/forcedelete', [UserController::class, 'forceDelete'])->name('users.forceDelete');
});
