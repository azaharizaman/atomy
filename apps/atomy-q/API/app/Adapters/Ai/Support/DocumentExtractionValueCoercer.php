<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Support;

final class DocumentExtractionValueCoercer
{
    public static function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    public static function floatOrNull(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        $isParenthesizedNegative = false;
        if (preg_match('/^\((.+)\)$/', $normalized, $matches) === 1) {
            $isParenthesizedNegative = true;
            $normalized = trim((string) ($matches[1] ?? ''));
        }

        $normalized = preg_replace('/\s+/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/^[^\d+\-.,]+/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^\d.,]+$/u', '', $normalized) ?? $normalized;
        if ($normalized === '' || preg_match('/[^\d+\-.,]/u', $normalized) === 1) {
            return null;
        }

        $sign = '';
        $firstCharacter = $normalized[0] ?? '';
        if ($firstCharacter === '+' || $firstCharacter === '-') {
            $sign = $firstCharacter;
            $normalized = substr($normalized, 1);
        }

        if ($normalized === '' || strpbrk($normalized, '+-') !== false) {
            return null;
        }

        $numeric = self::normalizeNumericString($normalized);
        if ($numeric === null) {
            return null;
        }

        if ($sign === '-') {
            $numeric = '-' . $numeric;
        } elseif ($isParenthesizedNegative) {
            $numeric = '-' . $numeric;
        }

        return is_numeric($numeric) ? (float) $numeric : null;
    }

    private static function normalizeNumericString(string $value): ?string
    {
        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            if (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $value) !== 1) {
                return null;
            }

            return str_replace(',', '', $value);
        }

        if ($hasComma) {
            if (preg_match('/^\d{1,3}(,\d{3})+$/', $value) === 1) {
                return str_replace(',', '', $value);
            }

            if (preg_match('/^\d+,\d+$/', $value) === 1) {
                $parts = explode(',', $value, 2);
                $fractional = $parts[1] ?? '';
                if (strlen($fractional) === 3) {
                    return null;
                }

                return ($parts[0] ?? '') . '.' . $fractional;
            }

            return null;
        }

        if (preg_match('/^\d+(\.\d+)?$/', $value) === 1) {
            return $value;
        }

        return null;
    }
}
