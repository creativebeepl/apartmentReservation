<?php

declare(strict_types=1);

namespace App\Tests\Unit\UI;

use App\UI\Http\Request\Iso8601Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Iso8601ParserTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function validValues(): iterable
    {
        yield 'zulu' => ['2026-07-01T10:00:00Z', '2026-07-01T10:00:00+00:00'];
        yield 'positive offset' => ['2026-07-01T12:00:00+02:00', '2026-07-01T12:00:00+02:00'];
        yield 'negative offset' => ['2026-07-01T05:30:00-04:00', '2026-07-01T05:30:00-04:00'];
        yield 'fraction of second' => ['2026-07-01T10:00:00.123Z', '2026-07-01T10:00:00+00:00'];
        yield 'leap day' => ['2028-02-29T10:00:00Z', '2028-02-29T10:00:00+00:00'];
    }

    #[DataProvider('validValues')]
    public function testParsesValidValues(string $input, string $expectedAtom): void
    {
        $parsed = Iso8601Parser::parse($input);

        self::assertNotNull($parsed);
        self::assertSame($expectedAtom, $parsed->format('c'));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        yield 'empty' => [''];
        yield 'no time zone' => ['2026-07-01T10:00:00'];
        yield 'space separator (laravel style)' => ['2026-07-01 10:00:00'];
        yield 'date only' => ['2026-07-01'];
        yield 'relative expression' => ['tomorrow'];
        yield 'nonexistent day' => ['2026-02-31T10:00:00Z'];
        yield 'not a leap year' => ['2026-02-29T10:00:00Z'];
        yield 'hour 25' => ['2026-07-01T25:00:00Z'];
        yield 'month 13' => ['2026-13-01T10:00:00Z'];
        yield 'garbage suffix' => ['2026-07-01T10:00:00Z; DROP TABLE bookings'];
        yield 'timestamp' => ['1782900000'];
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidValues(string $input): void
    {
        self::assertNull(Iso8601Parser::parse($input));
    }
}
