<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\ValueObjects;

use DateTimeImmutable;
use Nexus\DataPrivacy\Enums\BreachSeverity;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Exceptions\InvalidBreachRecordException;

/**
 * Represents a data breach incident record.
 *
 * Used to track and manage data breach incidents, notifications,
 * and remediation efforts as required by privacy regulations.
 */
final class BreachRecord
{
    /**
     * @param string $id Unique breach identifier
     * @param string $title Brief title/description of the breach
     * @param BreachSeverity $severity Calculated severity level
     * @param DateTimeImmutable $discoveredAt When the breach was discovered
     * @param DateTimeImmutable $occurredAt When the breach actually occurred (if known)
     * @param int $recordsAffected Estimated number of records affected
     * @param array<DataCategory> $dataCategories Types of data affected
     * @param string $description Detailed description of the breach
     * @param string|null $cause Root cause analysis
     * @param string|null $containmentActions Actions taken to contain the breach
     * @param bool $regulatoryNotified Whether regulatory authority was notified
     * @param DateTimeImmutable|null $regulatoryNotifiedAt When authority was notified
     * @param bool $individualsNotified Whether affected individuals were notified
     * @param DateTimeImmutable|null $individualsNotifiedAt When individuals were notified
     * @param bool $containedAt Whether breach has been contained
     * @param DateTimeImmutable|null $resolvedAt When breach was fully resolved
     * @param string|null $reportedBy Who reported/discovered the breach
     * @param string|null $incidentManager Person managing the incident
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly BreachSeverity $severity,
        public readonly DateTimeImmutable $discoveredAt,
        public readonly DateTimeImmutable $occurredAt,
        public readonly int $recordsAffected,
        public readonly array $dataCategories,
        public readonly string $description,
        public readonly ?string $cause = null,
        public readonly ?string $containmentActions = null,
        public readonly bool $regulatoryNotified = false,
        public readonly ?DateTimeImmutable $regulatoryNotifiedAt = null,
        public readonly bool $individualsNotified = false,
        public readonly ?DateTimeImmutable $individualsNotifiedAt = null,
        public readonly bool $containedAt = false,
        public readonly ?DateTimeImmutable $resolvedAt = null,
        public readonly ?string $reportedBy = null,
        public readonly ?string $incidentManager = null,
        public readonly array $metadata = [],
    ) {
        if (trim($id) === '') {
            throw new InvalidBreachRecordException('Breach ID cannot be empty');
        }

        if (trim($title) === '') {
            throw new InvalidBreachRecordException('Breach title cannot be empty');
        }

        if ($recordsAffected < 0) {
            throw new InvalidBreachRecordException('Records affected cannot be negative');
        }

        if ($this->occurredAt > $this->discoveredAt) {
            throw new InvalidBreachRecordException(
                'Breach occurrence date cannot be after discovery date'
            );
        }

        if ($this->resolvedAt !== null && $this->resolvedAt < $this->discoveredAt) {
            throw new InvalidBreachRecordException(
                'Resolution date cannot be before discovery date'
            );
        }

        if ($this->regulatoryNotified && $this->regulatoryNotifiedAt === null) {
            throw new InvalidBreachRecordException(
                'Regulatory notification date is required when regulatory is notified'
            );
        }

        if ($this->individualsNotified && $this->individualsNotifiedAt === null) {
            throw new InvalidBreachRecordException(
                'Individual notification date is required when individuals are notified'
            );
        }

        // Validate all data categories
        foreach ($this->dataCategories as $category) {
            if (!$category instanceof DataCategory) {
                throw new InvalidBreachRecordException(
                    'All data categories must be DataCategory enum instances'
                );
            }
        }
    }

    /**
     * Create a new breach record with minimal required fields.
     */
    public static function create(
        string $id,
        string $title,
        DateTimeImmutable $discoveredAt,
        int $recordsAffected,
        array $dataCategories,
        string $description,
        bool $containedQuickly = false,
        ?DateTimeImmutable $occurredAt = null,
    ): self {
        $occurredAt ??= $discoveredAt;

        // Auto-calculate severity
        $hasSensitiveData = array_reduce(
            $dataCategories,
            fn (bool $carry, DataCategory $cat) => $carry || $cat->isSpecialCategory(),
            false
        );

        $severity = BreachSeverity::calculate(
            recordsAffected: $recordsAffected,
            sensitiveData: $hasSensitiveData,
            encryptedData: false,
            containedQuickly: $containedQuickly,
        );

        return new self(
            id: $id,
            title: $title,
            severity: $severity,
            discoveredAt: $discoveredAt,
            occurredAt: $occurredAt,
            recordsAffected: $recordsAffected,
            dataCategories: $dataCategories,
            description: $description,
        );
    }

    /**
     * Check if regulatory notification is required.
     */
    public function requiresRegulatoryNotification(): bool
    {
        return $this->severity->requiresRegulatoryNotification();
    }

    /**
     * Check if individual notification is required.
     */
    public function requiresIndividualNotification(): bool
    {
        return $this->severity->requiresIndividualNotification();
    }

    /**
     * Check if the breach is resolved.
     */
    public function isResolved(): bool
    {
        return $this->resolvedAt !== null;
    }

