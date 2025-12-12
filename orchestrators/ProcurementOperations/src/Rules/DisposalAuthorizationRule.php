<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules;

use Nexus\ProcurementOperations\DTOs\Audit\DisposalCertificationData;
use Nexus\ProcurementOperations\DTOs\Audit\LegalHoldData;
use Nexus\ProcurementOperations\DTOs\Audit\RetentionPolicyData;
use Nexus\ProcurementOperations\Enums\RetentionCategory;

/**
 * Rule for validating document disposal authorization.
 */
final readonly class DisposalAuthorizationRule
{
    private const int MIN_APPROVER_LEVEL = 3; // Manager level minimum
    private const int SENSITIVE_MIN_APPROVER_LEVEL = 5; // Director level for sensitive
    private const int MIN_APPROVAL_COUNT = 2; // Dual approval required

    /**
     * Validate disposal eligibility for a document.
     *
     * @param string $documentId Document identifier
     * @param RetentionPolicyData $retentionPolicy Applicable retention policy
     * @param \DateTimeImmutable $documentDate Original document date
     * @param array<LegalHoldData> $activeLegalHolds Active legal holds
     * @return DisposalAuthorizationRuleResult Validation result
     */
    public function validateDisposalEligibility(
        string $documentId,
        RetentionPolicyData $retentionPolicy,
        \DateTimeImmutable $documentDate,
        array $activeLegalHolds = [],
    ): DisposalAuthorizationRuleResult {
        // Check if within retention period
        if ($retentionPolicy->isWithinRetention($documentDate)) {
            $expirationDate = $retentionPolicy->calculateExpirationDate($documentDate);
            $remainingDays = $retentionPolicy->getRemainingDays($documentDate);

            return DisposalAuthorizationRuleResult::fail(
                message: 'Document is still within retention period',
                reason: 'WITHIN_RETENTION_PERIOD',
                documentId: $documentId,
                category: $retentionPolicy->category->value,
                expirationDate: $expirationDate->format('Y-m-d'),
                remainingDays: $remainingDays,
            );
        }

        // Check for active legal holds
        foreach ($activeLegalHolds as $legalHold) {
            if ($legalHold->isActive() && $legalHold->affectsDocument($documentId)) {
                return DisposalAuthorizationRuleResult::fail(
                    message: 'Document is under active legal hold',
                    reason: 'LEGAL_HOLD_ACTIVE',
                    documentId: $documentId,
                    legalHoldId: $legalHold->holdId,
                    legalHoldMatter: $legalHold->matterReference,
                    holdStartDate: $legalHold->holdStartDate->format('Y-m-d'),
                );
            }
        }

        // Check if category requires legal hold verification
        if ($retentionPolicy->category->requiresLegalHoldCheck() && empty($activeLegalHolds)) {
            return DisposalAuthorizationRuleResult::warn(
                message: 'Category requires legal hold check - verify with legal department',
                documentId: $documentId,
                category: $retentionPolicy->category->value,
                requiredAction: 'LEGAL_CLEARANCE',
            );
        }

        return DisposalAuthorizationRuleResult::pass(
            message: 'Document eligible for disposal',
            documentId: $documentId,
            category: $retentionPolicy->category->value,
        );
    }

    /**
     * Validate disposal approval chain.
     *
     * @param DisposalCertificationData $certification Disposal certification
     * @param RetentionCategory $category Document category
     * @return DisposalAuthorizationRuleResult Validation result
     */
    public function validateApprovalChain(
        DisposalCertificationData $certification,
        RetentionCategory $category,
    ): DisposalAuthorizationRuleResult {
        $requiredLevel = $this->getRequiredApproverLevel($category);
        $approvals = $certification->approvalChain;

        // Check minimum approval count
        if (count($approvals) < self::MIN_APPROVAL_COUNT) {
            return DisposalAuthorizationRuleResult::fail(
                message: 'Insufficient approvals - dual approval required',
                reason: 'INSUFFICIENT_APPROVALS',
                category: $category->value,
                requiredApprovals: self::MIN_APPROVAL_COUNT,
                actualApprovals: count($approvals),
            );
        }

        // Validate approver levels
        $highestApproverLevel = 0;
        $approverLevels = [];

        foreach ($approvals as $approval) {
            $level = $approval['level'] ?? 0;
            $approverLevels[$approval['approver_id']] = $level;
            $highestApproverLevel = max($highestApproverLevel, $level);
        }

        if ($highestApproverLevel < $requiredLevel) {
            return DisposalAuthorizationRuleResult::fail(
                message: "Highest approver level {$highestApproverLevel} is below required level {$requiredLevel}",
                reason: 'INSUFFICIENT_AUTHORITY',
                category: $category->value,
                requiredLevel: $requiredLevel,
                actualLevel: $highestApproverLevel,
            );
        }

        // Check for segregation of duties in approvals
        if (count(array_unique(array_keys($approverLevels))) < self::MIN_APPROVAL_COUNT) {
            return DisposalAuthorizationRuleResult::fail(
                message: 'Same approver cannot provide multiple approvals',
                reason: 'DUPLICATE_APPROVER',
                category: $category->value,
            );
        }

        return DisposalAuthorizationRuleResult::pass(
            message: 'Approval chain validated successfully',
            category: $category->value,
            approvalCount: count($approvals),
            highestLevel: $highestApproverLevel,
        );
    }

    /**
     * Validate disposal method appropriateness.
     *
     * @param string $proposedMethod Proposed disposal method
     * @param RetentionCategory $category Document category
     * @param bool $containsPII Whether document contains PII
     * @param bool $containsFinancialData Whether document contains financial data
     * @return DisposalAuthorizationRuleResult Validation result
     */
    public function validateDisposalMethod(
        string $proposedMethod,
        RetentionCategory $category,
        bool $containsPII = false,
        bool $containsFinancialData = false,
    ): DisposalAuthorizationRuleResult {
        $requiredMethod = $category->getDisposalMethod();
        $acceptableMethods = $this->getAcceptableMethods($requiredMethod, $containsPII, $containsFinancialData);

        if (!in_array($proposedMethod, $acceptableMethods, true)) {
            return DisposalAuthorizationRuleResult::fail(
                message: "Disposal method '{$proposedMethod}' not acceptable for category",
                reason: 'INVALID_DISPOSAL_METHOD',
                category: $category->value,
                proposedMethod: $proposedMethod,
                acceptableMethods: $acceptableMethods,
            );
        }

        // Warn if not using most secure method for sensitive data
        if (($containsPII || $containsFinancialData) && $proposedMethod !== 'secure_shred') {
            return DisposalAuthorizationRuleResult::warn(
                message: 'Consider using secure_shred for sensitive data disposal',
                category: $category->value,
                proposedMethod: $proposedMethod,
                recommendedMethod: 'secure_shred',
            );
        }

        return DisposalAuthorizationRuleResult::pass(
            message: 'Disposal method approved',
            category: $category->value,
            proposedMethod: $proposedMethod,
        );
    }

    /**
     * Validate complete disposal authorization.
     *
     * @param string $documentId Document identifier
     * @param RetentionPolicyData $retentionPolicy Retention policy
     * @param DisposalCertificationData $certification Disposal certification
     * @param \DateTimeImmutable $documentDate Original document date
     * @param array<LegalHoldData> $activeLegalHolds Active legal holds
     * @return DisposalAuthorizationRuleResult Comprehensive validation result
     */
    public function validateCompleteAuthorization(
        string $documentId,
        RetentionPolicyData $retentionPolicy,
        DisposalCertificationData $certification,
        \DateTimeImmutable $documentDate,
        array $activeLegalHolds = [],
    ): DisposalAuthorizationRuleResult {
        // Check eligibility
        $eligibilityResult = $this->validateDisposalEligibility(
            $documentId,
            $retentionPolicy,
            $documentDate,
            $activeLegalHolds,
        );

        if (!$eligibilityResult->passed) {
            return $eligibilityResult;
        }

        // Check approval chain
        $approvalResult = $this->validateApprovalChain(
            $certification,
            $retentionPolicy->category,
        );

        if (!$approvalResult->passed) {
            return $approvalResult;
        }

        // Check disposal method
        $methodResult = $this->validateDisposalMethod(
            $certification->disposalMethod,
            $retentionPolicy->category,
            containsPII: $certification->containedPII,
            containsFinancialData: in_array(
                $retentionPolicy->category,
                [RetentionCategory::SOX_FINANCIAL_DATA, RetentionCategory::TAX_DOCUMENTATION],
                true,
            ),
        );

        if (!$methodResult->passed && !$methodResult->isWarning) {
            return $methodResult;
        }

        // Verify legal hold confirmation
        if (!$certification->legalHoldVerified) {
            return DisposalAuthorizationRuleResult::fail(
                message: 'Legal hold verification required before disposal',
                reason: 'LEGAL_HOLD_NOT_VERIFIED',
                documentId: $documentId,
            );
        }

        // Verify chain of custody
        if (empty($certification->chainOfCustody)) {
            return DisposalAuthorizationRuleResult::warn(
                message: 'Chain of custody not documented',
                documentId: $documentId,
                requiredAction: 'DOCUMENT_CUSTODY',
            );
        }

        return DisposalAuthorizationRuleResult::pass(
            message: 'Complete disposal authorization granted',
            documentId: $documentId,
            category: $retentionPolicy->category->value,
            approvalCount: count($certification->approvalChain),
            disposalMethod: $certification->disposalMethod,
            hasWarnings: $methodResult->isWarning || $eligibilityResult->isWarning,
        );
    }

    /**
     * Get required approver level for category.
     */
    private function getRequiredApproverLevel(RetentionCategory $category): int
    {
        return match ($category) {
            RetentionCategory::SOX_FINANCIAL_DATA,
            RetentionCategory::LEGAL_LITIGATION_RECORDS => self::SENSITIVE_MIN_APPROVER_LEVEL,
            default => self::MIN_APPROVER_LEVEL,
        };
    }

    /**
     * Get acceptable disposal methods based on requirements.
     *
     * @return array<string>
     */
    private function getAcceptableMethods(
        string $baseMethod,
        bool $containsPII,
        bool $containsFinancialData,
    ): array {
        $secureMethods = ['secure_shred', 'secure_deletion', 'certified_destruction'];

        if ($containsPII || $containsFinancialData) {
            return $secureMethods;
        }

        return match ($baseMethod) {
            'secure_shred' => $secureMethods,
            'archive' => ['archive', 'secure_archive', ...$secureMethods],
            'standard' => ['standard', 'archive', ...$secureMethods],
            default => [$baseMethod, ...$secureMethods],
        };
    }
}

