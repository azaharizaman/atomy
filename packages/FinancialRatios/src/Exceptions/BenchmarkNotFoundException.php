<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Exceptions;

/**
 * Exception thrown when benchmark data is not available.
 */
class BenchmarkNotFoundException extends \RuntimeException
{
    public static function forIndustry(string $industryCode): self
    {
        return new self(
            sprintf('Benchmark data not found for industry: %s', $industryCode)
        );
    }

    public static function forRatio(string $ratioName, string $industryCode): self
    {
        return new self(
            sprintf(
                'Benchmark for ratio "%s" not found in industry "%s".',
                $ratioName,
                $industryCode
            )
        );
    }
}
