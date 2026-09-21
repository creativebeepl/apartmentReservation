<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Entity\Booking;
use App\Tests\Support\ApiTestCase;

/** Reguły biznesowe: brak rezerwacji w przeszłości i najwyżej rok do przodu. */
final class BookingRulesApiTest extends ApiTestCase
{
    public function testRejectsBookingInThePast(): void
    {
        $this->post('-2 days 10:00', '-1 days 10:00');

        self::assertResponseStatusCodeSame(422);
        self::assertSame('booking_in_past', $this->responseJson()['code']);
        self::assertSame(0, $this->em()->getRepository(Booking::class)->count([]));
    }

    public function testRejectsBookingThatStartedInThePastAndEndsInTheFuture(): void
    {
        $this->post('-1 hour', '+2 days');

        self::assertResponseStatusCodeSame(422);
        self::assertSame('booking_in_past', $this->responseJson()['code']);
    }

    public function testRejectsBookingBeyondOneYearAhead(): void
    {
        $this->post('+2 years 10:00', '+2 years 12:00');

        self::assertResponseStatusCodeSame(422);
        self::assertSame('booking_too_far_ahead', $this->responseJson()['code']);
    }

    public function testRejectsBookingStartingInsideAndEndingBeyondTheWindow(): void
    {
        $this->post('+11 months', '+13 months');

        self::assertResponseStatusCodeSame(422);
        self::assertSame('booking_too_far_ahead', $this->responseJson()['code']);
    }

    public function testAcceptsBookingEndingJustInsideTheWindow(): void
    {
        $this->post('+11 months', '+1 year -1 day');

        self::assertResponseStatusCodeSame(201);
    }

    public function testAcceptsBookingStartingSoonInTheFuture(): void
    {
        $this->post('+1 hour', '+3 hours');

        self::assertResponseStatusCodeSame(201);
    }

    private function post(string $start, string $end): void
    {
        $resource = $this->createResource();

        $this->authenticatedApi('POST', '/api/bookings', [
            'resource_id' => (string) $resource->id(),
            'start_at' => $this->iso($start),
            'end_at' => $this->iso($end),
            'customer_name' => 'Jan',
        ]);
    }
}
