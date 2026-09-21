<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Entity\Booking;
use App\Tests\Support\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class BookingValidationApiTest extends ApiTestCase
{
    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidOverrides(): iterable
    {
        yield 'missing resource_id' => [['resource_id' => null], 'resource_id'];
        yield 'resource_id not a uuid' => [['resource_id' => 'not-a-uuid'], 'resource_id'];
        yield 'resource_id wrong type' => [['resource_id' => 123], 'resource_id'];
        yield 'missing start_at' => [['start_at' => null], 'start_at'];
        yield 'start_at without time zone' => [['start_at' => '2030-01-01T10:00:00'], 'start_at'];
        yield 'start_at laravel style' => [['start_at' => '2030-01-01 10:00:00'], 'start_at'];
        yield 'start_at relative expression' => [['start_at' => 'tomorrow'], 'start_at'];
        yield 'start_at nonexistent date' => [['start_at' => '2030-02-31T10:00:00Z'], 'start_at'];
        yield 'missing end_at' => [['end_at' => null], 'end_at'];
        yield 'end_at garbage' => [['end_at' => 'soon'], 'end_at'];
        yield 'missing customer_name' => [['customer_name' => null], 'customer_name'];
        yield 'blank customer_name' => [['customer_name' => '   '], 'customer_name'];
        yield 'too long customer_name' => [['customer_name' => str_repeat('a', 256)], 'customer_name'];
        yield 'customer_name wrong type' => [['customer_name' => ['x']], 'customer_name'];
    }

    /** @param array<string, mixed> $override */
    #[DataProvider('invalidOverrides')]
    public function testRejectsInvalidPayload(array $override, string $field): void
    {
        $resource = $this->createResource();
        $payload = array_merge([
            'resource_id' => (string) $resource->id(),
            'start_at' => $this->iso('+10 days 10:00'),
            'end_at' => $this->iso('+11 days 10:00'),
            'customer_name' => 'Jan',
        ], $override);

        $this->authenticatedApi('POST', '/api/bookings', $payload);

        self::assertResponseStatusCodeSame(422);
        $body = $this->responseJson();
        self::assertSame('validation_failed', $body['code']);
        self::assertIsArray($body['errors']);
        self::assertArrayHasKey($field, $body['errors']);
        self::assertSame(0, $this->em()->getRepository(Booking::class)->count([]));
    }

    public function testReportsAllInvalidFieldsAtOnce(): void
    {
        $this->authenticatedApi('POST', '/api/bookings', []);

        self::assertResponseStatusCodeSame(422);
        $errors = $this->responseJson()['errors'];
        self::assertIsArray($errors);
        self::assertEqualsCanonicalizing(['resource_id', 'start_at', 'end_at', 'customer_name'], array_keys($errors));
    }

    public function testRejectsEndBeforeStart(): void
    {
        $resource = $this->createResource();

        $this->authenticatedApi('POST', '/api/bookings', [
            'resource_id' => (string) $resource->id(),
            'start_at' => $this->iso('+11 days 10:00'),
            'end_at' => $this->iso('+10 days 10:00'),
            'customer_name' => 'Jan',
        ]);

        self::assertResponseStatusCodeSame(422);
        $errors = $this->responseJson()['errors'];
        self::assertIsArray($errors);
        self::assertArrayHasKey('end_at', $errors);
    }

    public function testRejectsEndEqualToStart(): void
    {
        $resource = $this->createResource();

        $this->authenticatedApi('POST', '/api/bookings', [
            'resource_id' => (string) $resource->id(),
            'start_at' => $this->iso('+10 days 10:00'),
            'end_at' => $this->iso('+10 days 10:00'),
            'customer_name' => 'Jan',
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testRejectsPeriodShorterThanOneSecondAfterNormalization(): void
    {
        $resource = $this->createResource();

        $this->authenticatedApi('POST', '/api/bookings', [
            'resource_id' => (string) $resource->id(),
            'start_at' => '2030-01-01T10:00:00.200Z',
            'end_at' => '2030-01-01T10:00:00.700Z',
            'customer_name' => 'Jan',
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testValidationErrorsDoNotLeakInternals(): void
    {
        $this->authenticatedApi('POST', '/api/bookings', ['resource_id' => 'x']);

        $json = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('App\\', $json);
        self::assertStringNotContainsString('.php', $json);
    }
}
