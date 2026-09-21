<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Entity\ApiToken;
use App\Domain\Repository\ApiTokenRepositoryInterface;

final class InMemoryApiTokenRepository implements ApiTokenRepositoryInterface
{
    /** @var array<string, ApiToken> indeksowane skrótem tokenu */
    public array $stored = [];

    public function add(ApiToken $token): void
    {
        $this->stored[$token->tokenHash()] = $token;
    }

    public function findByHash(string $tokenHash): ?ApiToken
    {
        return $this->stored[$tokenHash] ?? null;
    }

    public function removeExpired(\DateTimeImmutable $now): int
    {
        $removed = 0;
        foreach ($this->stored as $hash => $token) {
            if ($token->isExpiredAt($now)) {
                unset($this->stored[$hash]);
                ++$removed;
            }
        }

        return $removed;
    }
}
