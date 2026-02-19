<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Exceptions;

use RuntimeException;

/**
 * Exception for risk assessment operations.
 */
final class RiskAssessmentException extends RuntimeException
{
    public static function assessmentNotFound(string $partyId): self
    {
        return new self("Risk assessment not found for party: {$partyId}");
    }

    public static function assessmentFailed(string $partyId, string $reason): self
    {
        return new self("Risk assessment failed for party {$partyId}: {$reason}");
    }

    public static function invalidRiskLevel(string $level): self
    {
        return new self("Invalid risk level: {$level}");
    }
}
