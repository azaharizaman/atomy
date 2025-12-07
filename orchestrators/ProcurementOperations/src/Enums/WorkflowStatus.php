<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Workflow execution status.
 */
enum WorkflowStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case PAUSED = 'paused';
    case WAITING = 'waiting';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Check if status is terminal (no further transitions).
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FAILED, self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Check if workflow can be resumed from this status.
     */
    public function canResume(): bool
    {
        return match ($this) {
            self::PAUSED, self::WAITING => true,
            default => false,
        };
    }

    /**
     * Check if workflow can be cancelled from this status.
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::PENDING, self::RUNNING, self::PAUSED, self::WAITING => true,
            default => false,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::RUNNING => 'Running',
            self::PAUSED => 'Paused',
            self::WAITING => 'Waiting for Input',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
