<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Services;

use Nexus\Sequencing\Contracts\CounterRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceAuditInterface;
use Nexus\Sequencing\Contracts\SequenceInterface;
use Nexus\Sequencing\Exceptions\SequenceExhaustedException;
use Nexus\Sequencing\ValueObjects\OverflowBehavior;

/**
 * Service for monitoring sequence exhaustion and triggering alerts.
 */
final readonly class ExhaustionMonitor
{
    public function __construct(
        private CounterRepositoryInterface $counterRepository,
        private SequenceAuditInterface $auditLogger,
        private PatternMigrationService $migrationService,
    ) {}

    /**
     * Check if a sequence is approaching exhaustion.
     *
     * @throws SequenceExhaustedException
     */
    public function checkExhaustion(SequenceInterface $sequence, int $maxValue): void
    {
        $currentValue = $this->counterRepository->getCurrentValue($sequence);
        $threshold = $sequence->getExhaustionThreshold();

        $utilizationPercentage = ($currentValue / $maxValue) * 100;

        if ($utilizationPercentage >= $threshold) {
            $this->auditLogger->logExhaustionThresholdReached($sequence, $currentValue, $threshold);

            // Handle overflow based on configured behavior
            $behavior = OverflowBehavior::fromString($sequence->getOverflowBehavior());

            match ($behavior) {
                OverflowBehavior::THROW_EXCEPTION => throw SequenceExhaustedException::counterExhausted(
                    $sequence->getName(),
                    $currentValue,
                    $maxValue
                ),
                OverflowBehavior::SWITCH_PATTERN => $this->migrationService->migrate($sequence),
                OverflowBehavior::EXTEND_PADDING => $this->migrationService->migrate($sequence, 'extend_padding'),
            };
        }
    }

    /**
     * Calculate the maximum value for a counter based on pattern padding.
     */
    public function calculateMaxValue(string $pattern): int
    {
        if (preg_match('/\{COUNTER:(\d+)\}/', $pattern, $matches)) {
            $padding = (int) $matches[1];
            return (int) str_repeat('9', $padding);
        }

        // Default to large value if no padding specified
        return PHP_INT_MAX;
    }

    /**
     * Get exhaustion risk score (0-100).
     */
    public function getRiskScore(SequenceInterface $sequence, int $maxValue): float
    {
        $currentValue = $this->counterRepository->getCurrentValue($sequence);
        return min(100, ($currentValue / $maxValue) * 100);
    }
}
