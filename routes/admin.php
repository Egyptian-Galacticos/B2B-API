<?php

use App\Http\Controllers\Api\v1\Admin\AdminProductController;
use App\Http\Controllers\Api\v1\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['jwt.auth', 'role:admin'])->group(function () {

    // User Management Routes
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::post('/bulk-action', [AdminUserController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/{id}', [AdminUserController::class, 'show'])->name('show');
        Route::put('/{id}', [AdminUserController::class, 'update'])->name('update');
    });

    // Seller Registration Review Routes
    Route::prefix('seller-registrations')->name('admin.seller-registrations.')->group(function () {
        Route::get('/', [AdminUserController::class, 'pendingSellerRegistrations'])->name('index');
        Route::put('/{id}/review', [AdminUserController::class, 'reviewSellerRegistration'])->name('review');
    });

    // Product Management Routes
    Route::prefix('products')->name('admin.products.')->group(function () {
        Route::get('/', [AdminProductController::class, 'index'])->name('index');
        Route::get('/{id}', [AdminProductController::class, 'show'])->name('show');
        Route::put('/{id}/status', [AdminProductController::class, 'updateStatus'])->name('update-status');
        Route::post('/bulk-action', [AdminProductController::class, 'bulkAction'])->name('bulk-action');
    });

});
