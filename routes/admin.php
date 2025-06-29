<?php

use App\Http\Controllers\Api\v1\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['jwt.auth', 'role:admin'])->group(function () {

    // User Management Routes
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::put('/{id}', [AdminUserController::class, 'update'])->name('update');
    });

});
