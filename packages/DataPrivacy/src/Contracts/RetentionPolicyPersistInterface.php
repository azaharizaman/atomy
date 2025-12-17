<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\RetentionPolicy;

/**
 * Write operations for retention policies (CQRS Command Model).
 */
interface RetentionPolicyPersistInterface
{
    /**
     * Save a new policy.
     *
     * @return string The generated policy ID
     */
    public function save(RetentionPolicy $policy): string;

    /**
     * Update an existing policy.
     */
    public function update(RetentionPolicy $policy): void;

    /**
     * Delete a policy.
     */
    public function delete(string $id): void;

    /**
     * Deactivate a policy by setting effective end date.
     *
     * @return RetentionPolicy The updated policy
     */
    public function deactivate(
        string $id,
        \DateTimeImmutable $effectiveTo
    ): RetentionPolicy;

    /**
     * Replace an existing policy with a new version.
     * Deactivates the old policy and creates a new one.
     *
     * @return string The new policy ID
     */
    public function replacePolicy(
        string $oldPolicyId,
        RetentionPolicy $newPolicy
    ): string;
}
