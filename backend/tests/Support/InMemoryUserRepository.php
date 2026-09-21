<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<string, User> */
    private array $users = [];

    public function findByEmail(string $email): ?User
    {
        return $this->users[User::normalizeEmail($email)] ?? null;
    }

    public function add(User $user): void
    {
        $this->users[$user->email()] = $user;
    }
}