    /**
     * Check if regulatory notification deadline is approaching or passed.
     *
     * @param int $deadlineHours Regulatory deadline in hours (e.g., 72 for GDPR)
     */
    public function isRegulatoryDeadlineApproaching(
        int $deadlineHours,
        ?DateTimeImmutable $asOf = null,
    ): bool {
        if ($this->regulatoryNotified) {
            return false;
        }

        if (!$this->requiresRegulatoryNotification()) {
            return false;
        }

        $asOf ??= new DateTimeImmutable();
        $deadline = $this->discoveredAt->modify("+{$deadlineHours} hours");

        // Approaching = within 12 hours of deadline
        $approachingThreshold = $deadline->modify('-12 hours');

        return $asOf >= $approachingThreshold;
    }

    /**
     * Check if regulatory notification deadline has passed.
     */
    public function isRegulatoryDeadlinePassed(
        int $deadlineHours,
        ?DateTimeImmutable $asOf = null,
    ): bool {
        if ($this->regulatoryNotified) {
            return false;
        }

        $asOf ??= new DateTimeImmutable();
        $deadline = $this->discoveredAt->modify("+{$deadlineHours} hours");

        return $asOf > $deadline;
    }

    /**
     * Get hours since discovery.
     */
    public function getHoursSinceDiscovery(?DateTimeImmutable $asOf = null): int
    {
        $asOf ??= new DateTimeImmutable();
        $diff = $this->discoveredAt->diff($asOf);

        return ($diff->days * 24) + $diff->h;
    }

    /**
     * Check if the breach involves sensitive/special category data.
     */
    public function involvesSensitiveData(): bool
    {
        foreach ($this->dataCategories as $category) {
            if ($category->isSpecialCategory()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the highest risk level among affected data categories.
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
     * Create a copy with regulatory notification recorded.
     */
    public function markRegulatoryNotified(DateTimeImmutable $notifiedAt): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            severity: $this->severity,
            discoveredAt: $this->discoveredAt,
            occurredAt: $this->occurredAt,
            recordsAffected: $this->recordsAffected,
            dataCategories: $this->dataCategories,
            description: $this->description,
            cause: $this->cause,
            containmentActions: $this->containmentActions,
            regulatoryNotified: true,
            regulatoryNotifiedAt: $notifiedAt,
            individualsNotified: $this->individualsNotified,
            individualsNotifiedAt: $this->individualsNotifiedAt,
            containedAt: $this->containedAt,
            resolvedAt: $this->resolvedAt,
            reportedBy: $this->reportedBy,
            incidentManager: $this->incidentManager,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a copy with individual notification recorded.
     */
    public function markIndividualsNotified(DateTimeImmutable $notifiedAt): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            severity: $this->severity,
            discoveredAt: $this->discoveredAt,
            occurredAt: $this->occurredAt,
            recordsAffected: $this->recordsAffected,
            dataCategories: $this->dataCategories,
            description: $this->description,
            cause: $this->cause,
            containmentActions: $this->containmentActions,
            regulatoryNotified: $this->regulatoryNotified,
            regulatoryNotifiedAt: $this->regulatoryNotifiedAt,
            individualsNotified: true,
            individualsNotifiedAt: $notifiedAt,
            containedAt: $this->containedAt,
            resolvedAt: $this->resolvedAt,
            reportedBy: $this->reportedBy,
            incidentManager: $this->incidentManager,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a copy marked as resolved.
     */
    public function markResolved(DateTimeImmutable $resolvedAt): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            severity: $this->severity,
            discoveredAt: $this->discoveredAt,
            occurredAt: $this->occurredAt,
            recordsAffected: $this->recordsAffected,
            dataCategories: $this->dataCategories,
            description: $this->description,
            cause: $this->cause,
            containmentActions: $this->containmentActions,
            regulatoryNotified: $this->regulatoryNotified,
            regulatoryNotifiedAt: $this->regulatoryNotifiedAt,
            individualsNotified: $this->individualsNotified,
            individualsNotifiedAt: $this->individualsNotifiedAt,
            containedAt: true,
            resolvedAt: $resolvedAt,
            reportedBy: $this->reportedBy,
            incidentManager: $this->incidentManager,
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
            'title' => $this->title,
            'severity' => $this->severity->value,
            'severity_label' => $this->severity->getLabel(),
            'discovered_at' => $this->discoveredAt->format(DateTimeImmutable::ATOM),
            'occurred_at' => $this->occurredAt->format(DateTimeImmutable::ATOM),
            'records_affected' => $this->recordsAffected,
            'data_categories' => array_map(
                fn (DataCategory $cat) => $cat->value,
                $this->dataCategories
            ),
            'description' => $this->description,
            'cause' => $this->cause,
            'containment_actions' => $this->containmentActions,
            'regulatory_notified' => $this->regulatoryNotified,
            'regulatory_notified_at' => $this->regulatoryNotifiedAt?->format(DateTimeImmutable::ATOM),
            'individuals_notified' => $this->individualsNotified,
            'individuals_notified_at' => $this->individualsNotifiedAt?->format(DateTimeImmutable::ATOM),
            'contained' => $this->containedAt,
            'resolved_at' => $this->resolvedAt?->format(DateTimeImmutable::ATOM),
            'reported_by' => $this->reportedBy,
            'incident_manager' => $this->incidentManager,
            'is_resolved' => $this->isResolved(),
            'involves_sensitive_data' => $this->involvesSensitiveData(),
            'highest_risk_level' => $this->getHighestDataRiskLevel(),
            'hours_since_discovery' => $this->getHoursSinceDiscovery(),
            'metadata' => $this->metadata,
        ];
    }
}
