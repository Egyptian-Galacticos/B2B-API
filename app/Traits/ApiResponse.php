<?php

namespace App\Traits;

trait ApiResponse
{
    public function apiResponse($data = null, $message = '', $status = 200)
    {
        return response()->json([
            'success' => $status < 400 ? true : false,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }
}
