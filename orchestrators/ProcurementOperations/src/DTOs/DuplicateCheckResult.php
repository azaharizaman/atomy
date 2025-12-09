<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\ProcurementOperations\ValueObjects\DuplicateMatch;

/**
 * Result of duplicate invoice detection check.
 */
final readonly class DuplicateCheckResult
{
    /**
     * @param bool $hasDuplicates Whether potential duplicates were found
     * @param bool $shouldBlock Whether processing should be blocked
     * @param array<DuplicateMatch> $matches List of potential duplicate matches
     * @param string $highestRiskLevel Highest risk level among matches
     * @param float $highestConfidence Highest confidence score among matches
     * @param string $recommendation Overall recommendation
     * @param string $requestFingerprint Fingerprint of the checked invoice
     * @param \DateTimeImmutable $checkedAt When the check was performed
     */
    public function __construct(
        public bool $hasDuplicates,
        public bool $shouldBlock,
        public array $matches,
        public string $highestRiskLevel,
        public float $highestConfidence,
        public string $recommendation,
        public string $requestFingerprint,
        public \DateTimeImmutable $checkedAt,
    ) {}

    /**
     * Create a result indicating no duplicates found.
     */
    public static function noDuplicates(string $fingerprint): self
    {
        return new self(
            hasDuplicates: false,
            shouldBlock: false,
            matches: [],
            highestRiskLevel: 'none',
            highestConfidence: 0.0,
            recommendation: 'PROCEED: No duplicate invoices detected.',
            requestFingerprint: $fingerprint,
            checkedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a result from detected matches.
     *
     * @param array<DuplicateMatch> $matches
     */
    public static function fromMatches(array $matches, string $fingerprint, bool $strictMode = false): self
    {
        if (empty($matches)) {
            return self::noDuplicates($fingerprint);
        }

        // Determine highest risk and confidence
        $riskOrder = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1, 'none' => 0];
        $highestRisk = 'none';
        $highestConfidence = 0.0;
        $shouldBlock = false;

        foreach ($matches as $match) {
            $matchRisk = $match->getRiskLevel();
            if ($riskOrder[$matchRisk] > $riskOrder[$highestRisk]) {
                $highestRisk = $matchRisk;
            }
            if ($match->confidenceScore > $highestConfidence) {
                $highestConfidence = $match->confidenceScore;
            }
            if ($match->shouldBlock()) {
                $shouldBlock = true;
            }
        }

        // In strict mode, any match blocks processing
        if ($strictMode && !empty($matches)) {
            $shouldBlock = true;
        }

        // Generate recommendation
        $recommendation = self::generateRecommendation($matches, $shouldBlock, $highestRisk);

        return new self(
            hasDuplicates: true,
            shouldBlock: $shouldBlock,
            matches: $matches,
            highestRiskLevel: $highestRisk,
            highestConfidence: $highestConfidence,
            recommendation: $recommendation,
            requestFingerprint: $fingerprint,
            checkedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get count of matches by risk level.
     *
     * @return array<string, int>
     */
    public function getMatchCountsByRisk(): array
    {
        $counts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];

        foreach ($this->matches as $match) {
            $risk = $match->getRiskLevel();
            if (isset($counts[$risk])) {
                $counts[$risk]++;
            }
        }

        return $counts;
    }

    /**
     * Get blocking matches only.
     *
     * @return array<DuplicateMatch>
     */
    public function getBlockingMatches(): array
    {
        return array_filter(
            $this->matches,
            fn(DuplicateMatch $match) => $match->shouldBlock()
        );
    }

    /**
     * Get warning matches (non-blocking).
     *
     * @return array<DuplicateMatch>
     */
    public function getWarningMatches(): array
    {
        return array_filter(
            $this->matches,
            fn(DuplicateMatch $match) => !$match->shouldBlock()
        );
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'has_duplicates' => $this->hasDuplicates,
            'should_block' => $this->shouldBlock,
            'match_count' => count($this->matches),
            'highest_risk_level' => $this->highestRiskLevel,
            'highest_confidence' => $this->highestConfidence,
            'recommendation' => $this->recommendation,
            'request_fingerprint' => $this->requestFingerprint,
            'checked_at' => $this->checkedAt->format(\DateTimeInterface::ATOM),
            'matches_by_risk' => $this->getMatchCountsByRisk(),
            'matches' => array_map(fn(DuplicateMatch $m) => $m->toArray(), $this->matches),
        ];
    }

    /**
     * Generate recommendation based on matches.
     *
     * @param array<DuplicateMatch> $matches
     */
    private static function generateRecommendation(array $matches, bool $shouldBlock, string $highestRisk): string
    {
        $count = count($matches);

        if ($shouldBlock) {
            return sprintf(
                'BLOCK: %d potential duplicate(s) detected with %s risk. Review required before processing.',
                $count,
                $highestRisk
            );
        }

        return sprintf(
            'WARNING: %d potential duplicate(s) detected with %s risk. Proceed with caution.',
            $count,
            $highestRisk
        );
    }
}
