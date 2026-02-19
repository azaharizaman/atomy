<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Rules;

/**
 * Rule for sanctions screening matches.
 *
 * Validates sanctions screening results and determines
 * appropriate actions based on match types and scores.
 *
 * Following Advanced Orchestrator Pattern:
 * - Single responsibility: Sanctions match validation
 * - Testable in isolation
 * - Reusable across coordinators
 *
 * @see ARCHITECTURE.md Section 3 for rule patterns
 */
final readonly class SanctionsMatchRule implements RuleInterface
{
    /**
     * Match type constants.
     */
    public const MATCH_TYPE_EXACT = 'exact';
    public const MATCH_TYPE_STRONG = 'strong';
    public const MATCH_TYPE_PARTIAL = 'partial';
    public const MATCH_TYPE_WEAK = 'weak';

    /**
     * Match status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FALSE_POSITIVE = 'false_positive';
    public const STATUS_CLEARED = 'cleared';

    /**
     * Similarity thresholds.
     */
    private const SIMILARITY_EXACT = 1.0;
    private const SIMILARITY_STRONG = 0.85;
    private const SIMILARITY_PARTIAL = 0.70;
    private const SIMILARITY_WEAK = 0.50;

    /**
     * Sanctions list categories by severity.
     */
    private const HIGH_SEVERITY_LISTS = [
        'ofac_sdna',
        'un_consolidated',
        'eu_consolidated',
        'hmt_sanctions',
    ];

    private const MEDIUM_SEVERITY_LISTS = [
        'ofac_ssi',
        'bureau_of_industry',
        'dfat_australia',
    ];

    public function __construct(
        private float $minimumSimilarityThreshold = self::SIMILARITY_WEAK,
        private bool $blockOnExactMatch = true,
        private bool $blockOnStrongMatch = true,
    ) {}

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'sanctions_match';
    }

    /**
     * @inheritDoc
     */
    public function check(object $context): RuleResult
    {
        $hasMatches = $this->extractHasMatches($context);
        $matches = $this->extractMatches($context);
        $isPep = $this->extractIsPep($context);
        $pepDetails = $this->extractPepDetails($context);

        // Check for PEP status first
        if ($isPep) {
            $pepResult = $this->checkPepStatus($pepDetails);
            if ($pepResult !== null) {
                return $pepResult;
            }
        }

        // No matches found
        if (!$hasMatches || empty($matches)) {
            return RuleResult::pass(
                ruleName: $this->getName(),
                message: 'No sanctions matches found',
                context: [
                    'has_matches' => false,
                    'is_pep' => $isPep,
                ]
            );
        }

        // Process matches
        $highestSeverity = 'none';
        $unconfirmedMatches = [];
        $confirmedMatches = [];

        foreach ($matches as $match) {
            $matchResult = $this->processMatch($match);

            if ($matchResult['severity'] > $highestSeverity) {
                $highestSeverity = $matchResult['severity'];
            }

            if ($matchResult['status'] === self::STATUS_CONFIRMED) {
                $confirmedMatches[] = $match;
            } elseif ($matchResult['status'] === self::STATUS_PENDING) {
                $unconfirmedMatches[] = $match;
            }
        }

        // Block on confirmed matches
        if (!empty($confirmedMatches)) {
            return RuleResult::fail(
                ruleName: $this->getName(),
                message: 'Party has confirmed sanctions matches',
                context: [
                    'has_matches' => true,
                    'confirmed_matches' => count($confirmedMatches),
                    'highest_severity' => $highestSeverity,
                    'matches' => $confirmedMatches,
                ],
                severity: 'error'
            );
        }

        // Block on exact/strong matches if configured
        if ($this->blockOnExactMatch || $this->blockOnStrongMatch) {
            foreach ($unconfirmedMatches as $match) {
                $matchType = $this->determineMatchType($match['similarity'] ?? 0);

                if ($this->blockOnExactMatch && $matchType === self::MATCH_TYPE_EXACT) {
                    return RuleResult::fail(
                        ruleName: $this->getName(),
                        message: 'Exact sanctions match requires review before proceeding',
                        context: [
                            'match_type' => $matchType,
                            'match' => $match,
                        ],
                        severity: 'error'
                    );
                }

                if ($this->blockOnStrongMatch && $matchType === self::MATCH_TYPE_STRONG) {
                    return RuleResult::fail(
                        ruleName: $this->getName(),
                        message: 'Strong sanctions match requires review before proceeding',
                        context: [
                            'match_type' => $matchType,
                            'match' => $match,
                        ],
                        severity: 'error'
                    );
                }
            }
        }

        // Pending matches require review
        if (!empty($unconfirmedMatches)) {
            return RuleResult::warn(
                ruleName: $this->getName(),
                message: 'Party has pending sanctions matches requiring review',
                context: [
                    'has_matches' => true,
                    'pending_matches' => count($unconfirmedMatches),
                    'highest_severity' => $highestSeverity,
                    'recommendations' => $this->getMatchReviewRecommendations($unconfirmedMatches),
                ]
            );
        }

        return RuleResult::pass(
            ruleName: $this->getName(),
            message: 'Sanctions screening passed',
            context: [
                'has_matches' => false,
                'is_pep' => $isPep,
            ]
        );
    }

    /**
     * Determine match type from similarity score.
     *
     * @param float $similarity Similarity score (0.0-1.0)
     * @return string Match type
     */
    public function determineMatchType(float $similarity): string
    {
        if ($similarity >= self::SIMILARITY_EXACT) {
            return self::MATCH_TYPE_EXACT;
        }

        if ($similarity >= self::SIMILARITY_STRONG) {
            return self::MATCH_TYPE_STRONG;
        }

        if ($similarity >= self::SIMILARITY_PARTIAL) {
            return self::MATCH_TYPE_PARTIAL;
        }

        if ($similarity >= self::SIMILARITY_WEAK) {
            return self::MATCH_TYPE_WEAK;
        }

        return 'none';
    }

    /**
     * Check if a match should be blocked.
     *
     * @param array<string, mixed> $match Match data
     * @return bool True if should block
     */
    public function shouldBlock(array $match): bool
    {
        $matchType = $this->determineMatchType($match['similarity'] ?? 0);
        $status = $match['status'] ?? self::STATUS_PENDING;

        // Block on confirmed matches
        if ($status === self::STATUS_CONFIRMED) {
            return true;
        }

        // Block based on configuration
        if ($this->blockOnExactMatch && $matchType === self::MATCH_TYPE_EXACT) {
            return true;
        }

        if ($this->blockOnStrongMatch && $matchType === self::MATCH_TYPE_STRONG) {
            return true;
        }

        return false;
    }

    /**
     * Get severity level for a sanctions list.
     *
     * @param string $listId Sanctions list identifier
     * @return string Severity level (high, medium, low)
     */
    public function getListSeverity(string $listId): string
    {
        if (in_array(strtolower($listId), self::HIGH_SEVERITY_LISTS, true)) {
            return 'high';
        }

        if (in_array(strtolower($listId), self::MEDIUM_SEVERITY_LISTS, true)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get recommendations for match review.
     *
     * @param array<int, array<string, mixed>> $matches Matches to review
     * @return array<string> Recommendations
     */
    public function getMatchReviewRecommendations(array $matches): array
    {
        $recommendations = [
            'Review all pending matches for accuracy',
            'Verify party identity with additional documentation',
        ];

        $hasHighSeverity = false;
        foreach ($matches as $match) {
            $listId = $match['list_id'] ?? $match['listId'] ?? '';
            if ($this->getListSeverity($listId) === 'high') {
                $hasHighSeverity = true;
                break;
            }
        }

        if ($hasHighSeverity) {
            $recommendations[] = 'Escalate to compliance officer due to high-severity list match';
            $recommendations[] = 'Consider filing Suspicious Activity Report if warranted';
        }

        return $recommendations;
    }

    /**
     * Process a single match.
     *
     * @param array<string, mixed> $match Match data
     * @return array<string, mixed> Processed match info
     */
    private function processMatch(array $match): array
    {
        $similarity = $match['similarity'] ?? $match['score'] ?? 0;
        $status = $match['status'] ?? self::STATUS_PENDING;
        $listId = $match['list_id'] ?? $match['listId'] ?? '';

        return [
            'type' => $this->determineMatchType((float) $similarity),
            'status' => $status,
            'severity' => $this->getListSeverity($listId),
            'list_id' => $listId,
        ];
    }

    /**
     * Check PEP status.
     *
     * @param array<string, mixed> $pepDetails PEP details
     */
    private function checkPepStatus(array $pepDetails): ?RuleResult
    {
        if (empty($pepDetails)) {
            return RuleResult::warn(
                ruleName: $this->getName(),
                message: 'Party is a Politically Exposed Person (PEP)',
                context: [
                    'is_pep' => true,
                    'recommendations' => [
                        'Apply enhanced due diligence procedures',
                        'Obtain senior management approval',
                        'Establish source of wealth and funds',
                        'Implement ongoing enhanced monitoring',
                    ],
                ]
            );
        }

        // Check if PEP status has been reviewed
        $reviewed = $pepDetails['reviewed'] ?? false;
        if (!$reviewed) {
            return RuleResult::warn(
                ruleName: $this->getName(),
                message: 'PEP status requires review',
                context: [
                    'is_pep' => true,
                    'reviewed' => false,
                    'pep_details' => $pepDetails,
                ]
            );
        }

        return null;
    }

    /**
     * Extract has matches from context.
     */
    private function extractHasMatches(object $context): bool
    {
        if (method_exists($context, 'hasMatches')) {
            return $context->hasMatches();
        }

        if (property_exists($context, 'hasMatches')) {
            return $context->hasMatches;
        }

        if (property_exists($context, 'has_matches')) {
            return $context->has_matches;
        }

        return false;
    }

    /**
     * Extract matches from context.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractMatches(object $context): array
    {
        if (method_exists($context, 'getMatches')) {
            return $context->getMatches();
        }

        if (property_exists($context, 'matches')) {
            return $context->matches;
        }

        if (property_exists($context, 'sanctionsMatches')) {
            return $context->sanctionsMatches;
        }

        return [];
    }

    /**
     * Extract is PEP from context.
     */
    private function extractIsPep(object $context): bool
    {
        if (method_exists($context, 'isPep')) {
            return $context->isPep();
        }

        if (property_exists($context, 'isPep')) {
            return $context->isPep;
        }

        if (property_exists($context, 'is_pep')) {
            return $context->is_pep;
        }

        return false;
    }

    /**
     * Extract PEP details from context.
     *
     * @return array<string, mixed>
     */
    private function extractPepDetails(object $context): array
    {
        if (method_exists($context, 'getPepDetails')) {
            return $context->getPepDetails();
        }

        if (property_exists($context, 'pepDetails')) {
            return $context->pepDetails;
        }

        if (property_exists($context, 'pep_details')) {
            return $context->pep_details;
        }

        return [];
    }
}
