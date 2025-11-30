<?php

declare(strict_types=1);

namespace Nexus\Assets\Exceptions;

/**
 * Negative Book Value Exception
 *
 * Thrown when a calculation would result in negative book value.
 */
class NegativeBookValueException extends AssetException
{
    public static function fromDepreciation(string $assetId, float $currentValue, float $depreciation): self
    {
        return new self(
            "Depreciation calculation for asset {$assetId} would result in negative book value. " .
            "Current: {$currentValue}, Depreciation: {$depreciation}"
        );
    }
}
