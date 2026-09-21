<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class BookingTooFarAheadException extends DomainRuleViolationException
{
    public static function create(): self
    {
        return new self('The booking must end within the allowed booking window.');
    }

    public function errorCode(): string
    {
        return 'booking_too_far_ahead';
    }
}
