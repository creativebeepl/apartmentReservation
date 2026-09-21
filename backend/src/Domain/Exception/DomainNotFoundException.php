<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** Bazowy wyjątek "nie znaleziono" — warstwa HTTP mapuje go na 404. */
abstract class DomainNotFoundException extends \DomainException implements DomainErrorInterface
{
}
