<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\RetentionPolicy;
use Nexus\DataPrivacy\Enums\RetentionCategory;

/**
 * Read operations for retention policies (CQRS Query Model).
 */
interface RetentionPolicyQueryInterface
{
    /**
     * Find policy by ID.
     */
    public function findById(string $id): ?RetentionPolicy;

    /**
     * Find all policies.
     *
     * @return array<RetentionPolicy>
     */
    public function findAll(): array;

    /**
     * Find policies applicable to a data category.
     *
     * @return array<RetentionPolicy>
     */
    public function findByCategory(RetentionCategory $category): array;

    /**
     * Find effective policy for a data category at given date.
     */
    public function findEffectivePolicy(
        RetentionCategory $category,
        ?\DateTimeImmutable $date = null
    ): ?RetentionPolicy;

    /**
     * Find policies requiring secure deletion.
     *
     * @return array<RetentionPolicy>
     */
    public function findRequiringSecureDeletion(): array;

    /**
     * Find active policies (currently effective).
     *
     * @return array<RetentionPolicy>
     */
    public function findActive(): array;

    /**
     * Get policy history for a category.
     *
     * @return array<RetentionPolicy>
     */
    public function getPolicyHistory(RetentionCategory $category): array;
}
