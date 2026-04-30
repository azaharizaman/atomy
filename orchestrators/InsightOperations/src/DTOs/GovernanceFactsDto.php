<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class GovernanceFactsDto
{
    /**
     * @param array<int, array<string, mixed>> $evidence
     * @param array<int, array<string, mixed>> $findings
     * @param array<string, mixed> $scores
     * @param list<string> $warningFlags
     * @param array<int, array<string, mixed>> $sanctionsScreenings
     * @param array<string, mixed> $evidenceFreshness
     */
    public function __construct(
        public string $vendorId,
        public array $evidence,
        public array $findings,
        public array $scores,
        public array $warningFlags,
        public array $sanctionsScreenings,
        public string $dueDiligenceStatus,
        public array $evidenceFreshness,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'vendor_id' => $this->vendorId,
            'evidence' => $this->evidence,
            'findings' => $this->findings,
            'summary_scores' => $this->scores,
            'scores' => $this->scores,
            'warning_flags' => $this->warningFlags,
            'sanctions_screenings' => $this->sanctionsScreenings,
            'due_diligence_status' => $this->dueDiligenceStatus,
            'evidence_freshness' => $this->evidenceFreshness,
        ];
    }
}
