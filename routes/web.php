<?php

use App\Http\Resources\TagResource;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Register broadcasting authentication routes
// Moved to api.php for proper JWT authentication
// Broadcast::routes(['middleware' => ['api', 'cors', 'jwt.auth']]);

// Test authentication endpoint
Route::post('/test-auth', function (Illuminate\Http\Request $request) {
    try {
        $user = auth('api')->user();
        if ($user) {
            return response()->json([
                'authenticated' => true,
                'user_id'       => $user->id,
                'user_email'    => $user->email,
                'token_present' => $request->hasHeader('Authorization'),
                'auth_header'   => $request->header('Authorization') ? substr($request->header('Authorization'), 0, 20).'...' : 'Not present',
            ]);
        } else {
            return response()->json([
                'authenticated' => false,
                'error'         => 'No authenticated user',
                'token_present' => $request->hasHeader('Authorization'),
                'auth_header'   => $request->header('Authorization') ? substr($request->header('Authorization'), 0, 20).'...' : 'Not present',
            ]);
        }
    } catch (Exception $e) {
        return response()->json([
            'authenticated' => false,
            'error'         => $e->getMessage(),
            'token_present' => $request->hasHeader('Authorization'),
            'auth_header'   => $request->header('Authorization') ? substr($request->header('Authorization'), 0, 20).'...' : 'Not present',
        ]);
    }
})->middleware(['api']);

Route::get('/test-tags', function () {
    return TagResource::collection(\Spatie\Tags\Tag::all());
});

// Real-time WebSocket test page
Route::get('/test-realtime', function () {
    return view('test-realtime');
});

Route::fallback(function () {
    return redirect('docs/api');
});
