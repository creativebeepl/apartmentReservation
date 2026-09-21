<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Resource;
use App\Domain\Repository\ResourceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineResourceRepository implements ResourceRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function find(Uuid $id): ?Resource
    {
        return $this->entityManager->find(Resource::class, $id);
    }

    public function findAllOrderedByName(): array
    {
        return $this->entityManager->getRepository(Resource::class)->findBy([], ['name' => 'ASC']);
    }

    public function count(): int
    {
        return $this->entityManager->getRepository(Resource::class)->count([]);
    }

    public function add(Resource $resource): void
    {
        $this->entityManager->persist($resource);
        $this->entityManager->flush();
    }
}
