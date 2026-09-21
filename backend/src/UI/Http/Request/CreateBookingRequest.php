<?php

declare(strict_types=1);

namespace App\UI\Http\Request;

use App\Application\Booking\CreateBookingCommand;
use App\Domain\Entity\Booking;
use App\UI\Http\Validator\Iso8601DateTime;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class CreateBookingRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public ?string $resourceId = null,
        #[Assert\NotBlank]
        #[Iso8601DateTime]
        public ?string $startAt = null,
        #[Assert\NotBlank]
        #[Iso8601DateTime]
        public ?string $endAt = null,
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: Booking::CUSTOMER_NAME_MAX_LENGTH)]
        public ?string $customerName = null,
    ) {
    }

    #[Assert\Callback]
    public function validateOrder(ExecutionContextInterface $context): void
    {
        $start = Iso8601Parser::parse((string) $this->startAt);
        $end = Iso8601Parser::parse((string) $this->endAt);

        if ($start !== null && $end !== null && $end <= $start) {
            $context->buildViolation('The end must be after the start.')
                ->atPath('endAt')
                ->addViolation();
        }
    }

    /** Wołać dopiero po pomyślnej walidacji. */
    public function toCommand(): CreateBookingCommand
    {
        $start = Iso8601Parser::parse((string) $this->startAt);
        $end = Iso8601Parser::parse((string) $this->endAt);

        if ($start === null || $end === null || $this->resourceId === null || $this->customerName === null) {
            throw new \LogicException('toCommand() requires a validated request.');
        }

        return new CreateBookingCommand(Uuid::fromString($this->resourceId), $start, $end, $this->customerName);
    }
}
