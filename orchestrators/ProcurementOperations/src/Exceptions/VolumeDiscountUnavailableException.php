<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Volume Discount Unavailable Exception
 * 
 * Thrown when a volume discount operation is requested but not supported
 * or available for the given context.
 * 
 * @package Nexus\ProcurementOperations\Exceptions
 */
class VolumeDiscountUnavailableException extends ProcurementOperationsException
{
    public static function forVendor(string $vendorId, string $reason = 'Volume discount service is not available'): self
    {
        return new self("Volume discount for vendor {$vendorId} is unavailable: {$reason}");
    }

    public static function forTenant(string $tenantId, string $reason = 'Volume discount service is not available'): self
    {
        return new self("Volume discount for tenant {$tenantId} is unavailable: {$reason}");
    }
}
