<?php

declare(strict_types=1);

namespace Nexus\Import\ValueObjects;

/**
 * Import mode enumeration
 * 
 * Defines how imported data should be persisted to the domain.
 */
enum ImportMode: string
{
    case CREATE = 'create';     // Insert new records only, fail on duplicates
    case UPDATE = 'update';     // Update existing records only, skip new
    case UPSERT = 'upsert';     // Insert or update based on key
    case DELETE = 'delete';     // Delete records matching keys
    case SYNC = 'sync';         // Full synchronization (upsert + delete missing)

    /**
     * Check if mode requires duplicate detection
     */
    public function requiresDuplicateCheck(): bool
    {
        return match($this) {
            self::CREATE => true,
            self::UPDATE, self::UPSERT, self::DELETE, self::SYNC => true,
        };
    }

    /**
     * Check if mode allows partial success (some rows can fail)
     */
    public function allowsPartialSuccess(): bool
    {
        return match($this) {
            self::CREATE, self::UPDATE, self::UPSERT => true,
            self::DELETE, self::SYNC => false,  // All-or-nothing
        };
    }

    /**
     * Check if mode creates new records
     */
    public function createsRecords(): bool
    {
        return match($this) {
            self::CREATE, self::UPSERT, self::SYNC => true,
            self::UPDATE, self::DELETE => false,
        };
    }

    /**
     * Check if mode updates existing records
     */
    public function updatesRecords(): bool
    {
        return match($this) {
            self::UPDATE, self::UPSERT, self::SYNC => true,
            self::CREATE, self::DELETE => false,
        };
    }

    /**
     * Get human-readable mode description
     */
    public function getDescription(): string
    {
        return match($this) {
            self::CREATE => 'Create new records only',
            self::UPDATE => 'Update existing records only',
            self::UPSERT => 'Create or update records',
            self::DELETE => 'Delete matching records',
            self::SYNC => 'Full synchronization (add/update/delete)',
        };
    }

    /**
     * Check if mode allows creating new records
     */
    public function canCreate(): bool
    {
        return $this->createsRecords();
    }

    /**
     * Check if mode allows updating existing records
     */
    public function canUpdate(): bool
    {
        return $this->updatesRecords();
    }

    /**
     * Check if mode allows deleting records
     */
    public function canDelete(): bool
    {
        return in_array($this, [self::DELETE, self::SYNC]);
    }
}
