<?php

declare(strict_types=1);

namespace Nexus\CRM\Enums;

/**
 * Pipeline Status Enum
 * 
 * Represents the status of a sales pipeline.
 * 
 * @package Nexus\CRM\Enums
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
enum PipelineStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Archived => 'Archived',
        };
    }

    /**
     * Check if pipeline is active
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if pipeline can be used for new opportunities
     */
    public function isUsable(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if pipeline is archived
     */
    public function isArchived(): bool
    {
        return $this === self::Archived;
    }

    /**
     * Get valid transitions from this status
     * 
     * @return PipelineStatus[]
     */
    public function getValidTransitions(): array
    {
        return match ($this) {
            self::Active => [self::Inactive, self::Archived],
            self::Inactive => [self::Active, self::Archived],
            self::Archived => [],
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