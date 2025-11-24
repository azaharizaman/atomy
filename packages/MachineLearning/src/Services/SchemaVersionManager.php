<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Services;

use DateTimeImmutable;
use Nexus\MachineLearning\Contracts\SchemaVersionManagerInterface;
use Nexus\Setting\Contracts\SettingsManagerInterface;

/**
 * Schema version manager
 * 
 * Manages extractor schema versions, deprecation lifecycle, and backward compatibility.
 * Implements 6-month minimum support policy for deprecated schemas.
 */
final readonly class SchemaVersionManager implements SchemaVersionManagerInterface
{
    private const SETTING_PREFIX = 'intelligence.schema';
    
    public function __construct(
        private SettingsManagerInterface $settings
    ) {}
    
    /**
     * {@inheritDoc}
     */
    public function markSchemaDeprecated(string $context, string $version): void
    {
        $now = new DateTimeImmutable();
        
        // Store deprecation timestamp
        $this->settings->set(
            $this->getDeprecationKey($context, $version),
            $now->format('Y-m-d H:i:s')
        );
        
        // Calculate and store removal eligibility date
        $deprecationMonths = $this->settings->getInt(self::SETTING_PREFIX . '.deprecation_months', 6);
        $removalDate = $now->modify("+{$deprecationMonths} months");
        
        $this->settings->set(
            $this->getRemovalEligibilityKey($context, $version),
            $removalDate->format('Y-m-d H:i:s')
        );
    }
    
    /**
     * {@inheritDoc}
     */
    public function isSchemaSupported(string $context, string $version): bool
    {
        $removalDate = $this->getRemovalEligibilityDate($context, $version);
        
        // If not deprecated, it's supported
        if ($removalDate === null) {
            return true;
        }
        
        // Check if we're still within the support window
        $now = new DateTimeImmutable();
        return $now < $removalDate;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getActiveSchemas(string $context): array
    {
        // Get all schema versions for this context
        $allVersions = $this->getAllSchemaVersions($context);
        
        // Filter to only supported versions
        return array_filter($allVersions, fn(string $version) => $this->isSchemaSupported($context, $version));
    }
    
    /**
     * {@inheritDoc}
     */
    public function getDeprecationDate(string $context, string $version): ?DateTimeImmutable
    {
        $dateString = $this->settings->getString(
            $this->getDeprecationKey($context, $version),
            null
        );
        
        if ($dateString === null) {
            return null;
        }
        
        return new DateTimeImmutable($dateString);
    }
    
    /**
     * {@inheritDoc}
     */
    public function getRemovalEligibilityDate(string $context, string $version): ?DateTimeImmutable
    {
        $dateString = $this->settings->getString(
            $this->getRemovalEligibilityKey($context, $version),
            null
        );
        
        if ($dateString === null) {
            return null;
        }
        
        return new DateTimeImmutable($dateString);
    }
    
    /**
     * Get all registered schema versions for a context
     * 
     * @param string $context Model context
     * @return array<string> Array of version strings
     */
    private function getAllSchemaVersions(string $context): array
    {
        // In production, this would query a registry of all schema versions
        // For now, we'll use a simple setting-based approach
        $versionsJson = $this->settings->getString(
            self::SETTING_PREFIX . ".{$context}.versions",
            '[]'
        );
        
        return json_decode($versionsJson, true) ?? [];
    }
    
    /**
     * Get setting key for deprecation timestamp
     * 
     * @param string $context Model context
     * @param string $version Schema version
     * @return string Setting key
     */
    private function getDeprecationKey(string $context, string $version): string
    {
        return self::SETTING_PREFIX . ".{$context}.v{$version}.deprecated_at";
    }
    
    /**
     * Get setting key for removal eligibility date
     * 
     * @param string $context Model context
     * @param string $version Schema version
     * @return string Setting key
     */
    private function getRemovalEligibilityKey(string $context, string $version): string
    {
        return self::SETTING_PREFIX . ".{$context}.v{$version}.removal_eligible_at";
    }
}
