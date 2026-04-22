<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\VendorRecommendation;

use Nexus\ProcurementOperations\Exceptions\InvalidVendorRecommendation;

final readonly class VendorRecommendationScoredCandidate
{
    /**
     * @param list<string> $deterministicReasons
     * @param list<string> $llmInsights
     * @param list<string> $warningFlags
     * @param list<string> $warnings
     */
    public function __construct(
        public string $vendorId,
        public string $vendorName,
        public int $fitScore,
        public string $confidenceBand,
        public string $recommendedReasonSummary,
        public array $deterministicReasons,
        public array $llmInsights = [],
        public array $warningFlags = [],
        public array $warnings = [],
    ) {
        if ($fitScore < 0 || $fitScore > 100) {
            throw new InvalidVendorRecommendation(sprintf(
                'fitScore %d is outside the supported 0-100 range.',
                $fitScore,
            ));
        }

        $expectedConfidenceBand = self::confidenceBandFor($fitScore);
        if ($confidenceBand !== $expectedConfidenceBand) {
            throw new InvalidVendorRecommendation(sprintf(
                'confidenceBand "%s" does not match fitScore %d; expected "%s".',
                $confidenceBand,
                $fitScore,
                $expectedConfidenceBand,
            ));
        }
    }

    /**
     * @param list<string> $llmInsights
     */
    public function withLlmEnrichment(int $fitScore, string $reasonSummary, array $llmInsights): self
    {
        return new self(
            vendorId: $this->vendorId,
            vendorName: $this->vendorName,
            fitScore: $fitScore,
            confidenceBand: self::confidenceBandFor($fitScore),
            recommendedReasonSummary: $reasonSummary,
            deterministicReasons: $this->deterministicReasons,
            llmInsights: $llmInsights,
            warningFlags: $this->warningFlags,
            warnings: $this->warnings,
        );
    }

    public static function confidenceBandFor(int $fitScore): string
    {
        return match (true) {
            $fitScore >= 80 => 'high',
            $fitScore >= 55 => 'medium',
            default => 'low',
        };
    }
}
