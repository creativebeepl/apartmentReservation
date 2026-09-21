<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Booking\CreateBookingHandler;
use App\Application\Booking\ListBookingsHandler;
use App\UI\Http\Presenter\BookingPresenter;
use App\UI\Http\Request\CreateBookingRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class BookingController
{
    public function __construct(
        private CreateBookingHandler $createBooking,
        private ListBookingsHandler $listBookings,
        private BookingPresenter $presenter,
    ) {
    }

    #[Route('/api/bookings', name: 'api_bookings_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse(['data' => $this->presenter->presentMany(($this->listBookings)())]);
    }

    #[Route('/api/bookings', name: 'api_bookings_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateBookingRequest $payload): JsonResponse
    {
        $booking = ($this->createBooking)($payload->toCommand());

        return new JsonResponse(['data' => $this->presenter->present($booking)], Response::HTTP_CREATED);
    }
}
