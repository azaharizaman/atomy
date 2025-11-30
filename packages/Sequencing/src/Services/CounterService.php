<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Services;

use Nexus\Sequencing\Contracts\CounterRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceInterface;
use Nexus\Sequencing\Exceptions\InvalidCounterValueException;

/**
 * Service for counter state management and validation.
 */
final readonly class CounterService
{
    public function __construct(
        private CounterRepositoryInterface $counterRepository,
    ) {}

    /**
     * Check if counter needs reset based on reset period and limit.
     */
    public function needsReset(SequenceInterface $sequence): bool
    {
        // Check count-based reset limit
        if ($sequence->getResetLimit() !== null) {
            $generationCount = $this->counterRepository->getGenerationCount($sequence);
            if ($generationCount >= $sequence->getResetLimit()) {
                return true;
            }
        }

        // Check time-based reset period
        return $this->counterRepository->needsReset($sequence);
    }

    /**
     * Validate a manual counter override value.
     *
     * @throws InvalidCounterValueException
     */
    public function validateOverrideValue(SequenceInterface $sequence, int $newValue): void
    {
        if ($newValue <= 0) {
            throw InvalidCounterValueException::mustBePositive();
        }

        $currentValue = $this->counterRepository->getCurrentValue($sequence);
        if ($newValue <= $currentValue) {
            throw InvalidCounterValueException::mustBeGreaterThanCurrent($newValue, $currentValue);
        }
    }

    /**
     * Calculate remaining count until next reset.
     */
    public function getRemainingUntilReset(SequenceInterface $sequence): ?int
    {
        $resetLimit = $sequence->getResetLimit();
        if ($resetLimit === null) {
            return null;
        }

        $generationCount = $this->counterRepository->getGenerationCount($sequence);
        return max(0, $resetLimit - $generationCount);
    }
}
