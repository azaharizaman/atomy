<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

/**
 * Attribute mapping configuration value object
 * 
 * Maps IdP attributes to local user attributes
 * Immutable by design (readonly properties)
 */
final readonly class AttributeMap
{
    /**
     * @param array<string, string> $mappings Local field => IdP field mapping
     * @param array<string> $requiredFields Required local fields
     */
    public function __construct(
        public array $mappings,
        public array $requiredFields = ['email', 'sso_user_id'],
    ) {
    }
}
