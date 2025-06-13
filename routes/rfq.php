<?php

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
        Route::patch('/{id}/accept', [RfqController::class, 'accept']);
        Route::patch('/{id}/close', [RfqController::class, 'close']);

    });
});
