<?php

declare(strict_types=1);

namespace Nexus\Vendor\Internal;

final class BoundedStringValidator
{
    public static function requireTrimmedNonEmpty(
        string $value,
        int $maxLength,
        string $emptyMessage,
        ?string $tooLongMessage = null,
    ): string {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException($emptyMessage);
        }

        if (strlen($trimmed) > $maxLength) {
            throw new \InvalidArgumentException($tooLongMessage ?? 'Value exceeds maximum length.');
        }

        return $trimmed;
    }

    public static function nullableTrimmed(
        ?string $value,
        int $maxLength,
        ?string $tooLongMessage = null,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (strlen($trimmed) > $maxLength) {
            throw new \InvalidArgumentException($tooLongMessage ?? 'Value exceeds maximum length.');
        }

        return $trimmed;
    }
}
