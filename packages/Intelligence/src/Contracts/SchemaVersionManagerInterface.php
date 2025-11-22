<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Contracts;

/**
 * Schema version manager interface
 * 
 * Manages feature extractor schema versions and deprecation lifecycle.
 */
interface SchemaVersionManagerInterface
{
    /**
     * Mark a schema version as deprecated
     * 
     * @param string $context Model context (e.g., 'payable_duplicate_detection')
     * @param string $version Schema version to deprecate (e.g., '1.0')
     * @return void
     */
    public function markSchemaDeprecated(string $context, string $version): void;
    
    /**
     * Check if a schema version is still supported
     * 
     * @param string $context Model context
     * @param string $version Schema version to check
     * @return bool True if supported, false if past removal eligibility date
     */
    public function isSchemaSupported(string $context, string $version): bool;
    
    /**
     * Get all currently active/supported schema versions for a context
     * 
     * @param string $context Model context
     * @return array<string> Array of supported version strings
     */
    public function getActiveSchemas(string $context): array;
    
    /**
     * Get deprecation date for a schema version
     * 
     * @param string $context Model context
     * @param string $version Schema version
     * @return \DateTimeImmutable|null Deprecation date or null if not deprecated
     */
    public function getDeprecationDate(string $context, string $version): ?\DateTimeImmutable;
    
    /**
     * Get removal eligibility date for a schema version
     * 
     * @param string $context Model context
     * @param string $version Schema version
     * @return \DateTimeImmutable|null Removal eligibility date or null if not deprecated
     */
    public function getRemovalEligibilityDate(string $context, string $version): ?\DateTimeImmutable;
}
