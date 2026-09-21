<?php

declare(strict_types=1);

namespace App\Domain\Policy;

use App\Domain\Exception\BookingInPastException;
use App\Domain\Exception\BookingTooFarAheadException;
use App\Domain\ValueObject\BookingPeriod;
use Psr\Clock\ClockInterface;

/**
 * Reguła biznesowa: nie rezerwujemy w przeszłości ani dalej niż $maxAdvance do przodu.
 * Czas pochodzi z wstrzykniętego zegara, więc regułę da się testować bez czekania na upływ czasu.
 */
final readonly class BookingWindowPolicy
{
    private \DateInterval $maxAdvance;

    /** @param string $maxAdvance interwał ISO 8601, np. "P1Y" */
    public function __construct(private ClockInterface $clock, string $maxAdvance = 'P1Y')
    {
        $this->maxAdvance = new \DateInterval($maxAdvance);
    }

    public function assertAllowed(BookingPeriod $period): void
    {
        $now = $this->clock->now();

        if ($period->start() < $now) {
            throw BookingInPastException::create();
        }

        if ($period->end() > $now->add($this->maxAdvance)) {
            throw BookingTooFarAheadException::create();
        }
    }
}
