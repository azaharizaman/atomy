<?php

declare(strict_types=1);

namespace Nexus\Assets\Exceptions;

/**
 * Duplicate Asset Tag Exception
 *
 * Thrown when attempting to create an asset with a duplicate tag.
 */
class DuplicateAssetTagException extends AssetException
{
    public static function forTag(string $assetTag, string $tenantId): self
    {
        return new self("Asset tag '{$assetTag}' already exists for tenant {$tenantId}");
    }
}
