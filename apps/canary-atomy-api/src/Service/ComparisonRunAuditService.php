<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\QuoteComparisonRun;
use Nexus\Audit\Contracts\AuditEngineInterface;
use Nexus\Audit\ValueObjects\AuditLevel;

/**
 * Records immutable audit entries for comparison run lifecycle events.
 *
 * Uses the cryptographic hash-chained AuditEngine from Nexus\Audit (L1)
 * to ensure every run creation, save, discard, and approval is traceable.
 */
final readonly class ComparisonRunAuditService
{
    private const SUBJECT_TYPE = 'QuoteComparisonRun';
    private const RECORD_TYPE_CREATED = 'comparison_run.created';
    private const RECORD_TYPE_PREVIEW = 'comparison_run.preview';
    private const RECORD_TYPE_SAVED = 'comparison_run.saved';
    private const RECORD_TYPE_DISCARDED = 'comparison_run.discarded';
    private const RECORD_TYPE_APPROVED = 'comparison_run.approved';
    private const RECORD_TYPE_REJECTED = 'comparison_run.rejected';
    private const RECORD_TYPE_STALE = 'comparison_run.stale';

    private const SYSTEM_CAUSER_TYPE = 'System';
    private const SYSTEM_CAUSER_ID = 'cleanup-worker';

    public function __construct(
        private AuditEngineInterface $auditEngine,
    ) {
    }

    public function logCreated(QuoteComparisonRun $run): void
    {
        $recordType = $run->isPreview() ? self::RECORD_TYPE_PREVIEW : self::RECORD_TYPE_CREATED;
        $description = $run->isPreview()
            ? sprintf('Preview comparison run created for RFQ "%s".', $run->getRfqId())
            : sprintf('Comparison run "%s" created for RFQ "%s".', $run->getName(), $run->getRfqId());

        $this->auditEngine->logSync(
            tenantId: $run->getTenantId(),
            recordType: $recordType,
            description: $description,
            subjectType: self::SUBJECT_TYPE,
            subjectId: (string) $run->getId(),
            causerType: 'User',
            causerId: $run->getCreatedBy(),
            properties: [
                'rfq_id' => $run->getRfqId(),
                'status' => $run->getStatus(),
                'is_preview' => $run->isPreview(),
            ],
            level: AuditLevel::Medium,
        );
    }

    public function logSaved(QuoteComparisonRun $run): void
    {
        $this->auditEngine->logSync(
            tenantId: $run->getTenantId(),
            recordType: self::RECORD_TYPE_SAVED,
            description: sprintf('Comparison run "%s" saved for RFQ "%s".', $run->getName(), $run->getRfqId()),
            subjectType: self::SUBJECT_TYPE,
            subjectId: (string) $run->getId(),
            causerType: 'User',
            causerId: $run->getCreatedBy(),
            properties: [
                'rfq_id' => $run->getRfqId(),
                'name' => $run->getName(),
                'description' => $run->getDescription(),
                'expires_at' => $run->getExpiresAt()?->format(\DATE_ATOM),
                'status' => $run->getStatus(),
            ],
            level: AuditLevel::High,
        );
    }

    public function logDiscarded(QuoteComparisonRun $run): void
    {
        $this->auditEngine->logSync(
            tenantId: $run->getTenantId(),
            recordType: self::RECORD_TYPE_DISCARDED,
            description: sprintf(
                'Comparison run "%s" discarded by "%s" for RFQ "%s".',
                $run->getName(),
                $run->getDiscardedBy() ?? 'unknown',
                $run->getRfqId(),
            ),
            subjectType: self::SUBJECT_TYPE,
            subjectId: (string) $run->getId(),
            causerType: 'User',
            causerId: $run->getDiscardedBy(),
            properties: [
                'rfq_id' => $run->getRfqId(),
                'discarded_at' => $run->getDiscardedAt()?->format(\DATE_ATOM),
            ],
            level: AuditLevel::High,
        );
    }

    public function logApproval(QuoteComparisonRun $run, string $decision, string $decidedBy, string $reason): void
    {
        $recordType = $decision === 'approve' ? self::RECORD_TYPE_APPROVED : self::RECORD_TYPE_REJECTED;

        $this->auditEngine->logSync(
            tenantId: $run->getTenantId(),
            recordType: $recordType,
            description: sprintf(
                'Comparison run "%s" %s by "%s": %s',
                $run->getName(),
                $decision === 'approve' ? 'approved' : 'rejected',
                $decidedBy,
                $reason,
            ),
            subjectType: self::SUBJECT_TYPE,
            subjectId: (string) $run->getId(),
            causerType: 'User',
            causerId: $decidedBy,
            properties: [
                'rfq_id' => $run->getRfqId(),
                'decision' => $decision,
                'reason' => $reason,
                'status' => $run->getStatus(),
            ],
            level: AuditLevel::Critical,
        );
    }

    public function logStale(QuoteComparisonRun $run, string $reason): void
    {
        $this->auditEngine->logAsync(
            tenantId: $run->getTenantId(),
            recordType: self::RECORD_TYPE_STALE,
            description: sprintf('Comparison run "%s" marked stale: %s', $run->getName(), $reason),
            subjectType: self::SUBJECT_TYPE,
            subjectId: (string) $run->getId(),
            causerType: self::SYSTEM_CAUSER_TYPE,
            causerId: self::SYSTEM_CAUSER_ID,
            properties: [
                'rfq_id' => $run->getRfqId(),
                'reason' => $reason,
            ],
            level: AuditLevel::Medium,
        );
    }
}
