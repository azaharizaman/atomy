<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Vendor;

use Nexus\ProcurementOperations\DTOs\VendorComplianceContext;

/**
 * Rule to validate that a vendor meets compliance requirements.
 *
 * Checks various compliance indicators like certificates, insurance, tax documents.
 */
final readonly class VendorCompliantRule
{
    /**
     * @param array<string> $requiredChecks List of compliance checks that must pass
     */
    public function __construct(
        private array $requiredChecks = []
    ) {}

    /**
     * Check if vendor meets all compliance requirements.
     */
    public function check(VendorComplianceContext $context): VendorRuleResult
    {
        if (!$context->isCompliant) {
            $failedChecks = $this->getFailedChecks($context);

            return VendorRuleResult::fail(
                reason: sprintf(
                    'Vendor "%s" does not meet compliance requirements: %s',
                    $context->vendorName,
                    implode(', ', $failedChecks)
                ),
                code: 'VENDOR_NON_COMPLIANT'
            );
        }

        // If specific checks are required, validate them
        if (!empty($this->requiredChecks)) {
            $missingChecks = [];

            foreach ($this->requiredChecks as $checkName) {
                $checkResult = $context->getComplianceCheck($checkName);
                if ($checkResult !== true) {
                    $missingChecks[] = $checkName;
                }
            }

            if (!empty($missingChecks)) {
                return VendorRuleResult::fail(
                    reason: sprintf(
                        'Vendor "%s" is missing required compliance checks: %s',
                        $context->vendorName,
                        implode(', ', $missingChecks)
                    ),
                    code: 'VENDOR_MISSING_COMPLIANCE_CHECKS'
                );
            }
        }

        return VendorRuleResult::pass();
    }

    /**
     * Check compliance for high-value transactions (stricter requirements).
     */
    public function checkForHighValue(VendorComplianceContext $context): VendorRuleResult
    {
        // High value transactions require recent compliance review
        if ($context->lastComplianceReview === null) {
            return VendorRuleResult::fail(
                reason: sprintf(
                    'Vendor "%s" has never had a compliance review',
                    $context->vendorName
                ),
                code: 'VENDOR_NO_COMPLIANCE_REVIEW'
            );
        }

        $daysSinceReview = (new \DateTimeImmutable())->diff($context->lastComplianceReview)->days;
        if ($daysSinceReview > 365) {
            return VendorRuleResult::fail(
                reason: sprintf(
                    'Vendor "%s" compliance review is outdated (%d days old)',
                    $context->vendorName,
                    $daysSinceReview
                ),
                code: 'VENDOR_COMPLIANCE_REVIEW_OUTDATED'
            );
        }

        return $this->check($context);
    }

    /**
     * Get list of failed compliance checks.
     *
     * @return array<string>
     */
    private function getFailedChecks(VendorComplianceContext $context): array
    {
        $failed = [];

        foreach ($context->complianceChecks as $checkName => $passed) {
            if (!$passed) {
                $failed[] = $checkName;
            }
        }

        return $failed;
    }
}
