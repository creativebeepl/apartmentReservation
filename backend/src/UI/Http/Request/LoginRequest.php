<?php

declare(strict_types=1);

namespace App\UI\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 180)]
        public ?string $email = null,
        // Górny limit chroni przed kosztownym haszowaniem gigantycznych danych.
        #[Assert\NotBlank]
        #[Assert\Length(max: 4096)]
        public ?string $password = null,
    ) {
    }
}
