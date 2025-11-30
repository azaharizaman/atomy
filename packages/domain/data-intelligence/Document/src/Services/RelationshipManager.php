<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\AuditLogger\Services\AuditLogManager;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\Contracts\DocumentRelationshipInterface;
use Nexus\Document\Contracts\DocumentRelationshipRepositoryInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\PermissionCheckerInterface;
use Nexus\Document\Exceptions\PermissionDeniedException;
use Nexus\Document\ValueObjects\RelationshipType;
use Psr\Log\LoggerInterface;

/**
 * Relationship manager service.
 *
 * Manages document relationships (amendment, supersedes, related, attachment).
 */
final readonly class RelationshipManager
{
    public function __construct(
        private DocumentRelationshipRepositoryInterface $relationshipRepository,
        private DocumentRepositoryInterface $documentRepository,
        private PermissionCheckerInterface $permissions,
        private AuditLogManager $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Create a relationship between two documents.
     *
     * @param string $sourceDocumentId Source document ULID
     * @param string $targetDocumentId Target document ULID
     * @param RelationshipType $type Relationship type
     * @param string $userId User ULID
     * @return DocumentRelationshipInterface Created relationship
     * @throws PermissionDeniedException If user lacks edit permission on source
     * @throws \InvalidArgumentException If documents don't exist or relationship exists
     */
    public function createRelationship(
        string $sourceDocumentId,
        string $targetDocumentId,
        RelationshipType $type,
        string $userId
    ): DocumentRelationshipInterface {
        // Validate both documents exist
        $sourceDocument = $this->documentRepository->findById($sourceDocumentId);
        if (!$sourceDocument) {
            throw new \Nexus\Document\Exceptions\DocumentNotFoundException($sourceDocumentId);
        }

        $targetDocument = $this->documentRepository->findById($targetDocumentId);
        if (!$targetDocument) {
            throw new \Nexus\Document\Exceptions\DocumentNotFoundException($targetDocumentId);
        }

        // Permission check on source document
        if (!$this->permissions->canEdit($userId, $sourceDocumentId)) {
            throw new PermissionDeniedException($userId, $sourceDocumentId, 'create relationship');
        }

        // Check for duplicate relationship
        if ($this->relationshipRepository->exists($sourceDocumentId, $targetDocumentId, $type)) {
            throw new \InvalidArgumentException(
                "Relationship already exists between {$sourceDocumentId} and {$targetDocumentId}"
            );
        }

        // Create relationship
        $relationship = $this->relationshipRepository->create(
            $sourceDocumentId,
            $targetDocumentId,
            $type,
            $userId
        );

        // Audit log
        $this->auditLogger->log(
            logName: 'document_relationship_created',
            description: sprintf(
                "Relationship '%s' created: '%s' -> '%s'",
                $type->label(),
                $sourceDocument->getOriginalFilename(),
                $targetDocument->getOriginalFilename()
            ),
            subjectType: 'DocumentRelationship',
            subjectId: $relationship->getId(),
            causerType: 'User',
            causerId: $userId,
            properties: [
                'source_document_id' => $sourceDocumentId,
                'target_document_id' => $targetDocumentId,
                'relationship_type' => $type->value,
            ],
            level: 2
        );

        return $relationship;
    }

    /**
     * Get all related documents (one level deep).
     *
     * @param string $documentId Document ULID
     * @param RelationshipType|null $type Optional filter by type
     * @param string $userId User ULID
     * @return array<DocumentInterface> Related documents
     * @throws PermissionDeniedException If user lacks view permission
     */
    public function getRelatedDocuments(
        string $documentId,
        ?RelationshipType $type,
        string $userId
    ): array {
        if (!$this->permissions->canView($userId, $documentId)) {
            throw new PermissionDeniedException($userId, $documentId, 'view relationships');
        }

        // Get relationships where document is source or target
        $sourceRelationships = $this->relationshipRepository->findBySourceDocument($documentId, $type);
        $targetRelationships = $this->relationshipRepository->findByTargetDocument($documentId, $type);

        // Collect unique document IDs
        $relatedDocIds = [];
        foreach ($sourceRelationships as $rel) {
            $relatedDocIds[] = $rel->getTargetDocumentId();
        }
        foreach ($targetRelationships as $rel) {
            $relatedDocIds[] = $rel->getSourceDocumentId();
        }

        $relatedDocIds = array_unique($relatedDocIds);

        // Fetch documents
        $documents = [];
        foreach ($relatedDocIds as $docId) {
            if ($this->permissions->canView($userId, $docId)) {
                $doc = $this->documentRepository->findById($docId);
                if ($doc) {
                    $documents[] = $doc;
                }
            }
        }

        return $documents;
    }

    /**
     * Delete a relationship.
     *
     * @param string $relationshipId Relationship ULID
     * @param string $userId User ULID
     * @throws PermissionDeniedException If user lacks edit permission
     */
    public function deleteRelationship(string $relationshipId, string $userId): void
    {
        $relationship = $this->relationshipRepository->findById($relationshipId);
        if (!$relationship) {
            throw new \InvalidArgumentException("Relationship not found: {$relationshipId}");
        }

        // Permission check on source document
        if (!$this->permissions->canEdit($userId, $relationship->getSourceDocumentId())) {
            throw new PermissionDeniedException(
                $userId,
                $relationship->getSourceDocumentId(),
                'delete relationship'
            );
        }

        $this->relationshipRepository->delete($relationshipId);

        // Audit log
        $this->auditLogger->log(
            logName: 'document_relationship_deleted',
            description: "Relationship '{$relationship->getRelationshipType()->label()}' deleted",
            subjectType: 'DocumentRelationship',
            subjectId: $relationshipId,
            causerType: 'User',
            causerId: $userId,
            properties: [
                'source_document_id' => $relationship->getSourceDocumentId(),
                'target_document_id' => $relationship->getTargetDocumentId(),
                'relationship_type' => $relationship->getRelationshipType()->value,
            ],
            level: 2
        );
    }
}
