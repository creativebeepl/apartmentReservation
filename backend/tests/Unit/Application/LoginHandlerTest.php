<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\Auth\InvalidCredentialsException;
use App\Application\Auth\LoginHandler;
use App\Application\Auth\RegisterUserHandler;
use App\Application\Auth\UserAlreadyExistsException;
use App\Domain\Entity\ApiToken;
use App\Tests\Support\InMemoryApiTokenRepository;
use App\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class LoginHandlerTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryApiTokenRepository $tokens;
    private MockClock $clock;
    private LoginHandler $login;
    private RegisterUserHandler $register;

    protected function setUp(): void
    {
        $hasher = new UserPasswordHasher(new PasswordHasherFactory([
            PasswordAuthenticatedUserInterface::class => ['algorithm' => 'bcrypt', 'cost' => 4],
        ]));

        $this->users = new InMemoryUserRepository();
        $this->tokens = new InMemoryApiTokenRepository();
        $this->clock = new MockClock('2026-06-01 12:00:00 UTC');
        $this->login = new LoginHandler($this->users, $this->tokens, $hasher, $this->clock, 'PT2H');
        $this->register = new RegisterUserHandler($this->users, $hasher);
    }

    public function testIssuesTokenForValidCredentials(): void
    {
        ($this->register)('Jan@Example.com', 'correct-horse');

        $issued = ($this->login)('jan@example.com', 'correct-horse');

        self::assertSame(64, strlen($issued->plainToken));
        self::assertSame('2026-06-01T14:00:00+00:00', $issued->expiresAt->format('c'));
    }

    public function testStoresOnlyTheHashOfTheToken(): void
    {
        ($this->register)('jan@example.com', 'correct-horse');

        $issued = ($this->login)('jan@example.com', 'correct-horse');

        self::assertCount(1, $this->tokens->stored);
        self::assertNotNull($this->tokens->findByHash(ApiToken::hash($issued->plainToken)));
        self::assertNull($this->tokens->findByHash($issued->plainToken));
    }

    public function testEachLoginIssuesADifferentToken(): void
    {
        ($this->register)('jan@example.com', 'correct-horse');

        $first = ($this->login)('jan@example.com', 'correct-horse');
        $second = ($this->login)('jan@example.com', 'correct-horse');

        self::assertNotSame($first->plainToken, $second->plainToken);
    }

    public function testRejectsWrongPassword(): void
    {
        ($this->register)('jan@example.com', 'correct-horse');

        $this->expectException(InvalidCredentialsException::class);

        ($this->login)('jan@example.com', 'wrong-password');
    }

    public function testRejectsUnknownUser(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        ($this->login)('nobody@example.com', 'whatever-123');
    }

    public function testRemovesExpiredTokensOnLogin(): void
    {
        ($this->register)('jan@example.com', 'correct-horse');
        $old = ($this->login)('jan@example.com', 'correct-horse');

        $this->clock->modify('+3 hours');
        ($this->login)('jan@example.com', 'correct-horse');

        self::assertCount(1, $this->tokens->stored);
        self::assertNull($this->tokens->findByHash(ApiToken::hash($old->plainToken)));
    }

    public function testRegistrationRejectsShortPasswords(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->register)('jan@example.com', 'short');
    }

    public function testRegistrationRejectsInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->register)('not-an-email', 'correct-horse');
    }

    public function testRegistrationRejectsDuplicates(): void
    {
        ($this->register)('jan@example.com', 'correct-horse');

        $this->expectException(UserAlreadyExistsException::class);

        ($this->register)('JAN@example.com', 'another-pass-1');
    }
}
