<?php

declare(strict_types=1);

namespace App\UI\Http\Presenter;

use App\Domain\Entity\Booking;

final readonly class BookingPresenter
{
    /** @return array<string, string> */
    public function present(Booking $booking): array
    {
        $period = $booking->period();

        return [
            'id' => $booking->id()->toRfc4122(),
            'resource_id' => $booking->resource()->id()->toRfc4122(),
            'resource_name' => $booking->resource()->name(),
            'start_at' => self::format($period->start()),
            'end_at' => self::format($period->end()),
            'customer_name' => $booking->customerName(),
            'created_at' => self::format($booking->createdAt()),
        ];
    }

    /**
     * @param list<Booking> $bookings
     * @return list<array<string, string>>
     */
    public function presentMany(array $bookings): array
    {
        return array_map($this->present(...), $bookings);
    }

    private static function format(\DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
