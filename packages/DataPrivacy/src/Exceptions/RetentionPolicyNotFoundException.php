<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Exceptions;

/**
 * Exception thrown when retention policy is not found.
 */
class RetentionPolicyNotFoundException extends DataPrivacyException
{
    public static function withId(string $id): self
    {
        return new self("Retention policy with ID '{$id}' not found");
    }

    public static function forCategory(string $category): self
    {
        return new self("Retention policy for category '{$category}' not found");
    }
}
