<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Exceptions;

/**
 * Exception thrown when consent record is not found.
 */
class ConsentNotFoundException extends DataPrivacyException
{
    public static function withId(string $id): self
    {
        return new self("Consent record with ID '{$id}' not found");
    }

    public static function forDataSubjectAndPurpose(string $dataSubjectId, string $purpose): self
    {
        return new self(
            "Consent for data subject '{$dataSubjectId}' and purpose '{$purpose}' not found"
        );
    }
}
