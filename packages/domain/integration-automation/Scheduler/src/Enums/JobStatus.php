<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Enums;

/**
 * Job Status Enum
 *
 * Represents the lifecycle state of a scheduled job.
 */
enum JobStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case FAILED_PERMANENT = 'failed_permanent';
    case CANCELED = 'canceled';
    
    /**
     * Check if the job can transition to the given status
     */
    public function canTransitionTo(JobStatus $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [
                self::RUNNING,
                self::CANCELED,
            ], true),
            
            self::RUNNING => in_array($newStatus, [
                self::COMPLETED,
                self::FAILED,
                self::FAILED_PERMANENT,
            ], true),
            
            self::FAILED => in_array($newStatus, [
                self::PENDING,  // Retry
                self::FAILED_PERMANENT,
                self::CANCELED,
            ], true),
            
            // Final states cannot transition
            self::COMPLETED,
            self::FAILED_PERMANENT,
            self::CANCELED => false,
        };
    }
    
    /**
     * Check if the job can be executed
     */
    public function canExecute(): bool
    {
        return $this === self::PENDING;
    }
    
    /**
     * Check if this is a final (terminal) state
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED_PERMANENT,
            self::CANCELED,
        ], true);
    }
    
    /**
     * Check if the job is in a failed state
     */
    public function isFailed(): bool
    {
        return in_array($this, [
            self::FAILED,
            self::FAILED_PERMANENT,
        ], true);
    }
    
    /**
     * Check if the job can be retried
     */
    public function canRetry(): bool
    {
        return $this === self::FAILED;
    }
    
    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::RUNNING => 'Running',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed (Retriable)',
            self::FAILED_PERMANENT => 'Failed (Permanent)',
            self::CANCELED => 'Canceled',
        };
    }
    
    /**
     * Get color representation for UI
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'blue',
            self::RUNNING => 'yellow',
            self::COMPLETED => 'green',
            self::FAILED => 'orange',
            self::FAILED_PERMANENT => 'red',
            self::CANCELED => 'gray',
        };
    }
}
