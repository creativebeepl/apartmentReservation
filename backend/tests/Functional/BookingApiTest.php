<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Entity\Booking;
use App\Tests\Support\ApiTestCase;

final class BookingApiTest extends ApiTestCase
{
    public function testCreatesBooking(): void
    {
        $resource = $this->createResource('Apartament 101');

        $this->authenticatedApi('POST', '/api/bookings', [
            'resource_id' => (string) $resource->id(),
            'start_at' => $this->iso('+10 days 10:00'),
            'end_at' => $this->iso('+12 days 10:00'),
            'customer_name' => '  Jan Kowalski ',
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $this->responseData();
        self::assertSame(
            ['id', 'resource_id', 'resource_name', 'start_at', 'end_at', 'customer_name', 'created_at'],
            array_keys($data),
        );
        self::assertSame((string) $resource->id(), $data['resource_id']);
        self::assertSame('Apartament 101', $data['resource_name']);
        self::assertSame($this->iso('+10 days 10:00'), $data['start_at']);
        self::assertSame($this->iso('+12 days 10:00'), $data['end_at']);
        self::assertSame('Jan Kowalski', $data['customer_name']);
        self::assertSame(1, $this->em()->getRepository(Booking::class)->count([]));
    }

    public function testConvertsDatesWithOffsetToUtc(): void
    {
        $resource = $this->createResource();
        $start = new \DateTimeImmutable('+10 days 10:00', new \DateTimeZone('Europe/Warsaw'));

        $this->authenticatedApi('POST', '/api/bookings', [
            'resource_id' => (string) $resource->id(),
            'start_at' => $start->format('c'),
            'end_at' => $start->modify('+2 days')->format('c'),
            'customer_name' => 'Jan',
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $this->responseData();
        self::assertSame(
            $start->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            $data['start_at'],
            'The instant must be preserved, not the wall-clock time.',
        );
    }

    public function testListsBookingsNewestStartFirstWithResourceName(): void
    {
        $first = $this->createResource('Apartament 101');
        $second = $this->createResource('Apartament 202');
        $this->book($first->id(), '+10 days 10:00', '+11 days 10:00', 'Early');
        $this->book($second->id(), '+30 days 10:00', '+31 days 10:00', 'Late');
        $this->book($first->id(), '+20 days 10:00', '+21 days 10:00', 'Middle');

        $this->authenticatedApi('GET', '/api/bookings');

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseDataList();
        self::assertSame(['Late', 'Middle', 'Early'], array_column($data, 'customer_name'));
        self::assertSame(['Apartament 202', 'Apartament 101', 'Apartament 101'], array_column($data, 'resource_name'));
    }

    public function testRejectsOverlappingBooking(): void
    {
        $resource = $this->createResource();
        $this->book($resource->id(), '+10 days 10:00', '+14 days 10:00');

        $this->authenticatedApi('POST', '/api/bookings', $this->payload($resource->id(), '+12 days 10:00', '+16 days 10:00'));

        self::assertResponseStatusCodeSame(409);
        self::assertSame('slot_conflict', $this->responseJson()['code']);
        self::assertSame(1, $this->em()->getRepository(Booking::class)->count([]));
    }

    public function testRejectsBookingContainedInExistingOne(): void
    {
        $resource = $this->createResource();
        $this->book($resource->id(), '+10 days 10:00', '+20 days 10:00');

        $this->authenticatedApi('POST', '/api/bookings', $this->payload($resource->id(), '+12 days 10:00', '+13 days 10:00'));

        self::assertResponseStatusCodeSame(409);
    }

    public function testRejectsBookingContainingExistingOne(): void
    {
        $resource = $this->createResource();
        $this->book($resource->id(), '+12 days 10:00', '+13 days 10:00');

        $this->authenticatedApi('POST', '/api/bookings', $this->payload($resource->id(), '+10 days 10:00', '+20 days 10:00'));

        self::assertResponseStatusCodeSame(409);
    }

    public function testAllowsBackToBackBookings(): void
    {
        $resource = $this->createResource();
        $this->book($resource->id(), '+10 days 10:00', '+12 days 10:00');

        $this->authenticatedApi('POST', '/api/bookings', $this->payload($resource->id(), '+12 days 10:00', '+14 days 10:00'));
        self::assertResponseStatusCodeSame(201);

        $this->authenticatedApi('POST', '/api/bookings', $this->payload($resource->id(), '+8 days 10:00', '+10 days 10:00'));
        self::assertResponseStatusCodeSame(201);
    }

    public function testAllowsTheSamePeriodForDifferentResources(): void
    {
        $first = $this->createResource('Apartament 101');
        $second = $this->createResource('Apartament 102');
        $this->book($first->id(), '+10 days 10:00', '+12 days 10:00');

        $this->authenticatedApi('POST', '/api/bookings', $this->payload($second->id(), '+10 days 10:00', '+12 days 10:00'));

        self::assertResponseStatusCodeSame(201);
    }

    public function testConflictIsDetectedAcrossTimeZones(): void
    {
        $resource = $this->createResource();
        $this->book($resource->id(), '+10 days 10:00', '+10 days 14:00');

        // Ta sama chwila zapisana w strefie Europe/Warsaw — porównujemy chwile, nie godziny na zegarze.
        $start = (new \DateTimeImmutable('+10 days 10:00 UTC'))->setTimezone(new \DateTimeZone('Europe/Warsaw'));
        $this->authenticatedApi('POST', '/api/bookings', [
            'resource_id' => (string) $resource->id(),
            'start_at' => $start->format('c'),
            'end_at' => $start->modify('+4 hours')->format('c'),
            'customer_name' => 'Jan',
        ]);

        self::assertResponseStatusCodeSame(409);
    }

    public function testUnknownResourceReturns404(): void
    {
        $this->authenticatedApi('POST', '/api/bookings', [
            'resource_id' => '0190d7f0-0000-7000-8000-000000000000',
            'start_at' => $this->iso('+10 days 10:00'),
            'end_at' => $this->iso('+11 days 10:00'),
            'customer_name' => 'Jan',
        ]);

        self::assertResponseStatusCodeSame(404);
        self::assertSame('resource_not_found', $this->responseJson()['code']);
    }

    public function testMalformedJsonReturns400(): void
    {
        $this->client->request('POST', '/api/bookings', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ], '{not json');

        self::assertResponseStatusCodeSame(400);
        self::assertSame('bad_request', $this->responseJson()['code']);
    }

    private function book(\Symfony\Component\Uid\Uuid $resourceId, string $start, string $end, string $customer = 'Jan'): void
    {
        $this->authenticatedApi('POST', '/api/bookings', $this->payload($resourceId, $start, $end, $customer));
        self::assertResponseStatusCodeSame(201);
    }

    /** @return array<string, string> */
    private function payload(\Symfony\Component\Uid\Uuid $resourceId, string $start, string $end, string $customer = 'Anna'): array
    {
        return [
            'resource_id' => (string) $resourceId,
            'start_at' => $this->iso($start),
            'end_at' => $this->iso($end),
            'customer_name' => $customer,
        ];
    }
}
