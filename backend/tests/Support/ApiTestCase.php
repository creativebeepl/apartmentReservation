<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Entity\ApiToken;
use App\Domain\Entity\Resource;
use App\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

abstract class ApiTestCase extends WebTestCase
{
    protected const PASSWORD = 'correct-horse-1';

    protected KernelBrowser $client;

    /** Token Bearer użytkownika utworzonego w setUp(). */
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->resetDatabase();
        $this->token = $this->createToken($this->createUser());
    }

    protected function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        return $em;
    }

    protected function createUser(string $email = 'user@example.com'): User
    {
        $hasher = static::getContainer()->get('security.user_password_hasher');
        \assert($hasher instanceof UserPasswordHasherInterface);

        $hash = $hasher->hashPassword(new User(Uuid::v7(), $email, ''), self::PASSWORD);
        $user = new User(Uuid::v7(), $email, $hash);
        $this->em()->persist($user);
        $this->em()->flush();

        return $user;
    }

    protected function createToken(User $user, string $expires = '+1 hour'): string
    {
        $plain = bin2hex(random_bytes(32));
        $now = new \DateTimeImmutable();
        $user = $this->em()->getReference(User::class, $user->id()) ?? $user;
        $this->em()->persist(new ApiToken(Uuid::v7(), $user, ApiToken::hash($plain), $now->modify($expires), $now));
        $this->em()->flush();

        return $plain;
    }

    protected function createResource(string $name = 'Apartament 101'): Resource
    {
        $resource = new Resource(Uuid::v7(), $name);
        $this->em()->persist($resource);
        $this->em()->flush();

        return $resource;
    }

    /** Moment względem "teraz" w formacie API, np. iso('+10 days 10:00'). */
    protected function iso(string $modifier): string
    {
        return (new \DateTimeImmutable($modifier, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
    }

    /** @param array<string, mixed>|null $json */
    protected function api(string $method, string $uri, ?array $json = null, ?string $token = null): void
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $this->client->request($method, $uri, [], [], $server, $json === null ? null : json_encode($json, JSON_THROW_ON_ERROR));
    }

    /** @param array<string, mixed>|null $json */
    protected function authenticatedApi(string $method, string $uri, ?array $json = null): void
    {
        $this->api($method, $uri, $json, $this->token);
    }

    /** @return array<string, mixed> */
    protected function responseJson(): array
    {
        $decoded = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /** @return array<string, mixed> zawartość klucza "data" odpowiedzi (pojedynczy obiekt) */
    protected function responseData(): array
    {
        $data = $this->responseJson()['data'] ?? null;
        self::assertIsArray($data);

        /** @var array<string, mixed> $data */
        return $data;
    }

    /** @return list<array<string, mixed>> zawartość klucza "data" odpowiedzi (lista obiektów) */
    protected function responseDataList(): array
    {
        $data = $this->responseJson()['data'] ?? null;
        self::assertIsArray($data);
        self::assertTrue(array_is_list($data));

        /** @var list<array<string, mixed>> $data */
        return $data;
    }

    protected function resetDatabase(): void
    {
        $em = $this->em();
        $tool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
        $em->clear();
    }
}
