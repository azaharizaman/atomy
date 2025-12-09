<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\SpendPolicy;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\PolicyViolationSeverity;
use Nexus\ProcurementOperations\Enums\SpendPolicyType;

/**
 * DTO representing a spend policy violation.
 */
final readonly class SpendPolicyViolation
{
    /**
     * @param SpendPolicyType $policyType Type of policy that was violated
     * @param PolicyViolationSeverity $severity Severity of the violation
     * @param string $message Human-readable description of the violation
     * @param string $ruleCode Unique code for the violated rule
     * @param bool $isOverridable Whether the violation can be overridden with approval
     * @param Money|null $threshold The policy threshold (if applicable)
     * @param Money|null $actual The actual value that violated the policy
     * @param string|null $relatedEntityId Related entity (vendor, category, contract, etc.)
     * @param array<string, mixed> $context Additional context about the violation
     */
    public function __construct(
        public SpendPolicyType $policyType,
        public PolicyViolationSeverity $severity,
        public string $message,
        public string $ruleCode,
        public bool $isOverridable = false,
        public ?Money $threshold = null,
        public ?Money $actual = null,
        public ?string $relatedEntityId = null,
        public array $context = [],
    ) {}

    /**
     * Create a category limit violation.
     */
    public static function categoryLimitExceeded(
        Money $threshold,
        Money $actual,
        string $categoryId,
        PolicyViolationSeverity $severity = PolicyViolationSeverity::ERROR,
    ): self {
        return new self(
            policyType: SpendPolicyType::CATEGORY_LIMIT,
            severity: $severity,
            message: sprintf(
                'Category spend limit exceeded. Limit: %s, Requested: %s',
                $threshold->format(),
                $actual->format()
            ),
            ruleCode: 'SPEND_CATEGORY_LIMIT_EXCEEDED',
            isOverridable: true,
            threshold: $threshold,
            actual: $actual,
            relatedEntityId: $categoryId,
        );
    }

    /**
     * Create a vendor limit violation.
     */
    public static function vendorLimitExceeded(
        Money $threshold,
        Money $actual,
        string $vendorId,
        PolicyViolationSeverity $severity = PolicyViolationSeverity::ERROR,
    ): self {
        return new self(
            policyType: SpendPolicyType::VENDOR_LIMIT,
            severity: $severity,
            message: sprintf(
                'Vendor spend limit exceeded. Limit: %s, Requested: %s',
                $threshold->format(),
                $actual->format()
            ),
            ruleCode: 'SPEND_VENDOR_LIMIT_EXCEEDED',
            isOverridable: true,
            threshold: $threshold,
            actual: $actual,
            relatedEntityId: $vendorId,
        );
    }

    /**
     * Create a maverick spend violation.
     */
    public static function maverickSpendDetected(
        string $message,
        ?string $contractId = null,
        PolicyViolationSeverity $severity = PolicyViolationSeverity::WARNING,
    ): self {
        return new self(
            policyType: SpendPolicyType::MAVERICK_SPEND,
            severity: $severity,
            message: $message,
            ruleCode: 'SPEND_MAVERICK_DETECTED',
            isOverridable: true,
            relatedEntityId: $contractId,
        );
    }

    /**
     * Create a preferred vendor violation.
     */
    public static function preferredVendorRequired(
        string $categoryId,
        PolicyViolationSeverity $severity = PolicyViolationSeverity::WARNING,
    ): self {
        return new self(
            policyType: SpendPolicyType::PREFERRED_VENDOR,
            severity: $severity,
            message: 'A preferred vendor is required for this category',
            ruleCode: 'SPEND_PREFERRED_VENDOR_REQUIRED',
            isOverridable: true,
            relatedEntityId: $categoryId,
        );
    }

    /**
     * Create a contract compliance violation.
     */
    public static function contractComplianceRequired(
        string $message,
        string $contractId,
        PolicyViolationSeverity $severity = PolicyViolationSeverity::ERROR,
    ): self {
        return new self(
            policyType: SpendPolicyType::CONTRACT_COMPLIANCE,
            severity: $severity,
            message: $message,
            ruleCode: 'SPEND_CONTRACT_COMPLIANCE_REQUIRED',
            isOverridable: false,
            relatedEntityId: $contractId,
        );
    }

    /**
     * Create a budget availability violation.
     */
    public static function budgetUnavailable(
        Money $budgetRemaining,
        Money $requested,
        string $budgetId,
        PolicyViolationSeverity $severity = PolicyViolationSeverity::CRITICAL,
    ): self {
        return new self(
            policyType: SpendPolicyType::BUDGET_AVAILABILITY,
            severity: $severity,
            message: sprintf(
                'Insufficient budget. Available: %s, Requested: %s',
                $budgetRemaining->format(),
                $requested->format()
            ),
            ruleCode: 'SPEND_BUDGET_UNAVAILABLE',
            isOverridable: false,
            threshold: $budgetRemaining,
            actual: $requested,
            relatedEntityId: $budgetId,
        );
    }
}
