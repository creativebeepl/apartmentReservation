<?php

declare(strict_types=1);

namespace App\Application\Auth;

final readonly class IssuedToken
{
    public function __construct(
        /** Jawny token — pokazujemy go klientowi tylko raz, w bazie jest sam skrót. */
        public string $plainToken,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
