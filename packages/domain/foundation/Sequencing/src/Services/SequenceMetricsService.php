<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Services;

use Nexus\Sequencing\Contracts\CounterRepositoryInterface;
use Nexus\Sequencing\Contracts\GapRepositoryInterface;
use Nexus\Sequencing\Contracts\ReservationRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;
use Nexus\Sequencing\ValueObjects\SequenceMetrics;

/**
 * Service for collecting sequence metrics and statistics.
 */
final readonly class SequenceMetricsService
{
    public function __construct(
        private SequenceRepositoryInterface $sequenceRepository,
        private CounterRepositoryInterface $counterRepository,
        private ReservationRepositoryInterface $reservationRepository,
        private GapRepositoryInterface $gapRepository,
        private ExhaustionMonitor $exhaustionMonitor,
    ) {}

    /**
     * Get comprehensive metrics for a sequence.
     */
    public function getMetrics(string $sequenceName, ?string $scopeIdentifier = null): SequenceMetrics
    {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);

        $currentValue = $this->counterRepository->getCurrentValue($sequence);
        $generationCount = $this->counterRepository->getGenerationCount($sequence);
        $maxValue = $this->exhaustionMonitor->calculateMaxValue($sequence->getPattern());

        $utilizationPercentage = $maxValue > 0 ? ($currentValue / $maxValue) * 100 : 0;
        $exhaustionRiskScore = $this->exhaustionMonitor->getRiskScore($sequence, $maxValue);

        $activeReservations = count($this->reservationRepository->getActiveReservations($sequence));
        $gapCount = count($this->gapRepository->getGaps($sequence));

        return new SequenceMetrics(
            sequenceName: $sequence->getName(),
            scopeIdentifier: $sequence->getScopeIdentifier(),
            currentValue: $currentValue,
            generationCount: $generationCount,
            totalCapacity: $maxValue,
            utilizationPercentage: $utilizationPercentage,
            exhaustionRiskScore: $exhaustionRiskScore,
            avgLockWaitTimeMs: 0.0, // Would be calculated from actual performance metrics
            activeReservations: $activeReservations,
            gapCount: $gapCount,
            lastGeneratedAt: null, // Would come from audit logs or counter metadata
            lastResetAt: $this->counterRepository->getLastResetAt($sequence),
        );
    }
}
