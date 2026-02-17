<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\DocumentSearchInterface;
use Nexus\Document\Contracts\PermissionCheckerInterface;
use Nexus\Document\ValueObjects\DocumentType;

/**
 * Document search service.
 *
 * Provides metadata-based search capabilities.
 * All searches are tenant-scoped and permission-filtered.
 */
final readonly class DocumentSearchService implements DocumentSearchInterface
{
    public function __construct(
        private DocumentRepositoryInterface $repository,
        private PermissionCheckerInterface $permissions,
        private \Nexus\AuditLogger\Services\AuditLogManager $auditLogger
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function findByTags(array $tags, string $userId): array
    {
        $documents = $this->repository->findByTags($tags);
        $results = $this->filterByPermissions($documents, $userId);

        $this->auditLogger->log(
            logName: 'document_search_tags',
            description: sprintf("Search by tags: %s", implode(', ', $tags)),
            subjectType: 'Search',
            subjectId: 'tags',
            causerType: 'User',
            causerId: $userId,
            properties: [
                'tags' => $tags,
                'result_count' => count($results),
            ],
            level: 1
        );

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function findByType(DocumentType $type, array $filters, string $userId): array
    {
        $documents = $this->repository->findByType($type);

        // Apply filters
        if (isset($filters['dateFrom'], $filters['dateTo'])) {
            $dateFrom = $filters['dateFrom'] instanceof \DateTimeInterface
                ? $filters['dateFrom']
                : new \DateTimeImmutable($filters['dateFrom']);
            $dateTo = $filters['dateTo'] instanceof \DateTimeInterface
                ? $filters['dateTo']
                : new \DateTimeImmutable($filters['dateTo']);

            $documents = array_filter($documents, function (DocumentInterface $doc) use ($dateFrom, $dateTo) {
                return $doc->getCreatedAt() >= $dateFrom && $doc->getCreatedAt() <= $dateTo;
            });
        }

        if (isset($filters['ownerId'])) {
            $documents = array_filter($documents, function (DocumentInterface $doc) use ($filters) {
                return $doc->getOwnerId() === $filters['ownerId'];
            });
        }

        $results = $this->filterByPermissions($documents, $userId);

        $this->auditLogger->log(
            logName: 'document_search_type',
            description: sprintf("Search by type: %s", $type->value),
            subjectType: 'Search',
            subjectId: 'type',
            causerType: 'User',
            causerId: $userId,
            properties: [
                'type' => $type->value,
                'filters' => $filters,
                'result_count' => count($results),
            ],
            level: 1
        );

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function findByMetadata(array $criteria, string $userId): array
    {
        // This requires JSON query support in repository
        // For now, fetch all and filter in memory (inefficient, but functional)
        // Production implementation should push criteria to database layer

        $allDocuments = $this->repository->findByOwner($userId); // Basic filtering
        $filtered = [];

        foreach ($allDocuments as $document) {
            $metadata = $document->getMetadata();
            $matches = true;

            foreach ($criteria as $key => $value) {
                if (!isset($metadata[$key]) || $metadata[$key] !== $value) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                $filtered[] = $document;
            }
        }

        $results = $this->filterByPermissions($filtered, $userId);

        $this->auditLogger->log(
            logName: 'document_search_metadata',
            description: "Search by metadata criteria",
            subjectType: 'Search',
            subjectId: 'metadata',
            causerType: 'User',
            causerId: $userId,
            properties: [
                'criteria' => $criteria,
                'result_count' => count($results),
            ],
            level: 1
        );

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function findByOwner(string $ownerId, array $filters, string $userId): array
    {
        $documents = $this->repository->findByOwner($ownerId);

        // Apply type filter
        if (isset($filters['type'])) {
            $type = $filters['type'] instanceof DocumentType
                ? $filters['type']
                : DocumentType::from($filters['type']);

            $documents = array_filter($documents, function (DocumentInterface $doc) use ($type) {
                return $doc->getType() === $type;
            });
        }

        // Apply state filter
        if (isset($filters['state'])) {
            $documents = array_filter($documents, function (DocumentInterface $doc) use ($filters) {
                return $doc->getState()->value === $filters['state'];
            });
        }

        // Apply date range filter
        if (isset($filters['dateFrom'], $filters['dateTo'])) {
            $dateFrom = new \DateTimeImmutable($filters['dateFrom']);
            $dateTo = new \DateTimeImmutable($filters['dateTo']);

            $documents = array_filter($documents, function (DocumentInterface $doc) use ($dateFrom, $dateTo) {
                return $doc->getCreatedAt() >= $dateFrom && $doc->getCreatedAt() <= $dateTo;
            });
        }

        return $this->filterByPermissions($documents, $userId);
    }

    /**
     * {@inheritdoc}
     */
    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to, string $userId): array
    {
        $documents = $this->repository->findByDateRange($from, $to);
        $results = $this->filterByPermissions($documents, $userId);

        $this->auditLogger->log(
            logName: 'document_search_daterange',
            description: sprintf("Search by date range: %s to %s", $from->format('Y-m-d'), $to->format('Y-m-d')),
            subjectType: 'Search',
            subjectId: 'daterange',
            causerType: 'User',
            causerId: $userId,
            properties: [
                'from' => $from->format(\DateTimeInterface::ATOM),
                'to' => $to->format(\DateTimeInterface::ATOM),
                'result_count' => count($results),
            ],
            level: 1
        );

        return $results;
    }

    /**
     * Filter documents by user permissions.
     *
     * @param array<DocumentInterface> $documents
     * @param string $userId
     * @return array<DocumentInterface>
     */
    private function filterByPermissions(array $documents, string $userId): array
    {
        return array_filter($documents, function (DocumentInterface $doc) use ($userId) {
            return $this->permissions->canView($userId, $doc->getId());
        });
    }
}
