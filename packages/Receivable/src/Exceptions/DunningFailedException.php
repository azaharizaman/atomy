<?php

declare(strict_types=1);

namespace Nexus\Receivable\Exceptions;

use RuntimeException;

/**
 * Dunning Failed Exception
 *
 * Thrown when a dunning (collections reminder) operation fails.
 */
class DunningFailedException extends RuntimeException
{
    public static function notificationFailed(string $customerId, string $reason): self
    {
        return new self("Dunning notification failed for customer {$customerId}: {$reason}");
    }

    public static function noOverdueInvoices(string $customerId): self
    {
        return new self("No overdue invoices found for customer {$customerId}");
    }

    public static function invalidEscalationLevel(string $level): self
    {
        return new self("Invalid dunning escalation level: {$level}");
    }
}
