<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\ApiToken;
use App\Domain\Repository\ApiTokenRepositoryInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineApiTokenRepository implements ApiTokenRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(ApiToken $token): void
    {
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    public function findByHash(string $tokenHash): ?ApiToken
    {
        return $this->entityManager->getRepository(ApiToken::class)->findOneBy(['tokenHash' => $tokenHash]);
    }

    public function removeExpired(\DateTimeImmutable $now): int
    {
        $removed = $this->entityManager->createQueryBuilder()
            ->delete(ApiToken::class, 't')
            ->where('t.expiresAt <= :now')
            ->setParameter('now', $now->setTimezone(new \DateTimeZone('UTC')), Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute();

        return is_int($removed) ? $removed : 0;
    }
}
