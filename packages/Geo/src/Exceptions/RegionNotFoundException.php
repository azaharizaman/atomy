<?php

declare(strict_types=1);

namespace Nexus\Geo\Exceptions;

/**
 * Exception thrown when region is not found
 */
class RegionNotFoundException extends GeoException
{
    public static function byId(string $id): self
    {
        return new self("Region with ID '{$id}' not found");
    }

    public static function byCode(string $code): self
    {
        return new self("Region with code '{$code}' not found");
    }

    public static function forTenant(string $tenantId): self
    {
        return new self("No regions found for tenant '{$tenantId}'");
    }
}
