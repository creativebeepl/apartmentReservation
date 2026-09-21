<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final readonly class RegisterUserHandler
{
    public const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private UserRepositoryInterface $users,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     * @throws UserAlreadyExistsException
     */
    public function __invoke(string $email, string $plainPassword): User
    {
        $email = User::normalizeEmail($email);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Invalid e-mail address.');
        }

        if (mb_strlen($plainPassword) < self::MIN_PASSWORD_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Password must have at least %d characters.', self::MIN_PASSWORD_LENGTH),
            );
        }

        if ($this->users->findByEmail($email) !== null) {
            throw UserAlreadyExistsException::forEmail($email);
        }

        $hash = $this->passwordHasher->hashPassword(new User(Uuid::v7(), $email, ''), $plainPassword);
        $user = new User(Uuid::v7(), $email, $hash);
        $this->users->add($user);

        return $user;
    }
}
