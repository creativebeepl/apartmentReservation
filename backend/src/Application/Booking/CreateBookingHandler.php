<?php

declare(strict_types=1);

namespace App\Application\Booking;

use App\Domain\Entity\Booking;
use App\Domain\Exception\BookingSlotConflictException;
use App\Domain\Exception\ResourceNotFoundException;
use App\Domain\Policy\BookingWindowPolicy;
use App\Domain\Repository\BookingRepositoryInterface;
use App\Domain\Repository\ResourceRepositoryInterface;
use App\Domain\ValueObject\BookingPeriod;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

final readonly class CreateBookingHandler
{
    public function __construct(
        private ResourceRepositoryInterface $resources,
        private BookingRepositoryInterface $bookings,
        private BookingWindowPolicy $windowPolicy,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ResourceNotFoundException
     * @throws BookingSlotConflictException
     */
    public function __invoke(CreateBookingCommand $command): Booking
    {
        $resource = $this->resources->find($command->resourceId)
            ?? throw ResourceNotFoundException::forId($command->resourceId);

        $period = new BookingPeriod($command->startAt, $command->endAt);
        $this->windowPolicy->assertAllowed($period);

        $booking = new Booking(Uuid::v7(), $resource, $period, $command->customerName, $this->clock->now());

        if (! $this->bookings->addIfSlotFree($booking)) {
            throw BookingSlotConflictException::create();
        }

        return $booking;
    }
}
