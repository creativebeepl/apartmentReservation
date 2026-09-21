<?php

declare(strict_types=1);

namespace App\UI\Http\Presenter;

use App\Domain\Entity\Resource;

final readonly class ResourcePresenter
{
    /**
     * @param list<Resource> $resources
     * @return list<array{id: string, name: string}>
     */
    public function presentMany(array $resources): array
    {
        return array_map(
            static fn (Resource $resource): array => [
                'id' => $resource->id()->toRfc4122(),
                'name' => $resource->name(),
            ],
            $resources,
        );
    }
}
