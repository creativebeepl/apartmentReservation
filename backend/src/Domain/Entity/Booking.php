<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\BookingPeriod;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'bookings')]
#[ORM\Index(name: 'idx_bookings_resource_period', columns: ['resource_id', 'start_at', 'end_at'])]
#[ORM\Index(name: 'idx_bookings_start_at', columns: ['start_at'])]
class Booking
{
    public const CUSTOMER_NAME_MAX_LENGTH = 255;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Resource::class)]
    #[ORM\JoinColumn(name: 'resource_id', nullable: false, onDelete: 'RESTRICT')]
    private Resource $resource;

    #[ORM\Column(name: 'start_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startAt;

    #[ORM\Column(name: 'end_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $endAt;

    #[ORM\Column(name: 'customer_name', length: self::CUSTOMER_NAME_MAX_LENGTH)]
    private string $customerName;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Uuid $id,
        Resource $resource,
        BookingPeriod $period,
        string $customerName,
        \DateTimeImmutable $createdAt,
    ) {
        $customerName = trim($customerName);
        if ($customerName === '' || mb_strlen($customerName) > self::CUSTOMER_NAME_MAX_LENGTH) {
            throw new \InvalidArgumentException('Customer name must have 1-255 characters.');
        }

        $this->id = $id;
        $this->resource = $resource;
        $this->startAt = $period->start();
        $this->endAt = $period->end();
        $this->customerName = $customerName;
        $this->createdAt = $createdAt->setTimezone(new \DateTimeZone('UTC'));
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function resource(): Resource
    {
        return $this->resource;
    }

    public function period(): BookingPeriod
    {
        return new BookingPeriod($this->startAt, $this->endAt);
    }

    public function customerName(): string
    {
        return $this->customerName;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
