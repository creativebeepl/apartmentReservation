<?php

declare(strict_types=1);

namespace App\Domain\Exception;

interface DomainErrorInterface extends \Throwable
{
    /** Stabilny, maszynowo czytelny kod błędu (np. dla frontendu). */
    public function errorCode(): string;
}
