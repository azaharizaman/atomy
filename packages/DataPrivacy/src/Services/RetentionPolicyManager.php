<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Services;

use Nexus\DataPrivacy\Contracts\RetentionPolicyManagerInterface;
use Nexus\DataPrivacy\Contracts\RetentionPolicyQueryInterface;
use Nexus\DataPrivacy\Contracts\RetentionPolicyPersistInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\ValueObjects\RetentionPolicy;
use Nexus\DataPrivacy\Enums\RetentionCategory;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Exceptions\RetentionPolicyNotFoundException;
use Nexus\DataPrivacy\Exceptions\InvalidRetentionPolicyException;

/**
 * Manages data retention policy lifecycle.
 */
final readonly class RetentionPolicyManager implements RetentionPolicyManagerInterface
{
    public function __construct(
        private RetentionPolicyQueryInterface $policyQuery,
        private RetentionPolicyPersistInterface $policyPersist,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function createPolicy(
        RetentionCategory $category,
        int $retentionMonths,
        array $applicableCategories,
        bool $requiresSecureDeletion = false,
        bool $allowsLegalHold = true,
        ?\DateTimeImmutable $effectiveFrom = null,
        ?\DateTimeImmutable $effectiveTo = null
    ): RetentionPolicy {
        if ($retentionMonths < 1) {
            throw new InvalidRetentionPolicyException(
                'Retention period must be at least 1 month'
            );
        }

        if (empty($applicableCategories)) {
            throw new InvalidRetentionPolicyException(
                'At least one applicable data category is required'
            );
        }

        $policyId = $this->generatePolicyId();
        $policyName = "Retention Policy - {$category->value} ({$retentionMonths} months)";

        $policy = new RetentionPolicy(
            id: $policyId,
            name: $policyName,
            category: $category,
            retentionMonths: $retentionMonths,
            requiresSecureDeletion: $requiresSecureDeletion,
            allowsLegalHold: $allowsLegalHold,
            applicableDataCategories: $applicableCategories,
            effectiveFrom: $effectiveFrom ?? new \DateTimeImmutable(),
            effectiveTo: $effectiveTo
        );

        $this->policyPersist->save($policy);

        $this->auditLogger?->log(
            'retention_policy',
            $policyId,
            'created',
            "Retention policy created for {$retentionMonths} months",
            [
                'retention_months' => $retentionMonths,
                'requires_secure_deletion' => $requiresSecureDeletion,
                'allows_legal_hold' => $allowsLegalHold,
                'applicable_categories' => array_map(fn($c) => $c->value, $applicableCategories),
            ]
        );

        return $policy;
    }

    /**
     * Generate a unique policy ID.
     */
    private function generatePolicyId(): string
    {
        return 'pol-' . bin2hex(random_bytes(8));
    }

    public function getPolicy(string $policyId): RetentionPolicy
    {
        $policy = $this->policyQuery->findById($policyId);

        if ($policy === null) {
            throw RetentionPolicyNotFoundException::withId($policyId);
        }

        return $policy;
    }

    public function getAllPolicies(): array
    {
        return $this->policyQuery->findAll();
    }

    public function getEffectivePolicy(
        RetentionCategory $category,
        ?\DateTimeImmutable $date = null
    ): ?RetentionPolicy {
        return $this->policyQuery->findEffectivePolicy($category, $date);
    }

    public function updatePolicy(
        string $policyId,
        int $retentionMonths,
        bool $requiresSecureDeletion,
        bool $allowsLegalHold
    ): RetentionPolicy {
        $policy = $this->getPolicy($policyId);

        if ($retentionMonths < 1) {
            throw new InvalidRetentionPolicyException(
                'Retention period must be at least 1 month'
            );
        }

        $updatedPolicy = new RetentionPolicy(
            retentionMonths: $retentionMonths,
            requiresSecureDeletion: $requiresSecureDeletion,
            allowsLegalHold: $allowsLegalHold,
            applicableDataCategories: $policy->getApplicableDataCategories(),
            effectiveFrom: $policy->getEffectiveFrom(),
            effectiveTo: $policy->getEffectiveTo()
        );

        $this->policyPersist->update($updatedPolicy);

        $this->auditLogger?->log(
            'retention_policy',
            $policyId,
            'updated',
            "Retention policy updated to {$retentionMonths} months",
            [
                'old_retention_months' => $policy->getRetentionMonths(),
                'new_retention_months' => $retentionMonths,
            ]
        );

        return $updatedPolicy;
    }

    public function deactivatePolicy(string $policyId): RetentionPolicy
    {
        $policy = $this->getPolicy($policyId);

        $deactivatedPolicy = $this->policyPersist->deactivate(
            $policyId,
            new \DateTimeImmutable()
        );

        $this->auditLogger?->log(
            'retention_policy',
            $policyId,
            'deactivated',
            'Retention policy deactivated',
            []
        );

        return $deactivatedPolicy;
    }

    public function calculateDeletionDate(
        RetentionCategory $category,
        \DateTimeImmutable $createdAt
    ): ?\DateTimeImmutable {
        $policy = $this->getEffectivePolicy($category);

        if ($policy === null) {
            return null;
        }

        return $policy->calculateDeletionDate($createdAt);
    }

    public function shouldDelete(
        RetentionCategory $category,
        \DateTimeImmutable $createdAt
    ): bool {
        $policy = $this->getEffectivePolicy($category);

        if ($policy === null) {
            return false;
        }

        return $policy->shouldDelete($createdAt);
    }

    public function getCategoriesRequiringDeletion(): array
    {
        $categories = [];

        foreach (RetentionCategory::cases() as $category) {
            $policy = $this->getEffectivePolicy($category);

            if ($policy !== null) {
                // Check if any data in this category should be deleted
                // This would need integration with actual data storage
                $categories[] = $category;
            }
        }

        return $categories;
    }

    public function exportPolicies(): array
    {
        $policies = $this->getAllPolicies();

        return array_map(
            fn(RetentionPolicy $policy) => $policy->toArray(),
            $policies
        );
    }

    public function getPolicyByCategory(string $category, ?\DateTimeImmutable $date = null): ?RetentionPolicy
    {
        try {
            $retentionCategory = RetentionCategory::from($category);
            return $this->getEffectivePolicy($retentionCategory, $date);
        } catch (\ValueError $e) {
            // Invalid category string
            return null;
        }
    }
}
