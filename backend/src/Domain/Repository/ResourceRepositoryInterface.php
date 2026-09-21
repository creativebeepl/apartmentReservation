<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Resource;
use Symfony\Component\Uid\Uuid;

interface ResourceRepositoryInterface
{
    public function find(Uuid $id): ?Resource;

    /** @return list<Resource> */
    public function findAllOrderedByName(): array;

    public function count(): int;

    public function add(Resource $resource): void;
}
