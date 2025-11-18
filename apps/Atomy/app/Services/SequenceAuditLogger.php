<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Sequence;
use App\Models\SequenceAudit;
use Nexus\Sequencing\Contracts\SequenceAuditInterface;
use Nexus\Sequencing\Contracts\SequenceInterface;

/**
 * Audit logger implementation using database persistence.
 */
final readonly class SequenceAuditLogger implements SequenceAuditInterface
{
    public function logPatternCreated(SequenceInterface $sequence, array $metadata = []): void
    {
        $this->createAuditRecord($sequence, 'pattern_created', [
            'pattern' => $sequence->getPattern(),
            'metadata' => $metadata,
        ]);
    }

    public function logPatternModified(SequenceInterface $sequence, array $changes): void
    {
        $this->createAuditRecord($sequence, 'pattern_modified', [
            'changes' => $changes,
        ]);
    }

    public function logCounterReset(SequenceInterface $sequence, int $oldValue, int $newValue, string $reason): void
    {
        $this->createAuditRecord($sequence, 'counter_reset', [
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
        ]);
    }

    public function logCounterOverridden(SequenceInterface $sequence, int $oldValue, int $newValue, ?string $performedBy = null): void
    {
        $this->createAuditRecord($sequence, 'counter_overridden', [
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ], $performedBy);
    }

    public function logExhaustionThresholdReached(SequenceInterface $sequence, int $currentValue, int $threshold): void
    {
        $this->createAuditRecord($sequence, 'exhaustion_threshold_reached', [
            'current_value' => $currentValue,
            'threshold_percentage' => $threshold,
        ]);
    }

    public function logPatternVersionCreated(SequenceInterface $sequence, string $oldPattern, string $newPattern, \DateTimeInterface $effectiveFrom): void
    {
        $this->createAuditRecord($sequence, 'pattern_version_created', [
            'old_pattern' => $oldPattern,
            'new_pattern' => $newPattern,
            'effective_from' => $effectiveFrom->format('Y-m-d H:i:s'),
        ]);
    }

    public function logNumberGenerated(SequenceInterface $sequence, string $generatedNumber, array $context = []): void
    {
        $this->createAuditRecord($sequence, 'number_generated', [
            'generated_number' => $generatedNumber,
            'context' => $context,
        ]);
    }

    public function logGapReclaimed(SequenceInterface $sequence, string $number): void
    {
        $this->createAuditRecord($sequence, 'gap_reclaimed', [
            'number' => $number,
        ]);
    }

    public function logLockStatusChanged(SequenceInterface $sequence, bool $isLocked, ?string $performedBy = null): void
    {
        $action = $isLocked ? 'locked' : 'unlocked';
        $this->createAuditRecord($sequence, "sequence_{$action}", [
            'is_locked' => $isLocked,
        ], $performedBy);
    }

    /**
     * Create an audit record in the database.
     */
    private function createAuditRecord(SequenceInterface $sequence, string $eventType, array $eventData, ?string $performedBy = null): void
    {
        /** @var Sequence $sequence */
        SequenceAudit::create([
            'sequence_id' => $sequence->id,
            'event_type' => $eventType,
            'event_data' => $eventData,
            'performed_by' => $performedBy,
        ]);
    }
}
