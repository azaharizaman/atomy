<?php

declare(strict_types=1);

namespace Nexus\Sequencing\ValueObjects;

/**
 * Value object representing sequence metrics.
 */
final readonly class SequenceMetrics
{
    public function __construct(
        public string $sequenceName,
        public ?string $scopeIdentifier,
        public int $currentValue,
        public int $generationCount,
        public int $totalCapacity,
        public float $utilizationPercentage,
        public float $exhaustionRiskScore,
        public float $avgLockWaitTimeMs,
        public int $activeReservations,
        public int $gapCount,
        public ?\DateTimeInterface $lastGeneratedAt,
        public ?\DateTimeInterface $lastResetAt,
    ) {}

    /**
     * Check if the sequence is approaching exhaustion.
     */
    public function isApproachingExhaustion(int $threshold = 90): bool
    {
        return $this->utilizationPercentage >= $threshold;
    }

    /**
     * Check if lock wait time is concerning.
     */
    public function hasHighLockWaitTime(float $thresholdMs = 100.0): bool
    {
        return $this->avgLockWaitTimeMs >= $thresholdMs;
    }

    /**
     * Get remaining capacity.
     */
    public function getRemainingCapacity(): int
    {
        return max(0, $this->totalCapacity - $this->currentValue);
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sequence_name' => $this->sequenceName,
            'scope_identifier' => $this->scopeIdentifier,
            'current_value' => $this->currentValue,
            'generation_count' => $this->generationCount,
            'total_capacity' => $this->totalCapacity,
            'remaining_capacity' => $this->getRemainingCapacity(),
            'utilization_percentage' => $this->utilizationPercentage,
            'exhaustion_risk_score' => $this->exhaustionRiskScore,
            'avg_lock_wait_time_ms' => $this->avgLockWaitTimeMs,
            'active_reservations' => $this->activeReservations,
            'gap_count' => $this->gapCount,
            'last_generated_at' => $this->lastGeneratedAt?->format('Y-m-d H:i:s'),
            'last_reset_at' => $this->lastResetAt?->format('Y-m-d H:i:s'),
        ];
    }
}
