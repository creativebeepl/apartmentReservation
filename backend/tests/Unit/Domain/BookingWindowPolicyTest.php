<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Exception\BookingInPastException;
use App\Domain\Exception\BookingTooFarAheadException;
use App\Domain\Policy\BookingWindowPolicy;
use App\Domain\ValueObject\BookingPeriod;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class BookingWindowPolicyTest extends TestCase
{
    private const NOW = '2026-06-01 12:00:00 UTC';

    private BookingWindowPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new BookingWindowPolicy(new MockClock(self::NOW), 'P1Y');
    }

    public function testAllowsBookingInsideWindow(): void
    {
        $this->policy->assertAllowed($this->period('2026-06-02T10:00:00Z', '2026-06-05T10:00:00Z'));

        $this->addToAssertionCount(1);
    }

    public function testAllowsBookingStartingExactlyNow(): void
    {
        $this->policy->assertAllowed($this->period('2026-06-01T12:00:00Z', '2026-06-01T14:00:00Z'));

        $this->addToAssertionCount(1);
    }

    public function testRejectsBookingStartingOneSecondInThePast(): void
    {
        $this->expectException(BookingInPastException::class);

        $this->policy->assertAllowed($this->period('2026-06-01T11:59:59Z', '2026-06-01T14:00:00Z'));
    }

    public function testRejectsBookingEntirelyInThePast(): void
    {
        $this->expectException(BookingInPastException::class);

        $this->policy->assertAllowed($this->period('2025-01-01T10:00:00Z', '2025-01-02T10:00:00Z'));
    }

    public function testAllowsBookingEndingExactlyAtTheEndOfTheWindow(): void
    {
        $this->policy->assertAllowed($this->period('2027-05-30T12:00:00Z', '2027-06-01T12:00:00Z'));

        $this->addToAssertionCount(1);
    }

    public function testRejectsBookingEndingOneSecondAfterTheWindow(): void
    {
        $this->expectException(BookingTooFarAheadException::class);

        $this->policy->assertAllowed($this->period('2027-05-30T12:00:00Z', '2027-06-01T12:00:01Z'));
    }

    public function testRejectsBookingStartingBeyondTheWindow(): void
    {
        $this->expectException(BookingTooFarAheadException::class);

        $this->policy->assertAllowed($this->period('2028-01-01T10:00:00Z', '2028-01-02T10:00:00Z'));
    }

    public function testWindowLengthIsConfigurable(): void
    {
        $policy = new BookingWindowPolicy(new MockClock(self::NOW), 'P30D');

        $this->expectException(BookingTooFarAheadException::class);

        $policy->assertAllowed($this->period('2026-07-02T10:00:00Z', '2026-07-03T10:00:00Z'));
    }

    private function period(string $start, string $end): BookingPeriod
    {
        return new BookingPeriod(new \DateTimeImmutable($start), new \DateTimeImmutable($end));
    }
}
