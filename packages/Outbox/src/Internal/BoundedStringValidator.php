<?php

declare(strict_types=1);

namespace Nexus\Outbox\Internal;

use Nexus\Outbox\Exceptions\OutboxKeyInvalidException;

/**
 * Shared non-empty trimmed string validation for bounded VOs.
 */
final class BoundedStringValidator
{
    public static function requireTrimmedNonEmpty(string $value, int $maxLength, string $fieldName): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw OutboxKeyInvalidException::forField($fieldName, 'must not be empty');
        }
        if (strlen($trimmed) > $maxLength) {
            throw OutboxKeyInvalidException::forField(
                $fieldName,
                'exceeds maximum length of ' . (string) $maxLength,
            );
        }

        return $trimmed;
    }

    public static function requireNullableBounded(?string $value, int $maxLength, string $fieldName): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }
        if (strlen($trimmed) > $maxLength) {
            throw OutboxKeyInvalidException::forField(
                $fieldName,
                'exceeds maximum length of ' . (string) $maxLength,
            );
        }

        return $trimmed;
    }
}
