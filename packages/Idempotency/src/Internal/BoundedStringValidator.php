<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Internal;

use Nexus\Idempotency\Exceptions\IdempotencyKeyInvalidException;

/**
 * Shared non-empty trimmed string validation for bounded VOs.
 */
final class BoundedStringValidator
{
    public static function requireTrimmedNonEmpty(string $value, int $maxLength, string $fieldName): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw IdempotencyKeyInvalidException::forField($fieldName, 'must not be empty');
        }
        if (strlen($trimmed) > $maxLength) {
            throw IdempotencyKeyInvalidException::forField(
                $fieldName,
                'exceeds maximum length of ' . (string) $maxLength,
            );
        }

        return $trimmed;
    }
}
