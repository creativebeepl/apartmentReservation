<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** Bazowy wyjątek konfliktu ze stanem systemu — warstwa HTTP mapuje go na 409. */
abstract class DomainConflictException extends \DomainException implements DomainErrorInterface
{
}
