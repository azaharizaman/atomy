<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Rules;

/**
 * Rule for periodic review frequency based on risk level.
 *
 * Validates that review frequency is appropriate for the party's
 * risk level and determines when the next review should occur.
 *
 * Following Advanced Orchestrator Pattern:
 * - Single responsibility: Review frequency validation
 * - Testable in isolation
 * - Reusable across coordinators
 *
 * @see ARCHITECTURE.md Section 3 for rule patterns
 */
final readonly class ReviewFrequencyRule implements RuleInterface
{
    /**
     * Review frequency in months by risk level.
     */
    public const FREQUENCY_HIGH_RISK = 6;    // 6 months
    public const FREQUENCY_MEDIUM_RISK = 12;  // 12 months
    public const FREQUENCY_LOW_RISK = 24;     // 24 months

    /**
     * Risk level constants.
     */
    public const LEVEL_HIGH = 'high';
    public const LEVEL_MEDIUM = 'medium';
    public const LEVEL_LOW = 'low';

    /**
     * Review status constants.
     */
    public const STATUS_CURRENT = 'current';
    public const STATUS_DUE_SOON = 'due_soon';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_NEVER_REVIEWED = 'never_reviewed';

    /**
     * Days before due date to trigger "due soon" status.
     */
    private const DUE_SOON_THRESHOLD_DAYS = 30;

    public function __construct(
        private ?int $customHighRiskFrequency = null,
        private ?int $customMediumRiskFrequency = null,
        private ?int $customLowRiskFrequency = null,
        private int $dueSoonThresholdDays = self::DUE_SOON_THRESHOLD_DAYS,
    ) {}

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'review_frequency';
    }

    /**
     * @inheritDoc
     */
    public function check(object $context): RuleResult
    {
        $riskLevel = $this->extractRiskLevel($context);
        $lastReviewDate = $this->extractLastReviewDate($context);
        $nextReviewDate = $this->extractNextReviewDate($context);
        $asOfDate = $this->extractAsOfDate($context);

        // Calculate expected next review date
        $expectedFrequency = $this->getReviewFrequency($riskLevel);

        // Never reviewed
        if ($lastReviewDate === null) {
            return RuleResult::fail(
                ruleName: $this->getName(),
                message: 'Party has never been reviewed',
                context: [
                    'status' => self::STATUS_NEVER_REVIEWED,
                    'risk_level' => $riskLevel,
                    'required_frequency_months' => $expectedFrequency,
                ],
                severity: 'error'
            );
        }

        // Calculate review status
        $reviewStatus = $this->determineReviewStatus(
            lastReviewDate: $lastReviewDate,
            nextReviewDate: $nextReviewDate,
            riskLevel: $riskLevel,
            asOfDate: $asOfDate
        );

        // Check if overdue
        if ($reviewStatus === self::STATUS_OVERDUE) {
            $daysOverdue = $this->calculateDaysOverdue($lastReviewDate, $riskLevel, $asOfDate);

            return RuleResult::fail(
                ruleName: $this->getName(),
                message: "Periodic review is overdue by {$daysOverdue} days",
                context: [
                    'status' => self::STATUS_OVERDUE,
                    'risk_level' => $riskLevel,
                    'last_review_date' => $lastReviewDate->format(\DateTimeInterface::ATOM),
                    'days_overdue' => $daysOverdue,
                    'required_frequency_months' => $expectedFrequency,
                ],
                severity: 'error'
            );
        }

        // Check if due soon
        if ($reviewStatus === self::STATUS_DUE_SOON) {
            $daysUntilDue = $this->calculateDaysUntilDue($lastReviewDate, $riskLevel, $asOfDate);

            return RuleResult::warn(
                ruleName: $this->getName(),
                message: "Periodic review is due in {$daysUntilDue} days",
                context: [
                    'status' => self::STATUS_DUE_SOON,
                    'risk_level' => $riskLevel,
                    'last_review_date' => $lastReviewDate->format(\DateTimeInterface::ATOM),
                    'days_until_due' => $daysUntilDue,
                    'required_frequency_months' => $expectedFrequency,
                ]
            );
        }

        // Review is current
        $daysUntilDue = $this->calculateDaysUntilDue($lastReviewDate, $riskLevel, $asOfDate);

        return RuleResult::pass(
            ruleName: $this->getName(),
            message: 'Periodic review is current',
            context: [
                'status' => self::STATUS_CURRENT,
                'risk_level' => $riskLevel,
                'last_review_date' => $lastReviewDate->format(\DateTimeInterface::ATOM),
                'days_until_due' => $daysUntilDue,
                'required_frequency_months' => $expectedFrequency,
            ]
        );
    }

    /**
     * Get review frequency in months for a risk level.
     *
     * @param string $riskLevel Risk level
     * @return int Frequency in months
     */
    public function getReviewFrequency(string $riskLevel): int
    {
        return match (strtolower($riskLevel)) {
            self::LEVEL_HIGH => $this->customHighRiskFrequency ?? self::FREQUENCY_HIGH_RISK,
            self::LEVEL_MEDIUM => $this->customMediumRiskFrequency ?? self::FREQUENCY_MEDIUM_RISK,
            default => $this->customLowRiskFrequency ?? self::FREQUENCY_LOW_RISK,
        };
    }

    /**
     * Calculate the next review date from a given date.
     *
     * @param \DateTimeImmutable $lastReviewDate Last review date
     * @param string $riskLevel Risk level
     * @return \DateTimeImmutable Next review date
     */
    public function calculateNextReviewDate(
        \DateTimeImmutable $lastReviewDate,
        string $riskLevel
    ): \DateTimeImmutable {
        $frequencyMonths = $this->getReviewFrequency($riskLevel);

        return $lastReviewDate->modify("+{$frequencyMonths} months");
    }

    /**
     * Determine if a review is required.
     *
     * @param \DateTimeImmutable|null $lastReviewDate Last review date
     * @param string $riskLevel Risk level
     * @param \DateTimeImmutable|null $asOfDate Date to check against
     * @return bool True if review is required
     */
    public function isReviewRequired(
        ?\DateTimeImmutable $lastReviewDate,
        string $riskLevel,
        ?\DateTimeImmutable $asOfDate = null
    ): bool {
        if ($lastReviewDate === null) {
            return true;
        }

        $asOfDate ??= new \DateTimeImmutable();
        $nextReviewDate = $this->calculateNextReviewDate($lastReviewDate, $riskLevel);

        return $asOfDate >= $nextReviewDate;
    }

    /**
     * Determine review status.
     *
     * @param \DateTimeImmutable $lastReviewDate Last review date
     * @param \DateTimeImmutable|null $nextReviewDate Next review date (if set)
     * @param string $riskLevel Risk level
     * @param \DateTimeImmutable|null $asOfDate Date to check against
     * @return string Review status
     */
    public function determineReviewStatus(
        \DateTimeImmutable $lastReviewDate,
        ?\DateTimeImmutable $nextReviewDate,
        string $riskLevel,
        ?\DateTimeImmutable $asOfDate = null
    ): string {
        $asOfDate ??= new \DateTimeImmutable();

        // Use provided next review date or calculate it
        $nextReview = $nextReviewDate ?? $this->calculateNextReviewDate($lastReviewDate, $riskLevel);

        // Check if overdue
        if ($asOfDate >= $nextReview) {
            return self::STATUS_OVERDUE;
        }

        // Check if due soon
        $dueSoonThreshold = $nextReview->modify("-{$this->dueSoonThresholdDays} days");
        if ($asOfDate >= $dueSoonThreshold) {
            return self::STATUS_DUE_SOON;
        }

        return self::STATUS_CURRENT;
    }

    /**
     * Get parties due for review within a date range.
     *
     * @param string $riskLevel Risk level to filter by
     * @param \DateTimeImmutable $fromDate Range start
     * @param \DateTimeImmutable $toDate Range end
     * @return array<string> Party IDs due for review (placeholder)
     */
    public function getPartiesDueForReview(
        string $riskLevel,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate
    ): array {
        // This would typically query a data provider
        return [];
    }

    /**
     * Calculate days until review is due.
     */
    private function calculateDaysUntilDue(
        \DateTimeImmutable $lastReviewDate,
        string $riskLevel,
        \DateTimeImmutable $asOfDate
    ): int {
        $nextReviewDate = $this->calculateNextReviewDate($lastReviewDate, $riskLevel);
        $diff = $asOfDate->diff($nextReviewDate);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Calculate days overdue.
     */
    private function calculateDaysOverdue(
        \DateTimeImmutable $lastReviewDate,
        string $riskLevel,
        \DateTimeImmutable $asOfDate
    ): int {
        $nextReviewDate = $this->calculateNextReviewDate($lastReviewDate, $riskLevel);
        $diff = $nextReviewDate->diff($asOfDate);

        return $diff->days;
    }

    /**
     * Extract risk level from context.
     */
    private function extractRiskLevel(object $context): string
    {
        if (method_exists($context, 'getRiskLevel')) {
            return $context->getRiskLevel();
        }

        if (property_exists($context, 'riskLevel')) {
            return $context->riskLevel;
        }

        if (property_exists($context, 'risk_level')) {
            return $context->risk_level;
        }

        // Default to medium
        return self::LEVEL_MEDIUM;
    }

    /**
     * Extract last review date from context.
     */
    private function extractLastReviewDate(object $context): ?\DateTimeImmutable
    {
        if (method_exists($context, 'getLastReviewDate')) {
            return $context->getLastReviewDate();
        }

        if (property_exists($context, 'lastReviewDate')) {
            return $context->lastReviewDate;
        }

        if (property_exists($context, 'last_review_date')) {
            return $context->last_review_date;
        }

        return null;
    }

    /**
     * Extract next review date from context.
     */
    private function extractNextReviewDate(object $context): ?\DateTimeImmutable
    {
        if (method_exists($context, 'getNextReviewDate')) {
            return $context->getNextReviewDate();
        }

        if (property_exists($context, 'nextReviewDate')) {
            return $context->nextReviewDate;
        }

        if (property_exists($context, 'next_review_date')) {
            return $context->next_review_date;
        }

        return null;
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
