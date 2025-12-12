<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules;

use Nexus\ProcurementOperations\DTOs\Audit\LegalHoldData;
use Nexus\ProcurementOperations\DTOs\Audit\RetentionPolicyData;
use Nexus\ProcurementOperations\Enums\RetentionCategory;

/**
 * Rule for validating document retention policy compliance.
 */
final readonly class RetentionPolicyRule
{
    /**
     * Check if document is within retention period.
     *
     * @param \DateTimeImmutable $documentDate Document creation date
     * @param RetentionPolicyData $policy Retention policy
     * @param \DateTimeImmutable|null $asOfDate Date to check against
     * @return RetentionPolicyRuleResult Validation result
     */
    public function checkRetentionPeriod(
        \DateTimeImmutable $documentDate,
        RetentionPolicyData $policy,
        ?\DateTimeImmutable $asOfDate = null,
    ): RetentionPolicyRuleResult {
        $asOfDate ??= new \DateTimeImmutable();

        $isWithin = $policy->isWithinRetention($documentDate, $asOfDate);

        if ($isWithin) {
            return RetentionPolicyRuleResult::pass(
                message: 'Document is within retention period',
                remainingDays: $policy->getRemainingDays($documentDate, $asOfDate),
            );
        }

        return RetentionPolicyRuleResult::pass(
            message: 'Document retention period has expired',
            remainingDays: 0,
            canDispose: true,
        );
    }

    /**
     * Check if document can be disposed.
     *
     * @param \DateTimeImmutable $documentDate Document creation date
     * @param RetentionPolicyData $policy Retention policy
     * @param array<LegalHoldData> $activeLegalHolds Active legal holds
     * @param \DateTimeImmutable|null $asOfDate Date to check against
     * @return RetentionPolicyRuleResult Validation result
     */
    public function checkDisposalEligibility(
        \DateTimeImmutable $documentDate,
        RetentionPolicyData $policy,
        array $activeLegalHolds = [],
        ?\DateTimeImmutable $asOfDate = null,
    ): RetentionPolicyRuleResult {
        $asOfDate ??= new \DateTimeImmutable();

        // Check if still within retention
        if ($policy->isWithinRetention($documentDate, $asOfDate)) {
            return RetentionPolicyRuleResult::fail(
                message: 'Document is still within retention period',
                reason: 'RETENTION_ACTIVE',
                remainingDays: $policy->getRemainingDays($documentDate, $asOfDate),
            );
        }

        // Check for active legal holds
        foreach ($activeLegalHolds as $hold) {
            if ($hold->isActive($asOfDate)) {
                return RetentionPolicyRuleResult::fail(
                    message: 'Document is subject to active legal hold',
                    reason: 'LEGAL_HOLD_ACTIVE',
                    holdReference: $hold->holdId,
                );
            }
        }

        // Check if category requires legal hold verification
        if ($policy->category->requiresLegalHoldCheck()) {
            return RetentionPolicyRuleResult::pass(
                message: 'Document eligible for disposal pending legal hold verification',
                canDispose: true,
                requiresVerification: true,
            );
        }

        return RetentionPolicyRuleResult::pass(
            message: 'Document is eligible for disposal',
            canDispose: true,
        );
    }

    /**
     * Check if retention policy meets regulatory requirements.
     *
     * @param RetentionPolicyData $policy Retention policy
     * @param RetentionCategory $category Document category
     * @return RetentionPolicyRuleResult Validation result
     */
    public function checkRegulatoryCompliance(
        RetentionPolicyData $policy,
        RetentionCategory $category,
    ): RetentionPolicyRuleResult {
        $requiredYears = $category->getRetentionYears();
        $policyYears = $policy->retentionYears;

        if ($policyYears < $requiredYears) {
            return RetentionPolicyRuleResult::fail(
                message: "Policy retention ({$policyYears} years) is less than regulatory requirement ({$requiredYears} years)",
                reason: 'INSUFFICIENT_RETENTION',
                requiredYears: $requiredYears,
            );
        }

        if ($category->isSubjectToSox() && !$this->validateSoxRequirements($policy)) {
            return RetentionPolicyRuleResult::fail(
                message: 'Policy does not meet SOX retention requirements',
                reason: 'SOX_NON_COMPLIANT',
            );
        }

        return RetentionPolicyRuleResult::pass(
            message: 'Policy meets regulatory requirements',
            regulatoryBasis: $category->getRegulatoryBasis(),
        );
    }

    /**
     * Validate SOX-specific retention requirements.
     */
    private function validateSoxRequirements(RetentionPolicyData $policy): bool
    {
        // SOX requires minimum 7 years for financial records
        return $policy->retentionYears >= 7;
    }
}

/**
 * Result object for retention policy rule validation.
 */
final readonly class RetentionPolicyRuleResult
{
    private function __construct(
        public bool $passed,
        public string $message,
        public ?string $reason = null,
        public ?int $remainingDays = null,
        public bool $canDispose = false,
        public bool $requiresVerification = false,
        public ?string $holdReference = null,
        public ?int $requiredYears = null,
        public ?string $regulatoryBasis = null,
    ) {}

    public static function pass(
        string $message,
        ?int $remainingDays = null,
        bool $canDispose = false,
        bool $requiresVerification = false,
        ?string $regulatoryBasis = null,
    ): self {
        return new self(
            passed: true,
            message: $message,
            remainingDays: $remainingDays,
            canDispose: $canDispose,
            requiresVerification: $requiresVerification,
            regulatoryBasis: $regulatoryBasis,
        );
    }

    public static function fail(
        string $message,
        string $reason,
        ?int $remainingDays = null,
        ?string $holdReference = null,
        ?int $requiredYears = null,
    ): self {
        return new self(
            passed: false,
            message: $message,
            reason: $reason,
            remainingDays: $remainingDays,
            holdReference: $holdReference,
            requiredYears: $requiredYears,
        );
    }
}
