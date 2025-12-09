<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\SpendPolicy;

use Nexus\ProcurementOperations\Contracts\SpendPolicyRuleInterface;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyContext;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyViolation;
use Nexus\ProcurementOperations\Enums\PolicyViolationSeverity;
use Nexus\ProcurementOperations\Enums\SpendPolicyType;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Rule to enforce vendor spend limits.
 *
 * Validates that the transaction does not exceed the configured
 * spend limit for the vendor.
 */
final readonly class VendorSpendLimitRule implements SpendPolicyRuleInterface
{
    private const string NAME = 'vendor_spend_limit';

    /**
     * @inheritDoc
     */
    public function check(SpendPolicyContext $context): RuleResult
    {
        // Skip if no vendor in request
        if (!$context->request->hasVendor()) {
            return RuleResult::pass(self::NAME, 'No vendor specified');
        }

        // Skip if no limit defined
        if ($context->vendorLimit === null) {
            return RuleResult::pass(self::NAME, 'No vendor limit defined');
        }

        // Check if limit would be exceeded
        if (!$context->wouldExceedVendorLimit()) {
            $remaining = $context->vendorLimit->subtract($context->getProjectedVendorSpend());
            return RuleResult::pass(self::NAME, sprintf(
                'Within vendor limit. Remaining: %s',
                $remaining->format()
            ));
        }

        // Determine severity based on how much over limit
        $projectedSpend = $context->getProjectedVendorSpend();
        $overagePercent = ($projectedSpend->getAmountInMinorUnits() - $context->vendorLimit->getAmountInMinorUnits())
            / $context->vendorLimit->getAmountInMinorUnits() * 100;

        $severity = match (true) {
            $overagePercent >= 50 => PolicyViolationSeverity::CRITICAL,
            $overagePercent >= 20 => PolicyViolationSeverity::ERROR,
            default => PolicyViolationSeverity::WARNING,
        };

        $violation = SpendPolicyViolation::vendorLimitExceeded(
            threshold: $context->vendorLimit,
            actual: $projectedSpend,
            vendorId: $context->request->vendorId,
            severity: $severity,
        );

        return RuleResult::fail(
            self::NAME,
            $violation->message,
            [
                'violation' => $violation,
                'overage_percent' => round($overagePercent, 2),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getPolicyType(): string
    {
        return SpendPolicyType::VENDOR_LIMIT->value;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SpendPolicyContext $context): bool
    {
        return $context->request->hasVendor()
            && $context->isPolicyEnabled(SpendPolicyType::VENDOR_LIMIT->value);
    }
}
