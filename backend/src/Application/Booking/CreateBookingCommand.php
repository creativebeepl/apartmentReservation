<?php

declare(strict_types=1);

namespace App\Application\Booking;

use Symfony\Component\Uid\Uuid;

final readonly class CreateBookingCommand
{
    public function __construct(
        public Uuid $resourceId,
        public \DateTimeImmutable $startAt,
        public \DateTimeImmutable $endAt,
        public string $customerName,
    ) {
    }
}
