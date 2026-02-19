<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Rules;

/**
 * Rule for KYC verification thresholds.
 *
 * Validates that KYC verification meets required thresholds
 * based on party type, risk level, and transaction amount.
 *
 * Following Advanced Orchestrator Pattern:
 * - Single responsibility: KYC threshold validation
 * - Testable in isolation
 * - Reusable across coordinators
 *
 * @see ARCHITECTURE.md Section 3 for rule patterns
 */
final readonly class KycThresholdRule implements RuleInterface
{
    /**
     * Minimum verification scores by due diligence level.
     */
    private const MIN_SCORE_SIMPLIFIED = 50;
    private const MIN_SCORE_STANDARD = 70;
    private const MIN_SCORE_ENHANCED = 85;

    /**
     * Transaction thresholds requiring verification (in cents).
     */
    private const TRANSACTION_THRESHOLD_LOW = 1_000_00;    // $1,000
    private const TRANSACTION_THRESHOLD_MEDIUM = 10_000_00; // $10,000
    private const TRANSACTION_THRESHOLD_HIGH = 50_000_00;   // $50,000

    public function __construct(
        private ?int $customMinScore = null,
        private ?int $customTransactionThreshold = null,
    ) {}

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'kyc_threshold';
    }

    /**
     * @inheritDoc
     */
    public function check(object $context): RuleResult
    {
        $verificationScore = $this->extractVerificationScore($context);
        $dueDiligenceLevel = $this->extractDueDiligenceLevel($context);
        $transactionAmount = $this->extractTransactionAmount($context);
        $isVerified = $this->extractIsVerified($context);

        // Check if verification is complete
        if (!$isVerified) {
            return RuleResult::fail(
                ruleName: $this->getName(),
                message: 'KYC verification is not complete',
                context: [
                    'verification_score' => $verificationScore,
                    'required_level' => $dueDiligenceLevel,
                ],
                severity: 'error'
            );
        }

        // Check minimum score threshold
        $minScore = $this->getMinimumScore($dueDiligenceLevel);
        if ($verificationScore < $minScore) {
            return RuleResult::fail(
                ruleName: $this->getName(),
                message: "KYC verification score ({$verificationScore}) is below required threshold ({$minScore})",
                context: [
                    'verification_score' => $verificationScore,
                    'required_score' => $minScore,
                    'due_diligence_level' => $dueDiligenceLevel,
                ],
                severity: 'error'
            );
        }

        // Check transaction threshold requirements
        $thresholdResult = $this->checkTransactionThreshold($transactionAmount, $dueDiligenceLevel);
        if ($thresholdResult !== null) {
            return $thresholdResult;
        }

        // Check for score near threshold (warning)
        if ($verificationScore < $minScore + 10) {
            return RuleResult::warn(
                ruleName: $this->getName(),
                message: 'KYC verification score is near the minimum threshold',
                context: [
                    'verification_score' => $verificationScore,
                    'required_score' => $minScore,
                    'margin' => $verificationScore - $minScore,
                ]
            );
        }

        return RuleResult::pass(
            ruleName: $this->getName(),
            message: 'KYC verification meets threshold requirements',
            context: [
                'verification_score' => $verificationScore,
                'due_diligence_level' => $dueDiligenceLevel,
            ]
        );
    }

    /**
     * Check if verification score meets threshold for a specific level.
     *
     * @param int $score Verification score
     * @param string $level Due diligence level
     * @return bool True if score meets threshold
     */
    public function meetsThreshold(int $score, string $level): bool
    {
        return $score >= $this->getMinimumScore($level);
    }

    /**
     * Get the minimum required score for a due diligence level.
     *
     * @param string $level Due diligence level
     * @return int Minimum required score
     */
    public function getMinimumScore(string $level): int
    {
        if ($this->customMinScore !== null) {
            return $this->customMinScore;
        }

        return match (strtolower($level)) {
            'simplified' => self::MIN_SCORE_SIMPLIFIED,
            'enhanced', 'edd' => self::MIN_SCORE_ENHANCED,
            default => self::MIN_SCORE_STANDARD,
        };
    }

    /**
     * Get required due diligence level for a transaction amount.
     *
     * @param int $amountCents Transaction amount in cents
     * @return string Required due diligence level
     */
    public function getRequiredLevelForAmount(int $amountCents): string
    {
        $threshold = $this->customTransactionThreshold ?? self::TRANSACTION_THRESHOLD_HIGH;

        if ($amountCents >= $threshold) {
            return 'enhanced';
        }

        if ($amountCents >= self::TRANSACTION_THRESHOLD_MEDIUM) {
            return 'standard';
        }

        return 'simplified';
    }

    /**
     * Extract verification score from context.
     */
    private function extractVerificationScore(object $context): int
    {
        if (method_exists($context, 'getVerificationScore')) {
            return $context->getVerificationScore();
        }

        if (property_exists($context, 'verificationScore')) {
            return $context->verificationScore;
        }

        if (property_exists($context, 'verification_score')) {
            return $context->verification_score;
        }

        // Default to 0 if not found
        return 0;
    }

    /**
     * Extract due diligence level from context.
     */
    private function extractDueDiligenceLevel(object $context): string
    {
        if (method_exists($context, 'getDueDiligenceLevel')) {
            return $context->getDueDiligenceLevel();
        }

        if (property_exists($context, 'dueDiligenceLevel')) {
            return $context->dueDiligenceLevel;
        }

        if (property_exists($context, 'due_diligence_level')) {
            return $context->due_diligence_level;
        }

        // Default to standard
        return 'standard';
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
     * Extract verification status from context.
     */
    private function extractIsVerified(object $context): bool
    {
        if (method_exists($context, 'isVerified')) {
            return $context->isVerified();
        }

        if (property_exists($context, 'isVerified')) {
            return $context->isVerified;
        }

        if (property_exists($context, 'is_verified')) {
            return $context->is_verified;
        }

        if (property_exists($context, 'verified')) {
            return $context->verified;
        }

        // Default to true if not specified
        return true;
    }

    /**
     * Check transaction threshold requirements.
     */
    private function checkTransactionThreshold(int $amount, string $level): ?RuleResult
    {
        if ($amount <= 0) {
            return null;
        }

        $requiredLevel = $this->getRequiredLevelForAmount($amount);
        $levelHierarchy = ['simplified' => 1, 'standard' => 2, 'enhanced' => 3];

        $currentLevelRank = $levelHierarchy[strtolower($level)] ?? 2;
        $requiredLevelRank = $levelHierarchy[$requiredLevel] ?? 2;

        if ($currentLevelRank < $requiredLevelRank) {
            return RuleResult::fail(
                ruleName: $this->getName(),
                message: "Transaction amount requires {$requiredLevel} due diligence, but current level is {$level}",
                context: [
                    'transaction_amount' => $amount,
                    'current_level' => $level,
                    'required_level' => $requiredLevel,
                ],
                severity: 'error'
            );
        }

        return null;
    }
}
