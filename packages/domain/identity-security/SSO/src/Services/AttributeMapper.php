<?php

declare(strict_types=1);

namespace Nexus\SSO\Services;

use Nexus\SSO\Contracts\AttributeMapperInterface;
use Nexus\SSO\Exceptions\AttributeMappingException;
use Nexus\SSO\ValueObjects\AttributeMap;
use Nexus\SSO\ValueObjects\UserProfile;

/**
 * Attribute mapper service
 * 
 * Maps SSO provider attributes to local user attributes
 */
final readonly class AttributeMapper implements AttributeMapperInterface
{
    public function map(array $ssoAttributes, AttributeMap $mapping): UserProfile
    {
        // Build reverse mapping (local field => SSO field path)
        $reversedMapping = empty($mapping->mappings) ? [] : array_flip($mapping->mappings);

        // Validate required attributes exist in SSO response
        foreach ($mapping->requiredFields as $requiredField) {
            $ssoFieldPath = $mapping->mappings[$requiredField] ?? $requiredField;
            
            if (!isset($ssoAttributes[$ssoFieldPath]) && !$this->hasNestedValue($ssoAttributes, $ssoFieldPath)) {
                throw new AttributeMappingException(
                    "Required attribute '{$requiredField}' (mapped from '{$ssoFieldPath}') is missing from SSO response"
                );
            }
        }

        // Extract mapped values
        $ssoUserId = $this->extractValue($ssoAttributes, $mapping->mappings['sso_user_id'] ?? 'sso_user_id');
        $email = $this->extractValue($ssoAttributes, $mapping->mappings['email'] ?? 'email');
        $firstName = $this->extractValue($ssoAttributes, $mapping->mappings['first_name'] ?? 'first_name');
        $lastName = $this->extractValue($ssoAttributes, $mapping->mappings['last_name'] ?? 'last_name');
        $displayName = $this->extractValue($ssoAttributes, $mapping->mappings['display_name'] ?? 'display_name');

        // Collect unmapped attributes
        $mappedKeys = array_values($mapping->mappings);
        $unmappedAttributes = array_filter(
            $ssoAttributes,
            fn($key) => !in_array($key, $mappedKeys, true),
            ARRAY_FILTER_USE_KEY
        );

        return new UserProfile(
            ssoUserId: $ssoUserId,
            email: $email,
            firstName: $firstName,
            lastName: $lastName,
            displayName: $displayName,
            attributes: $unmappedAttributes
        );
    }

    public function validateRequiredAttributes(array $ssoAttributes, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($ssoAttributes[$field]) && !$this->hasNestedValue($ssoAttributes, $field)) {
                throw new AttributeMappingException(
                    "Required attribute '{$field}' is missing from SSO response"
                );
            }
        }
    }

    /**
     * Extract value from SSO attributes supporting dot notation
     * 
     * @param array<string, mixed> $attributes
     * @param string $path Dot-notation path (e.g., 'emails.0.value')
     * @return mixed
     */
    private function extractValue(array $attributes, string $path): mixed
    {
        // Check if simple key exists
        if (isset($attributes[$path])) {
            return $attributes[$path];
        }

        // Handle dot notation for nested values
        if (str_contains($path, '.')) {
            return $this->getNestedValue($attributes, $path);
        }

        return null;
    }

    /**
     * Get nested value using dot notation
     */
    private function getNestedValue(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Check if nested value exists
     */
    private function hasNestedValue(array $data, string $path): bool
    {
        return $this->getNestedValue($data, $path) !== null;
    }
}
