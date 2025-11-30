<?php

declare(strict_types=1);

namespace Nexus\Budget\Enums;

/**
 * Rollover Policy enum
 * 
 * Defines how unused budget is handled at period end.
 */
enum RolloverPolicy: string
{
    case Expire = 'expire';
    case AutoRoll = 'auto_roll';
    case RequireApproval = 'require_approval';

    /**
     * Check if policy requires workflow approval
     */
    public function requiresWorkflow(): bool
    {
        return $this === self::RequireApproval;
    }

    /**
     * Check if policy automatically carries over funds
     */
    public function isAutomatic(): bool
    {
        return $this === self::AutoRoll;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Expire => 'Expire Unused Funds',
            self::AutoRoll => 'Automatic Rollover',
            self::RequireApproval => 'Require Approval for Rollover',
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match($this) {
            self::Expire => 'Unused budget will be zeroed out at period end',
            self::AutoRoll => 'Unused budget will automatically carry to next period',
            self::RequireApproval => 'Unused budget rollover requires manager approval',
        };
    }
}
