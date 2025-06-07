<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    public function apiResponse($data = null, $message = '', $status = 200): JsonResponse
    {
        return response()->json([
            'success' => $status < 400 ? true : false,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public function apiResponseErrors($message, $errors = [], $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }
}
