<?php

declare(strict_types=1);

namespace App\Application\Booking;

use App\Domain\Entity\Booking;
use App\Domain\Repository\BookingRepositoryInterface;

final readonly class ListBookingsHandler
{
    public function __construct(private BookingRepositoryInterface $bookings)
    {
    }

    /** @return list<Booking> */
    public function __invoke(): array
    {
        return $this->bookings->findAllNewestFirst();
    }
}
