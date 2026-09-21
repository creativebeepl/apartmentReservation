<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidBookingPeriodException extends DomainRuleViolationException
{
    public static function endNotAfterStart(): self
    {
        return new self('The booking must end after it starts.');
    }

    public function errorCode(): string
    {
        return 'invalid_period';
    }
}
