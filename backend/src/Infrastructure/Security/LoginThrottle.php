<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/** Ogranicza liczbę prób logowania: osobno na parę IP+e-mail i na samo IP. */
final readonly class LoginThrottle
{
    public function __construct(
        private RateLimiterFactoryInterface $loginCredentialsLimiter,
        private RateLimiterFactoryInterface $loginIpLimiter,
    ) {
    }

    /** @throws TooManyRequestsHttpException */
    public function hit(string $ip, string $email): void
    {
        $attempts = [
            [$this->loginIpLimiter, $ip],
            [$this->loginCredentialsLimiter, $ip . '|' . mb_strtolower(trim($email))],
        ];

        foreach ($attempts as [$factory, $key]) {
            $limit = $factory->create($key)->consume();

            if (! $limit->isAccepted()) {
                $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());

                throw new TooManyRequestsHttpException($retryAfter, 'Too many login attempts. Try again later.');
            }
        }
    }
}
