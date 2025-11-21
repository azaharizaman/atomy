<?php

declare(strict_types=1);

namespace Nexus\Assets\Enums;

/**
 * Maintenance Type Enum
 *
 * Defines types of maintenance activities (Tier 2+).
 */
enum MaintenanceType: string
{
    case REPAIR = 'repair';
    case SCHEDULED = 'scheduled';
    case EMERGENCY = 'emergency';
    case PREVENTIVE = 'preventive';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::REPAIR => 'Repair',
            self::SCHEDULED => 'Scheduled Maintenance',
            self::EMERGENCY => 'Emergency Repair',
            self::PREVENTIVE => 'Preventive Maintenance',
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match($this) {
            self::REPAIR => 'Corrective maintenance to fix identified issues',
            self::SCHEDULED => 'Routine maintenance according to schedule',
            self::EMERGENCY => 'Urgent repair required to restore functionality',
            self::PREVENTIVE => 'Proactive maintenance to prevent future issues',
        };
    }

    /**
     * Get typical priority level
     */
    public function getPriorityLevel(): int
    {
        return match($this) {
            self::EMERGENCY => 1,
            self::REPAIR => 2,
            self::SCHEDULED => 3,
            self::PREVENTIVE => 4,
        };
    }

    /**
     * Check if type is planned
     */
    public function isPlanned(): bool
    {
        return match($this) {
            self::SCHEDULED, self::PREVENTIVE => true,
            self::REPAIR, self::EMERGENCY => false,
        };
    }
}
