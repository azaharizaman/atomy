<?php

declare(strict_types=1);

namespace Nexus\Hrm\ValueObjects;

/**
 * Performance review status value object.
 */
enum ReviewStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
    
    public function isEditable(): bool
    {
        return match($this) {
            self::DRAFT, self::PENDING => true,
            self::COMPLETED, self::CANCELLED => false,
        };
    }
}
