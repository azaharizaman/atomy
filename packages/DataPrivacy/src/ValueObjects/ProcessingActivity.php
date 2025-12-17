<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\ValueObjects;

use DateTimeImmutable;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Enums\LawfulBasisType;
use Nexus\DataPrivacy\Exceptions\InvalidProcessingActivityException;

/**
 * Represents a record of processing activity (ROPA entry).
 *
 * Article 30 of GDPR requires controllers to maintain records
 * of processing activities. This value object represents one such record.
 */
final class ProcessingActivity
{
    /**
     * @param string $id Unique activity identifier
     * @param string $name Name/title of the processing activity
     * @param string $purpose Purpose of processing
     * @param LawfulBasisType $lawfulBasis Legal basis for processing
     * @param array<DataCategory> $dataCategories Categories of data processed
     * @param string $controllerName Name of the data controller
     * @param string|null $controllerContact Controller contact information
     * @param string|null $dpoContact Data Protection Officer contact
     * @param array<string> $recipientCategories Categories of recipients
     * @param array<string> $dataSubjectCategories Categories of data subjects
     * @param bool $crossBorderTransfer Whether data is transferred cross-border
     * @param array<string> $transferCountries Countries data is transferred to
     * @param string|null $transferSafeguards Safeguards for cross-border transfer
     * @param int|null $retentionMonths Retention period in months
     * @param string|null $retentionDescription Description of retention policy
     * @param string|null $securityMeasures Description of security measures
     * @param bool $automatedDecisionMaking Whether automated decision-making is used
     * @param string|null $automatedDecisionDescription Description of automated decisions
     * @param bool $isActive Whether this processing activity is currently active
     * @param DateTimeImmutable $createdAt When this record was created
     * @param DateTimeImmutable|null $lastReviewedAt When this record was last reviewed
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $purpose,
        public readonly LawfulBasisType $lawfulBasis,
        public readonly array $dataCategories,
        public readonly string $controllerName,
        public readonly ?string $controllerContact = null,
        public readonly ?string $dpoContact = null,
        public readonly array $recipientCategories = [],
        public readonly array $dataSubjectCategories = [],
        public readonly bool $crossBorderTransfer = false,
        public readonly array $transferCountries = [],
        public readonly ?string $transferSafeguards = null,
        public readonly ?int $retentionMonths = null,
        public readonly ?string $retentionDescription = null,
        public readonly ?string $securityMeasures = null,
        public readonly bool $automatedDecisionMaking = false,
        public readonly ?string $automatedDecisionDescription = null,
        public readonly bool $isActive = true,
        public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public readonly ?DateTimeImmutable $lastReviewedAt = null,
        public readonly array $metadata = [],
    ) {
        if (trim($id) === '') {
            throw new InvalidProcessingActivityException('Activity ID cannot be empty');
        }

        if (trim($name) === '') {
            throw new InvalidProcessingActivityException('Activity name cannot be empty');
        }

        if (trim($purpose) === '') {
            throw new InvalidProcessingActivityException('Processing purpose cannot be empty');
        }

        if (trim($controllerName) === '') {
            throw new InvalidProcessingActivityException('Controller name cannot be empty');
        }

        if ($this->dataCategories === []) {
            throw new InvalidProcessingActivityException(
                'At least one data category must be specified'
            );
        }

        // Validate all data categories
        foreach ($this->dataCategories as $category) {
            if (!$category instanceof DataCategory) {
                throw new InvalidProcessingActivityException(
                    'All data categories must be DataCategory enum instances'
                );
            }
        }

        if ($this->crossBorderTransfer && $this->transferCountries === []) {
            throw new InvalidProcessingActivityException(
                'Transfer countries must be specified when cross-border transfer is enabled'
            );
        }

        if ($this->automatedDecisionMaking && $this->automatedDecisionDescription === null) {
            throw new InvalidProcessingActivityException(
                'Automated decision description is required when automated decision-making is enabled'
            );
        }

        if ($this->retentionMonths !== null && $this->retentionMonths < 0) {
            throw new InvalidProcessingActivityException('Retention months cannot be negative');
        }
    }

    /**
     * Create a new processing activity with minimal required fields.
     *
     * @param array<DataCategory> $dataCategories
     */
    public static function create(
        string $id,
        string $name,
        string $purpose,
        LawfulBasisType $lawfulBasis,
        array $dataCategories,
        string $controllerName,
        ?string $processorName = null,
        bool $crossBorderTransfer = false,
        bool $automatedDecisionMaking = false,
    ): self {
        return new self(
            id: $id,
            name: $name,
            purpose: $purpose,
            lawfulBasis: $lawfulBasis,
            dataCategories: $dataCategories,
            controllerName: $controllerName,
            controllerContact: null,
            dpoContact: $processorName,
            crossBorderTransfer: $crossBorderTransfer,
            transferCountries: $crossBorderTransfer ? ['unspecified'] : [],
            automatedDecisionMaking: $automatedDecisionMaking,
            automatedDecisionDescription: $automatedDecisionMaking ? 'Automated decision-making enabled' : null,
        );
    }

