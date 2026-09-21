<?php

declare(strict_types=1);

namespace App\Application\Auth;

final class UserAlreadyExistsException extends \RuntimeException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('User "%s" already exists.', $email));
    }
}
