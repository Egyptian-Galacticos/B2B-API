<?php

use App\Http\Resources\TagResource;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Register broadcasting authentication routes
Broadcast::routes(['middleware' => ['api', 'jwt.auth']]);

Route::fallback(function () {
    return redirect('docs/api');
});

Route::get('/test-tags', function () {
    return TagResource::collection(\Spatie\Tags\Tag::all());
});
