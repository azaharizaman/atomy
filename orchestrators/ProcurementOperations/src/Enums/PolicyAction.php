<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Actions to take when policy violation occurs.
 */
enum PolicyAction: string
{
    /**
     * Allow transaction to proceed normally
     */
    case ALLOW = 'allow';

    /**
     * Block transaction from proceeding
     */
    case BLOCK = 'block';

    /**
     * Require additional approval before proceeding
     */
    case REQUIRE_APPROVAL = 'require_approval';

    /**
     * Flag for review but allow to proceed
     */
    case FLAG_FOR_REVIEW = 'flag_for_review';

    /**
     * Route to exception handling workflow
     */
    case ROUTE_TO_EXCEPTION = 'route_to_exception';

    /**
     * Escalate to higher authority
     */
    case ESCALATE = 'escalate';

    /**
     * Get human-readable label for this action.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ALLOW => 'Allow',
            self::BLOCK => 'Block',
            self::REQUIRE_APPROVAL => 'Require Approval',
            self::FLAG_FOR_REVIEW => 'Flag for Review',
            self::ROUTE_TO_EXCEPTION => 'Route to Exception',
            self::ESCALATE => 'Escalate',
        };
    }

    /**
     * Check if this action stops the transaction flow.
     */
    public function stopsFlow(): bool
    {
        return match ($this) {
            self::ALLOW,
            self::FLAG_FOR_REVIEW => false,
            self::BLOCK,
            self::REQUIRE_APPROVAL,
            self::ROUTE_TO_EXCEPTION,
            self::ESCALATE => true,
        };
    }
}
