<?php

use App\Http\Controllers\Api\v1\QuoteController;
use App\Http\Controllers\Api\v1\RfqController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt.auth', 'is_suspended:active'])->group(function () {
    Route::prefix('rfqs')->group(function () {
        Route::post('/', [RfqController::class, 'store']);

        Route::get('/', [RfqController::class, 'index']);
        Route::get('/{id}', [RfqController::class, 'show']);
        Route::patch('/{id}/in-progress', [RfqController::class, 'markInProgress']);
        Route::patch('/{id}/seen', [RfqController::class, 'markSeen']);
        Route::patch('/{id}/reject', [RfqController::class, 'reject']);
        Route::patch('/{id}/close', [RfqController::class, 'close']);
    });

    Route::prefix('quotes')->name('quotes.')->group(function () {
        Route::get('/', [QuoteController::class, 'index'])->name('index');
        Route::post('/', [QuoteController::class, 'store'])->name('store');
        Route::get('/{id}', [QuoteController::class, 'show'])->name('show');
        Route::put('/{id}', [QuoteController::class, 'update'])->name('update');
        Route::patch('/{id}/accept', [QuoteController::class, 'accept'])->name('accept');
        Route::patch('/{id}/reject', [QuoteController::class, 'reject'])->name('reject');
        Route::delete('/{id}', [QuoteController::class, 'destroy'])->name('destroy');
    });
});
