<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\ValueObjects;

use Nexus\AmlCompliance\Enums\SarStatus;

/**
 * Suspicious Activity Report (SAR)
 * 
 * Immutable value object representing a SAR filing for regulatory compliance.
 * Based on FinCEN SAR requirements and FATF guidelines.
 */
final class SuspiciousActivityReport
{
    /**
     * SAR types based on FinCEN categorization
     */
    public const TYPE_STRUCTURING = 'structuring';
    public const TYPE_MONEY_LAUNDERING = 'money_laundering';
    public const TYPE_TERRORIST_FINANCING = 'terrorist_financing';
    public const TYPE_FRAUD = 'fraud';
    public const TYPE_IDENTITY_THEFT = 'identity_theft';
    public const TYPE_SANCTIONS_EVASION = 'sanctions_evasion';
    public const TYPE_BRIBERY_CORRUPTION = 'bribery_corruption';
    public const TYPE_TAX_EVASION = 'tax_evasion';
    public const TYPE_INSIDER_TRADING = 'insider_trading';
    public const TYPE_SUSPICIOUS_PARTY = 'suspicious_party';
    public const TYPE_OTHER = 'other';

    /**
     * Get all valid SAR types
     * 
     * @return array<string>
     */
    public static function getAllTypes(): array
    {
        return [
            self::TYPE_STRUCTURING,
            self::TYPE_MONEY_LAUNDERING,
            self::TYPE_TERRORIST_FINANCING,
            self::TYPE_FRAUD,
            self::TYPE_IDENTITY_THEFT,
            self::TYPE_SANCTIONS_EVASION,
            self::TYPE_BRIBERY_CORRUPTION,
            self::TYPE_TAX_EVASION,
            self::TYPE_INSIDER_TRADING,
            self::TYPE_SUSPICIOUS_PARTY,
            self::TYPE_OTHER,
        ];
    }

    /**
     * @param string $sarId Unique SAR identifier
     * @param string $partyId Subject party identifier
     * @param SarStatus $status Current SAR status
     * @param string $type Type of suspicious activity
     * @param string $narrative Detailed description of suspicious activity
     * @param float|null $totalAmount Total amount involved (null for risk-based SARs)
     * @param string|null $currency Currency of amounts (null for risk-based SARs)
     * @param \DateTimeImmutable|null $activityStartDate Start of suspicious activity period
     * @param \DateTimeImmutable|null $activityEndDate End of suspicious activity period
     * @param array<string> $transactionIds Related transaction identifiers
     * @param array<string> $suspiciousActivities List of suspicious activity indicators
     * @param array<TransactionAlert> $alerts Related alerts that triggered SAR
     * @param string|null $filingReference Regulatory filing reference number
     * @param string|null $assignedOfficer Compliance officer assigned
     * @param string $createdBy User who created the SAR
     * @param \DateTimeImmutable $createdAt Creation timestamp
     * @param \DateTimeImmutable|null $submittedAt When SAR was submitted
     * @param \DateTimeImmutable|null $closedAt When SAR was closed
     * @param string|null $closureReason Reason for closure
     * @param array<string, mixed> $subjectInfo Subject party information snapshot
     * @param array<string, mixed> $metadata Additional SAR data
     */
    public function __construct(
        public readonly string $sarId,
        public readonly string $partyId,
        public readonly SarStatus $status,
        public readonly string $type,
        public readonly string $narrative,
        public readonly ?float $totalAmount,
        public readonly ?string $currency,
        public readonly ?\DateTimeImmutable $activityStartDate,
        public readonly ?\DateTimeImmutable $activityEndDate,
        public readonly array $transactionIds = [],
        public readonly array $suspiciousActivities = [],
        public readonly array $alerts = [],
        public readonly ?string $filingReference = null,
        public readonly ?string $assignedOfficer = null,
        public readonly string $createdBy = 'system',
        public readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        public readonly ?\DateTimeImmutable $submittedAt = null,
        public readonly ?\DateTimeImmutable $closedAt = null,
        public readonly ?string $closureReason = null,
        public readonly array $subjectInfo = [],
        public readonly array $metadata = [],
    ) {
        if ($this->activityStartDate !== null && $this->activityEndDate !== null) {
            if ($this->activityEndDate < $this->activityStartDate) {
                throw new \InvalidArgumentException(
                    'Activity end date cannot be before start date'
                );
            }
        }
    }

