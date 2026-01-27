<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Centralizes API error codes/messages so controllers and handlers never drift.
 */
class Errors
{
    public const UNAUTHENTICATED = 'unauthenticated';
    public const FORBIDDEN = 'forbidden';
    public const NOT_FOUND = 'not_found';
    public const LOCKED = 'locked';
    public const INVALID_SCHEME = 'invalid_scheme';
    public const APPEALS_NOT_READY = 'appeals_not_ready';

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            self::UNAUTHENTICATED => 'Unauthenticated',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::LOCKED => 'Locked',
            self::INVALID_SCHEME => 'Invalid scoring scheme',
            self::APPEALS_NOT_READY => 'Appeals are not yet available',
        ];
    }

    public static function message(string $code): string
    {
        return self::messages()[$code] ?? 'Error';
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function envelope(string $code): array
    {
        return [
            'error' => [
                'code' => $code,
                'message' => self::message($code),
            ],
        ];
    }

    public static function response(string $code, int $status): JsonResponse
    {
        return response()->json(self::envelope($code), $status);
    }
}
