<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Exceptions;

use RuntimeException;

/**
 * Exception for KYC data operations.
 */
final class KycDataException extends RuntimeException
{
    public static function profileNotFound(string $partyId): self
    {
        return new self("KYC profile not found for party: {$partyId}");
    }

    public static function verificationFailed(string $partyId, string $reason): self
    {
        return new self("KYC verification failed for party {$partyId}: {$reason}");
    }

    public static function riskAssessmentNotFound(string $partyId): self
    {
        return new self("Risk assessment not found for party: {$partyId}");
    }

    public static function invalidStatus(string $partyId, string $status): self
    {
        return new self("Invalid KYC status '{$status}' for party: {$partyId}");
    }
}
