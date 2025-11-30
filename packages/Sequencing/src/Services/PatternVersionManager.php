<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Services;

use Nexus\Sequencing\Contracts\PatternVersionRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceInterface;

/**
 * Service for managing pattern versions with effective dates.
 */
final readonly class PatternVersionManager
{
    public function __construct(
        private PatternVersionRepositoryInterface $versionRepository,
        private PatternParser $patternParser,
    ) {}

    /**
     * Get the active pattern for a sequence at a given date.
     */
    public function getActivePattern(SequenceInterface $sequence, ?\DateTimeInterface $date = null): string
    {
        $date ??= new \DateTimeImmutable();
        return $this->versionRepository->getActivePattern($sequence, $date);
    }

    /**
     * Create a new pattern version with effective date.
     *
     * @throws \Nexus\Sequencing\Exceptions\InvalidPatternException
     * @throws \Nexus\Sequencing\Exceptions\PatternVersionConflictException
     */
    public function createVersion(
        SequenceInterface $sequence,
        string $newPattern,
        \DateTimeInterface $effectiveFrom,
        ?\DateTimeInterface $effectiveUntil = null
    ): void {
        // Validate pattern syntax
        $this->patternParser->validateSyntax($newPattern);

        // Validate effective date doesn't conflict
        $this->versionRepository->validateEffectiveDate($sequence, $effectiveFrom);

        // Create version
        $this->versionRepository->createVersion($sequence, $newPattern, $effectiveFrom, $effectiveUntil);
    }

    /**
     * Get all pattern versions for a sequence.
     *
     * @return array<string, mixed>[]
     */
    public function getAllVersions(SequenceInterface $sequence): array
    {
        return $this->versionRepository->getAllVersions($sequence);
    }
}
