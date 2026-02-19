<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Exceptions;

use RuntimeException;

/**
 * Exception for privacy data operations.
 */
final class PrivacyDataException extends RuntimeException
{
    public static function dataSubjectNotFound(string $dataSubjectId): self
    {
        return new self("Data subject not found: {$dataSubjectId}");
    }

    public static function consentNotFound(string $consentId): self
    {
        return new self("Consent not found: {$consentId}");
    }

    public static function requestNotFound(string $requestId): self
    {
        return new self("Data subject request not found: {$requestId}");
    }

    public static function invalidRequest(string $reason): self
    {
        return new self("Invalid data subject request: {$reason}");
    }
}
