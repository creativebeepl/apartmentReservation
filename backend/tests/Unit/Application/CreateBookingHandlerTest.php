<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\Booking\CreateBookingCommand;
use App\Application\Booking\CreateBookingHandler;
use App\Domain\Entity\Resource;
use App\Domain\Exception\BookingInPastException;
use App\Domain\Exception\BookingSlotConflictException;
use App\Domain\Exception\InvalidBookingPeriodException;
use App\Domain\Exception\ResourceNotFoundException;
use App\Domain\Policy\BookingWindowPolicy;
use App\Tests\Support\InMemoryBookingRepository;
use App\Tests\Support\InMemoryResourceRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Uid\Uuid;

final class CreateBookingHandlerTest extends TestCase
{
    private InMemoryResourceRepository $resources;
    private InMemoryBookingRepository $bookings;
    private CreateBookingHandler $handler;
    private Resource $resource;

    protected function setUp(): void
    {
        $clock = new MockClock('2026-06-01 12:00:00 UTC');
        $this->resources = new InMemoryResourceRepository();
        $this->bookings = new InMemoryBookingRepository();
        $this->handler = new CreateBookingHandler(
            $this->resources,
            $this->bookings,
            new BookingWindowPolicy($clock, 'P1Y'),
            $clock,
        );

        $this->resource = new Resource(Uuid::v7(), 'Apartament 101');
        $this->resources->add($this->resource);
    }

    public function testCreatesBooking(): void
    {
        $booking = ($this->handler)($this->command('2026-07-01T10:00:00Z', '2026-07-03T10:00:00Z', '  Jan Kowalski  '));

        self::assertSame('Jan Kowalski', $booking->customerName());
        self::assertSame('2026-06-01T12:00:00+00:00', $booking->createdAt()->format('c'));
        self::assertSame([$booking], $this->bookings->stored);
    }

    public function testRejectsUnknownResource(): void
    {
        $command = new CreateBookingCommand(
            Uuid::v7(),
            new \DateTimeImmutable('2026-07-01T10:00:00Z'),
            new \DateTimeImmutable('2026-07-02T10:00:00Z'),
            'Jan',
        );

        $this->expectException(ResourceNotFoundException::class);

        ($this->handler)($command);
    }

    public function testRejectsOverlappingBookingOfTheSameResource(): void
    {
        ($this->handler)($this->command('2026-07-01T10:00:00Z', '2026-07-03T10:00:00Z'));

        $this->expectException(BookingSlotConflictException::class);

        ($this->handler)($this->command('2026-07-02T10:00:00Z', '2026-07-04T10:00:00Z'));
    }

    public function testAllowsBackToBackBookings(): void
    {
        ($this->handler)($this->command('2026-07-01T10:00:00Z', '2026-07-03T10:00:00Z'));
        ($this->handler)($this->command('2026-07-03T10:00:00Z', '2026-07-05T10:00:00Z'));

        self::assertCount(2, $this->bookings->stored);
    }

    public function testAllowsTheSamePeriodForDifferentResources(): void
    {
        $other = new Resource(Uuid::v7(), 'Apartament 102');
        $this->resources->add($other);

        ($this->handler)($this->command('2026-07-01T10:00:00Z', '2026-07-03T10:00:00Z'));
        ($this->handler)(new CreateBookingCommand(
            $other->id(),
            new \DateTimeImmutable('2026-07-01T10:00:00Z'),
            new \DateTimeImmutable('2026-07-03T10:00:00Z'),
            'Anna',
        ));

        self::assertCount(2, $this->bookings->stored);
    }

    public function testDoesNotStoreAnythingWhenPolicyRejectsTheBooking(): void
    {
        try {
            ($this->handler)($this->command('2026-05-01T10:00:00Z', '2026-05-02T10:00:00Z'));
            self::fail('Expected BookingInPastException.');
        } catch (BookingInPastException) {
            self::assertSame([], $this->bookings->stored);
        }
    }

    public function testRejectsInvertedPeriod(): void
    {
        $this->expectException(InvalidBookingPeriodException::class);

        ($this->handler)($this->command('2026-07-03T10:00:00Z', '2026-07-01T10:00:00Z'));
    }

    private function command(string $start, string $end, string $customer = 'Jan'): CreateBookingCommand
    {
        return new CreateBookingCommand(
            $this->resource->id(),
            new \DateTimeImmutable($start),
            new \DateTimeImmutable($end),
            $customer,
        );
    }
}
