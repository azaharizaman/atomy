<?php

declare(strict_types=1);

namespace Nexus\Assets\Exceptions;

use Nexus\Assets\Enums\DepreciationMethod;

/**
 * Unsupported Depreciation Method Exception
 *
 * Thrown when a depreciation method is not supported by current tier or engine.
 */
class UnsupportedDepreciationMethodException extends AssetException
{
    public static function forMethod(DepreciationMethod $method, string $currentTier): self
    {
        return new self(
            "Depreciation method {$method->value} requires tier '{$method->getRequiredTier()}' " .
            "but current tier is '{$currentTier}'"
        );
    }

    public static function unitsNotSupported(DepreciationMethod $method): self
    {
        return new self(
            "Depreciation method {$method->value} does not support unit-based calculation"
        );
    }
}
