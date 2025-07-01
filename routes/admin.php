<?php

use App\Http\Controllers\Api\v1\Admin\AdminCategoryController;
use App\Http\Controllers\Api\v1\Admin\AdminContractController;
use App\Http\Controllers\Api\v1\Admin\AdminProductController;
use App\Http\Controllers\Api\v1\Admin\AdminQuoteController;
use App\Http\Controllers\Api\v1\Admin\AdminRfqController;
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

    // Category Management Routes
    Route::prefix('categories')->name('admin.categories.')->group(function () {
        Route::get('/', [AdminCategoryController::class, 'index'])->name('index');
        Route::post('/', [AdminCategoryController::class, 'store'])->name('store');
        Route::get('/hierarchy', [AdminCategoryController::class, 'hierarchy'])->name('hierarchy');
        Route::get('/trashed', [AdminCategoryController::class, 'trashed'])->name('trashed');
        Route::post('/bulk-action', [AdminCategoryController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/{id}', [AdminCategoryController::class, 'show'])->name('show');
        Route::put('/{id}', [AdminCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminCategoryController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/restore', [AdminCategoryController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force', [AdminCategoryController::class, 'forceDelete'])->name('force-delete');
    });

    // RFQ Oversight Routes
    Route::prefix('rfqs')->name('admin.rfqs.')->group(function () {
        Route::get('/', [AdminRfqController::class, 'index'])->name('index'); // works
        Route::get('/{id}', [AdminRfqController::class, 'show'])->name('show'); // works
        Route::put('/{id}/status', [AdminRfqController::class, 'updateStatus'])->name('update-status');
        Route::post('/bulk-action', [AdminRfqController::class, 'bulkAction'])->name('bulk-action');
    });

    // Quote Oversight Routes
    Route::prefix('quotes')->name('admin.quotes.')->group(function () {
        Route::get('/', [AdminQuoteController::class, 'index'])->name('index');
        Route::get('/{id}', [AdminQuoteController::class, 'show'])->name('show');
        Route::put('/{id}/status', [AdminQuoteController::class, 'updateStatus'])->name('update-status');
        Route::post('/bulk-action', [AdminQuoteController::class, 'bulkAction'])->name('bulk-action');
    });

    // Contract Management Routes
    Route::prefix('contracts')->name('admin.contracts.')->group(function () {
        Route::get('/', [AdminContractController::class, 'index'])->name('index');
        Route::get('/{id}', [AdminContractController::class, 'show'])->name('show');
        Route::put('/{id}/status', [AdminContractController::class, 'updateStatus'])->name('update-status');
        Route::post('/bulk-action', [AdminContractController::class, 'bulkAction'])->name('bulk-action');
    });

});