    /**
     * Generate a new SAR ID
     */
    public static function generateSarId(): string
    {
        return sprintf(
            'SAR-%s-%s',
            date('Ymd'),
            strtoupper(bin2hex(random_bytes(4)))
        );
    }

    /**
     * Create a new draft SAR
     */
    public static function createDraft(
        string $partyId,
        string $type,
        string $narrative,
        float $totalAmount,
        string $currency,
        \DateTimeImmutable $activityStartDate,
        \DateTimeImmutable $activityEndDate,
        array $transactionIds = [],
        array $suspiciousActivities = [],
        array $alerts = [],
        string $createdBy = 'system',
        array $subjectInfo = [],
    ): self {
        return new self(
            sarId: self::generateSarId(),
            partyId: $partyId,
            status: SarStatus::DRAFT,
            type: $type,
            narrative: $narrative,
            totalAmount: $totalAmount,
            currency: $currency,
            activityStartDate: $activityStartDate,
            activityEndDate: $activityEndDate,
            transactionIds: $transactionIds,
            suspiciousActivities: $suspiciousActivities,
            alerts: $alerts,
            createdBy: $createdBy,
            subjectInfo: $subjectInfo,
        );
    }

    /**
     * Create from array (for hydration)
     * 
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $alerts = [];
        foreach (($data['alerts'] ?? []) as $alertData) {
            $alerts[] = $alertData instanceof TransactionAlert
                ? $alertData
                : TransactionAlert::fromArray($alertData);
        }

        return new self(
            sarId: (string) $data['sar_id'],
            partyId: (string) $data['party_id'],
            status: $data['status'] instanceof SarStatus
                ? $data['status']
                : SarStatus::from($data['status']),
            type: (string) ($data['type'] ?? self::TYPE_OTHER),
            narrative: (string) ($data['narrative'] ?? ''),
            totalAmount: (float) ($data['total_amount'] ?? 0.0),
            currency: (string) ($data['currency'] ?? 'USD'),
            activityStartDate: $data['activity_start_date'] instanceof \DateTimeImmutable
                ? $data['activity_start_date']
                : new \DateTimeImmutable($data['activity_start_date']),
            activityEndDate: $data['activity_end_date'] instanceof \DateTimeImmutable
                ? $data['activity_end_date']
                : new \DateTimeImmutable($data['activity_end_date']),
            transactionIds: (array) ($data['transaction_ids'] ?? []),
            suspiciousActivities: (array) ($data['suspicious_activities'] ?? []),
            alerts: $alerts,
            filingReference: $data['filing_reference'] ?? null,
            assignedOfficer: $data['assigned_officer'] ?? null,
            createdBy: (string) ($data['created_by'] ?? 'system'),
            createdAt: $data['created_at'] instanceof \DateTimeImmutable
                ? $data['created_at']
                : new \DateTimeImmutable($data['created_at'] ?? 'now'),
            submittedAt: isset($data['submitted_at'])
                ? ($data['submitted_at'] instanceof \DateTimeImmutable
                    ? $data['submitted_at']
                    : new \DateTimeImmutable($data['submitted_at']))
                : null,
            closedAt: isset($data['closed_at'])
                ? ($data['closed_at'] instanceof \DateTimeImmutable
                    ? $data['closed_at']
                    : new \DateTimeImmutable($data['closed_at']))
                : null,
            closureReason: $data['closure_reason'] ?? null,
            subjectInfo: (array) ($data['subject_info'] ?? []),
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    /**
     * Transition to a new status
     */
    public function transitionTo(SarStatus $newStatus): self
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot transition SAR from %s to %s',
                $this->status->value,
                $newStatus->value
            ));
        }

        $submittedAt = $this->submittedAt;
        $closedAt = $this->closedAt;

        if ($newStatus === SarStatus::SUBMITTED && $submittedAt === null) {
            $submittedAt = new \DateTimeImmutable();
        }

        if ($newStatus === SarStatus::CLOSED && $closedAt === null) {
            $closedAt = new \DateTimeImmutable();
        }

        return new self(
            sarId: $this->sarId,
            partyId: $this->partyId,
            status: $newStatus,
            type: $this->type,
            narrative: $this->narrative,
            totalAmount: $this->totalAmount,
            currency: $this->currency,
            activityStartDate: $this->activityStartDate,
            activityEndDate: $this->activityEndDate,
            transactionIds: $this->transactionIds,
            suspiciousActivities: $this->suspiciousActivities,
            alerts: $this->alerts,
            filingReference: $this->filingReference,
            assignedOfficer: $this->assignedOfficer,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            submittedAt: $submittedAt,
            closedAt: $closedAt,
            closureReason: $this->closureReason,
            subjectInfo: $this->subjectInfo,
            metadata: $this->metadata,
        );
    }

    /**
     * Submit for review
     */
    public function submitForReview(): self
    {
        return $this->transitionTo(SarStatus::PENDING_REVIEW);
    }

    /**
     * Approve the SAR
     */
    public function approve(): self
    {
        return $this->transitionTo(SarStatus::APPROVED);
    }

    /**
     * Submit to regulatory authority
     */
    public function submitToAuthority(string $filingReference): self
    {
        $submitted = $this->transitionTo(SarStatus::SUBMITTED);

        return new self(
            sarId: $submitted->sarId,
            partyId: $submitted->partyId,
            status: $submitted->status,
            type: $submitted->type,
            narrative: $submitted->narrative,
            totalAmount: $submitted->totalAmount,
            currency: $submitted->currency,
            activityStartDate: $submitted->activityStartDate,
            activityEndDate: $submitted->activityEndDate,
            transactionIds: $submitted->transactionIds,
            suspiciousActivities: $submitted->suspiciousActivities,
            alerts: $submitted->alerts,
            filingReference: $filingReference,
            assignedOfficer: $submitted->assignedOfficer,
            createdBy: $submitted->createdBy,
            createdAt: $submitted->createdAt,
            submittedAt: $submitted->submittedAt,
            closedAt: $submitted->closedAt,
            closureReason: $submitted->closureReason,
            subjectInfo: $submitted->subjectInfo,
            metadata: $submitted->metadata,
        );
    }

    /**
     * Close the SAR
     */
    public function close(string $reason): self
    {
        $closed = $this->transitionTo(SarStatus::CLOSED);

        return new self(
            sarId: $closed->sarId,
            partyId: $closed->partyId,
            status: $closed->status,
            type: $closed->type,
            narrative: $closed->narrative,
            totalAmount: $closed->totalAmount,
            currency: $closed->currency,
            activityStartDate: $closed->activityStartDate,
            activityEndDate: $closed->activityEndDate,
            transactionIds: $closed->transactionIds,
            suspiciousActivities: $closed->suspiciousActivities,
            alerts: $closed->alerts,
            filingReference: $closed->filingReference,
            assignedOfficer: $closed->assignedOfficer,
            createdBy: $closed->createdBy,
            createdAt: $closed->createdAt,
            submittedAt: $closed->submittedAt,
            closedAt: $closed->closedAt,
            closureReason: $reason,
            subjectInfo: $closed->subjectInfo,
            metadata: $closed->metadata,
        );
    }

    /**
     * Assign compliance officer
     */
    public function assignOfficer(string $officerId): self
    {
        return new self(
            sarId: $this->sarId,
            partyId: $this->partyId,
            status: $this->status,
            type: $this->type,
            narrative: $this->narrative,
            totalAmount: $this->totalAmount,
            currency: $this->currency,
            activityStartDate: $this->activityStartDate,
            activityEndDate: $this->activityEndDate,
            transactionIds: $this->transactionIds,
            suspiciousActivities: $this->suspiciousActivities,
            alerts: $this->alerts,
            filingReference: $this->filingReference,
            assignedOfficer: $officerId,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            submittedAt: $this->submittedAt,
            closedAt: $this->closedAt,
            closureReason: $this->closureReason,
            subjectInfo: $this->subjectInfo,
            metadata: $this->metadata,
        );
    }

    /**
     * Check if SAR is overdue for filing
     * FinCEN requires filing within 30 days of detection
     */
    public function isOverdue(): bool
    {
        if ($this->status->isSubmitted()) {
            return false;
        }

        $deadline = $this->createdAt->modify('+30 days');
        return new \DateTimeImmutable() > $deadline;
    }

    /**
     * Get days remaining until filing deadline
     */
    public function getDaysUntilDeadline(): int
    {
        $deadline = $this->createdAt->modify('+30 days');
        $now = new \DateTimeImmutable();
        $interval = $now->diff($deadline);

        return $interval->invert === 1 ? -$interval->days : $interval->days;
    }

    /**
     * Check if SAR is editable
     */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Check if SAR is pending action
     */
    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    /**
     * Get the activity period in days
     */
    public function getActivityPeriodDays(): int
    {
        return $this->activityStartDate->diff($this->activityEndDate)->days;
    }

    /**
     * Get type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_STRUCTURING => 'Structuring/Smurfing',
            self::TYPE_MONEY_LAUNDERING => 'Money Laundering',
            self::TYPE_TERRORIST_FINANCING => 'Terrorist Financing',
            self::TYPE_FRAUD => 'Fraud',
            self::TYPE_IDENTITY_THEFT => 'Identity Theft',
            self::TYPE_SANCTIONS_EVASION => 'Sanctions Evasion',
            self::TYPE_BRIBERY_CORRUPTION => 'Bribery/Corruption',
            self::TYPE_TAX_EVASION => 'Tax Evasion',
            self::TYPE_INSIDER_TRADING => 'Insider Trading',
            self::TYPE_OTHER => 'Other Suspicious Activity',
            default => 'Unknown',
        };
    }

    /**
     * Create a copy with updated narrative
     */
    public function withNarrative(string $narrative): self
    {
        return new self(
            sarId: $this->sarId,
            partyId: $this->partyId,
            status: $this->status,
            type: $this->type,
            narrative: $narrative,
            totalAmount: $this->totalAmount,
            currency: $this->currency,
            activityStartDate: $this->activityStartDate,
            activityEndDate: $this->activityEndDate,
            transactionIds: $this->transactionIds,
            suspiciousActivities: $this->suspiciousActivities,
            alerts: $this->alerts,
            filingReference: $this->filingReference,
            assignedOfficer: $this->assignedOfficer,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            submittedAt: $this->submittedAt,
            closedAt: $this->closedAt,
            closureReason: $this->closureReason,
            subjectInfo: $this->subjectInfo,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a copy with updated metadata (used for tracking 'updated_by')
     */
    public function withUpdatedBy(string $updatedBy): self
    {
        $metadata = $this->metadata;
        $metadata['updated_by'] = $updatedBy;
        $metadata['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        return new self(
            sarId: $this->sarId,
            partyId: $this->partyId,
            status: $this->status,
            type: $this->type,
            narrative: $this->narrative,
            totalAmount: $this->totalAmount,
            currency: $this->currency,
            activityStartDate: $this->activityStartDate,
            activityEndDate: $this->activityEndDate,
            transactionIds: $this->transactionIds,
            suspiciousActivities: $this->suspiciousActivities,
            alerts: $this->alerts,
            filingReference: $this->filingReference,
            assignedOfficer: $this->assignedOfficer,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            submittedAt: $this->submittedAt,
            closedAt: $this->closedAt,
            closureReason: $this->closureReason,
            subjectInfo: $this->subjectInfo,
            metadata: $metadata,
        );
    }

    /**
     * Create a copy with assigned officer
     */
    public function withAssignedOfficer(string $officerId): self
    {
        return $this->assignOfficer($officerId);
    }

    /**
     * Create a copy with rejection reason (for rejected SARs)
     */
    public function withRejectionReason(string $reason): self
    {
        $metadata = $this->metadata;
        $metadata['rejection_reason'] = $reason;
        $metadata['rejected_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        return new self(
            sarId: $this->sarId,
            partyId: $this->partyId,
            status: $this->status,
            type: $this->type,
            narrative: $this->narrative,
            totalAmount: $this->totalAmount,
            currency: $this->currency,
            activityStartDate: $this->activityStartDate,
            activityEndDate: $this->activityEndDate,
            transactionIds: $this->transactionIds,
            suspiciousActivities: $this->suspiciousActivities,
            alerts: $this->alerts,
            filingReference: $this->filingReference,
            assignedOfficer: $this->assignedOfficer,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            submittedAt: $this->submittedAt,
            closedAt: $this->closedAt,
            closureReason: $this->closureReason,
            subjectInfo: $this->subjectInfo,
            metadata: $metadata,
        );
    }

    /**
     * Create a copy with cancellation reason (for cancelled SARs)
     */
    public function withCancellationReason(string $reason): self
    {
        $metadata = $this->metadata;
        $metadata['cancellation_reason'] = $reason;
        $metadata['cancelled_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        return new self(
            sarId: $this->sarId,
            partyId: $this->partyId,
            status: $this->status,
            type: $this->type,
            narrative: $this->narrative,
            totalAmount: $this->totalAmount,
            currency: $this->currency,
            activityStartDate: $this->activityStartDate,
            activityEndDate: $this->activityEndDate,
            transactionIds: $this->transactionIds,
            suspiciousActivities: $this->suspiciousActivities,
            alerts: $this->alerts,
            filingReference: $this->filingReference,
            assignedOfficer: $this->assignedOfficer,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            submittedAt: $this->submittedAt,
            closedAt: $this->closedAt,
            closureReason: $this->closureReason,
            subjectInfo: $this->subjectInfo,
            metadata: $metadata,
        );
    }

    /**
     * Check if SAR has been submitted
     */
    public function isSubmitted(): bool
    {
        return $this->status === SarStatus::SUBMITTED;
    }

    /**
     * Check if SAR is closed
     */
    public function isClosed(): bool
    {
        return $this->status === SarStatus::CLOSED || $this->status === SarStatus::CANCELLED;
    }

    /**
     * Get duration of suspicious activity in days
     */
    public function getActivityDurationDays(): int
    {
        if ($this->activityStartDate === null || $this->activityEndDate === null) {
            return 0;
        }
        return $this->activityStartDate->diff($this->activityEndDate)->days;
    }

    /**
     * Convert to array for serialization
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sar_id' => $this->sarId,
            'party_id' => $this->partyId,
            'status' => $this->status->value,
            'status_description' => $this->status->getDescription(),
            'is_editable' => $this->isEditable(),
            'is_pending' => $this->isPending(),
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'narrative' => $this->narrative,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'activity_start_date' => $this->activityStartDate->format(\DateTimeInterface::ATOM),
            'activity_end_date' => $this->activityEndDate->format(\DateTimeInterface::ATOM),
            'activity_period_days' => $this->getActivityPeriodDays(),
            'transaction_ids' => $this->transactionIds,
            'transaction_count' => count($this->transactionIds),
            'suspicious_activities' => $this->suspiciousActivities,
            'alerts' => array_map(fn(TransactionAlert $a) => $a->toArray(), $this->alerts),
            'alert_count' => count($this->alerts),
            'filing_reference' => $this->filingReference,
            'assigned_officer' => $this->assignedOfficer,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'submitted_at' => $this->submittedAt?->format(\DateTimeInterface::ATOM),
            'closed_at' => $this->closedAt?->format(\DateTimeInterface::ATOM),
            'closure_reason' => $this->closureReason,
            'is_overdue' => $this->isOverdue(),
            'days_until_deadline' => $this->getDaysUntilDeadline(),
            'subject_info' => $this->subjectInfo,
            'metadata' => $this->metadata,
        ];
    }
}
