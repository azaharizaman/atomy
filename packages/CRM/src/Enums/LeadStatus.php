<?php

declare(strict_types=1);

namespace Nexus\CRM\Enums;

/**
 * Lead Status Enum
 * 
 * Represents the lifecycle status of a lead in the CRM system.
 * 
 * @package Nexus\CRM\Enums
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Disqualified = 'disqualified';
    case Converted = 'converted';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Disqualified => 'Disqualified',
            self::Converted => 'Converted',
        };
    }

    /**
     * Check if lead is active (can be worked on)
     */
    public function isActive(): bool
    {
        return in_array($this, [self::New, self::Contacted, self::Qualified], true);
    }

    /**
     * Check if lead is in a final state
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Disqualified, self::Converted], true);
    }

    /**
     * Check if lead can be converted to opportunity
     */
    public function isConvertible(): bool
    {
        return $this === self::Qualified;
    }

    /**
     * Get valid transitions from this status
     * 
     * @return LeadStatus[]
     */
    public function getValidTransitions(): array
    {
        return match ($this) {
            self::New => [self::Contacted, self::Disqualified],
            self::Contacted => [self::Qualified, self::Disqualified],
            self::Qualified => [self::Converted, self::Disqualified],
            self::Disqualified => [],
            self::Converted => [],
        };
    }

    /**
     * Check if transition to another status is valid
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->getValidTransitions(), true);
    }
}