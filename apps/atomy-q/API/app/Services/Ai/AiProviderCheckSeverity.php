<?php

declare(strict_types=1);

namespace App\Services\Ai;

final class AiProviderCheckSeverity
{
    public const OK = 'ok';
    public const WARNING = 'warning';
    public const FAILED = 'failed';
    public const SKIPPED = 'skipped';
    public const UNKNOWN = 'unknown';

    public static function rank(string $severity): int
    {
        return match ($severity) {
            self::FAILED => 50,
            self::WARNING => 40,
            self::UNKNOWN => 30,
            self::SKIPPED => 20,
            self::OK => 10,
            default => 30,
        };
    }

    public static function worse(string $left, string $right): string
    {
        return self::rank($left) >= self::rank($right) ? $left : $right;
    }
}
