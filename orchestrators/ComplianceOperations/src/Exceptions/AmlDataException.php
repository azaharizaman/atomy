<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Exceptions;

use RuntimeException;

/**
 * Exception for AML data operations.
 */
final class AmlDataException extends RuntimeException
{
    public static function assessmentNotFound(string $partyId): self
    {
        return new self("AML assessment not found for party: {$partyId}");
    }

    public static function screeningFailed(string $partyId, string $reason): self
    {
        return new self("AML screening failed for party {$partyId}: {$reason}");
    }

    public static function monitoringFailed(string $partyId, string $reason): self
    {
        return new self("Transaction monitoring failed for party {$partyId}: {$reason}");
    }

    public static function sarNotFound(string $sarId): self
    {
        return new self("SAR not found: {$sarId}");
    }
}
