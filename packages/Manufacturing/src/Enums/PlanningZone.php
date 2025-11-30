<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

/**
 * Planning Zone enum.
 *
 * Defines the planning zones for capacity planning horizons.
 */
enum PlanningZone: string
{
    /**
     * Frozen zone - no changes allowed.
     * Orders are firm and committed.
     */
    case FROZEN = 'frozen';

    /**
     * Slushy zone - limited changes with approval.
     * Changes require evaluation of impact.
     */
    case SLUSHY = 'slushy';

    /**
     * Liquid zone - free to make changes.
     * Planning is tentative and adjustable.
     */
    case LIQUID = 'liquid';

    /**
     * Check if zone allows changes.
     */
    public function allowsChanges(): bool
    {
        return match ($this) {
            self::FROZEN => false,
            self::SLUSHY, self::LIQUID => true,
        };
    }

    /**
     * Check if zone requires approval for changes.
     */
    public function requiresApproval(): bool
    {
        return match ($this) {
            self::SLUSHY => true,
            default => false,
        };
    }

    /**
     * Get change difficulty level (1-3).
     */
    public function getChangeDifficulty(): int
    {
        return match ($this) {
            self::FROZEN => 3,
            self::SLUSHY => 2,
            self::LIQUID => 1,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::FROZEN => 'Frozen',
            self::SLUSHY => 'Slushy',
            self::LIQUID => 'Liquid',
        };
    }

    /**
     * Get description.
     */
    public function description(): string
    {
        return match ($this) {
            self::FROZEN => 'No changes allowed - orders are firm and committed',
            self::SLUSHY => 'Limited changes with approval required',
            self::LIQUID => 'Free to make changes - planning is tentative',
        };
    }

    /**
     * Get typical days range for this zone.
     *
     * @return array{min: int, max: int}
     */
    public function getTypicalDaysRange(): array
    {
        return match ($this) {
            self::FROZEN => ['min' => 0, 'max' => 14],
            self::SLUSHY => ['min' => 15, 'max' => 30],
            self::LIQUID => ['min' => 31, 'max' => 90],
        };
    }
}
