<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Symfony\Component\Uid\Uuid;

final class ResourceNotFoundException extends DomainNotFoundException
{
    public static function forId(Uuid $id): self
    {
        return new self(sprintf('Resource "%s" does not exist.', $id->toRfc4122()));
    }

    public function errorCode(): string
    {
        return 'resource_not_found';
    }
}
