<?php

declare(strict_types=1);

namespace App\UI\Http\Security;

use App\UI\Http\Response\ApiErrorResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/** Brak lub błędny token => 401 z ciałem JSON (zamiast pustej odpowiedzi domyślnego mechanizmu). */
final class JsonAuthenticationErrorHandler implements AuthenticationEntryPointInterface, AuthenticationFailureHandlerInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->unauthenticated();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->unauthenticated();
    }

    private function unauthenticated(): Response
    {
        return ApiErrorResponse::create(
            Response::HTTP_UNAUTHORIZED,
            'unauthenticated',
            'Authentication required: send a valid "Authorization: Bearer <token>" header.',
            headers: ['WWW-Authenticate' => 'Bearer'],
        );
    }
}
