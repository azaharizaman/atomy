<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Contracts;

/**
 * Repository interface for pattern version management.
 *
 * Pattern versions allow sequences to use different patterns over time
 * with effective date ranges.
 */
interface PatternVersionRepositoryInterface
{
    /**
     * Get the active pattern version for a sequence at a given date.
     *
     * @throws \Nexus\Sequencing\Exceptions\NoActivePatternException
     */
    public function getActivePattern(SequenceInterface $sequence, \DateTimeInterface $date): string;

    /**
     * Create a new pattern version.
     *
     * @param string $pattern
     * @param \DateTimeInterface $effectiveFrom
     * @param \DateTimeInterface|null $effectiveUntil
     * @throws \Nexus\Sequencing\Exceptions\PatternVersionConflictException
     */
    public function createVersion(SequenceInterface $sequence, string $pattern, \DateTimeInterface $effectiveFrom, ?\DateTimeInterface $effectiveUntil = null): void;

    /**
     * Get all pattern versions for a sequence.
     *
     * @return array<string, mixed>[]
     */
    public function getAllVersions(SequenceInterface $sequence): array;

    /**
     * Validate that a new effective date doesn't conflict with existing versions.
     *
     * @throws \Nexus\Sequencing\Exceptions\PatternVersionConflictException
     */
    public function validateEffectiveDate(SequenceInterface $sequence, \DateTimeInterface $effectiveFrom): void;
}
