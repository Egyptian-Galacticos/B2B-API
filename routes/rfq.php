<?php

use App\Http\Controllers\Api\v1\RfqController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt.auth', 'is_suspended:active'])->group(function () {
    Route::prefix('rfqs')->group(function () {
        Route::post('/', [RfqController::class, 'store']);

        Route::get('/', [RfqController::class, 'index']);
        Route::get('/{rfq}', [RfqController::class, 'show']);
        Route::patch('/{rfq}/in-progress', [RfqController::class, 'markInProgress']);
        Route::patch('/{rfq}/seen', [RfqController::class, 'markSeen']);
        Route::patch('/{rfq}/reject', [RfqController::class, 'reject']);
        Route::patch('/{rfq}/accept', [RfqController::class, 'accept']);
        Route::patch('/{rfq}/close', [RfqController::class, 'close']);

    });
});
