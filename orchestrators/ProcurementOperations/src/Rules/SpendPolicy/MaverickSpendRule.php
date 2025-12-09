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
 * Rule to detect maverick spend (off-contract purchasing).
 *
 * Maverick spend occurs when purchases are made outside of
 * established contracts or from non-preferred vendors.
 */
final readonly class MaverickSpendRule implements SpendPolicyRuleInterface
{
    private const string NAME = 'maverick_spend';

    /**
     * @inheritDoc
     */
    public function check(SpendPolicyContext $context): RuleResult
    {
        $issues = [];

        // Check 1: Is there an active contract but not using it?
        if ($context->hasActiveContract && !$context->vendorIsPreferred) {
            $issues[] = sprintf(
                'Active contract exists for category but vendor %s is not a contracted vendor',
                $context->request->vendorId ?? 'N/A'
            );
        }

        // Check 2: Contract available but purchasing from non-contract vendor?
        if ($context->hasActiveContract
            && $context->request->hasVendor()
            && $context->activeContractId !== null
        ) {
            // If vendor is not the contracted vendor, it's maverick spend
            $contractVendorId = $context->getPolicySetting('contract_vendor_' . $context->activeContractId);
            if ($contractVendorId !== null && $contractVendorId !== $context->request->vendorId) {
                $issues[] = sprintf(
                    'Contract %s exists with different vendor. Current vendor: %s',
                    $context->activeContractId,
                    $context->request->vendorId
                );
            }
        }

        // Check 3: High-value purchase without contract?
        $thresholdPercent = $context->getPolicySetting('maverick_spend_threshold_percent', 10);
        if ($context->categoryLimit !== null) {
            $thresholdAmount = $context->categoryLimit->multiply($thresholdPercent / 100);
            if (!$context->hasActiveContract && $context->request->amount->isGreaterThanOrEqual($thresholdAmount)) {
                $issues[] = sprintf(
                    'High-value purchase (%s) exceeds %d%% of category limit without contract',
                    $context->request->amount->format(),
                    $thresholdPercent
                );
            }
        }

        if (empty($issues)) {
            return RuleResult::pass(self::NAME, 'No maverick spend detected');
        }

        // Determine severity based on number of issues
        $severity = count($issues) > 1
            ? PolicyViolationSeverity::WARNING
            : PolicyViolationSeverity::INFO;

        $violation = SpendPolicyViolation::maverickSpendDetected(
            message: implode('; ', $issues),
            contractId: $context->activeContractId,
            severity: $severity,
        );

        return RuleResult::fail(
            self::NAME,
            $violation->message,
            ['violation' => $violation, 'issues' => $issues]
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
        return SpendPolicyType::MAVERICK_SPEND->value;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SpendPolicyContext $context): bool
    {
        return $context->isPolicyEnabled(SpendPolicyType::MAVERICK_SPEND->value);
    }
}
