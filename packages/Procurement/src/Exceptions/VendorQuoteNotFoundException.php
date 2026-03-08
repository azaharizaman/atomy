<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

/**
 * Exception thrown when a vendor quote is not found.
 */
final class VendorQuoteNotFoundException extends ProcurementException
{
    /**
     * Create exception for quote ID and tenant.
     *
     * @param string $tenantId
     * @param string $quoteId
     * @return self
     */
    public static function forId(string $tenantId, string $quoteId): self
    {
        return new self("Vendor quote '{$quoteId}' not found for tenant '{$tenantId}'.");
    }
}
