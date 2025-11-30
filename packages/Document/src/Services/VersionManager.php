<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\AuditLogger\Services\AuditLogManager;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\DocumentVersionInterface;
use Nexus\Document\Contracts\DocumentVersionRepositoryInterface;
use Nexus\Document\Contracts\PermissionCheckerInterface;
use Nexus\Document\Core\PathGenerator;
use Nexus\Document\Exceptions\PermissionDeniedException;
use Nexus\Document\Exceptions\VersionNotFoundException;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Psr\Log\LoggerInterface;

/**
 * Version manager service.
 *
 * Manages document version creation, history, rollback, and pruning.
 */
final readonly class VersionManager
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private DocumentVersionRepositoryInterface $versionRepository,
        private StorageDriverInterface $storage,
        private PathGenerator $pathGenerator,
        private HasherInterface $hasher,
        private PermissionCheckerInterface $permissions,
        private AuditLogManager $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Create a new version of a document.
     *
     * @param string $documentId Document ULID
     * @param resource $stream File stream for new version
     * @param string $changeDescription Description of changes
     * @param string $userId User ULID
     * @return DocumentVersionInterface Created version
     * @throws PermissionDeniedException If user lacks edit permission
     */
    public function createVersion(
        string $documentId,
        $stream,
        string $changeDescription,
        string $userId
    ): DocumentVersionInterface {
        if (!$this->permissions->canCreateVersion($userId, $documentId)) {
            throw new PermissionDeniedException($userId, $documentId, 'create version');
        }

        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new \Nexus\Document\Exceptions\DocumentNotFoundException($documentId);
        }

        // Increment version number
        $newVersionNumber = $document->getVersion() + 1;

        // Generate new storage path
        $newStoragePath = $this->pathGenerator->getVersionPath(
            $document->getStoragePath(),
            $newVersionNumber
        );

        // Calculate checksum
        $content = stream_get_contents($stream);
        $checksum = $this->hasher->hash($content);

        // Store new version file
        rewind($stream);
        $this->storage->put($newStoragePath, $stream, \Nexus\Storage\ValueObjects\Visibility::Private);

        // Create version record
        $version = $this->versionRepository->create([
            'document_id' => $documentId,
            'version_number' => $newVersionNumber,
            'storage_path' => $newStoragePath,
            'change_description' => $changeDescription,
            'created_by' => $userId,
            'checksum' => $checksum,
            'file_size' => strlen($content),
        ]);

        $this->versionRepository->save($version);

        // Update document's current version
        $document->setVersion($newVersionNumber);
        $document->setStoragePath($newStoragePath);
        $document->setChecksum($checksum);
        $this->documentRepository->save($document);

        // Audit log
        $this->auditLogger->log(
            logName: 'document_version_created',
            description: "Version {$newVersionNumber} created for '{$document->getOriginalFilename()}'",
            subjectType: 'Document',
            subjectId: $documentId,
            causerType: 'User',
            causerId: $userId,
            properties: [
                'version_number' => $newVersionNumber,
                'change_description' => $changeDescription,
                'file_size' => strlen($content),
            ],
            level: 2
        );

        return $version;
    }

    /**
     * Get complete version history for a document.
     *
     * @param string $documentId Document ULID
     * @param string $userId User ULID
     * @return array<DocumentVersionInterface> Versions ordered by version DESC
     * @throws PermissionDeniedException If user lacks view permission
     */
    public function getVersionHistory(string $documentId, string $userId): array
    {
        if (!$this->permissions->canView($userId, $documentId)) {
            throw new PermissionDeniedException($userId, $documentId, 'view versions');
        }

        return $this->versionRepository->findByDocumentId($documentId);
    }

    /**
     * Rollback document to a previous version.
     *
     * @param string $documentId Document ULID
     * @param int $versionNumber Version to rollback to
     * @param string $userId User ULID
     * @return DocumentInterface Updated document
     * @throws PermissionDeniedException If user lacks edit permission
     * @throws VersionNotFoundException If version doesn't exist
     */
    public function rollbackToVersion(string $documentId, int $versionNumber, string $userId): DocumentInterface
    {
        if (!$this->permissions->canEdit($userId, $documentId)) {
            throw new PermissionDeniedException($userId, $documentId, 'rollback');
        }

        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new \Nexus\Document\Exceptions\DocumentNotFoundException($documentId);
        }

        $targetVersion = $this->versionRepository->findByVersion($documentId, $versionNumber);
        if (!$targetVersion) {
            throw new VersionNotFoundException($documentId, $versionNumber);
        }

        // Copy version file to new current version
        $stream = $this->storage->get($targetVersion->getStoragePath());

        $this->createVersion(
            $documentId,
            $stream,
            "Rolled back to version {$versionNumber}",
            $userId
        );

        // Audit log
        $this->auditLogger->log(
            logName: 'document_version_rollback',
            description: "Document rolled back to version {$versionNumber}",
            subjectType: 'Document',
            subjectId: $documentId,
            causerType: 'User',
            causerId: $userId,
            properties: [
                'target_version' => $versionNumber,
                'new_version' => $document->getVersion() + 1,
            ],
            level: 3
        );

        return $this->documentRepository->findById($documentId);
    }

    /**
     * Get a specific version.
     *
     * @param string $documentId Document ULID
     * @param int $versionNumber Version number
     * @param string $userId User ULID
     * @return resource File stream
     * @throws PermissionDeniedException If user lacks view permission
     * @throws VersionNotFoundException If version doesn't exist
     */
    public function getVersion(string $documentId, int $versionNumber, string $userId)
    {
        if (!$this->permissions->canView($userId, $documentId)) {
            throw new PermissionDeniedException($userId, $documentId, 'view version');
        }

        $version = $this->versionRepository->findByVersion($documentId, $versionNumber);
        if (!$version) {
            throw new VersionNotFoundException($documentId, $versionNumber);
        }

        return $this->storage->get($version->getStoragePath());
    }

    /**
     * Manually prune old versions, keeping N latest versions.
     *
     * @param string $documentId Document ULID
     * @param int $keepCount Number of latest versions to keep
     * @param string $userId User ULID
     * @return int Number of versions deleted
     * @throws PermissionDeniedException If user lacks delete permission
     */
    public function pruneVersions(string $documentId, int $keepCount, string $userId): int
    {
        if (!$this->permissions->canDelete($userId, $documentId)) {
            throw new PermissionDeniedException($userId, $documentId, 'prune versions');
        }

        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new \Nexus\Document\Exceptions\DocumentNotFoundException($documentId);
        }

        $currentVersion = $document->getVersion();
        $deleteFromVersion = $currentVersion - $keepCount;

        if ($deleteFromVersion < 1) {
            return 0; // Nothing to prune
        }

        // Delete old versions from storage and database
        $versions = $this->versionRepository->findByDocumentId($documentId);
        $deletedCount = 0;

        foreach ($versions as $version) {
            if ($version->getVersionNumber() < $deleteFromVersion) {
                $this->storage->delete($version->getStoragePath());
                $this->versionRepository->delete($version->getId());
                $deletedCount++;
            }
        }

        // Audit log
        $this->auditLogger->log(
            logName: 'document_versions_pruned',
            description: "Pruned {$deletedCount} old versions from '{$document->getOriginalFilename()}'",
            subjectType: 'Document',
            subjectId: $documentId,
            causerType: 'User',
            causerId: $userId,
            properties: [
                'deleted_count' => $deletedCount,
                'kept_count' => $keepCount,
            ],
            level: 2
        );

        return $deletedCount;
    }
}