    /**
     * Check if this activity processes sensitive/special category data.
     */
    public function processesSensitiveData(): bool
    {
        foreach ($this->dataCategories as $category) {
            if ($category->isSpecialCategory()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a Data Protection Impact Assessment (DPIA) is likely required.
     *
     * DPIA is typically required when:
     * - Systematic and extensive profiling with significant effects
     * - Large scale processing of special categories
     * - Systematic monitoring of public areas
     */
    public function requiresDpia(): bool
    {
        // Processing special category data at scale
        if ($this->processesSensitiveData()) {
            return true;
        }

        // Automated decision-making with legal effects
        if ($this->automatedDecisionMaking) {
            return true;
        }

        // Cross-border transfer without standard safeguards
        if ($this->crossBorderTransfer && $this->transferSafeguards === null) {
            return true;
        }

        return false;
    }

    /**
     * Get the highest risk level among processed data categories.
     */
    public function getHighestDataRiskLevel(): int
    {
        if ($this->dataCategories === []) {
            return 1;
        }

        return max(array_map(
            fn (DataCategory $cat) => $cat->getRiskLevel(),
            $this->dataCategories
        ));
    }

    /**
     * Check if this activity needs review (not reviewed in last 12 months).
     */
    public function needsReview(?DateTimeImmutable $asOf = null): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if ($this->lastReviewedAt === null) {
            return true;
        }

        $asOf ??= new DateTimeImmutable();
        $reviewThreshold = $this->lastReviewedAt->modify('+12 months');

        return $asOf >= $reviewThreshold;
    }

    /**
     * Get months since last review.
     */
    public function getMonthsSinceReview(?DateTimeImmutable $asOf = null): ?int
    {
        if ($this->lastReviewedAt === null) {
            return null;
        }

        $asOf ??= new DateTimeImmutable();
        $diff = $this->lastReviewedAt->diff($asOf);

        return ($diff->y * 12) + $diff->m;
    }

    /**
     * Create a copy marked as reviewed.
     */
    public function markReviewed(DateTimeImmutable $reviewedAt): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            purpose: $this->purpose,
            lawfulBasis: $this->lawfulBasis,
            dataCategories: $this->dataCategories,
            controllerName: $this->controllerName,
            controllerContact: $this->controllerContact,
            dpoContact: $this->dpoContact,
            recipientCategories: $this->recipientCategories,
            dataSubjectCategories: $this->dataSubjectCategories,
            crossBorderTransfer: $this->crossBorderTransfer,
            transferCountries: $this->transferCountries,
            transferSafeguards: $this->transferSafeguards,
            retentionMonths: $this->retentionMonths,
            retentionDescription: $this->retentionDescription,
            securityMeasures: $this->securityMeasures,
            automatedDecisionMaking: $this->automatedDecisionMaking,
            automatedDecisionDescription: $this->automatedDecisionDescription,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            lastReviewedAt: $reviewedAt,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a copy marked as inactive.
     */
    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            purpose: $this->purpose,
            lawfulBasis: $this->lawfulBasis,
            dataCategories: $this->dataCategories,
            controllerName: $this->controllerName,
            controllerContact: $this->controllerContact,
            dpoContact: $this->dpoContact,
            recipientCategories: $this->recipientCategories,
            dataSubjectCategories: $this->dataSubjectCategories,
            crossBorderTransfer: $this->crossBorderTransfer,
            transferCountries: $this->transferCountries,
            transferSafeguards: $this->transferSafeguards,
            retentionMonths: $this->retentionMonths,
            retentionDescription: $this->retentionDescription,
            securityMeasures: $this->securityMeasures,
            automatedDecisionMaking: $this->automatedDecisionMaking,
            automatedDecisionDescription: $this->automatedDecisionDescription,
            isActive: false,
            createdAt: $this->createdAt,
            lastReviewedAt: $this->lastReviewedAt,
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
            'purpose' => $this->purpose,
            'lawful_basis' => $this->lawfulBasis->value,
            'lawful_basis_label' => $this->lawfulBasis->getLabel(),
            'data_categories' => array_map(
                fn (DataCategory $cat) => $cat->value,
                $this->dataCategories
            ),
            'controller_name' => $this->controllerName,
            'controller_contact' => $this->controllerContact,
            'dpo_contact' => $this->dpoContact,
            'recipient_categories' => $this->recipientCategories,
            'data_subject_categories' => $this->dataSubjectCategories,
            'cross_border_transfer' => $this->crossBorderTransfer,
            'transfer_countries' => $this->transferCountries,
            'transfer_safeguards' => $this->transferSafeguards,
            'retention_months' => $this->retentionMonths,
            'retention_description' => $this->retentionDescription,
            'security_measures' => $this->securityMeasures,
            'automated_decision_making' => $this->automatedDecisionMaking,
            'automated_decision_description' => $this->automatedDecisionDescription,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format(DateTimeImmutable::ATOM),
            'last_reviewed_at' => $this->lastReviewedAt?->format(DateTimeImmutable::ATOM),
            'processes_sensitive_data' => $this->processesSensitiveData(),
            'requires_dpia' => $this->requiresDpia(),
            'highest_risk_level' => $this->getHighestDataRiskLevel(),
            'needs_review' => $this->needsReview(),
            'metadata' => $this->metadata,
        ];
    }
}
