<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\EmailVerificationController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\SellerUpgradeController;
use App\Http\Controllers\Api\v1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ========================================
    // PUBLIC ROUTES (No Authentication Required)
    // ========================================

    Route::prefix('auth')->group(function () {
        // Authentication endpoints
        Route::post('login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('refresh-token', [AuthController::class, 'refresh'])->name('auth.refresh');

        // Password reset endpoints
        Route::post('forgot-password', [AuthController::class, 'sendResetLink'])->name('auth.forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');

        // Email verification (token-based, no auth required)
        Route::post('email/verify', [EmailVerificationController::class, 'verify'])->name('auth.email.verify');
    });

    // Public products endpoint (browsing without auth)
    Route::get('products', [ProductController::class, 'index'])->name('products.public.index');
    Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.public.show');

    // ========================================
    // PROTECTED ROUTES (Authentication Required)
    // ========================================

    Route::middleware(['auth:api'])->group(function () {

        // =====================================
        // BASIC AUTH ROUTES (Just Authentication)
        // =====================================
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        });

        // Email verification management (requires auth but not verified email)
        Route::prefix('email')->group(function () {
            Route::post('send-verification', [EmailVerificationController::class, 'send'])->name('email.send');
            Route::post('resend-verification', [EmailVerificationController::class, 'resend'])->name('email.resend');
            Route::get('status', [EmailVerificationController::class, 'status'])->name('email.status');
        });

        // Company email verification management
        Route::prefix('company-email')->group(function () {
            Route::post('send-verification', [EmailVerificationController::class, 'sendCompany'])->name('company-email.send');
            Route::post('resend-verification', [EmailVerificationController::class, 'resendCompany'])->name('company-email.resend');
        });

        // ====================================================
        // VERIFIED & ACTIVE USER ROUTES (Full Restrictions)
        // ====================================================
        Route::middleware(['is_email_verified', 'is_suspended'])->group(function () {

            Route::get('me', [AuthController::class, 'me'])->name('auth.me');

            // Product creation (no ownership check needed)
            Route::post('products', [ProductController::class, 'store'])->name('products.store');

            // Product management (requires ownership)
            Route::middleware(['product.owner'])->group(function () {
                Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update')->whereAlphaNumeric('product');
                Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy')->whereAlphaNumeric('product');

                // Product media management routes
                Route::delete('products/{product}/images/{mediaId}', [ProductController::class, 'deleteImage'])->name('products.images.destroy');
                Route::delete('products/{product}/document/{mediaId}', [ProductController::class, 'deleteDocument'])->name('products.documents.destroy');
            });

            // User management
            Route::prefix('users')->group(function () {
                Route::delete('{user}', [UserController::class, 'destroy'])->name('users.destroy');
                Route::patch('{user}/restore', [UserController::class, 'restore'])->name('users.restore');
                Route::delete('{user}/force-delete', [UserController::class, 'forceDelete'])->name('users.force-delete');
            });

            // Seller-specific routes
            Route::prefix('seller')->group(function () {
                // Seller upgrade management
                Route::post('upgrade', [SellerUpgradeController::class, 'upgradeToSeller'])->name('seller.upgrade');
                Route::get('upgrade-status', [SellerUpgradeController::class, 'getUpgradeStatus'])->name('seller.upgrade-status');

                // Company management for sellers
                Route::controller(SellerUpgradeController::class)->group(function () {
                    Route::get('company', 'getCompany')->name('seller.company.show');
                    Route::put('company', 'updateCompany')->name('seller.company.update');
                });
            });

            // =====================================
            // ROLE-BASED ROUTES (Add when needed)
            // =====================================
            /*
            // Admin only routes
            Route::middleware(['role:admin'])->group(function () {
                Route::get('admin/dashboard', [AdminController::class, 'dashboard']);
                Route::resource('admin/users', AdminUserController::class);
            });

            // Seller only routes
            Route::middleware(['role:seller'])->group(function () {
                Route::get('seller/dashboard', [SellerController::class, 'dashboard']);
                Route::resource('seller/inventory', InventoryController::class);
            });

            // Buyer only routes
            Route::middleware(['role:buyer'])->group(function () {
                Route::resource('orders', OrderController::class);
                Route::resource('cart', CartController::class);
            });
            */
        });
    });
});
