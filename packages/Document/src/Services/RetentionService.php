<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\AuditLogger\Services\AuditLogManager;
use Nexus\Document\Contracts\DisposalCertificationInterface;
use Nexus\Document\Contracts\DisposalCertificationRepositoryInterface;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\LegalHoldInterface;
use Nexus\Document\Contracts\LegalHoldRepositoryInterface;
use Nexus\Document\Contracts\RetentionPolicyInterface;
use Nexus\Document\Exceptions\DocumentNotFoundException;
use Nexus\Document\Exceptions\RetentionPolicyViolationException;
use Nexus\Document\ValueObjects\DocumentState;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Retention service.
 *
 * Manages compliance-aware document retention, legal holds, and disposal.
 * Provides full lifecycle management for document retention compliance.
 */
final readonly class RetentionService
{
    public function __construct(
        private DocumentRepositoryInterface $repository,
        private RetentionPolicyInterface $retentionPolicy,
        private LegalHoldRepositoryInterface $legalHoldRepository,
        private DisposalCertificationRepositoryInterface $disposalCertificationRepository,
        private StorageDriverInterface $storage,
        private TenantContextInterface $tenantContext,
        private AuditLogManager $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Purge expired soft-deleted documents with full certification.
     *
     * @param string $disposedBy User performing the disposal
     * @param string|null $approvedBy User who approved (for dual-control)
     * @return array{purged_count: int, freed_bytes: int, certifications: array<string>}
     */
    public function purgeExpiredDocuments(
        string $disposedBy,
        ?string $approvedBy = null
    ): array {
        $deletedDocuments = $this->repository->getDeleted();
        $purgedCount = 0;
        $freedBytes = 0;
        $certificationIds = [];

        foreach ($deletedDocuments as $document) {
            try {
                // Check if document can be purged
                if (!$this->retentionPolicy->canPurge($document->getId())) {
                    continue;
                }

                // Check retention period
                if (!$this->retentionPolicy->isExpired(
                    $document->getCreatedAt(),
                    $document->getType()->value
                )) {
                    continue;
                }

                // Dispose document with certification
                $certification = $this->disposeDocument(
                    document: $document,
                    disposalMethod: $this->retentionPolicy->getDisposalMethod($document->getType()->value),
                    disposedBy: $disposedBy,
                    approvedBy: $approvedBy,
                    reason: 'Retention period expired - automated purge'
                );

                $certificationIds[] = $certification->getId();
                $purgedCount++;
                $freedBytes += $document->getFileSize();

                $this->logger->info('Document purged with certification', [
                    'document_id' => $document->getId(),
                    'certification_id' => $certification->getId(),
                    'type' => $document->getType()->value,
                    'file_size' => $document->getFileSize(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to purge document', [
                    'document_id' => $document->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Audit log
        if ($purgedCount > 0) {
            $this->auditLogger->log(
                logName: 'documents_purged',
                description: "Purged {$purgedCount} expired documents",
                subjectType: 'System',
                subjectId: 'retention_service',
                causerType: 'User',
                causerId: $disposedBy,
                properties: [
                    'purged_count' => $purgedCount,
                    'freed_bytes' => $freedBytes,
                    'freed_mb' => round($freedBytes / 1024 / 1024, 2),
                    'certification_ids' => $certificationIds,
                ],
                level: 3
            );
        }

        return [
            'purged_count' => $purgedCount,
            'freed_bytes' => $freedBytes,
            'certifications' => $certificationIds,
        ];
    }

    /**
     * Dispose a single document with full compliance certification.
     *
     * @param DocumentInterface $document Document to dispose
     * @param string $disposalMethod Disposal method (SECURE_DELETE, ARCHIVE, ANONYMIZE)
     * @param string $disposedBy User performing disposal
     * @param string|null $approvedBy User who approved (for dual-control)
     * @param string $reason Reason for disposal
     * @param string|null $witnessedBy Optional witness for compliance
     * @return DisposalCertificationInterface
     * @throws RetentionPolicyViolationException If document cannot be disposed
     */
    public function disposeDocument(
        DocumentInterface $document,
        string $disposalMethod,
        string $disposedBy,
        ?string $approvedBy = null,
        string $reason = 'Retention period expired',
        ?string $witnessedBy = null
    ): DisposalCertificationInterface {
        $documentId = $document->getId();

        // Verify no active legal hold
        if ($this->legalHoldRepository->hasActiveHold($documentId)) {
            throw new RetentionPolicyViolationException(
                $documentId,
                'Document is under legal hold and cannot be disposed'
            );
        }

        // Calculate retention expiration
        $retentionDays = $this->retentionPolicy->getRetentionDays($document->getType()->value);
        $retentionExpiredAt = $this->retentionPolicy->getExpirationDate(
            $document->getCreatedAt(),
            $document->getType()->value
        );

        // Permanently delete from storage
        $this->storage->delete($document->getStoragePath());

        // Force delete from database
        $this->repository->forceDelete($documentId);

        // Create disposal certification
        $certification = $this->disposalCertificationRepository->create([
            'id' => (string) new Ulid(),
            'tenant_id' => $this->tenantContext->requireTenant(),
            'document_id' => $documentId,
            'document_type' => $document->getType()->value,
            'document_name' => $document->getOriginalFilename(),
            'disposal_method' => $disposalMethod,
            'disposed_by' => $disposedBy,
            'disposed_at' => new \DateTimeImmutable(),
            'approved_by' => $approvedBy,
            'approved_at' => $approvedBy ? new \DateTimeImmutable() : null,
            'disposal_reason' => $reason,
            'document_created_at' => $document->getCreatedAt(),
            'retention_period_days' => $retentionDays,
            'retention_expired_at' => $retentionExpiredAt,
            'legal_hold_verified' => true,
            'document_checksum' => $document->getChecksum(),
            'regulatory_basis' => $this->retentionPolicy->getRegulatoryBasis($document->getType()->value),
            'witnessed_by' => $witnessedBy,
            'metadata' => $document->getMetadata(),
            'chain_of_custody' => [
                [
                    'action' => 'disposed',
                    'by' => $disposedBy,
                    'at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                    'notes' => $reason,
                ],
            ],
        ]);

        $this->disposalCertificationRepository->save($certification);

        // Audit log
        $this->auditLogger->log(
            logName: 'document_disposed',
            description: "Document '{$document->getOriginalFilename()}' disposed with certification",
            subjectType: 'Document',
            subjectId: $documentId,
            causerType: 'User',
            causerId: $disposedBy,
            properties: [
                'certification_id' => $certification->getId(),
                'disposal_method' => $disposalMethod,
                'regulatory_basis' => $this->retentionPolicy->getRegulatoryBasis($document->getType()->value),
            ],
            level: 3
        );

        return $certification;
    }

    /**
     * Apply a legal hold to a document.
     *
     * @param string $documentId Document ULID
     * @param string $reason Reason for the hold
     * @param string $appliedBy User applying the hold
     * @param string|null $matterReference Legal matter/case reference
     * @param \DateTimeInterface|null $expiresAt Optional expiration date
     * @return LegalHoldInterface
     * @throws DocumentNotFoundException If document not found
     */
    public function applyLegalHold(
        string $documentId,
        string $reason,
        string $appliedBy,
        ?string $matterReference = null,
        ?\DateTimeInterface $expiresAt = null
    ): LegalHoldInterface {
        $document = $this->repository->findById($documentId);
        if (!$document) {
            throw new DocumentNotFoundException($documentId);
        }

        $legalHold = $this->legalHoldRepository->create([
            'id' => (string) new Ulid(),
            'tenant_id' => $this->tenantContext->requireTenant(),
            'document_id' => $documentId,
            'reason' => $reason,
            'matter_reference' => $matterReference,
            'applied_by' => $appliedBy,
            'applied_at' => new \DateTimeImmutable(),
            'expires_at' => $expiresAt,
            'metadata' => [],
        ]);

        $this->legalHoldRepository->save($legalHold);

        // Audit log
        $this->auditLogger->log(
            logName: 'legal_hold_applied',
            description: "Legal hold applied to document '{$document->getOriginalFilename()}'",
            subjectType: 'Document',
            subjectId: $documentId,
            causerType: 'User',
            causerId: $appliedBy,
            properties: [
                'hold_id' => $legalHold->getId(),
                'matter_reference' => $matterReference,
                'expires_at' => $expiresAt?->format(\DateTimeInterface::ATOM),
            ],
            level: 3
        );

        return $legalHold;
    }

    /**
     * Release a legal hold from a document.
     *
     * @param string $holdId Legal hold ULID
     * @param string $releasedBy User releasing the hold
     * @param string|null $releaseReason Reason for release
     * @return LegalHoldInterface
     */
    public function releaseLegalHold(
        string $holdId,
        string $releasedBy,
        ?string $releaseReason = null
    ): LegalHoldInterface {
        $legalHold = $this->legalHoldRepository->findById($holdId);
        if (!$legalHold) {
            throw new \InvalidArgumentException("Legal hold not found: {$holdId}");
        }

        // Update the hold with release information
        // Note: The consuming application must implement the update logic
        // This is a contract-driven approach
        $updatedHold = $this->legalHoldRepository->create([
            'id' => $legalHold->getId(),
            'tenant_id' => $legalHold->getTenantId(),
            'document_id' => $legalHold->getDocumentId(),
            'reason' => $legalHold->getReason(),
            'matter_reference' => $legalHold->getMatterReference(),
            'applied_by' => $legalHold->getAppliedBy(),
            'applied_at' => $legalHold->getAppliedAt(),
            'released_by' => $releasedBy,
            'released_at' => new \DateTimeImmutable(),
            'release_reason' => $releaseReason,
            'expires_at' => $legalHold->getExpiresAt(),
            'metadata' => $legalHold->getMetadata(),
        ]);

        $this->legalHoldRepository->save($updatedHold);

        // Audit log
        $this->auditLogger->log(
            logName: 'legal_hold_released',
            description: "Legal hold released from document",
            subjectType: 'LegalHold',
            subjectId: $holdId,
            causerType: 'User',
            causerId: $releasedBy,
            properties: [
                'document_id' => $legalHold->getDocumentId(),
                'release_reason' => $releaseReason,
            ],
            level: 3
        );

        return $updatedHold;
    }

    /**
     * Check if a document complies with retention policy for deletion.
     *
     * @param string $documentId Document ULID
     * @return bool True if document can be deleted
     * @throws RetentionPolicyViolationException If retention policy prevents deletion
     */
    public function checkRetentionCompliance(string $documentId): bool
    {
        $document = $this->repository->findById($documentId);
        if (!$document) {
            throw new DocumentNotFoundException($documentId);
        }

        // Check for legal hold
        if ($this->legalHoldRepository->hasActiveHold($documentId)) {
            throw new RetentionPolicyViolationException(
                $documentId,
                'Document is under legal hold and cannot be deleted'
            );
        }

        // Check retention period
        if (!$this->retentionPolicy->isExpired(
            $document->getCreatedAt(),
            $document->getType()->value
        )) {
            $retentionDays = $this->retentionPolicy->getRetentionDays($document->getType()->value);
            throw new RetentionPolicyViolationException(
                $documentId,
                "Document must be retained for {$retentionDays} days"
            );
        }

        return true;
    }

    /**
     * Apply retention policy to a document (auto-archive if threshold reached).
     *
     * @param string $documentId Document ULID
     */
    public function applyRetentionPolicy(string $documentId): void
    {
        $document = $this->repository->findById($documentId);
        if (!$document) {
            throw new DocumentNotFoundException($documentId);
        }

        // Check if document should be archived
        if ($this->retentionPolicy->isExpired(
            $document->getCreatedAt(),
            $document->getType()->value
        )) {
            // Auto-archive logic
            if ($document->getState() !== DocumentState::ARCHIVED) {
                $document->setState(DocumentState::ARCHIVED);
                $this->repository->save($document);

                $this->auditLogger->log(
                    logName: 'document_auto_archived',
                    description: "Document auto-archived by retention policy",
                    subjectType: 'Document',
                    subjectId: $documentId,
                    causerType: 'System',
                    causerId: 'retention_service',
                    properties: [
                        'type' => $document->getType()->value,
                    ],
                    level: 2
                );
            }
        }
    }

    /**
     * Get documents eligible for disposal.
     *
     * @param string|null $documentType Optional filter by type
     * @return array<DocumentInterface>
     */
    public function getDocumentsForDisposal(?string $documentType = null): array
    {
        // Get all document types if not specified
        $types = $documentType
            ? [\Nexus\Document\ValueObjects\DocumentType::tryFrom($documentType)]
            : \Nexus\Document\ValueObjects\DocumentType::cases();

        $eligibleDocuments = [];

        foreach ($types as $type) {
            if ($type === null) {
                continue;
            }

            $retentionDays = $this->retentionPolicy->getRetentionDays($type->value);
            $cutoffDate = (new \DateTimeImmutable())->modify("-{$retentionDays} days");

            $documents = $this->repository->findEligibleForDisposal($cutoffDate, $type);

            foreach ($documents as $document) {
                // Double-check no legal hold
                if (!$this->legalHoldRepository->hasActiveHold($document->getId())) {
                    $eligibleDocuments[] = $document;
                }
            }
        }

        return $eligibleDocuments;
    }

    /**
     * Generate a retention compliance report.
     *
     * @param \DateTimeInterface $periodStart Report period start
     * @param \DateTimeInterface $periodEnd Report period end
     * @return array{
     *     period: array{start: string, end: string},
     *     disposals: array{count: int, by_method: array<string, int>, by_type: array<string, int>},
     *     legal_holds: array{active: int, released: int},
     *     pending_disposal: int,
     *     compliance_rate: float
     * }
     */
    public function generateRetentionReport(
        \DateTimeInterface $periodStart,
        \DateTimeInterface $periodEnd
    ): array {
        $disposalStats = $this->disposalCertificationRepository->getStatistics($periodStart, $periodEnd);

        return [
            'period' => [
                'start' => $periodStart->format(\DateTimeInterface::ATOM),
                'end' => $periodEnd->format(\DateTimeInterface::ATOM),
            ],
            'disposals' => [
                'count' => $disposalStats['total_disposed'],
                'by_method' => $disposalStats['by_method'],
                'by_type' => $disposalStats['by_type'],
            ],
            'legal_holds' => [
                'active' => $this->legalHoldRepository->countActive(),
                'released' => 0, // Would need additional query
            ],
            'pending_disposal' => count($this->getDocumentsForDisposal()),
            'compliance_rate' => $disposalStats['total_disposed'] > 0
                ? ($disposalStats['compliant_count'] / $disposalStats['total_disposed']) * 100
                : 100.0,
        ];
    }
}
