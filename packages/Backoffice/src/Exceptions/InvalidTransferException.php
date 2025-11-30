<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Exceptions;

use Exception;

class InvalidTransferException extends Exception
{
    public static function pendingTransferExists(string $staffId): self
    {
        return new self("Staff {$staffId} has a pending transfer request");
    }

    public static function invalidStatus(string $transferId, string $currentStatus, string $requiredStatus): self
    {
        return new self(
            "Transfer {$transferId} cannot be processed. Current status: {$currentStatus}, required: {$requiredStatus}"
        );
    }

    public static function retroactiveDate(\DateTimeInterface $effectiveDate): self
    {
        return new self(
            "Transfer effective date {$effectiveDate->format('Y-m-d')} is beyond the 30-day retroactive limit"
        );
    }
}
