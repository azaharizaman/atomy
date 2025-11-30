<?php

declare(strict_types=1);

namespace Nexus\Assets\Exceptions;

/**
 * Invalid Asset Status Exception
 *
 * Thrown when an operation is attempted on an asset with invalid status.
 */
class InvalidAssetStatusException extends AssetException
{
    public static function cannotDepreciate(string $assetId, string $status): self
    {
        return new self("Asset {$assetId} cannot be depreciated in status: {$status}");
    }

    public static function cannotDispose(string $assetId, string $status): self
    {
        return new self("Asset {$assetId} cannot be disposed in status: {$status}");
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self("Invalid status transition from {$from} to {$to}");
    }
}
