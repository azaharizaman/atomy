<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

/**
 * Resolution Action enum.
 *
 * Defines actions for resolving capacity constraints.
 */
enum ResolutionAction: string
{
    /**
     * Use alternative work center.
     */
    case ALTERNATIVE_WORK_CENTER = 'alternative_work_center';

    /**
     * Add overtime hours.
     */
    case OVERTIME = 'overtime';

    /**
     * Reschedule to different date.
     */
    case RESCHEDULE = 'reschedule';

    /**
     * Subcontract the operation.
     */
    case SUBCONTRACT = 'subcontract';

    /**
     * Split order across multiple periods/resources.
     */
    case SPLIT_ORDER = 'split_order';

    /**
     * Add additional shift.
     */
    case ADDITIONAL_SHIFT = 'additional_shift';

    /**
     * Expedite preceding operations.
     */
    case EXPEDITE = 'expedite';

    /**
     * Reduce order quantity.
     */
    case REDUCE_QUANTITY = 'reduce_quantity';

    /**
     * Get typical cost impact (1-5, where 5 is highest cost).
     */
    public function getCostImpact(): int
    {
        return match ($this) {
            self::RESCHEDULE => 1,
            self::SPLIT_ORDER => 2,
            self::ALTERNATIVE_WORK_CENTER => 2,
            self::EXPEDITE => 3,
            self::OVERTIME => 3,
            self::ADDITIONAL_SHIFT => 4,
            self::SUBCONTRACT => 4,
            self::REDUCE_QUANTITY => 5,
        };
    }

    /**
     * Get typical lead time impact (days).
     */
    public function getLeadTimeImpact(): int
    {
        return match ($this) {
            self::EXPEDITE => -2,
            self::OVERTIME => 0,
            self::ADDITIONAL_SHIFT => 0,
            self::ALTERNATIVE_WORK_CENTER => 0,
            self::SPLIT_ORDER => 1,
            self::SUBCONTRACT => 3,
            self::RESCHEDULE => 5,
            self::REDUCE_QUANTITY => 0,
        };
    }

    /**
     * Check if action requires approval.
     */
    public function requiresApproval(): bool
    {
        return match ($this) {
            self::SUBCONTRACT, self::ADDITIONAL_SHIFT, self::REDUCE_QUANTITY => true,
            default => false,
        };
    }

    /**
     * Check if action can be automated.
     */
    public function canBeAutomated(): bool
    {
        return match ($this) {
            self::RESCHEDULE, self::SPLIT_ORDER, self::ALTERNATIVE_WORK_CENTER => true,
            default => false,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ALTERNATIVE_WORK_CENTER => 'Use Alternative Work Center',
            self::OVERTIME => 'Add Overtime',
            self::RESCHEDULE => 'Reschedule Order',
            self::SUBCONTRACT => 'Subcontract Operation',
            self::SPLIT_ORDER => 'Split Order',
            self::ADDITIONAL_SHIFT => 'Add Additional Shift',
            self::EXPEDITE => 'Expedite Operations',
            self::REDUCE_QUANTITY => 'Reduce Quantity',
        };
    }

    /**
     * Get description.
     */
    public function description(): string
    {
        return match ($this) {
            self::ALTERNATIVE_WORK_CENTER => 'Route to a different work center with available capacity',
            self::OVERTIME => 'Extend working hours beyond normal schedule',
            self::RESCHEDULE => 'Move order to a different date with available capacity',
            self::SUBCONTRACT => 'Send operation to external vendor for processing',
            self::SPLIT_ORDER => 'Divide order across multiple periods or resources',
            self::ADDITIONAL_SHIFT => 'Add extra shift to increase daily capacity',
            self::EXPEDITE => 'Accelerate preceding operations to free capacity',
            self::REDUCE_QUANTITY => 'Lower order quantity to fit available capacity',
        };
    }

    /**
     * Get default priority (1 = highest priority).
     */
    public function getDefaultPriority(): int
    {
        return match ($this) {
            self::ALTERNATIVE_WORK_CENTER => 1,
            self::RESCHEDULE => 2,
            self::SPLIT_ORDER => 3,
            self::OVERTIME => 4,
            self::EXPEDITE => 5,
            self::ADDITIONAL_SHIFT => 6,
            self::SUBCONTRACT => 7,
            self::REDUCE_QUANTITY => 8,
        };
    }
}
