<?php

declare(strict_types=1);

namespace Nexus\Assets\Exceptions;

/**
 * Asset Not Found Exception
 *
 * Thrown when a requested asset does not exist.
 */
class AssetNotFoundException extends AssetException
{
    public static function forId(string $assetId): self
    {
        return new self("Asset not found: {$assetId}");
    }

    public static function forTag(string $assetTag): self
    {
        return new self("Asset not found with tag: {$assetTag}");
    }
}
