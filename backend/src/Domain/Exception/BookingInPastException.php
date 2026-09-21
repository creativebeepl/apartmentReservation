<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class BookingInPastException extends DomainRuleViolationException
{
    public static function create(): self
    {
        return new self('The booking cannot start in the past.');
    }

    public function errorCode(): string
    {
        return 'booking_in_past';
    }
}
