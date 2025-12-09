<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Vendor;

use Nexus\ProcurementOperations\DTOs\VendorComplianceContext;

/**
 * Rule to validate that a vendor is active.
 */
final readonly class VendorActiveRule
{
    /**
     * Check if vendor is active.
     */
    public function check(VendorComplianceContext $context): VendorRuleResult
    {
        if (!$context->isActive) {
            return VendorRuleResult::fail(
                reason: sprintf('Vendor "%s" is not active', $context->vendorName),
                code: 'VENDOR_INACTIVE'
            );
        }

        return VendorRuleResult::pass();
    }
}
