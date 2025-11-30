<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

/**
 * Work Order status enum.
 *
 * Defines the lifecycle states of a manufacturing work order.
 */
enum WorkOrderStatus: string
{
    /**
     * Work order created but not yet planned.
     */
    case DRAFT = 'draft';

    /**
     * Work order planned with materials and capacity allocated.
     */
    case PLANNED = 'planned';

    /**
     * Work order released to shop floor.
     */
    case RELEASED = 'released';

    /**
     * Production has started.
     */
    case IN_PROGRESS = 'in_progress';

    /**
     * Production completed, pending final accounting.
     */
    case COMPLETED = 'completed';

    /**
     * Work order closed with all variances posted.
     */
    case CLOSED = 'closed';

    /**
     * Work order cancelled.
     */
    case CANCELLED = 'cancelled';

    /**
     * Work order on hold.
     */
    case ON_HOLD = 'on_hold';

    /**
     * Check if status allows production operations.
     */
    public function allowsProduction(): bool
    {
        return match ($this) {
            self::RELEASED, self::IN_PROGRESS => true,
            default => false,
        };
    }

    /**
     * Check if status allows modifications.
     */
    public function allowsModification(): bool
    {
        return match ($this) {
            self::DRAFT, self::PLANNED, self::ON_HOLD => true,
            default => false,
        };
    }

    /**
     * Check if work order is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::CLOSED, self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Check if this status can transition to cancelled.
     */
    public function canCancel(): bool
    {
        return in_array(self::CANCELLED, $this->getValidTransitions(), true);
    }

    /**
     * Get valid next statuses from current status.
     *
     * @return array<WorkOrderStatus>
     */
    public function getValidTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PLANNED, self::CANCELLED],
            self::PLANNED => [self::RELEASED, self::ON_HOLD, self::CANCELLED],
            self::RELEASED => [self::IN_PROGRESS, self::ON_HOLD, self::CANCELLED],
            self::IN_PROGRESS => [self::COMPLETED, self::ON_HOLD],
            self::COMPLETED => [self::CLOSED],
            self::ON_HOLD => [self::PLANNED, self::RELEASED, self::IN_PROGRESS, self::CANCELLED],
            self::CLOSED, self::CANCELLED => [],
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PLANNED => 'Planned',
            self::RELEASED => 'Released',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CLOSED => 'Closed',
            self::CANCELLED => 'Cancelled',
            self::ON_HOLD => 'On Hold',
        };
    }
}
