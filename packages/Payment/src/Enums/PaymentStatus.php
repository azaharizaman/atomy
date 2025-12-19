<?php

declare(strict_types=1);

namespace Nexus\Payment\Enums;

/**
 * Payment transaction status lifecycle.
 *
 * Represents the current state of a payment transaction through its lifecycle.
 * Status transitions must follow defined rules to ensure data integrity.
 *
 * Lifecycle:
 * PENDING → PROCESSING → COMPLETED
 *    ↓          ↓            ↓
 * CANCELLED   FAILED     REVERSED
 */
enum PaymentStatus: string
{
    /**
     * Payment created but not yet submitted for processing.
     */
    case PENDING = 'pending';

    /**
     * Payment is being processed by the payment provider.
     */
    case PROCESSING = 'processing';

    /**
     * Payment completed successfully.
     */
    case COMPLETED = 'completed';

    /**
     * Payment processing failed.
     */
    case FAILED = 'failed';

    /**
     * Payment was cancelled before processing.
     */
    case CANCELLED = 'cancelled';

    /**
     * Payment was reversed after completion (refund, chargeback, etc.).
     */
    case REVERSED = 'reversed';

    /**
     * Check if this status is a terminal state.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FAILED, self::CANCELLED, self::REVERSED => true,
            default => false,
        };
    }

    /**
     * Get all terminal statuses.
     *
     * @return array<self>
     */
    public static function terminalStatuses(): array
    {
        return [self::COMPLETED, self::FAILED, self::CANCELLED, self::REVERSED];
    }

    /**
     * Check if this status allows cancellation.
     */
    public function canCancel(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if this status allows reversal.
     */
    public function canReverse(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if transition to another status is valid.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::PENDING => in_array($target, [self::PROCESSING, self::CANCELLED], true),
            self::PROCESSING => in_array($target, [self::COMPLETED, self::FAILED], true),
            self::COMPLETED => $target === self::REVERSED,
            self::FAILED, self::CANCELLED, self::REVERSED => false,
        };
    }

    /**
     * Get valid next statuses from this status.
     *
     * @return array<self>
     */
    public function getValidTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::COMPLETED, self::FAILED],
            self::COMPLETED => [self::REVERSED],
            self::FAILED, self::CANCELLED, self::REVERSED => [],
        };
    }

    /**
     * Alias for getValidTransitions() for API consistency.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return $this->getValidTransitions();
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::REVERSED => 'Reversed',
        };
    }
}
