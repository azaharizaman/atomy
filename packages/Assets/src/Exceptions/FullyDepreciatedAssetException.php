<?php

declare(strict_types=1);

namespace Nexus\Assets\Exceptions;

/**
 * Fully Depreciated Asset Exception
 *
 * Thrown when attempting to depreciate an asset that is already fully depreciated.
 */
class FullyDepreciatedAssetException extends AssetException
{
    public static function forAsset(string $assetId, float $netBookValue, float $salvageValue): self
    {
        return new self(
            "Asset {$assetId} is fully depreciated. " .
            "Net book value ({$netBookValue}) equals salvage value ({$salvageValue})"
        );
    }
}
