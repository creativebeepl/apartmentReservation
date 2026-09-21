<?php

declare(strict_types=1);

namespace App\UI\Http\Validator;

use App\UI\Http\Request\Iso8601Parser;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class Iso8601DateTimeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof Iso8601DateTime) {
            throw new UnexpectedTypeException($constraint, Iso8601DateTime::class);
        }

        // Puste wartości obsługuje NotBlank.
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || Iso8601Parser::parse($value) === null) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
