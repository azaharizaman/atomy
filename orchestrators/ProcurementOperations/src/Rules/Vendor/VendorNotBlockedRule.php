<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Vendor;

use Nexus\ProcurementOperations\DTOs\VendorComplianceContext;

/**
 * Rule to validate that a vendor is not blocked for new transactions.
 *
 * This rule checks for hard blocks that prevent any new POs or payments.
 */
final readonly class VendorNotBlockedRule
{
    /**
     * Check if vendor is not blocked for new POs.
     */
    public function checkForPurchaseOrder(VendorComplianceContext $context): VendorRuleResult
    {
        if (!$context->canReceiveNewPurchaseOrders()) {
            if ($context->hasHardBlock()) {
                $reasons = array_map(
                    fn ($r) => $r->description(),
                    array_filter(
                        $context->activeHoldReasons,
                        fn ($r) => $r->isHardBlock()
                    )
                );

                return VendorRuleResult::fail(
                    reason: sprintf(
                        'Vendor "%s" is blocked: %s',
                        $context->vendorName,
                        implode(', ', $reasons)
                    ),
                    code: 'VENDOR_HARD_BLOCKED'
                );
            }

            return VendorRuleResult::fail(
                reason: sprintf('Vendor "%s" is not active', $context->vendorName),
                code: 'VENDOR_INACTIVE'
            );
        }

        return VendorRuleResult::pass();
    }

    /**
     * Check if vendor is not blocked for payments.
     */
    public function checkForPayment(VendorComplianceContext $context): VendorRuleResult
    {
        if (!$context->canReceivePayments()) {
            if ($context->hasHardBlock()) {
                $reasons = array_map(
                    fn ($r) => $r->description(),
                    array_filter(
                        $context->activeHoldReasons,
                        fn ($r) => $r->isHardBlock()
                    )
                );

                return VendorRuleResult::fail(
                    reason: sprintf(
                        'Vendor "%s" is blocked for payments: %s',
                        $context->vendorName,
                        implode(', ', $reasons)
                    ),
                    code: 'VENDOR_PAYMENT_BLOCKED'
                );
            }

            return VendorRuleResult::fail(
                reason: sprintf('Vendor "%s" is not active for payments', $context->vendorName),
                code: 'VENDOR_INACTIVE'
            );
        }

        return VendorRuleResult::pass();
    }

    /**
     * Check if vendor has any blocks (hard or soft).
     *
     * Useful for warning users even if transaction is allowed.
     */
    public function checkForAnyBlocks(VendorComplianceContext $context): VendorRuleResult
    {
        if ($context->isBlocked) {
            $reasons = array_map(
                fn ($r) => $r->description(),
                $context->activeHoldReasons
            );

            return VendorRuleResult::fail(
                reason: sprintf(
                    'Vendor "%s" has active holds: %s',
                    $context->vendorName,
                    implode(', ', $reasons)
                ),
                code: $context->hasHardBlock() ? 'VENDOR_HARD_BLOCKED' : 'VENDOR_SOFT_BLOCKED'
            );
        }

        return VendorRuleResult::pass();
    }
}
