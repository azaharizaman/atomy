<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\AttributeMap;
use Nexus\SSO\ValueObjects\UserProfile;

/**
 * Attribute mapper interface
 * 
 * Maps SSO provider attributes to local user attributes
 */
interface AttributeMapperInterface
{
    /**
     * Map SSO attributes to local user profile
     * 
     * @param array<string, mixed> $ssoAttributes Attributes from IdP
     * @param AttributeMap $mapping Attribute mapping configuration
     * @return UserProfile Mapped user profile
     * @throws \Nexus\SSO\Exceptions\AttributeMappingException
     */
    public function map(array $ssoAttributes, AttributeMap $mapping): UserProfile;

    /**
     * Extract required attributes from SSO response
     * 
     * @param array<string, mixed> $ssoAttributes Attributes from IdP
     * @param array<string> $requiredFields List of required field names
     * @throws \Nexus\SSO\Exceptions\AttributeMappingException If required field is missing
     */
    public function validateRequiredAttributes(array $ssoAttributes, array $requiredFields): void;
}
