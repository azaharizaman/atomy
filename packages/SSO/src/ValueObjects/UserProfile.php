<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

/**
 * SSO user profile value object
 * 
 * Represents a user profile extracted from SSO provider
 * Immutable by design (readonly properties)
 */
final readonly class UserProfile
{
    /**
     * @param string $ssoUserId Unique identifier from IdP (e.g., Azure Object ID, Google sub)
     * @param string $email User's email address
     * @param string|null $firstName User's first name
     * @param string|null $lastName User's last name
     * @param string|null $displayName User's display name
     * @param array<string, mixed> $attributes All mapped attributes from IdP
     */
    public function __construct(
        public string $ssoUserId,
        public string $email,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $displayName = null,
        public array $attributes = [],
    ) {
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }
}
