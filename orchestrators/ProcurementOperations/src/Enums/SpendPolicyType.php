<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Spend policy types for procurement governance.
 */
enum SpendPolicyType: string
{
    /**
     * Maximum spend per category within a time period
     */
    case CATEGORY_LIMIT = 'category_limit';

    /**
     * Maximum spend per vendor within a time period
     */
    case VENDOR_LIMIT = 'vendor_limit';

    /**
     * Contract compliance requirements
     */
    case CONTRACT_COMPLIANCE = 'contract_compliance';

    /**
     * Preferred vendor enforcement
     */
    case PREFERRED_VENDOR = 'preferred_vendor';

    /**
     * Approval override requirements
     */
    case APPROVAL_OVERRIDE = 'approval_override';

    /**
     * Maverick spend detection (off-contract purchasing)
     */
    case MAVERICK_SPEND = 'maverick_spend';

    /**
     * Budget availability check
     */
    case BUDGET_AVAILABILITY = 'budget_availability';

    /**
     * Department spend threshold
     */
    case DEPARTMENT_THRESHOLD = 'department_threshold';

    /**
     * Get human-readable label for this policy type.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::CATEGORY_LIMIT => 'Category Spend Limit',
            self::VENDOR_LIMIT => 'Vendor Spend Limit',
            self::CONTRACT_COMPLIANCE => 'Contract Compliance',
            self::PREFERRED_VENDOR => 'Preferred Vendor Requirement',
            self::APPROVAL_OVERRIDE => 'Approval Override Required',
            self::MAVERICK_SPEND => 'Maverick Spend Detection',
            self::BUDGET_AVAILABILITY => 'Budget Availability',
            self::DEPARTMENT_THRESHOLD => 'Department Threshold',
        };
    }

    /**
     * Check if this policy type blocks transactions on violation.
     */
    public function isBlockingByDefault(): bool
    {
        return match ($this) {
            self::CATEGORY_LIMIT,
            self::VENDOR_LIMIT,
            self::CONTRACT_COMPLIANCE,
            self::BUDGET_AVAILABILITY => true,

            self::PREFERRED_VENDOR,
            self::APPROVAL_OVERRIDE,
            self::MAVERICK_SPEND,
            self::DEPARTMENT_THRESHOLD => false,
        };
    }
}
