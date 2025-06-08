<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\EmailVerificationController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\SellerUpgradeController;
use App\Http\Controllers\API\v1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('register', [AuthController::class, 'register'])->name('auth.register');
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('refresh-token', [AuthController::class, 'refresh'])->name('auth.refresh');
    })->middleware('is_email_verified');

    Route::prefix('auth')->group(function () {
        Route::middleware('auth:api')->group(function () {
            // user email verification
            Route::post('email/send-verification', [EmailVerificationController::class, 'send']);
            Route::post('email/resend-verification', [EmailVerificationController::class, 'resend']);

            // company email verification
            Route::post('company-email/send-verification', [EmailVerificationController::class, 'sendCompany']);
            Route::post('company-email/resend-verification', [EmailVerificationController::class, 'resendCompany']);

            Route::get('email/status', [EmailVerificationController::class, 'status']);
        });

        Route::post('email/verify', [EmailVerificationController::class, 'verify']); // work for both user and company email verification
    });
    Route::post('/reset-password', [AuthController::class, 'sendResetLink'])->name('api.password.forgot');
    Route::post('/forgot-password', [AuthController::class, 'resetPassword'])->name('api.password.reset');
    Route::resource('products', ProductController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy']);

    Route::middleware('auth:api')->group(function () {
        Route::resource('users', UserController::class)->only(['destroy']);
        Route::patch('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::delete('users/{id}/forcedelete', [UserController::class, 'forceDelete'])->name('users.forceDelete');
        Route::prefix('seller')->group(function () {
            Route::post('upgrade', [SellerUpgradeController::class, 'upgradeToSeller']);
            Route::get('upgrade-status', [SellerUpgradeController::class, 'getUpgradeStatus']);
            Route::put('company', [SellerUpgradeController::class, 'updateCompany']);
            Route::get('company', [SellerUpgradeController::class, 'getCompany']);
        });
    });

});
