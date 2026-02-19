<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Rules;

/**
 * Rule for data retention compliance.
 *
 * Validates that data retention periods comply with regulatory
 * requirements and determines if data can be disposed.
 *
 * Following Advanced Orchestrator Pattern:
 * - Single responsibility: Data retention validation
 * - Testable in isolation
 * - Reusable across coordinators
 *
 * @see ARCHITECTURE.md Section 3 for rule patterns
 */
final readonly class DataRetentionRule implements RuleInterface
{
    /**
     * Retention periods in years by data category.
     */
    public const RETENTION_KYC_RECORDS = 7;        // 7 years (AML requirements)
    public const RETENTION_TRANSACTION_RECORDS = 7; // 7 years (SOX/AML)
    public const RETENTION_AML_RECORDS = 7;        // 7 years (FATF)
    public const RETENTION_AUDIT_LOGS = 7;         // 7 years (SOX)
    public const RETENTION_CONSENT_RECORDS = 7;    // 7 years (GDPR)
    public const RETENTION_DSAR_RECORDS = 5;       // 5 years (GDPR)

    /**
     * Data category constants.
     */
    public const CATEGORY_KYC = 'kyc';
    public const CATEGORY_TRANSACTION = 'transaction';
    public const CATEGORY_AML = 'aml';
    public const CATEGORY_AUDIT = 'audit';
    public const CATEGORY_CONSENT = 'consent';
    public const CATEGORY_DSAR = 'dsar';
    public const CATEGORY_GENERAL = 'general';

    /**
     * Retention status constants.
     */
    public const STATUS_WITHIN_RETENTION = 'within_retention';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ELIGIBLE_FOR_DISPOSAL = 'eligible_for_disposal';
    public const STATUS_ON_LEGAL_HOLD = 'on_legal_hold';

    /**
     * Default retention period for unspecified categories.
     */
    private const DEFAULT_RETENTION_YEARS = 5;

    /**
     * Retention periods by category.
     */
    private const RETENTION_PERIODS = [
        self::CATEGORY_KYC => self::RETENTION_KYC_RECORDS,
        self::CATEGORY_TRANSACTION => self::RETENTION_TRANSACTION_RECORDS,
        self::CATEGORY_AML => self::RETENTION_AML_RECORDS,
        self::CATEGORY_AUDIT => self::RETENTION_AUDIT_LOGS,
        self::CATEGORY_CONSENT => self::RETENTION_CONSENT_RECORDS,
        self::CATEGORY_DSAR => self::RETENTION_DSAR_RECORDS,
    ];

    public function __construct(
        private ?int $customRetentionYears = null,
        private ?string $customCategory = null,
    ) {}

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'data_retention';
    }

    /**
     * @inheritDoc
     */
    public function check(object $context): RuleResult
    {
        $documentDate = $this->extractDocumentDate($context);
        $category = $this->extractCategory($context);
        $legalHolds = $this->extractLegalHolds($context);
        $asOfDate = $this->extractAsOfDate($context);

        // Get retention period for category
        $retentionYears = $this->getRetentionPeriod($category);

        // Calculate retention expiry
        $retentionExpiryDate = $documentDate->modify("+{$retentionYears} years");

        // Check if within retention period
        if ($asOfDate < $retentionExpiryDate) {
            $remainingDays = $this->calculateDaysBetween($asOfDate, $retentionExpiryDate);

            return RuleResult::pass(
                ruleName: $this->getName(),
                message: 'Document is within retention period',
                context: [
                    'status' => self::STATUS_WITHIN_RETENTION,
                    'category' => $category,
                    'document_date' => $documentDate->format(\DateTimeInterface::ATOM),
                    'retention_years' => $retentionYears,
                    'expiry_date' => $retentionExpiryDate->format(\DateTimeInterface::ATOM),
                    'remaining_days' => $remainingDays,
                ]
            );
        }

        // Check for legal holds
        if (!empty($legalHolds)) {
            $activeHolds = $this->filterActiveHolds($legalHolds, $asOfDate);

            if (!empty($activeHolds)) {
                return RuleResult::fail(
                    ruleName: $this->getName(),
                    message: 'Document is subject to active legal hold and cannot be disposed',
                    context: [
                        'status' => self::STATUS_ON_LEGAL_HOLD,
                        'category' => $category,
                        'document_date' => $documentDate->format(\DateTimeInterface::ATOM),
                        'retention_years' => $retentionYears,
                        'expiry_date' => $retentionExpiryDate->format(\DateTimeInterface::ATOM),
                        'active_holds' => count($activeHolds),
                        'hold_details' => $activeHolds,
                    ],
                    severity: 'error'
                );
            }
        }

        // Retention period expired and no legal holds
        $daysPastExpiry = $this->calculateDaysBetween($retentionExpiryDate, $asOfDate);

        return RuleResult::pass(
            ruleName: $this->getName(),
            message: 'Document retention period has expired and is eligible for disposal',
            context: [
                'status' => self::STATUS_ELIGIBLE_FOR_DISPOSAL,
                'category' => $category,
                'document_date' => $documentDate->format(\DateTimeInterface::ATOM),
                'retention_years' => $retentionYears,
                'expiry_date' => $retentionExpiryDate->format(\DateTimeInterface::ATOM),
                'days_past_expiry' => $daysPastExpiry,
                'can_dispose' => true,
            ]
        );
    }

    /**
     * Check if a document can be disposed.
     *
     * @param \DateTimeImmutable $documentDate Document creation date
     * @param string $category Data category
     * @param array<int, array<string, mixed>> $legalHolds Active legal holds
     * @param \DateTimeImmutable|null $asOfDate Date to check against
     * @return bool True if can be disposed
     */
    public function canDispose(
        \DateTimeImmutable $documentDate,
        string $category,
        array $legalHolds = [],
        ?\DateTimeImmutable $asOfDate = null
    ): bool {
        $asOfDate ??= new \DateTimeImmutable();

        // Check retention period
        $retentionYears = $this->getRetentionPeriod($category);
        $retentionExpiryDate = $documentDate->modify("+{$retentionYears} years");

        if ($asOfDate < $retentionExpiryDate) {
            return false;
        }

        // Check legal holds
        $activeHolds = $this->filterActiveHolds($legalHolds, $asOfDate);
        return empty($activeHolds);
    }

    /**
     * Get retention period for a category.
     *
     * @param string $category Data category
     * @return int Retention period in years
     */
    public function getRetentionPeriod(string $category): int
    {
        if ($this->customRetentionYears !== null && $this->customCategory === $category) {
            return $this->customRetentionYears;
        }

        return self::RETENTION_PERIODS[strtolower($category)] ?? self::DEFAULT_RETENTION_YEARS;
    }

    /**
     * Calculate retention expiry date.
     *
     * @param \DateTimeImmutable $documentDate Document creation date
     * @param string $category Data category
     * @return \DateTimeImmutable Expiry date
     */
    public function calculateExpiryDate(
        \DateTimeImmutable $documentDate,
        string $category
    ): \DateTimeImmutable {
        $retentionYears = $this->getRetentionPeriod($category);

        return $documentDate->modify("+{$retentionYears} years");
    }

    /**
     * Get documents eligible for disposal.
     *
     * @param string $category Data category
     * @param \DateTimeImmutable|null $asOfDate Date to check against
     * @return array<string> Document IDs eligible for disposal (placeholder)
     */
    public function getDocumentsEligibleForDisposal(
        string $category,
        ?\DateTimeImmutable $asOfDate = null
    ): array {
        // This would typically query a data provider
        return [];
    }

    /**
     * Validate retention policy compliance.
     *
     * @param string $category Data category
     * @param int $policyYears Policy retention years
     * @return array<string, mixed> Validation result
     */
    public function validatePolicyCompliance(string $category, int $policyYears): array
    {
        $requiredYears = $this->getRetentionPeriod($category);
        $compliant = $policyYears >= $requiredYears;

        return [
            'category' => $category,
            'policy_years' => $policyYears,
            'required_years' => $requiredYears,
            'compliant' => $compliant,
            'message' => $compliant
                ? 'Policy meets retention requirements'
                : "Policy ({$policyYears} years) is below required retention ({$requiredYears} years)",
        ];
    }

    /**
     * Get regulatory basis for retention requirement.
     *
     * @param string $category Data category
     * @return array<string> Regulatory basis
     */
    public function getRegulatoryBasis(string $category): array
    {
        return match (strtolower($category)) {
            self::CATEGORY_KYC => [
                'FATF Recommendation 11',
                'EU AML Directive',
                'Bank Secrecy Act',
            ],
            self::CATEGORY_TRANSACTION => [
                'SOX Section 802',
                'Bank Secrecy Act',
                'FATF Recommendations',
            ],
            self::CATEGORY_AML => [
                'FATF Recommendation 11',
                'EU AML Directive',
                'Wolfsberg Group Guidance',
            ],
            self::CATEGORY_AUDIT => [
                'SOX Section 802',
                'PCAOB Standards',
            ],
            self::CATEGORY_CONSENT => [
                'GDPR Article 7(1)',
                'GDPR Article 5(1)(e)',
            ],
            self::CATEGORY_DSAR => [
                'GDPR Article 5(1)(e)',
                'GDPR Article 17',
            ],
            default => [
                'General data protection requirements',
            ],
        };
    }

    /**
     * Filter active legal holds.
     *
     * @param array<int, array<string, mixed>> $legalHolds Legal holds
     * @param \DateTimeImmutable $asOfDate Date to check
     * @return array<int, array<string, mixed>> Active holds
     */
    private function filterActiveHolds(array $legalHolds, \DateTimeImmutable $asOfDate): array
    {
        return array_filter($legalHolds, function (array $hold) use ($asOfDate): bool {
            $isActive = $hold['active'] ?? $hold['is_active'] ?? false;
            $endDate = $hold['end_date'] ?? $hold['endDate'] ?? null;

            if (!$isActive) {
                return false;
            }

            if ($endDate !== null) {
                $endDate = $endDate instanceof \DateTimeImmutable
                    ? $endDate
                    : new \DateTimeImmutable($endDate);

                return $asOfDate <= $endDate;
            }

            return true;
        });
    }

    /**
     * Calculate days between two dates.
     */
    private function calculateDaysBetween(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): int {
        $diff = $startDate->diff($endDate);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Extract document date from context.
     */
    private function extractDocumentDate(object $context): \DateTimeImmutable
    {
        if (method_exists($context, 'getDocumentDate')) {
            return $context->getDocumentDate();
        }

        if (property_exists($context, 'documentDate')) {
            return $context->documentDate;
        }

        if (property_exists($context, 'document_date')) {
            return $context->document_date;
        }

        if (property_exists($context, 'createdAt')) {
            return $context->createdAt;
        }

        // Default to current date
        return new \DateTimeImmutable();
    }

    /**
     * Extract category from context.
     */
    private function extractCategory(object $context): string
    {
        if (method_exists($context, 'getCategory')) {
            return $context->getCategory();
        }

        if (property_exists($context, 'category')) {
            return $context->category;
        }

        if (property_exists($context, 'dataCategory')) {
            return $context->dataCategory;
        }

        return self::CATEGORY_GENERAL;
    }

    /**
     * Extract legal holds from context.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractLegalHolds(object $context): array
    {
        if (method_exists($context, 'getLegalHolds')) {
            return $context->getLegalHolds();
        }

        if (property_exists($context, 'legalHolds')) {
            return $context->legalHolds;
        }

        if (property_exists($context, 'legal_holds')) {
            return $context->legal_holds;
        }

        return [];
    }

    /**
     * Extract as-of date from context.
     */
    private function extractAsOfDate(object $context): \DateTimeImmutable
    {
        if (method_exists($context, 'getAsOfDate')) {
            return $context->getAsOfDate();
        }

        if (property_exists($context, 'asOfDate')) {
            return $context->asOfDate;
        }

        if (property_exists($context, 'as_of_date')) {
            return $context->as_of_date;
        }

        return new \DateTimeImmutable();
    }
}
