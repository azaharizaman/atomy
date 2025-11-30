<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Services;

use Nexus\Sequencing\Contracts\GapRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;

/**
 * Service for managing gaps in sequences.
 */
final readonly class GapManager
{
    public function __construct(
        private SequenceRepositoryInterface $sequenceRepository,
        private GapRepositoryInterface $gapRepository,
    ) {}

    /**
     * Record a gap in the sequence (e.g., from a voided/cancelled number).
     */
    public function reclaimGap(
        string $sequenceName,
        string $number,
        ?string $scopeIdentifier = null,
        ?string $reason = null
    ): void {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);
        $this->gapRepository->recordGap($sequence, $number, $reason);
    }

    /**
     * Get a report of all unfilled gaps.
     *
     * @return string[]
     */
    public function getGapReport(string $sequenceName, ?string $scopeIdentifier = null): array
    {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);
        return $this->gapRepository->getGapReport($sequence);
    }

    /**
     * Get all gaps with details.
     *
     * @return array<string, mixed>[]
     */
    public function getGapDetails(string $sequenceName, ?string $scopeIdentifier = null): array
    {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);
        return $this->gapRepository->getGaps($sequence);
    }

    /**
     * Clear all gaps for a sequence.
     */
    public function clearGaps(string $sequenceName, ?string $scopeIdentifier = null): void
    {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);
        $this->gapRepository->clearGaps($sequence);
    }
}
