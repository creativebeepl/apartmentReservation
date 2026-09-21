<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidBookingPeriodException;

/**
 * Półotwarty przedział czasu [start, end): rezerwacje stykające się końcami nie kolidują.
 * Wartości są normalizowane do UTC i do pełnych sekund, dzięki czemu to, co sprawdzamy,
 * jest dokładnie tym, co zapisujemy w bazie.
 */
final readonly class BookingPeriod
{
    private \DateTimeImmutable $start;
    private \DateTimeImmutable $end;

    public function __construct(\DateTimeImmutable $start, \DateTimeImmutable $end)
    {
        $this->start = self::normalize($start);
        $this->end = self::normalize($end);

        if ($this->end <= $this->start) {
            throw InvalidBookingPeriodException::endNotAfterStart();
        }
    }

    public function start(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): \DateTimeImmutable
    {
        return $this->end;
    }

    public function overlaps(self $other): bool
    {
        return $this->start < $other->end && $this->end > $other->start;
    }

    private static function normalize(\DateTimeImmutable $moment): \DateTimeImmutable
    {
        $utc = $moment->setTimezone(new \DateTimeZone('UTC'));

        return $utc->setTime((int) $utc->format('H'), (int) $utc->format('i'), (int) $utc->format('s'));
    }
}
