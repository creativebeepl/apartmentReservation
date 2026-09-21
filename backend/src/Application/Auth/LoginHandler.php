<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Entity\ApiToken;
use App\Domain\Entity\User;
use App\Domain\Repository\ApiTokenRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final readonly class LoginHandler
{
    private \DateInterval $tokenTtl;

    /** @param string $tokenTtl interwał ISO 8601, np. "PT12H" */
    public function __construct(
        private UserRepositoryInterface $users,
        private ApiTokenRepositoryInterface $tokens,
        private UserPasswordHasherInterface $passwordHasher,
        private ClockInterface $clock,
        string $tokenTtl = 'PT12H',
    ) {
        $this->tokenTtl = new \DateInterval($tokenTtl);
    }

    /** @throws InvalidCredentialsException */
    public function __invoke(string $email, string $password): IssuedToken
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            // Wyrównujemy czas odpowiedzi, żeby nie zdradzać, które adresy e-mail istnieją.
            $this->passwordHasher->hashPassword(new User(Uuid::v7(), 'unknown@invalid', ''), $password);

            throw InvalidCredentialsException::create();
        }

        if (! $this->passwordHasher->isPasswordValid($user, $password)) {
            throw InvalidCredentialsException::create();
        }

        $now = $this->clock->now();
        $plainToken = bin2hex(random_bytes(32));
        $expiresAt = $now->add($this->tokenTtl);

        $this->tokens->removeExpired($now);
        $this->tokens->add(new ApiToken(Uuid::v7(), $user, ApiToken::hash($plainToken), $expiresAt, $now));

        return new IssuedToken($plainToken, $expiresAt);
    }
}
