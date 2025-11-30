<?php

declare(strict_types=1);

namespace Nexus\Messaging\Enums;

/**
 * Archival status for regulatory compliance and data retention
 * 
 * @package Nexus\Messaging
 */
enum ArchivalStatus: string
{
    case Active = 'active';
    case PreArchived = 'pre_archived';
    case Archived = 'archived';

    /**
     * Check if record is still active
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if record is archived
     */
    public function isArchived(): bool
    {
        return $this === self::Archived;
    }

    /**
     * Check if record is scheduled for archival
     */
    public function isPendingArchival(): bool
    {
        return $this === self::PreArchived;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::PreArchived => 'Pending Archival',
            self::Archived => 'Archived',
        };
    }
}
