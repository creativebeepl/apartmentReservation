<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class AuthenticationTest extends ApiTestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function protectedEndpoints(): iterable
    {
        yield 'list resources' => ['GET', '/api/resources'];
        yield 'list bookings' => ['GET', '/api/bookings'];
        yield 'create booking' => ['POST', '/api/bookings'];
    }

    #[DataProvider('protectedEndpoints')]
    public function testEndpointsRequireAToken(string $method, string $uri): void
    {
        $this->api($method, $uri, $method === 'POST' ? [] : null);

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Bearer', $this->client->getResponse()->headers->get('WWW-Authenticate'));
        self::assertSame('unauthenticated', $this->responseJson()['code']);
    }

    #[DataProvider('protectedEndpoints')]
    public function testEndpointsRejectAnUnknownToken(string $method, string $uri): void
    {
        $this->api($method, $uri, $method === 'POST' ? [] : null, 'not-a-real-token');

        self::assertResponseStatusCodeSame(401);
        self::assertSame('unauthenticated', $this->responseJson()['code']);
    }

    public function testExpiredTokenIsRejected(): void
    {
        $expired = $this->createToken($this->createUser('other@example.com'), '-1 second');

        $this->api('GET', '/api/resources', null, $expired);

        self::assertResponseStatusCodeSame(401);
    }

    public function testValidTokenGrantsAccess(): void
    {
        $this->authenticatedApi('GET', '/api/resources');

        self::assertResponseIsSuccessful();
    }

    public function testLoginReturnsATokenThatWorks(): void
    {
        $this->api('POST', '/api/auth/login', ['email' => 'user@example.com', 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseData();
        self::assertIsString($data['token']);
        self::assertSame('Bearer', $data['token_type']);
        self::assertIsString($data['expires_at']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $data['expires_at']);

        $this->api('GET', '/api/resources', null, $data['token']);
        self::assertResponseIsSuccessful();
    }

    public function testLoginIsCaseInsensitiveForEmail(): void
    {
        $this->api('POST', '/api/auth/login', ['email' => 'USER@Example.COM', 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(200);
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $this->api('POST', '/api/auth/login', ['email' => 'user@example.com', 'password' => 'wrong-password']);

        self::assertResponseStatusCodeSame(401);
        self::assertSame('invalid_credentials', $this->responseJson()['code']);
    }

    public function testLoginRejectsUnknownUserWithTheSameResponseAsWrongPassword(): void
    {
        $this->api('POST', '/api/auth/login', ['email' => 'user@example.com', 'password' => 'wrong-password']);
        $wrongPassword = $this->responseJson();

        $this->api('POST', '/api/auth/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password']);
        $unknownUser = $this->responseJson();

        self::assertResponseStatusCodeSame(401);
        self::assertSame($wrongPassword, $unknownUser, 'The API must not reveal which e-mails exist.');
    }

    public function testLoginValidatesPayload(): void
    {
        $this->api('POST', '/api/auth/login', ['email' => '']);

        self::assertResponseStatusCodeSame(422);
        $errors = $this->responseJson()['errors'];
        self::assertIsArray($errors);
        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('password', $errors);
    }

    public function testLoginIsRateLimitedPerCredentials(): void
    {
        // Liczniki żyją w pamięci jądra, więc nie wolno go restartować między żądaniami.
        $this->client->disableReboot();

        for ($i = 0; $i < 5; ++$i) {
            $this->api('POST', '/api/auth/login', ['email' => 'user@example.com', 'password' => 'wrong-password']);
            self::assertResponseStatusCodeSame(401);
        }

        $this->api('POST', '/api/auth/login', ['email' => 'user@example.com', 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(429);
        self::assertTrue($this->client->getResponse()->headers->has('Retry-After'));
        self::assertSame('too_many_requests', $this->responseJson()['code']);
    }

    public function testRateLimitOfOneAccountDoesNotBlockAnother(): void
    {
        $this->client->disableReboot();
        $this->createUser('second@example.com');

        for ($i = 0; $i < 6; ++$i) {
            $this->api('POST', '/api/auth/login', ['email' => 'user@example.com', 'password' => 'wrong-password']);
        }
        self::assertResponseStatusCodeSame(429);

        $this->api('POST', '/api/auth/login', ['email' => 'second@example.com', 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(200);
    }
}
