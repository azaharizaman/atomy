<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Contracts;

/**
 * Contract for period close sequence management.
 */
interface CloseSequenceInterface
{
    /**
     * Get the close sequence steps.
     *
     * @return array<string>
     */
    public function getSteps(): array;

    /**
     * Get the current step in the sequence.
     *
     * @param string $periodId
     * @return string|null
     */
    public function getCurrentStep(string $periodId): ?string;

    /**
     * Advance to the next step.
     *
     * @param string $periodId
     * @return string|null Next step or null if complete
     */
    public function advanceStep(string $periodId): ?string;
}
