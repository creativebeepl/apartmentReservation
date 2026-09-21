<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** Bazowy wyjątek naruszenia reguły biznesowej — warstwa HTTP mapuje go na 422. */
abstract class DomainRuleViolationException extends \DomainException implements DomainErrorInterface
{
}
