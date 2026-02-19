<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\RetentionPolicy;
use Nexus\DataPrivacy\Enums\RetentionCategory;
use Nexus\DataPrivacy\Enums\DataCategory;

/**
 * Manager interface for retention policy operations.
 */
interface RetentionPolicyManagerInterface
{
    /**
     * Create a new retention policy.
     *
     * @param array<DataCategory> $applicableCategories
     */
    public function createPolicy(
        RetentionCategory $category,
        int $retentionMonths,
        array $applicableCategories,
        bool $requiresSecureDeletion = false,
        bool $allowsLegalHold = true,
        ?\DateTimeImmutable $effectiveFrom = null,
        ?\DateTimeImmutable $effectiveTo = null
    ): RetentionPolicy;

    /**
     * Get policy by ID.
     */
    public function getPolicy(string $policyId): RetentionPolicy;

    /**
     * Get all policies.
     *
     * @return array<RetentionPolicy>
     */
    public function getAllPolicies(): array;

    /**
     * Get effective policy for a data category.
     */
    public function getEffectivePolicy(
        RetentionCategory $category,
        ?\DateTimeImmutable $date = null
    ): ?RetentionPolicy;

    /**
     * Update a retention policy.
     */
    public function updatePolicy(
        string $policyId,
        int $retentionMonths,
        bool $requiresSecureDeletion,
        bool $allowsLegalHold
    ): RetentionPolicy;

    /**
     * Deactivate a policy.
     */
    public function deactivatePolicy(string $policyId): RetentionPolicy;

    /**
     * Calculate deletion date for data created at given date.
     */
    public function calculateDeletionDate(
        RetentionCategory $category,
        \DateTimeImmutable $createdAt
    ): ?\DateTimeImmutable;

    /**
     * Check if data should be deleted based on retention policy.
     */
    public function shouldDelete(
        RetentionCategory $category,
        \DateTimeImmutable $createdAt
    ): bool;

    /**
     * Get data categories requiring deletion.
     *
     * @return array<RetentionCategory>
     */
    public function getCategoriesRequiringDeletion(): array;

    /**
     * Export retention policies for compliance documentation.
     *
     * @return array<array<string, mixed>>
     */
    public function exportPolicies(): array;

    /**
     * Get policy by category
     *
     * Retrieves the effective retention policy for a specific category.
     * Returns null if no policy exists for the category.
     *
     * @param string $category The retention category identifier
     * @param \DateTimeImmutable|null $date Optional date to check for (default: now)
     * @return RetentionPolicy|null The policy if found, null otherwise
     */
    public function getPolicyByCategory(string $category, ?\DateTimeImmutable $date = null): ?RetentionPolicy;
}
