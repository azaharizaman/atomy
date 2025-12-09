<?php

declare(strict_types=1);

namespace Nexus\Tax\Exceptions;

/**
 * Exception thrown when a jurisdiction is not supported for tax calculations.
 */
final class JurisdictionNotSupportedException extends \RuntimeException
{
    /**
     * Create exception for unsupported jurisdiction.
     */
    public static function forJurisdiction(string $jurisdictionCode): self
    {
        return new self(
            message: "Jurisdiction '{$jurisdictionCode}' is not supported for withholding tax calculations.",
            code: 4001
        );
    }

    /**
     * Create exception for unsupported jurisdiction with available options.
     *
     * @param array<string> $supportedJurisdictions
     */
    public static function forJurisdictionWithOptions(
        string $jurisdictionCode,
        array $supportedJurisdictions
    ): self {
        $supported = implode(', ', $supportedJurisdictions);

        return new self(
            message: "Jurisdiction '{$jurisdictionCode}' is not supported. Supported jurisdictions: {$supported}",
            code: 4001
        );
    }

    /**
     * Create exception for missing WHT strategy.
     */
    public static function noStrategyFor(string $jurisdictionCode): self
    {
        return new self(
            message: "No withholding tax strategy registered for jurisdiction '{$jurisdictionCode}'.",
            code: 4002
        );
    }
}
