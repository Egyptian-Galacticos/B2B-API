<?php

use App\Http\Resources\TagResource;
use Illuminate\Support\Facades\Route;

Route::fallback(function () {
    return redirect('docs/api');
});

Route::get('test', [\App\Http\Controllers\TestController::class, 'create'])
    ->name('test.create');
Route::post('test', [\App\Http\Controllers\TestController::class, 'store'])
    ->name('test.store');

Route::get('/test-tags', function () {
    return TagResource::collection(\Spatie\Tags\Tag::all());
});
