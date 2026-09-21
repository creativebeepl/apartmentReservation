<?php

declare(strict_types=1);

namespace App\UI\Http\Request;

/**
 * Ścisły parser dat ISO 8601 z OBOWIĄZKOWĄ strefą czasową (Z lub ±HH:MM).
 * Dzięki temu nie ma niejednoznacznych dat "bez strefy" ani napisów typu "tomorrow".
 */
final class Iso8601Parser
{
    private const PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/';

    public static function parse(string $value): ?\DateTimeImmutable
    {
        if (preg_match(self::PATTERN, $value) !== 1) {
            return null;
        }

        try {
            $moment = new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }

        // Konstruktor "zawija" nieistniejące daty (np. 2026-02-31) — odrzucamy je.
        $errors = \DateTimeImmutable::getLastErrors();
        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        return $moment;
    }
}
