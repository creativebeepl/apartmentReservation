<?php

declare(strict_types=1);

namespace App\Application\Resource;

use App\Domain\Entity\Resource;
use App\Domain\Repository\ResourceRepositoryInterface;

final readonly class ListResourcesHandler
{
    public function __construct(private ResourceRepositoryInterface $resources)
    {
    }

    /** @return list<Resource> */
    public function __invoke(): array
    {
        return $this->resources->findAllOrderedByName();
    }
}
