<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ApiToken;

interface ApiTokenRepositoryInterface
{
    public function add(ApiToken $token): void;

    public function findByHash(string $tokenHash): ?ApiToken;

    /** @return int liczba usuniętych tokenów */
    public function removeExpired(\DateTimeImmutable $now): int;
}
