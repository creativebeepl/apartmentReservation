<?php

declare(strict_types=1);

namespace App\UI\Http\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

/** Jednolity kształt błędów API: {"message": ..., "code": ..., "errors": {...}?}. */
final class ApiErrorResponse
{
    /**
     * @param array<string, list<string>>|null $errors błędy pól formularza (nazwa pola => komunikaty)
     * @param array<mixed> $headers
     */
    public static function create(
        int $status,
        string $code,
        string $message,
        ?array $errors = null,
        array $headers = [],
    ): JsonResponse {
        $body = ['message' => $message, 'code' => $code];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return new JsonResponse($body, $status, $headers);
    }
}
