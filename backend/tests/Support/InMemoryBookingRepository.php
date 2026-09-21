<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Entity\Booking;
use App\Domain\Repository\BookingRepositoryInterface;

final class InMemoryBookingRepository implements BookingRepositoryInterface
{
    /** @var list<Booking> */
    public array $stored = [];

    public function addIfSlotFree(Booking $booking): bool
    {
        foreach ($this->stored as $existing) {
            if (
                $existing->resource()->id()->equals($booking->resource()->id())
                && $existing->period()->overlaps($booking->period())
            ) {
                return false;
            }
        }

        $this->stored[] = $booking;

        return true;
    }

    public function findAllNewestFirst(): array
    {
        $sorted = $this->stored;
        usort($sorted, static fn (Booking $a, Booking $b): int => $b->period()->start() <=> $a->period()->start());

        return $sorted;
    }
}
