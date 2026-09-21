<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Entity\Resource;
use App\Domain\Repository\ResourceRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class InMemoryResourceRepository implements ResourceRepositoryInterface
{
    /** @var array<string, Resource> */
    private array $resources = [];

    public function find(Uuid $id): ?Resource
    {
        return $this->resources[$id->toRfc4122()] ?? null;
    }

    public function findAllOrderedByName(): array
    {
        $all = array_values($this->resources);
        usort($all, static fn (Resource $a, Resource $b): int => strcmp($a->name(), $b->name()));

        return $all;
    }

    public function count(): int
    {
        return count($this->resources);
    }

    public function add(Resource $resource): void
    {
        $this->resources[$resource->id()->toRfc4122()] = $resource;
    }
}
