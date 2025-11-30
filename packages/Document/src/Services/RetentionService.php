<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\AuditLogger\Services\AuditLogManager;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\RetentionPolicyInterface;
use Nexus\Document\Exceptions\RetentionPolicyViolationException;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Psr\Log\LoggerInterface;

/**
 * Retention service.
 *
 * Manages compliance-aware document retention and purging.
 */
final readonly class RetentionService
{
    public function __construct(
        private DocumentRepositoryInterface $repository,
        private RetentionPolicyInterface $retentionPolicy,
        private StorageDriverInterface $storage,
        private AuditLogManager $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Purge expired soft-deleted documents.
     *
     * @return int Number of documents purged
     */
    public function purgeExpiredDocuments(): int
    {
        $deletedDocuments = $this->repository->getDeleted();
        $purgedCount = 0;
        $freedBytes = 0;

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

                // Permanently delete from storage
                $this->storage->delete($document->getStoragePath());

                // Delete from database (hard delete)
                // This would require a forceDelete method in repository
                // For now, log the action

                $purgedCount++;
                $freedBytes += $document->getFileSize();

                $this->logger->info('Document purged', [
                    'document_id' => $document->getId(),
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
                causerType: 'System',
                causerId: 'retention_service',
                properties: [
                    'purged_count' => $purgedCount,
                    'freed_bytes' => $freedBytes,
                    'freed_mb' => round($freedBytes / 1024 / 1024, 2),
                ],
                level: 3
            );
        }

        return $purgedCount;
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
            throw new \Nexus\Document\Exceptions\DocumentNotFoundException($documentId);
        }

        // Check for legal hold
        if ($this->retentionPolicy->hasLegalHold($documentId)) {
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
            throw new \Nexus\Document\Exceptions\DocumentNotFoundException($documentId);
        }

        // Check if document should be archived
        if ($this->retentionPolicy->isExpired(
            $document->getCreatedAt(),
            $document->getType()->value
        )) {
            // Auto-archive logic
            if ($document->getState() !== \Nexus\Document\ValueObjects\DocumentState::ARCHIVED) {
                $document->setState(\Nexus\Document\ValueObjects\DocumentState::ARCHIVED);
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
}
