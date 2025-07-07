<?php

use App\Http\Resources\TagResource;
use Illuminate\Support\Facades\Route;

Route::fallback(function () {
    return redirect('docs/api');
});

Route::get('/test-tags', function () {
    return TagResource::collection(\Spatie\Tags\Tag::all());
});
