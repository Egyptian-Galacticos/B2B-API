<?php

use App\Http\Controllers\Api\v1\ContractController;
use App\Http\Controllers\Api\v1\QuoteController;
use App\Http\Controllers\Api\v1\RfqController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt.auth', 'is_email_verified', 'is_suspended:active'])->group(function () {
    Route::apiResource('rfqs', RfqController::class);

    Route::prefix('quotes')->name('quotes.')->group(function () {
        Route::get('/', [QuoteController::class, 'index'])->name('index');
        Route::post('/', [QuoteController::class, 'store'])->name('store');
        Route::get('/{id}', [QuoteController::class, 'show'])->name('show');
        Route::put('/{id}', [QuoteController::class, 'update'])->name('update');
        Route::delete('/{id}', [QuoteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/', [ContractController::class, 'index'])->name('index');
        Route::get('/{id}', [ContractController::class, 'show'])->name('show');
        Route::put('/{id}', [ContractController::class, 'update'])->name('update');
    });
});
