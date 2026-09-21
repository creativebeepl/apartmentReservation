<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class BookingSlotConflictException extends DomainConflictException
{
    public static function create(): self
    {
        return new self('The selected period overlaps an existing booking of this resource.');
    }

    public function errorCode(): string
    {
        return 'slot_conflict';
    }
}