/**
 * Result object for disposal authorization rule validation.
 */
final readonly class DisposalAuthorizationRuleResult
{
    private function __construct(
        public bool $passed,
        public bool $isWarning,
        public string $message,
        public ?string $reason = null,
        public ?string $documentId = null,
        public ?string $category = null,
        public ?string $expirationDate = null,
        public ?int $remainingDays = null,
        public ?string $legalHoldId = null,
        public ?string $legalHoldMatter = null,
        public ?string $holdStartDate = null,
        public ?string $requiredAction = null,
        public ?int $requiredApprovals = null,
        public ?int $actualApprovals = null,
        public ?int $approvalCount = null,
        public ?int $requiredLevel = null,
        public ?int $actualLevel = null,
        public ?int $highestLevel = null,
        public ?string $proposedMethod = null,
        public ?string $recommendedMethod = null,
        public ?string $disposalMethod = null,
        public array $acceptableMethods = [],
        public bool $hasWarnings = false,
    ) {}

    public static function pass(
        string $message,
        ?string $documentId = null,
        ?string $category = null,
        ?int $approvalCount = null,
        ?int $highestLevel = null,
        ?string $proposedMethod = null,
        ?string $disposalMethod = null,
        bool $hasWarnings = false,
    ): self {
        return new self(
            passed: true,
            isWarning: false,
            message: $message,
            documentId: $documentId,
            category: $category,
            approvalCount: $approvalCount,
            highestLevel: $highestLevel,
            proposedMethod: $proposedMethod,
            disposalMethod: $disposalMethod,
            hasWarnings: $hasWarnings,
        );
    }

    public static function warn(
        string $message,
        ?string $documentId = null,
        ?string $category = null,
        ?string $requiredAction = null,
        ?string $proposedMethod = null,
        ?string $recommendedMethod = null,
    ): self {
        return new self(
            passed: true,
            isWarning: true,
            message: $message,
            documentId: $documentId,
            category: $category,
            requiredAction: $requiredAction,
            proposedMethod: $proposedMethod,
            recommendedMethod: $recommendedMethod,
        );
    }

    public static function fail(
        string $message,
        string $reason,
        ?string $documentId = null,
        ?string $category = null,
        ?string $expirationDate = null,
        ?int $remainingDays = null,
        ?string $legalHoldId = null,
        ?string $legalHoldMatter = null,
        ?string $holdStartDate = null,
        ?int $requiredApprovals = null,
        ?int $actualApprovals = null,
        ?int $requiredLevel = null,
        ?int $actualLevel = null,
        ?string $proposedMethod = null,
        array $acceptableMethods = [],
    ): self {
        return new self(
            passed: false,
            isWarning: false,
            message: $message,
            reason: $reason,
            documentId: $documentId,
            category: $category,
            expirationDate: $expirationDate,
            remainingDays: $remainingDays,
            legalHoldId: $legalHoldId,
            legalHoldMatter: $legalHoldMatter,
            holdStartDate: $holdStartDate,
            requiredApprovals: $requiredApprovals,
            actualApprovals: $actualApprovals,
            requiredLevel: $requiredLevel,
            actualLevel: $actualLevel,
            proposedMethod: $proposedMethod,
            acceptableMethods: $acceptableMethods,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'passed' => $this->passed,
            'is_warning' => $this->isWarning,
            'message' => $this->message,
            'reason' => $this->reason,
            'document_id' => $this->documentId,
            'category' => $this->category,
            'expiration_date' => $this->expirationDate,
            'remaining_days' => $this->remainingDays,
            'legal_hold_id' => $this->legalHoldId,
            'legal_hold_matter' => $this->legalHoldMatter,
            'hold_start_date' => $this->holdStartDate,
            'required_action' => $this->requiredAction,
            'required_approvals' => $this->requiredApprovals,
            'actual_approvals' => $this->actualApprovals,
            'approval_count' => $this->approvalCount,
            'required_level' => $this->requiredLevel,
            'actual_level' => $this->actualLevel,
            'highest_level' => $this->highestLevel,
            'proposed_method' => $this->proposedMethod,
            'recommended_method' => $this->recommendedMethod,
            'disposal_method' => $this->disposalMethod,
            'acceptable_methods' => $this->acceptableMethods,
            'has_warnings' => $this->hasWarnings,
        ], fn ($value) => $value !== null && $value !== [] && $value !== false);
    }
}
