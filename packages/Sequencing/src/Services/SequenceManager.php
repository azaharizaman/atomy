<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Services;

use Nexus\Sequencing\Contracts\CounterRepositoryInterface;
use Nexus\Sequencing\Contracts\GapRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceAuditInterface;
use Nexus\Sequencing\Contracts\SequenceInterface;
use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;
use Nexus\Sequencing\Exceptions\SequenceLockedException;
use Nexus\Sequencing\ValueObjects\GapPolicy;

/**
 * Main service for sequence number generation and management.
 *
 * This is the primary public API for the Sequencing package.
 */
final readonly class SequenceManager
{
    public function __construct(
        private SequenceRepositoryInterface $sequenceRepository,
        private CounterRepositoryInterface $counterRepository,
        private GapRepositoryInterface $gapRepository,
        private PatternParser $patternParser,
        private CounterService $counterService,
        private SequenceAuditInterface $auditLogger,
    ) {}

    /**
     * Generate the next unique number for a sequence.
     *
     * @param string $sequenceName
     * @param string|null $scopeIdentifier
     * @param array<string, string|int> $contextVariables
     * @return string The generated number
     * @throws SequenceLockedException
     * @throws \Nexus\Sequencing\Exceptions\SequenceNotFoundException
     * @throws \Nexus\Sequencing\Exceptions\SequenceExhaustedException
     */
    public function generate(
        string $sequenceName,
        ?string $scopeIdentifier = null,
        array $contextVariables = []
    ): string {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);

        if ($sequence->isLocked()) {
            throw SequenceLockedException::cannotGenerate($sequenceName);
        }

        // Check if we should fill a gap first
        $gapPolicy = GapPolicy::fromString($sequence->getGapPolicy());
        if ($gapPolicy->allowsFilling()) {
            $gapNumber = $this->gapRepository->getNextGap($sequence);
            if ($gapNumber !== null) {
                $this->gapRepository->markGapFilled($sequence, $gapNumber);
                $this->auditLogger->logGapReclaimed($sequence, $gapNumber);
                return $gapNumber;
            }
        }

        // Check if counter needs reset
        if ($this->counterRepository->needsReset($sequence)) {
            $oldValue = $this->counterRepository->getCurrentValue($sequence);
            $this->counterRepository->reset($sequence);
            $this->auditLogger->logCounterReset($sequence, $oldValue, 0, 'Period reset');
        }

        // Atomic generation with database lock
        $currentValue = $this->counterRepository->getCurrentValueWithLock($sequence);
        $newValue = $this->counterRepository->increment($sequence, $sequence->getStepSize());

        // Parse pattern and generate number
        $generatedNumber = $this->patternParser->parse(
            pattern: $sequence->getPattern(),
            counterValue: $newValue,
            contextVariables: $contextVariables
        );

        $this->auditLogger->logNumberGenerated($sequence, $generatedNumber, $contextVariables);

        return $generatedNumber;
    }

    /**
     * Preview the next number without consuming the counter.
     *
     * @param string $sequenceName
     * @param string|null $scopeIdentifier
     * @param array<string, string|int> $contextVariables
     * @return string The preview number
     */
    public function preview(
        string $sequenceName,
        ?string $scopeIdentifier = null,
        array $contextVariables = []
    ): string {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);

        $currentValue = $this->counterRepository->getCurrentValue($sequence);
        $previewValue = $currentValue + $sequence->getStepSize();

        return $this->patternParser->parse(
            pattern: $sequence->getPattern(),
            counterValue: $previewValue,
            contextVariables: $contextVariables
        );
    }

    /**
     * Manually override the counter value.
     *
     * @throws \Nexus\Sequencing\Exceptions\InvalidCounterValueException
     */
    public function overrideCounter(
        string $sequenceName,
        int $newValue,
        ?string $scopeIdentifier = null,
        ?string $performedBy = null
    ): void {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);

        $oldValue = $this->counterRepository->getCurrentValue($sequence);
        $this->counterRepository->setCounterValue($sequence, $newValue);

        $this->auditLogger->logCounterOverridden($sequence, $oldValue, $newValue, $performedBy);
    }

    /**
     * Lock a sequence to prevent generation.
     */
    public function lock(string $sequenceName, ?string $scopeIdentifier = null, ?string $performedBy = null): void
    {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);
        $this->sequenceRepository->lock($sequence);
        $this->auditLogger->logLockStatusChanged($sequence, true, $performedBy);
    }

    /**
     * Unlock a sequence to allow generation.
     */
    public function unlock(string $sequenceName, ?string $scopeIdentifier = null, ?string $performedBy = null): void
    {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);
        $this->sequenceRepository->unlock($sequence);
        $this->auditLogger->logLockStatusChanged($sequence, false, $performedBy);
    }

    /**
     * Compose a child sequence number from a parent sequence.
     *
     * Example: Parent "INV-001" + child pattern "-{COUNTER:3}" = "INV-001-001"
     */
    public function composeChild(
        string $parentNumber,
        string $childPattern,
        int $childCounter = 1
    ): string {
        return $parentNumber . $this->patternParser->parse(
            pattern: $childPattern,
            counterValue: $childCounter,
            contextVariables: []
        );
    }
}
