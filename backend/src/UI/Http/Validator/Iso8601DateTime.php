<?php

declare(strict_types=1);

namespace App\UI\Http\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Iso8601DateTime extends Constraint
{
    public string $message = 'This value must be an ISO 8601 date-time with a time zone, e.g. 2026-07-01T10:00:00Z.';
}
