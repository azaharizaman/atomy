<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\ValueObjects;

use DateTimeImmutable;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Enums\RetentionCategory;
use Nexus\DataPrivacy\Exceptions\InvalidRetentionPolicyException;

/**
 * Represents a data retention policy defining how long data should be kept.
 */
final class RetentionPolicy
{
    /**
     * @param string $id Unique policy identifier
     * @param string $name Human-readable policy name
     * @param RetentionCategory $category Category of data this policy applies to
     * @param int $retentionMonths Number of months to retain data (0 = delete immediately)
     * @param bool $requiresSecureDeletion Whether secure deletion is required
     * @param bool $allowsLegalHold Whether legal hold can override deletion
     * @param string|null $description Policy description
     * @param string|null $legalBasis Legal basis for this retention period
     * @param array<DataCategory> $applicableDataCategories Specific data categories this applies to
     * @param DateTimeImmutable|null $effectiveFrom When policy becomes effective
     * @param DateTimeImmutable|null $effectiveTo When policy expires (null = indefinite)
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly RetentionCategory $category,
        public readonly int $retentionMonths,
        public readonly bool $requiresSecureDeletion = false,
        public readonly bool $allowsLegalHold = true,
        public readonly ?string $description = null,
        public readonly ?string $legalBasis = null,
        public readonly array $applicableDataCategories = [],
        public readonly ?DateTimeImmutable $effectiveFrom = null,
        public readonly ?DateTimeImmutable $effectiveTo = null,
        public readonly array $metadata = [],
    ) {
        if (trim($id) === '') {
            throw new InvalidRetentionPolicyException('Policy ID cannot be empty');
        }

        if (trim($name) === '') {
            throw new InvalidRetentionPolicyException('Policy name cannot be empty');
        }

        if ($retentionMonths < 0) {
            throw new InvalidRetentionPolicyException('Retention months cannot be negative');
        }

        if ($this->effectiveFrom !== null && $this->effectiveTo !== null) {
            if ($this->effectiveTo <= $this->effectiveFrom) {
                throw new InvalidRetentionPolicyException('Effective end date must be after start date');
            }
        }
    }

    /**
     * Check if policy is currently effective.
     */
    public function isEffective(?DateTimeImmutable $asOf = null): bool
    {
        $asOf ??= new DateTimeImmutable();

        if ($this->effectiveFrom !== null && $asOf < $this->effectiveFrom) {
            return false;
        }

        if ($this->effectiveTo !== null && $asOf > $this->effectiveTo) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the deletion date based on a reference date.
     */
    public function calculateDeletionDate(DateTimeImmutable $referenceDate): DateTimeImmutable
    {
        if ($this->retentionMonths === 0) {
            return $referenceDate;
        }

        return $referenceDate->modify("+{$this->retentionMonths} months");
    }

    /**
     * Check if data should be deleted based on reference date.
     */
    public function shouldDelete(
        DateTimeImmutable $referenceDate,
        ?DateTimeImmutable $asOf = null,
        bool $hasLegalHold = false,
    ): bool {
        if ($hasLegalHold && $this->allowsLegalHold) {
            return false;
        }

        $asOf ??= new DateTimeImmutable();
        $deletionDate = $this->calculateDeletionDate($referenceDate);

        return $asOf >= $deletionDate;
    }

    /**
     * Get the retention period in human-readable format.
     */
    public function getRetentionPeriodDescription(): string
    {
        if ($this->retentionMonths === 0) {
            return 'Delete immediately';
        }

        if ($this->retentionMonths % 12 === 0) {
            $years = $this->retentionMonths / 12;

            return $years === 1 ? '1 year' : "{$years} years";
        }

        return $this->retentionMonths === 1
            ? '1 month'
            : "{$this->retentionMonths} months";
    }

    /**
     * Check if this policy applies to a specific data category.
     */
    public function appliesToDataCategory(DataCategory $category): bool
    {
        if ($this->applicableDataCategories === []) {
            return true; // Applies to all if not specified
        }

        return in_array($category, $this->applicableDataCategories, true);
    }

    /**
     * Create a copy with updated retention period.
     */
    public function withRetentionMonths(int $months): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            category: $this->category,
            retentionMonths: $months,
            requiresSecureDeletion: $this->requiresSecureDeletion,
            allowsLegalHold: $this->allowsLegalHold,
            description: $this->description,
            legalBasis: $this->legalBasis,
            applicableDataCategories: $this->applicableDataCategories,
            effectiveFrom: $this->effectiveFrom,
            effectiveTo: $this->effectiveTo,
            metadata: $this->metadata,
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category->value,
            'category_label' => $this->category->getLabel(),
            'retention_months' => $this->retentionMonths,
            'retention_description' => $this->getRetentionPeriodDescription(),
            'requires_secure_deletion' => $this->requiresSecureDeletion,
            'allows_legal_hold' => $this->allowsLegalHold,
            'description' => $this->description,
            'legal_basis' => $this->legalBasis,
            'applicable_data_categories' => array_map(
                fn (DataCategory $cat) => $cat->value,
                $this->applicableDataCategories
            ),
            'effective_from' => $this->effectiveFrom?->format(DateTimeImmutable::ATOM),
            'effective_to' => $this->effectiveTo?->format(DateTimeImmutable::ATOM),
            'is_effective' => $this->isEffective(),
            'metadata' => $this->metadata,
        ];
    }
}
