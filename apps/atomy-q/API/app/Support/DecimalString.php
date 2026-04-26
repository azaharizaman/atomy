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

        $sign = $matches[1];
        $integerPart = ltrim($matches[2], '0');
        if ($integerPart === '') {
            $integerPart = '0';
        }

        $fractionPart = $matches[3] ?? '';
        if ($scale === 0) {
            return $sign . $integerPart;
        }

        $fractionPart = substr($fractionPart, 0, $scale);
        $fractionPart = str_pad($fractionPart, $scale, '0');

        return $sign . $integerPart . '.' . $fractionPart;
    }
}
