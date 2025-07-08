<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class BroadcastingController extends Controller
{
    /**
     * Authenticate the request for broadcasting channels.
     */
    public function authenticate(Request $request)
    {
        // Validate required parameters
        $request->validate([
            'channel_name' => 'required|string',
            'socket_id'    => 'required|string',
        ]);

        try {
            return Broadcast::auth($request);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Broadcasting authentication failed',
                'message' => $e->getMessage(),
                'channel' => $request->input('channel_name'),
            ], 403);
        }
    }
}
