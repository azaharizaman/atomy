<?php

declare(strict_types=1);

namespace App\Support;

final class DecimalString
{
    private const NUMERIC_PATTERN = '/^([+-]?)(\d+)(?:\.(\d+))?$/';

    public static function normalize(string $value, int $scale): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (! preg_match(self::NUMERIC_PATTERN, $trimmed, $matches)) {
            return null;
        }

        if (function_exists('bcadd')) {
            return self::canonicalizeNormalized((string) bcadd($trimmed, '0', $scale));
        }

        $sign = $matches[1] === '-' ? '-' : '';
        $integerPart = ltrim($matches[2], '0');
        if ($integerPart === '') {
            $integerPart = '0';
        }

        $fractionPart = $matches[3] ?? '';
        if ($scale === 0) {
            return self::canonicalizeNormalized((string) ($sign . $integerPart));
        }

        $normalizedFraction = substr($fractionPart, 0, $scale);
        $normalizedFraction = str_pad($normalizedFraction, $scale, '0');

        return self::canonicalizeNormalized($sign . $integerPart . '.' . $normalizedFraction);
    }

    private static function canonicalizeNormalized(string $normalized): string
    {
        $trimmed = trim($normalized);
        if ($trimmed === '') {
            return '0';
        }

        if ($trimmed[0] === '+') {
            $trimmed = substr($trimmed, 1);
        }

        if (preg_match('/^-?0+(?:\.0+)?$/', $trimmed) === 1) {
            return '0';
        }

        return $trimmed;
    }
}
