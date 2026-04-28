<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class ApiResponse
{
    public static function success(
        JsonResource|array|null $data = null,
        string $message = 'success',
        int $status = Response::HTTP_OK
    ) {
        return Response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public static function error(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        array $errors = []
    ) {
        return Response()->json([
            'success' => true,
            'message' => $message,
            'error' => $errors
        ], $status);
    }
}
