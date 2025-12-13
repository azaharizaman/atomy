<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Audit;

use Nexus\ProcurementOperations\Enums\AuditFindingSeverity;
use Nexus\ProcurementOperations\Enums\ControlArea;

/**
 * DTO representing an audit finding record.
 */
final readonly class AuditFindingData
{
    /**
     * @param string $findingId Finding identifier
     * @param string $tenantId Tenant context
     * @param ControlArea $controlArea Affected control area
     * @param AuditFindingSeverity $severity Finding severity
     * @param string $title Finding title
     * @param string $description Detailed description
     * @param string $rootCause Root cause analysis
     * @param string $recommendation Remediation recommendation
     * @param string $recordedBy Auditor who recorded finding
     * @param \DateTimeImmutable $recordedAt When finding was recorded
     * @param \DateTimeImmutable $remediationDeadline Deadline for remediation
     * @param string|null $resolvedBy User who resolved (null if open)
     * @param \DateTimeImmutable|null $resolvedAt When resolved (null if open)
     * @param string|null $resolution Resolution description
     * @param array $supportingDocuments Supporting document references
     * @param array $metadata Additional finding metadata
     */
    public function __construct(
        public string $findingId,
        public string $tenantId,
        public ControlArea $controlArea,
        public AuditFindingSeverity $severity,
        public string $title,
        public string $description,
        public string $rootCause,
        public string $recommendation,
        public string $recordedBy,
        public \DateTimeImmutable $recordedAt,
        public \DateTimeImmutable $remediationDeadline,
        public ?string $resolvedBy = null,
        public ?\DateTimeImmutable $resolvedAt = null,
        public ?string $resolution = null,
        public array $supportingDocuments = [],
        public array $metadata = [],
    ) {}

    /**
     * Check if finding is open (unresolved).
     */
    public function isOpen(): bool
    {
        return $this->resolvedAt === null;
    }

    /**
     * Check if finding is resolved.
     */
    public function isResolved(): bool
    {
        return $this->resolvedAt !== null;
    }

    /**
     * Check if finding is overdue.
     */
    public function isOverdue(?\DateTimeImmutable $asOfDate = null): bool
    {
        if ($this->isResolved()) {
            return false;
        }

        $asOfDate ??= new \DateTimeImmutable();
        return $asOfDate > $this->remediationDeadline;
    }

    /**
     * Get days until deadline (negative if overdue).
     */
    public function getDaysUntilDeadline(?\DateTimeImmutable $asOfDate = null): int
    {
        $asOfDate ??= new \DateTimeImmutable();
        $diff = $asOfDate->diff($this->remediationDeadline);
        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Get days since finding was recorded.
     */
    public function getAgeDays(?\DateTimeImmutable $asOfDate = null): int
    {
        $asOfDate ??= new \DateTimeImmutable();
        return $asOfDate->diff($this->recordedAt)->days;
    }

    /**
     * Check if finding requires board notification.
     */
    public function requiresBoardNotification(): bool
    {
        return $this->severity->requiresBoardNotification();
    }

    /**
     * Check if finding requires public disclosure.
     */
    public function requiresDisclosure(): bool
    {
        return $this->severity->requiresDisclosure();
    }

    /**
     * Get status label.
     */
    public function getStatus(): string
    {
        if ($this->isResolved()) {
            return 'RESOLVED';
        }

        if ($this->isOverdue()) {
            return 'OVERDUE';
        }

        return 'OPEN';
    }

    /**
     * Create resolved copy of finding.
     */
    public function withResolution(
        string $resolution,
        string $resolvedBy,
        ?\DateTimeImmutable $resolvedAt = null,
        array $additionalDocuments = [],
    ): self {
        return new self(
            findingId: $this->findingId,
            tenantId: $this->tenantId,
            controlArea: $this->controlArea,
            severity: $this->severity,
            title: $this->title,
            description: $this->description,
            rootCause: $this->rootCause,
            recommendation: $this->recommendation,
            recordedBy: $this->recordedBy,
            recordedAt: $this->recordedAt,
            remediationDeadline: $this->remediationDeadline,
            resolvedBy: $resolvedBy,
            resolvedAt: $resolvedAt ?? new \DateTimeImmutable(),
            resolution: $resolution,
            supportingDocuments: array_merge($this->supportingDocuments, $additionalDocuments),
            metadata: $this->metadata,
        );
    }
}
