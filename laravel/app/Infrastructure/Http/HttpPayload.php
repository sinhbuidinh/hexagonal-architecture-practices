<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Illuminate\Http\JsonResponse;

final class HttpPayload
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function toJsonResponse(array $payload): JsonResponse
    {
        $status = (int) ($payload['status'] ?? 200);
        unset($payload['status']);

        return response()->json($payload, $status);
    }
}
