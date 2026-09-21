<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Booking;
use App\Domain\Repository\BookingRepositoryInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;

final readonly class DoctrineBookingRepository implements BookingRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Kontrola kolizji i zapis to JEDNO polecenie SQL (INSERT ... SELECT ... WHERE NOT EXISTS).
     * W SQLite polecenie zajmuje blokadę zapisu, zanim wykona podzapytanie, więc dwa równoległe
     * żądania nie mogą obie zobaczyć "wolnego" terminu. Nie polegamy na SELECT ... FOR UPDATE,
     * którego SQLite nie obsługuje. Przy migracji na PostgreSQL dodaj dodatkowo ograniczenie
     * EXCLUDE USING gist na (resource_id, zakres czasu); MySQL wymaga blokady wiersza zasobu.
     */
    public function addIfSlotFree(Booking $booking): bool
    {
        $period = $booking->period();

        // Parametry przechodzą przez te same typy DBAL co w ORM (UUID jako BLOB, daty jako UTC).
        $inserted = $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO bookings (id, resource_id, start_at, end_at, customer_name, created_at)
             SELECT :id, :resource_id, :start_at, :end_at, :customer_name, :created_at
             WHERE NOT EXISTS (
                 SELECT 1 FROM bookings
                 WHERE resource_id = :resource_id AND start_at < :end_at AND end_at > :start_at
             )',
            [
                'id' => $booking->id(),
                'resource_id' => $booking->resource()->id(),
                'start_at' => $period->start(),
                'end_at' => $period->end(),
                'customer_name' => $booking->customerName(),
                'created_at' => $booking->createdAt(),
            ],
            [
                'id' => UuidType::NAME,
                'resource_id' => UuidType::NAME,
                'start_at' => Types::DATETIME_IMMUTABLE,
                'end_at' => Types::DATETIME_IMMUTABLE,
                'customer_name' => Types::STRING,
                'created_at' => Types::DATETIME_IMMUTABLE,
            ],
        );

        return $inserted > 0;
    }

    public function findAllNewestFirst(): array
    {
        /** @var list<Booking> $bookings */
        $bookings = $this->entityManager->createQueryBuilder()
            ->select('b', 'r')
            ->from(Booking::class, 'b')
            ->join('b.resource', 'r')
            ->orderBy('b.startAt', 'DESC')
            ->addOrderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $bookings;
    }
}
