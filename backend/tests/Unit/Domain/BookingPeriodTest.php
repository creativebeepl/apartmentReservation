<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Exception\InvalidBookingPeriodException;
use App\Domain\ValueObject\BookingPeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BookingPeriodTest extends TestCase
{
    public function testNormalizesToUtcAndDropsFractionsOfSecond(): void
    {
        $period = new BookingPeriod(
            new \DateTimeImmutable('2026-07-01T12:00:00.750+02:00'),
            new \DateTimeImmutable('2026-07-01T16:00:00+02:00'),
        );

        self::assertSame('2026-07-01T10:00:00+00:00', $period->start()->format('c'));
        self::assertSame('2026-07-01T14:00:00+00:00', $period->end()->format('c'));
        self::assertSame(0, (int) $period->start()->format('u'));
    }

    public function testRejectsEndEqualToStart(): void
    {
        $this->expectException(InvalidBookingPeriodException::class);

        new BookingPeriod(new \DateTimeImmutable('2026-07-01T10:00:00Z'), new \DateTimeImmutable('2026-07-01T10:00:00Z'));
    }

    public function testRejectsEndBeforeStart(): void
    {
        $this->expectException(InvalidBookingPeriodException::class);

        new BookingPeriod(new \DateTimeImmutable('2026-07-01T10:00:00Z'), new \DateTimeImmutable('2026-07-01T09:00:00Z'));
    }

    /** @return iterable<string, array{string, string, string, string, bool}> */
    public static function overlapCases(): iterable
    {
        $base = ['2026-07-01T10:00:00Z', '2026-07-01T14:00:00Z'];

        yield 'identical' => [...$base, '2026-07-01T10:00:00Z', '2026-07-01T14:00:00Z', true];
        yield 'contained' => [...$base, '2026-07-01T11:00:00Z', '2026-07-01T12:00:00Z', true];
        yield 'containing' => [...$base, '2026-07-01T09:00:00Z', '2026-07-01T15:00:00Z', true];
        yield 'overlapping start' => [...$base, '2026-07-01T09:00:00Z', '2026-07-01T10:00:01Z', true];
        yield 'overlapping end' => [...$base, '2026-07-01T13:59:59Z', '2026-07-01T18:00:00Z', true];
        yield 'touching at end' => [...$base, '2026-07-01T14:00:00Z', '2026-07-01T18:00:00Z', false];
        yield 'touching at start' => [...$base, '2026-07-01T06:00:00Z', '2026-07-01T10:00:00Z', false];
        yield 'before' => [...$base, '2026-06-30T10:00:00Z', '2026-06-30T14:00:00Z', false];
        yield 'after' => [...$base, '2026-07-02T10:00:00Z', '2026-07-02T14:00:00Z', false];
        yield 'same instant, other offset' => [...$base, '2026-07-01T12:00:00+02:00', '2026-07-01T16:00:00+02:00', true];
    }

    #[DataProvider('overlapCases')]
    public function testOverlaps(string $aStart, string $aEnd, string $bStart, string $bEnd, bool $expected): void
    {
        $a = new BookingPeriod(new \DateTimeImmutable($aStart), new \DateTimeImmutable($aEnd));
        $b = new BookingPeriod(new \DateTimeImmutable($bStart), new \DateTimeImmutable($bEnd));

        self::assertSame($expected, $a->overlaps($b));
        self::assertSame($expected, $b->overlaps($a), 'Overlap must be symmetric.');
    }
}
