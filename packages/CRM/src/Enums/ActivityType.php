<?php

declare(strict_types=1);

namespace Nexus\CRM\Enums;

/**
 * Activity Type Enum
 * 
 * Represents the types of activities tracked in the CRM system.
 * 
 * @package Nexus\CRM\Enums
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
enum ActivityType: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Task = 'task';
    case Note = 'note';

    /**
     * Get human-readable label for the activity type
     */
    public function label(): string
    {
        return match ($this) {
            self::Call => 'Phone Call',
            self::Email => 'Email',
            self::Meeting => 'Meeting',
            self::Task => 'Task',
            self::Note => 'Note',
        };
    }

    /**
     * Check if this activity type requires scheduling
     */
    public function requiresScheduling(): bool
    {
        return in_array($this, [self::Call, self::Meeting, self::Task], true);
    }

    /**
     * Check if this activity type has duration
     */
    public function hasDuration(): bool
    {
        return in_array($this, [self::Call, self::Meeting], true);
    }

    /**
     * Check if this is a communication activity
     */
    public function isCommunication(): bool
    {
        return in_array($this, [self::Call, self::Email, self::Meeting], true);
    }

    /**
     * Check if this is a standalone activity (not tied to time)
     */
    public function isStandalone(): bool
    {
        return $this === self::Note;
    }

    /**
     * Get default duration in minutes for this activity type
     */
    public function getDefaultDurationMinutes(): ?int
    {
        return match ($this) {
            self::Call => 15,
            self::Meeting => 60,
            self::Task => 30,
            self::Email => null,
            self::Note => null,
        };
    }

    /**
     * Get icon name for UI display
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::Call => 'phone',
            self::Email => 'envelope',
            self::Meeting => 'calendar',
            self::Task => 'check-square',
            self::Note => 'file-text',
        };
    }
}