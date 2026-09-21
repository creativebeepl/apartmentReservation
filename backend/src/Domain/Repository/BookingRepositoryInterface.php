<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Booking;

interface BookingRepositoryInterface
{
    /**
     * Zapisuje rezerwację tylko wtedy, gdy nie koliduje z inną rezerwacją tego samego zasobu.
     * Sprawdzenie i zapis muszą być jedną operacją atomową — inaczej dwa równoległe żądania
     * mogłyby obie przejść kontrolę i podwójnie zarezerwować ten sam termin.
     *
     * @return bool true, gdy zapisano; false, gdy termin jest zajęty
     */
    public function addIfSlotFree(Booking $booking): bool;

    /** @return list<Booking> od najnowszego początku rezerwacji, z dołączonym zasobem */
    public function findAllNewestFirst(): array;
}
