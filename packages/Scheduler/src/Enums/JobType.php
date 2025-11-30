<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Enums;

/**
 * Job Type Enum
 *
 * Defines the type of scheduled job to execute.
 * Domain packages should extend this with their own job types.
 */
enum JobType: string
{
    case EXPORT_REPORT = 'export_report';
    case DOCUMENT_SHREDDING = 'document_shredding';
    case WORK_ORDER_START = 'work_order_start';
    case SEND_REMINDER = 'send_reminder';
    case PERIOD_CLOSE = 'period_close';
    case DATA_CLEANUP = 'data_cleanup';
    
    /**
     * Get human-readable label for the job type
     */
    public function label(): string
    {
        return match($this) {
            self::EXPORT_REPORT => 'Export Report',
            self::DOCUMENT_SHREDDING => 'Document Shredding',
            self::WORK_ORDER_START => 'Work Order Start',
            self::SEND_REMINDER => 'Send Reminder',
            self::PERIOD_CLOSE => 'Period Close',
            self::DATA_CLEANUP => 'Data Cleanup',
        };
    }
    
    /**
     * Check if this job type requires a target entity
     */
    public function requiresTarget(): bool
    {
        return match($this) {
            self::EXPORT_REPORT,
            self::DOCUMENT_SHREDDING,
            self::WORK_ORDER_START,
            self::SEND_REMINDER => true,
            self::PERIOD_CLOSE,
            self::DATA_CLEANUP => false,
        };
    }
}
