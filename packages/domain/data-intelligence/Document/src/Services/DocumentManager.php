<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\AuditLogger\Services\AuditLogManager;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Document\Contracts\ContentProcessorInterface;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\PermissionCheckerInterface;
use Nexus\Document\Core\PathGenerator;
use Nexus\Document\Exceptions\ChecksumMismatchException;
use Nexus\Document\Exceptions\PermissionDeniedException;
use Nexus\Document\Exceptions\StorageException;
use Nexus\Document\ValueObjects\ContentAnalysisResult;
use Nexus\Document\ValueObjects\DocumentState;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Nexus\Storage\ValueObjects\Visibility;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Document manager service.
 *
 * Orchestrates document upload, download, deletion, and state management.
 * Integrates with Storage, Crypto, AuditLogger, and optional ContentProcessor.
 */
final readonly class DocumentManager
{
    public function __construct(
        private DocumentRepositoryInterface $repository,
        private StorageDriverInterface $storage,
        private PermissionCheckerInterface $permissions,
        private ContentProcessorInterface $contentProcessor,
        private AuditLogManager $auditLogger,
        private PathGenerator $pathGenerator,
        private HasherInterface $hasher,
        private TenantContextInterface $tenantContext,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Upload a new document.
     *
     * @param resource $stream File stream
     * @param array{
     *     type?: \Nexus\Document\ValueObjects\DocumentType,
     *     original_filename: string,
     *     mime_type: string,
     *     tags?: array<string>,
     *     custom_fields?: array<string, mixed>
     * } $metadata Document metadata
     * @param string $ownerId Owner user ULID
     * @param bool $autoAnalyze Whether to perform ML content analysis
     * @return DocumentInterface Created document
     * @throws StorageException If storage operation fails
     */
    public function upload(
        $stream,
        array $metadata,
        string $ownerId,
        bool $autoAnalyze = false
    ): DocumentInterface {
        // Generate unique identifiers
        $documentId = (string) new Ulid();
        $tenantId = $this->tenantContext->requireTenant();

        // Extract extension from filename
        $extension = pathinfo($metadata['original_filename'], PATHINFO_EXTENSION);

        // Generate S3-optimized storage path
        $storagePath = $this->pathGenerator->generateStoragePath(
            $tenantId,
            $documentId,
            1, // Initial version
            $extension
        );

        // Read stream content for checksum calculation
        $content = stream_get_contents($stream);
        if ($content === false) {
            throw new StorageException($storagePath, 'read', 'Failed to read stream content');
        }

        // Calculate SHA-256 checksum
        $checksum = $this->hasher->hash($content);

        // Store file with private visibility
        try {
            rewind($stream); // Reset stream pointer
            $this->storage->put($storagePath, $stream, Visibility::Private);
        } catch (\Throwable $e) {
            $this->logger->error('Document upload failed: storage error', [
                'document_id' => $documentId,
                'storage_path' => $storagePath,
                'error' => $e->getMessage(),
            ]);
            throw new StorageException($storagePath, 'put', 'Storage operation failed', 0, $e);
        }

        // Optional: Auto-classify document using ML
        $analysis = null;
        if ($autoAnalyze) {
            try {
                $analysis = $this->contentProcessor->analyze($storagePath);

                // Use predicted type if reliable and not already specified
                if (!isset($metadata['type']) && $analysis->hasPrediction() && $analysis->isReliable()) {
                    $metadata['type'] = $analysis->predictedType;
                }

                // Merge extracted metadata
                if (!empty($analysis->extractedMetadata)) {
                    $metadata['custom_fields'] = array_merge(
                        $metadata['custom_fields'] ?? [],
                        $analysis->extractedMetadata
                    );
                }

                // Add suggested tags
                if (!empty($analysis->suggestedTags)) {
                    $metadata['tags'] = array_unique(array_merge(
                        $metadata['tags'] ?? [],
                        $analysis->suggestedTags
                    ));
                }
            } catch (\Throwable $e) {
                // Log but don't fail upload if analysis fails
                $this->logger->warning('Document auto-analysis failed', [
                    'document_id' => $documentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Create document entity (implementation in Atomy will handle this)
        $document = $this->repository->create([
            'id' => $documentId,
            'tenant_id' => $tenantId,
            'owner_id' => $ownerId,
            'type' => $metadata['type'] ?? \Nexus\Document\ValueObjects\DocumentType::OTHER,
            'state' => DocumentState::DRAFT,
            'storage_path' => $storagePath,
            'checksum' => $checksum,
            'mime_type' => $metadata['mime_type'],
            'file_size' => strlen($content),
            'original_filename' => $metadata['original_filename'],
            'version' => 1,
            'metadata' => [
                'tags' => $metadata['tags'] ?? [],
                'custom_fields' => $metadata['custom_fields'] ?? [],
            ],
        ]);

        $this->repository->save($document);

        // Audit log
        $this->auditLogger->log(
            logName: 'document_uploaded',
            description: "Document '{$metadata['original_filename']}' uploaded",
            subjectType: 'Document',
            subjectId: $documentId,
            causerType: 'User',
            causerId: $ownerId,
            properties: [
                'type' => $document->getType()->value,
                'file_size' => $document->getFileSize(),
                'mime_type' => $document->getMimeType(),
                'auto_analyzed' => $autoAnalyze,
                'analysis_confidence' => $analysis?->confidenceScore,
            ],
            level: 2
        );

        return $document;
    }

    /**
     * Upload multiple documents in batch.
     *
     * @param array<array{stream: resource, metadata: array}> $files Array of files to upload
     * @param string $ownerId Owner user ULID
     * @return array<DocumentInterface> Created documents
     */
    public function uploadBatch(array $files, string $ownerId): array
    {
        $documents = [];
        $totalSize = 0;

        foreach ($files as $file) {
            try {
                $document = $this->upload($file['stream'], $file['metadata'], $ownerId);
                $documents[] = $document;
                $totalSize += $document->getFileSize();
            } catch (\Throwable $e) {
                $this->logger->error('Batch upload: file failed', [
                    'filename' => $file['metadata']['original_filename'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                // Continue with next file
            }
        }

        // Single audit log for batch
        $this->auditLogger->log(
            logName: 'documents_batch_uploaded',
            description: sprintf('%d documents uploaded in batch', count($documents)),
            subjectType: 'Batch',
            subjectId: $this->generateUlid(),
            causerType: 'User',
            causerId: $ownerId,
            properties: [
                'count' => count($documents),
                'total_size' => $totalSize,
            ],
            level: 2
        );

        return $documents;
    }

    /**
     * Download a document with permission check and checksum verification.
     *
     * @param string $documentId Document ULID
     * @param string $userId User ULID
     * @return resource File stream
     * @throws PermissionDeniedException If user lacks view permission
     * @throws ChecksumMismatchException If checksum verification fails
     */
    public function download(string $documentId, string $userId)
    {
        // Permission check
        if (!$this->permissions->canView($userId, $documentId)) {
            throw new PermissionDeniedException($userId, $documentId, 'view');
        }

        $document = $this->repository->findById($documentId);
        if (!$document) {
            throw new \Nexus\Document\Exceptions\DocumentNotFoundException($documentId);
        }

        // Retrieve stream from storage
        $stream = $this->storage->get($document->getStoragePath());

        // Verify checksum integrity
        $content = stream_get_contents($stream);
        $actualChecksum = $this->hasher->hash($content);

        if ($actualChecksum !== $document->getChecksum()) {
            $this->logger->critical('Checksum mismatch detected - possible data corruption', [
                'document_id' => $documentId,
                'expected' => $document->getChecksum(),
                'actual' => $actualChecksum,
            ]);

            throw new ChecksumMismatchException(
                $documentId,
                $document->getChecksum(),
                $actualChecksum
            );
        }

        // Reset stream pointer for consumer
        rewind($stream);

        // Audit log
        $this->auditLogger->log(
            logName: 'document_downloaded',
            description: "Document '{$document->getOriginalFilename()}' downloaded",
            subjectType: 'Document',
            subjectId: $documentId,
            causerType: 'User',
            causerId: $userId,
            properties: [
                'file_size' => $document->getFileSize(),
            ],
            level: 1
        );

        return $stream;
    }

    /**
     * Download document without checksum verification (for trusted internal use).
     *
     * ⚠️ WARNING: Use only for preview generation or trusted internal operations.
     * Always use download() for user-facing downloads.
     *
     * @param string $documentId Document ULID
     * @param string $userId User ULID
     * @return resource File stream
     */
    public function downloadWithoutVerification(string $documentId, string $userId)
    {
        if (!$this->permissions->canView($userId, $documentId)) {
            throw new PermissionDeniedException($userId, $documentId, 'view');
        }

        $document = $this->repository->findById($documentId);
        if (!$document) {
            throw new \Nexus\Document\Exceptions\DocumentNotFoundException($documentId);
        }

        return $this->storage->get($document->getStoragePath());
    }

    /**
     * Generate a temporary download URL with permission check.
     *
     * @param string $documentId Document ULID
     * @param string $userId User ULID
     * @param int $ttl Time-to-live in seconds (default: 1 hour)
     * @return string Signed temporary URL
     * @throws PermissionDeniedException If user lacks view permission
     */
    public function getTemporaryDownloadUrl(string $documentId, string $userId, int $ttl = 3600): string
    {
        // Permission check first
        if (!$this->permissions->canView($userId, $documentId)) {
            throw new PermissionDeniedException($userId, $documentId, 'view');
        }

        $document = $this->repository->findById($documentId);
        if (!$document) {
            throw new \Nexus\Document\Exceptions\DocumentNotFoundException($documentId);
        }

        // Delegate to Storage driver
        $url = $this->storage->getTemporaryUrl($document->getStoragePath(), $ttl);

        // Audit log
        $this->auditLogger->log(
            logName: 'document_url_generated',
            description: "Temporary URL generated for '{$document->getOriginalFilename()}'",
            subjectType: 'Document',
            subjectId: $documentId,
            causerType: 'User',
            causerId: $userId,
            properties: [
                'ttl' => $ttl,
            ],
            level: 1
        );

        return $url;
    }

    /**
     * Soft delete a document.
     *
     * @param string $documentId Document ULID
     * @param string $userId User ULID
     * @throws PermissionDeniedException If user lacks delete permission
     */
    public function delete(string $documentId, string $userId): void
    {
        if (!$this->permissions->canDelete($userId, $documentId)) {
            throw new PermissionDeniedException($userId, $documentId, 'delete');
        }

        $document = $this->repository->findById($documentId);
        if (!$document) {
            throw new \Nexus\Document\Exceptions\DocumentNotFoundException($documentId);
        }

        // Soft delete
        $this->repository->delete($documentId);

        // Audit log
        $this->auditLogger->log(
            logName: 'document_deleted',
            description: "Document '{$document->getOriginalFilename()}' deleted",
            subjectType: 'Document',
            subjectId: $documentId,
            causerType: 'User',
            causerId: $userId,
            properties: [
                'type' => $document->getType()->value,
                'file_size' => $document->getFileSize(),
            ],
            level: 3
        );
    }

    /**
     * Update document state with transition validation.
     *
     * @param string $documentId Document ULID
     * @param DocumentState $newState New state
     * @param string $userId User ULID
     * @throws PermissionDeniedException If user lacks edit permission
     * @throws \InvalidArgumentException If state transition is invalid
     */
    public function updateState(string $documentId, DocumentState $newState, string $userId): void
    {
        if (!$this->permissions->canEdit($userId, $documentId)) {
            throw new PermissionDeniedException($userId, $documentId, 'edit');
        }

        $document = $this->repository->findById($documentId);
        if (!$document) {
            throw new \Nexus\Document\Exceptions\DocumentNotFoundException($documentId);
        }

        $currentState = $document->getState();

        // Validate state transition
        if (!in_array($newState, $currentState->allowedTransitions(), true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid state transition from %s to %s',
                    $currentState->value,
                    $newState->value
                )
            );
        }

        // Update state (implementation in Atomy model)
        $document->setState($newState);
        $this->repository->save($document);

        // Audit log
        $this->auditLogger->log(
            logName: 'document_state_changed',
            description: sprintf(
                "Document '{$document->getOriginalFilename()}' state changed from %s to %s",
                $currentState->label(),
                $newState->label()
            ),
            subjectType: 'Document',
            subjectId: $documentId,
            causerType: 'User',
            causerId: $userId,
            properties: [
                'old_state' => $currentState->value,
                'new_state' => $newState->value,
            ],
            level: 2
        );
    }
}
