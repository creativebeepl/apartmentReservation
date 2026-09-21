<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Auth\InvalidCredentialsException;
use App\Application\Auth\LoginHandler;
use App\Infrastructure\Security\LoginThrottle;
use App\UI\Http\Request\LoginRequest;
use App\UI\Http\Response\ApiErrorResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class LoginController
{
    public function __construct(
        private LoginHandler $login,
        private LoginThrottle $throttle,
    ) {
    }

    #[Route('/api/auth/login', name: 'api_login', methods: ['POST'])]
    public function __invoke(Request $request, #[MapRequestPayload] LoginRequest $payload): JsonResponse
    {
        $email = (string) $payload->email;
        $this->throttle->hit($request->getClientIp() ?? 'unknown', $email);

        try {
            $token = ($this->login)($email, (string) $payload->password);
        } catch (InvalidCredentialsException $e) {
            return ApiErrorResponse::create(Response::HTTP_UNAUTHORIZED, 'invalid_credentials', $e->getMessage());
        }

        return new JsonResponse([
            'data' => [
                'token' => $token->plainToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->expiresAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            ],
        ]);
    }
}
