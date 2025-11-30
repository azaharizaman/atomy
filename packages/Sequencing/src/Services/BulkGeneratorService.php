<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Services;

use Nexus\Sequencing\Contracts\CounterRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;

/**
 * Service for generating multiple numbers atomically.
 */
final readonly class BulkGeneratorService
{
    public function __construct(
        private SequenceRepositoryInterface $sequenceRepository,
        private CounterRepositoryInterface $counterRepository,
        private PatternParser $patternParser,
    ) {}

    /**
     * Generate multiple numbers atomically with optimized locking.
     *
     * @param string $sequenceName
     * @param int $count Number of sequences to generate
     * @param string|null $scopeIdentifier
     * @param array<string, string|int> $contextVariables
     * @return string[] Array of generated numbers
     */
    public function generateBulk(
        string $sequenceName,
        int $count,
        ?string $scopeIdentifier = null,
        array $contextVariables = []
    ): array {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);

        // Acquire lock once
        $startValue = $this->counterRepository->getCurrentValueWithLock($sequence);
        $stepSize = $sequence->getStepSize();

        $numbers = [];
        for ($i = 0; $i < $count; $i++) {
            $counterValue = $startValue + ($stepSize * ($i + 1));
            $numbers[] = $this->patternParser->parse(
                pattern: $sequence->getPattern(),
                counterValue: $counterValue,
                contextVariables: $contextVariables
            );
        }

        // Increment counter by (count * stepSize)
        $this->counterRepository->increment($sequence, $stepSize * $count);

        return $numbers;
    }
}
