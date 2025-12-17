<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Exceptions;

/**
 * Exception thrown when processing is not allowed due to privacy constraints.
 */
class ProcessingNotAllowedException extends DataPrivacyException
{
    public static function noValidConsent(string $dataSubjectId, string $purpose): self
    {
        return new self(
            "Processing not allowed: no valid consent from data subject '{$dataSubjectId}' for purpose '{$purpose}'"
        );
    }

    public static function dataRetentionExpired(string $dataSubjectId): self
    {
        return new self(
            "Processing not allowed: data retention period expired for data subject '{$dataSubjectId}'"
        );
    }

    public static function objectionReceived(string $dataSubjectId): self
    {
        return new self(
            "Processing not allowed: objection received from data subject '{$dataSubjectId}'"
        );
    }
}
