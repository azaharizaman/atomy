<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Services;

use Nexus\DataPrivacy\Contracts\BreachRecordManagerInterface;
use Nexus\DataPrivacy\Contracts\BreachRecordQueryInterface;
use Nexus\DataPrivacy\Contracts\BreachRecordPersistInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\Contracts\External\NotificationDispatcherInterface;
use Nexus\DataPrivacy\Contracts\External\StorageInterface;
use Nexus\DataPrivacy\ValueObjects\BreachRecord;
use Nexus\DataPrivacy\Enums\BreachSeverity;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Exceptions\BreachRecordNotFoundException;
use Nexus\DataPrivacy\Exceptions\InvalidBreachRecordException;

/**
 * Manages data breach incident lifecycle.
 */
final readonly class BreachRecordManager implements BreachRecordManagerInterface
{
    public function __construct(
        private BreachRecordQueryInterface $breachQuery,
        private BreachRecordPersistInterface $breachPersist,
        private ?AuditLoggerInterface $auditLogger = null,
        private ?NotificationDispatcherInterface $notifier = null,
        private ?StorageInterface $storage = null
    ) {
    }

    public function reportBreach(
        string $description,
        BreachSeverity $severity,
        int $recordsAffected,
        array $affectedCategories,
        \DateTimeImmutable $detectedAt,
        ?string $reportedBy = null
    ): BreachRecord {
        if ($recordsAffected < 0) {
            throw new InvalidBreachRecordException(
                'Records affected cannot be negative'
            );
        }

        if (empty($affectedCategories)) {
            throw new InvalidBreachRecordException(
                'At least one affected data category is required'
            );
        }

        $now = new \DateTimeImmutable();

        $breach = new BreachRecord(
            id: $this->generateBreachId(),
            title: 'Data Breach Incident',
            severity: $severity,
            discoveredAt: $detectedAt,
            occurredAt: $detectedAt,
            recordsAffected: $recordsAffected,
            dataCategories: $affectedCategories,
            description: $description,
            reportedBy: $reportedBy,
        );

        $breachId = $this->breachPersist->save($breach);

        $this->auditLogger?->logBreachDetected(
            $breachId,
            $severity->value,
            $recordsAffected
        );

        $this->notifier?->notifyTeamBreach(
            $breachId,
            $severity->value,
            $recordsAffected,
            $breach->requiresRegulatoryNotification()
        );

        return $breach;
    }

    public function getBreach(string $breachId): BreachRecord
    {
        $breach = $this->breachQuery->findById($breachId);

        if ($breach === null) {
            throw BreachRecordNotFoundException::withId($breachId);
        }

        return $breach;
    }

    public function getAllBreaches(): array
    {
        return $this->breachQuery->findAll();
    }

    public function getUnresolvedBreaches(): array
    {
        return $this->breachQuery->findUnresolved();
    }

    public function updateSeverity(
        string $breachId,
        BreachSeverity $newSeverity,
        string $reason
    ): BreachRecord {
        $breach = $this->getBreach($breachId);
        $oldSeverity = $breach->getSeverity();

        $updatedBreach = BreachRecord::create(
            id: $breachId,
            description: $breach->getDescription(),
            severity: $newSeverity,
            recordsAffected: $breach->getRecordsAffected(),
            affectedDataCategories: $breach->getAffectedDataCategories(),
            detectedAt: $breach->getDetectedAt()
        );

        $this->breachPersist->update($updatedBreach);

        $this->auditLogger?->log(
            'breach',
            $breachId,
            'severity_updated',
            "Severity changed from {$oldSeverity->value} to {$newSeverity->value}: {$reason}",
            [
                'old_severity' => $oldSeverity->value,
                'new_severity' => $newSeverity->value,
                'reason' => $reason,
            ]
        );

        return $updatedBreach;
    }

    public function updateRecordsAffected(
        string $breachId,
        int $recordsAffected
    ): BreachRecord {
        if ($recordsAffected < 0) {
            throw new InvalidBreachRecordException(
                'Records affected cannot be negative'
            );
        }

        return $this->breachPersist->updateRecordsAffected($breachId, $recordsAffected);
    }

    public function notifyRegulatoryAuthority(
        string $breachId,
        string $notificationDetails
    ): string {
        $breach = $this->getBreach($breachId);

        if (!$breach->requiresRegulatoryNotification()) {
            throw new InvalidBreachRecordException(
                'This breach does not require regulatory notification'
            );
        }

        if ($breach->isRegulatoryNotified()) {
            throw new InvalidBreachRecordException(
                'Regulatory authority has already been notified'
            );
        }

        $referenceNumber = $this->notifier?->notifyRegulatoryAuthority(
            $breachId,
            $notificationDetails,
            $breach->getRecordsAffected(),
            $breach->getAffectedDataCategories(),
            'Mitigation measures in place'
        ) ?? 'MANUAL-' . date('YmdHis');

        $this->markRegulatoryNotified($breachId, $referenceNumber);

        return $referenceNumber;
    }

    public function markRegulatoryNotified(
        string $breachId,
        string $referenceNumber
    ): BreachRecord {
        $breach = $this->getBreach($breachId);
        $notifiedAt = new \DateTimeImmutable();

        $notifiedBreach = $breach->markRegulatoryNotified($notifiedAt);
        $this->breachPersist->markRegulatoryNotified($breachId, $notifiedAt, $referenceNumber);

        $this->auditLogger?->logRegulatoryNotification(
            $breachId,
            'Data Protection Authority',
            $notifiedAt
        );

        return $notifiedBreach;
    }

    public function notifyAffectedIndividuals(
        string $breachId,
        string $notificationTemplate,
        array $additionalContext = []
    ): int {
        $breach = $this->getBreach($breachId);

        // This would integrate with a system that knows which individuals
        // were affected. For now, return the records affected count.
        $individualsNotified = $breach->getRecordsAffected();

        $this->markIndividualsNotified($breachId, $individualsNotified);

        return $individualsNotified;
    }

    public function markIndividualsNotified(
        string $breachId,
        int $individualsNotified
    ): BreachRecord {
        $notifiedAt = new \DateTimeImmutable();

        $updatedBreach = $this->breachPersist->markIndividualsNotified(
            $breachId,
            $notifiedAt,
            $individualsNotified
        );

        $this->auditLogger?->log(
            'breach',
            $breachId,
            'individuals_notified',
            "Notified {$individualsNotified} affected individuals",
            ['individuals_notified' => $individualsNotified]
        );

        return $updatedBreach;
    }

    public function resolveBreach(
        string $breachId,
        string $resolutionDetails
    ): BreachRecord {
        $breach = $this->getBreach($breachId);

        if ($breach->isResolved()) {
            throw new InvalidBreachRecordException('Breach is already resolved');
        }

        $resolvedAt = new \DateTimeImmutable();
        $resolvedBreach = $breach->markResolved($resolvedAt);

        $this->breachPersist->markResolved($breachId, $resolvedAt, $resolutionDetails);

        $this->auditLogger?->log(
            'breach',
            $breachId,
            'resolved',
            "Breach resolved: {$resolutionDetails}",
            ['resolution_details' => $resolutionDetails]
        );

        return $resolvedBreach;
    }

    public function addEvidence(
        string $breachId,
        string $evidenceContent,
        string $filename
    ): string {
        $breach = $this->getBreach($breachId);

        $reference = $this->storage?->storeBreachEvidence(
            $breachId,
            $evidenceContent,
            $filename
        ) ?? "evidence-{$breachId}-" . time();

        $this->breachPersist->addEvidence($breachId, $reference, $filename);

        $this->auditLogger?->log(
            'breach',
            $breachId,
            'evidence_added',
            "Evidence added: {$filename}",
            ['filename' => $filename, 'reference' => $reference]
        );

        return $reference;
    }

    public function getBreachesRequiringNotification(): array
    {
        return $this->breachQuery->findRequiringRegulatoryNotification();
    }

    public function getBreachesApproachingDeadline(int $withinHours = 24): array
    {
        $breaches = $this->breachQuery->findPendingRegulatoryNotification();

        return array_filter(
            $breaches,
            fn(BreachRecord $breach) => $breach->isRegulatoryDeadlineApproaching($withinHours)
        );
    }

    public function getBreachStatistics(): array
    {
        $stats = $this->breachQuery->getStatistics();

        // Calculate average resolution time
        $breaches = $this->breachQuery->findAll();
        $resolutionTimes = [];

        foreach ($breaches as $breach) {
            if ($breach->isResolved() && $breach->getResolvedAt() !== null) {
                $detectedAt = $breach->getDetectedAt();
                $resolvedAt = $breach->getResolvedAt();
                $diff = $detectedAt->diff($resolvedAt);
                $resolutionTimes[] = $diff->days;
            }
        }

        $avgResolutionDays = empty($resolutionTimes)
            ? 0.0
            : array_sum($resolutionTimes) / count($resolutionTimes);

        return [
            'total' => $stats['total'],
            'by_severity' => $stats['by_severity'],
            'resolved' => $stats['resolved'],
            'unresolved' => $stats['unresolved'],
            'average_resolution_days' => round($avgResolutionDays, 2),
            'total_records_affected' => $stats['total_records_affected'],
        ];
    }

    private function generateBreachId(): string
    {
        return 'BRH-' . date('Y') . '-' . strtoupper(substr(md5((string)microtime(true)), 0, 8));
    }
}
