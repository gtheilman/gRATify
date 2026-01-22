<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use App\Support\Errors;

/**
 * Base controller with a shared error envelope for consistent API responses.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function errorResponse(string $code, ?string $message, int $status): JsonResponse
    {
        // Use centralized messages so clients can rely on stable error strings.
        $message = $message ?? Errors::message($code);
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
