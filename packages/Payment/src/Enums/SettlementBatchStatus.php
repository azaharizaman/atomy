<?php

declare(strict_types=1);

namespace Nexus\Payment\Enums;

/**
 * Settlement Batch Status Enum
 *
 * Defines the lifecycle states for processor settlement batches.
 * Settlement batches group payments for reconciliation with payment processors.
 *
 * Lifecycle flow:
 * OPEN → CLOSED → RECONCILED
 *              ↘ DISPUTED → RECONCILED
 *
 * @package Nexus\Payment\Enums
 */
enum SettlementBatchStatus: string
{
    /**
     * Batch is open and accepting new payments.
     * Payments can be added to the batch.
     */
    case OPEN = 'open';

    /**
     * Batch is closed and no longer accepting payments.
     * Ready for reconciliation with processor statement.
     */
    case CLOSED = 'closed';

    /**
     * Batch has been fully reconciled with processor.
     * All payments matched with settlement report.
     */
    case RECONCILED = 'reconciled';

    /**
     * Batch has discrepancies that require investigation.
     * Amounts or transactions don't match processor statement.
     */
    case DISPUTED = 'disputed';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::CLOSED => 'Closed',
            self::RECONCILED => 'Reconciled',
            self::DISPUTED => 'Disputed',
        };
    }

    /**
     * Check if status is terminal (no further transitions allowed).
     */
    public function isTerminal(): bool
    {
        return $this === self::RECONCILED;
    }

    /**
     * Check if batch is still open for new payments.
     */
    public function isOpen(): bool
    {
        return $this === self::OPEN;
    }

    /**
     * Check if batch can be reconciled.
     */
    public function canReconcile(): bool
    {
        return $this === self::CLOSED || $this === self::DISPUTED;
    }

    /**
     * Get valid transitions from current status.
     *
     * @return array<SettlementBatchStatus>
     */
    public function getValidTransitions(): array
    {
        return match ($this) {
            self::OPEN => [self::CLOSED],
            self::CLOSED => [self::RECONCILED, self::DISPUTED],
            self::DISPUTED => [self::RECONCILED],
            self::RECONCILED => [],
        };
    }

    /**
     * Check if transition to given status is valid.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->getValidTransitions(), true);
    }
}
