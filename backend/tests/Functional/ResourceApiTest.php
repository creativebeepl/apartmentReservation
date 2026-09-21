<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\ApiTestCase;

final class ResourceApiTest extends ApiTestCase
{
    public function testListsResourcesOrderedByName(): void
    {
        $this->createResource('Penthouse');
        $this->createResource('Apartament 201');
        $this->createResource('Apartament 101');

        $this->authenticatedApi('GET', '/api/resources');

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseDataList();
        self::assertSame(
            ['Apartament 101', 'Apartament 201', 'Penthouse'],
            array_column($data, 'name'),
        );
        self::assertIsString($data[0]['id']);
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $data[0]['id']);
        self::assertSame(['id', 'name'], array_keys($data[0]));
    }

    public function testReturnsEmptyListWhenThereAreNoResources(): void
    {
        $this->authenticatedApi('GET', '/api/resources');

        self::assertResponseStatusCodeSame(200);
        self::assertSame(['data' => []], $this->responseJson());
    }

    public function testUnknownApiRouteReturnsJson404(): void
    {
        $this->authenticatedApi('GET', '/api/does-not-exist');

        self::assertResponseStatusCodeSame(404);
        self::assertSame('not_found', $this->responseJson()['code']);
    }

    public function testWrongMethodReturnsJson405(): void
    {
        $this->authenticatedApi('DELETE', '/api/resources');

        self::assertResponseStatusCodeSame(405);
        self::assertSame('method_not_allowed', $this->responseJson()['code']);
    }
}
