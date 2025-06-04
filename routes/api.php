<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::get('me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('refresh/{token}', [AuthController::class, 'refresh'])->name('auth.refresh');
})->middleware('is_email_verified');

Route::get('/email/verify/{id}', [AuthController::class, 'verifyEmail'])->name('api.verification.verify');
Route::get('/email/resend/{id}', [AuthController::class, 'resendVerificationEmail'])->name('api.verification.resend');
Route::post('/password/forgot', [AuthController::class, 'sendResetLink'])->name('api.password.forgot');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('api.password.reset');
Route::resource('products', ProductController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);
