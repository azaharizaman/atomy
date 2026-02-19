<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Rules;

/**
 * Rule for AML risk score thresholds.
 *
 * Validates that AML risk scores are within acceptable thresholds
 * and determines if enhanced due diligence is required.
 *
 * Following Advanced Orchestrator Pattern:
 * - Single responsibility: AML risk threshold validation
 * - Testable in isolation
 * - Reusable across coordinators
 *
 * @see ARCHITECTURE.md Section 3 for rule patterns
 */
final readonly class AmlRiskThresholdRule implements RuleInterface
{
    /**
     * Risk score thresholds.
     */
    public const THRESHOLD_LOW = 25;
    public const THRESHOLD_MEDIUM = 50;
    public const THRESHOLD_HIGH = 75;

    /**
     * Risk level constants.
     */
    public const LEVEL_LOW = 'low';
    public const LEVEL_MEDIUM = 'medium';
    public const LEVEL_HIGH = 'high';

    /**
     * Score ranges for each risk level.
     */
    private const SCORE_RANGES = [
        self::LEVEL_LOW => [0, self::THRESHOLD_LOW],
        self::LEVEL_MEDIUM => [self::THRESHOLD_LOW + 1, self::THRESHOLD_MEDIUM],
        self::LEVEL_HIGH => [self::THRESHOLD_MEDIUM + 1, 100],
    ];

    public function __construct(
        private ?int $customHighThreshold = null,
        private ?int $customMediumThreshold = null,
        private ?int $maxAllowedScore = null,
    ) {}

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'aml_risk_threshold';
    }

    /**
     * @inheritDoc
     */
    public function check(object $context): RuleResult
    {
        $riskScore = $this->extractRiskScore($context);
        $riskLevel = $this->extractRiskLevel($context);
        $requiresEdd = $this->extractRequiresEdd($context);
        $transactionAmount = $this->extractTransactionAmount($context);

        // Check if score exceeds maximum allowed
        if ($this->maxAllowedScore !== null && $riskScore > $this->maxAllowedScore) {
            return RuleResult::fail(
                ruleName: $this->getName(),
                message: "AML risk score ({$riskScore}) exceeds maximum allowed ({$this->maxAllowedScore})",
                context: [
                    'risk_score' => $riskScore,
                    'max_allowed' => $this->maxAllowedScore,
                ],
                severity: 'error'
            );
        }

        // Determine risk level from score
        $calculatedLevel = $this->determineRiskLevel($riskScore);

        // Verify consistency between score and level
        if (strtolower($riskLevel) !== $calculatedLevel) {
            return RuleResult::warn(
                ruleName: $this->getName(),
                message: "Risk level ({$riskLevel}) does not match calculated level ({$calculatedLevel}) for score {$riskScore}",
                context: [
                    'risk_score' => $riskScore,
                    'stated_level' => $riskLevel,
                    'calculated_level' => $calculatedLevel,
                ]
            );
        }

        // Check if high risk requires EDD
        if ($calculatedLevel === self::LEVEL_HIGH && !$requiresEdd) {
            return RuleResult::fail(
                ruleName: $this->getName(),
                message: 'High risk parties require Enhanced Due Diligence',
                context: [
                    'risk_score' => $riskScore,
                    'risk_level' => $calculatedLevel,
                    'requires_edd' => $requiresEdd,
                ],
                severity: 'error'
            );
        }

        // Check transaction amount against risk level
        $transactionCheck = $this->checkTransactionRisk($transactionAmount, $calculatedLevel, $riskScore);
        if ($transactionCheck !== null) {
            return $transactionCheck;
        }

        // High risk warning
        if ($calculatedLevel === self::LEVEL_HIGH) {
            return RuleResult::warn(
                ruleName: $this->getName(),
                message: 'Party is classified as high AML risk',
                context: [
                    'risk_score' => $riskScore,
                    'risk_level' => $calculatedLevel,
                    'requires_edd' => $requiresEdd,
                    'recommendations' => $this->getRecommendations($calculatedLevel),
                ]
            );
        }

        // Medium risk info
        if ($calculatedLevel === self::LEVEL_MEDIUM) {
            return RuleResult::pass(
                ruleName: $this->getName(),
                message: 'Party is classified as medium AML risk',
                context: [
                    'risk_score' => $riskScore,
                    'risk_level' => $calculatedLevel,
                    'recommendations' => $this->getRecommendations($calculatedLevel),
                ]
            );
        }

        return RuleResult::pass(
            ruleName: $this->getName(),
            message: 'AML risk score is within acceptable thresholds',
            context: [
                'risk_score' => $riskScore,
                'risk_level' => $calculatedLevel,
            ]
        );
    }

    /**
     * Determine risk level from score.
     *
     * @param int $score Risk score (0-100)
     * @return string Risk level
     */
    public function determineRiskLevel(int $score): string
    {
        $highThreshold = $this->customHighThreshold ?? self::THRESHOLD_MEDIUM;
        $mediumThreshold = $this->customMediumThreshold ?? self::THRESHOLD_LOW;

        if ($score > $highThreshold) {
            return self::LEVEL_HIGH;
        }

        if ($score > $mediumThreshold) {
            return self::LEVEL_MEDIUM;
        }

        return self::LEVEL_LOW;
    }

    /**
     * Check if score is within acceptable range.
     *
     * @param int $score Risk score
     * @return bool True if acceptable
     */
    public function isAcceptable(int $score): bool
    {
        if ($this->maxAllowedScore !== null) {
            return $score <= $this->maxAllowedScore;
        }

        return true;
    }

    /**
     * Get the high risk threshold.
     *
     * @return int High risk threshold
     */
    public function getHighThreshold(): int
    {
        return $this->customHighThreshold ?? self::THRESHOLD_MEDIUM;
    }

    /**
     * Get the medium risk threshold.
     *
     * @return int Medium risk threshold
     */
    public function getMediumThreshold(): int
    {
        return $this->customMediumThreshold ?? self::THRESHOLD_LOW;
    }

    /**
     * Get recommendations for a risk level.
     *
     * @param string $level Risk level
     * @return array<string> Recommendations
     */
    public function getRecommendations(string $level): array
    {
        return match ($level) {
            self::LEVEL_HIGH => [
                'Perform Enhanced Due Diligence (EDD)',
                'Obtain senior management approval',
                'Implement ongoing transaction monitoring',
                'Conduct more frequent periodic reviews',
                'Document source of funds',
            ],
            self::LEVEL_MEDIUM => [
                'Consider Enhanced Due Diligence',
                'Implement transaction monitoring',
                'Schedule regular periodic reviews',
            ],
            default => [
                'Standard monitoring procedures apply',
            ],
        };
    }

    /**
     * Extract risk score from context.
     */
    private function extractRiskScore(object $context): int
    {
        if (method_exists($context, 'getRiskScore')) {
            return $context->getRiskScore();
        }

        if (property_exists($context, 'riskScore')) {
            return $context->riskScore;
        }

        if (property_exists($context, 'risk_score')) {
            return $context->risk_score;
        }

        if (property_exists($context, 'amlRiskScore')) {
            return $context->amlRiskScore;
        }

        // Default to medium risk
        return 50;
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

        if (property_exists($context, 'amlRiskLevel')) {
            return $context->amlRiskLevel;
        }

        // Calculate from score
        return $this->determineRiskLevel($this->extractRiskScore($context));
    }

    /**
     * Extract EDD requirement from context.
     */
    private function extractRequiresEdd(object $context): bool
    {
        if (method_exists($context, 'requiresEdd')) {
            return $context->requiresEdd();
        }

        if (method_exists($context, 'getRequiresEdd')) {
            return $context->getRequiresEdd();
        }

        if (property_exists($context, 'requiresEdd')) {
            return $context->requiresEdd;
        }

        if (property_exists($context, 'requires_edd')) {
            return $context->requires_edd;
        }

        // Default to false
        return false;
    }

    /**
     * Extract transaction amount from context.
     */
    private function extractTransactionAmount(object $context): int
    {
        if (method_exists($context, 'getTransactionAmount')) {
            return $context->getTransactionAmount();
        }

        if (property_exists($context, 'transactionAmount')) {
            return $context->transactionAmount;
        }

        if (property_exists($context, 'transaction_amount')) {
            return $context->transaction_amount;
        }

        if (property_exists($context, 'amountCents')) {
            return $context->amountCents;
        }

        // Default to 0
        return 0;
    }

    /**
     * Check transaction risk against level.
     */
    private function checkTransactionRisk(int $amount, string $level, int $score): ?RuleResult
    {
        if ($amount <= 0) {
            return null;
        }

        // High value transaction thresholds
        $highValueThreshold = 50_000_00; // $50,000

        if ($amount >= $highValueThreshold && $level === self::LEVEL_HIGH) {
            return RuleResult::fail(
                ruleName: $this->getName(),
                message: 'High value transaction with high-risk party requires manual approval',
                context: [
                    'transaction_amount' => $amount,
                    'risk_level' => $level,
                    'risk_score' => $score,
                ],
                severity: 'error'
            );
        }

        return null;
    }
}
