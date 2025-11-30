<?php

declare(strict_types=1);

/**
 * Advanced Usage Examples: Nexus\Document
 * 
 * This file demonstrates advanced document management scenarios including
 * version rollback, batch operations, concurrent handling, and S3 optimization.
 */

use Nexus\Document\Services\DocumentManager;
use Nexus\Document\Services\VersionManager;
use Nexus\Document\Services\RelationshipManager;
use Nexus\Document\ValueObjects\DocumentState;
use Nexus\Document\ValueObjects\RelationshipType;

// Assume these are injected via DI container
/** @var DocumentManager $documentManager */
/** @var VersionManager $versionManager */
/** @var RelationshipManager $relationshipManager */

// ============================================================================
// Example 1: Non-Destructive Version Rollback
// ============================================================================

$documentId = '01JCQR5Z8H9X6Y2W1V0TMKN3JP';

// Current version is v5, but we want to revert to v3
// This creates v6 with the content from v3 (non-destructive)

$restoredVersion = $versionManager->rollbackToVersion(
    documentId: $documentId,
    versionNumber: 3
);

echo "Rolled back to version 3 (created new version {$restoredVersion->getVersionNumber()})\n";
echo "Version 3 checksum: {$restoredVersion->getChecksum()}\n";

// All versions preserved:
// v1, v2, v3, v4, v5 (bad version), v6 (copy of v3)

// ============================================================================
// Example 2: Batch Document Upload with Transaction
// ============================================================================

use Nexus\Document\Contracts\DocumentRepositoryInterface;

/** @var DocumentRepositoryInterface $documentRepository */

$uploadedFiles = [
    ['file' => '/tmp/contract1.pdf', 'title' => 'Contract with Acme'],
    ['file' => '/tmp/contract2.pdf', 'title' => 'Contract with Beta Inc'],
    ['file' => '/tmp/contract3.pdf', 'title' => 'Contract with Gamma LLC'],
];

// Using database transaction for atomicity
DB::transaction(function () use ($uploadedFiles, $documentManager) {
    foreach ($uploadedFiles as $fileData) {
        $documentManager->create(
            type: 'contract',
            file: $fileData['file'],
            metadata: [
                'title' => $fileData['title'],
                'tags' => ['contract', 'batch-upload'],
            ]
        );
    }
});

echo "Batch uploaded " . count($uploadedFiles) . " documents\n";

// ============================================================================
// Example 3: Generate Temporary Signed URL for Document Download
// ============================================================================

use Nexus\Storage\Contracts\StorageInterface;

/** @var StorageInterface $storage */

$document = $documentManager->findById($documentId);
$storagePath = $document->getCurrentVersion()->getStoragePath();

// Generate presigned URL valid for 1 hour
$signedUrl = $storage->temporaryUrl($storagePath, '+1 hour');

echo "Temporary download URL (valid for 1 hour):\n{$signedUrl}\n";

// ============================================================================
// Example 4: Content Processing with OCR/ML
// ============================================================================

use Nexus\Document\Contracts\ContentProcessorInterface;

/** @var ContentProcessorInterface $contentProcessor */

$uploadedInvoice = '/tmp/scanned-invoice.pdf';

// Process content (OCR, classification, metadata extraction)
$analysisResult = $contentProcessor->processContent(
    filePath: $uploadedInvoice,
    mimeType: 'application/pdf'
);

echo "Content Analysis Results:\n";
echo "- Classification: {$analysisResult->classification} (confidence: {$analysisResult->confidence})\n";
echo "- Extracted Text Length: " . strlen($analysisResult->extractedText ?? '') . " chars\n";
echo "- Metadata: " . json_encode($analysisResult->metadata) . "\n";

// Create document with enriched metadata
$document = $documentManager->create(
    type: $analysisResult->classification ?? 'general',
    file: $uploadedInvoice,
    metadata: [
        'title' => $analysisResult->metadata['invoice_number'] ?? 'Scanned Invoice',
        'description' => 'Auto-classified via ML',
        'tags' => ['scanned', 'ocr', $analysisResult->classification],
        'extracted_metadata' => $analysisResult->metadata,
    ]
);

// ============================================================================
// Example 5: Retention Policy with Legal Hold
// ============================================================================

use Nexus\Document\Services\RetentionService;
use Nexus\Document\Contracts\RetentionPolicyInterface;

/** @var RetentionService $retentionService */
/** @var RetentionPolicyInterface $retentionPolicy */

// Apply retention policy (7 years for financial records)
$retentionService->applyPolicy(
    documentId: $documentId,
    retainUntil: new \DateTimeImmutable('+7 years'),
    reason: 'Financial records retention (SOX compliance)'
);

// Check if document can be deleted
if (!$retentionPolicy->canDelete($documentId)) {
    echo "Document is protected by retention policy\n";
}

// Apply legal hold (indefinite retention)
$retentionService->applyPolicy(
    documentId: $documentId,
    retainUntil: new \DateTimeImmutable('+100 years'), // Effectively permanent
    reason: 'Legal hold - pending litigation'
);

// ============================================================================
// Example 6: Complex Relationship Chain
// ============================================================================

// Master Contract → Amendments → SOWs → Invoices

$masterContractId = $documentManager->create(
    type: 'contract',
    file: '/tmp/master-contract.pdf',
    metadata: ['title' => 'Master Service Agreement']
)->getId();

// Amendment 1
$amendment1Id = $documentManager->create(
    type: 'contract',
    file: '/tmp/amendment-1.pdf',
    metadata: ['title' => 'Amendment #1']
)->getId();

$relationshipManager->createRelationship(
    documentId: $amendment1Id,
    relatedDocumentId: $masterContractId,
    type: RelationshipType::Amendment
);

// Statement of Work (related to master contract)
$sowId = $documentManager->create(
    type: 'contract',
    file: '/tmp/sow-1.pdf',
    metadata: ['title' => 'Statement of Work #1']
)->getId();

$relationshipManager->createRelationship(
    documentId: $sowId,
    relatedDocumentId: $masterContractId,
    type: RelationshipType::Related
);

// Invoice (attachment to SOW)
$invoiceId = $documentManager->create(
    type: 'invoice',
    file: '/tmp/invoice-001.pdf',
    metadata: ['title' => 'Invoice #001']
)->getId();

$relationshipManager->createRelationship(
    documentId: $invoiceId,
    relatedDocumentId: $sowId,
    type: RelationshipType::Attachment
);

// Get full relationship graph
$graph = $relationshipManager->getRelationshipGraph($masterContractId);
echo "Relationship Graph:\n";
print_r($graph);

// ============================================================================
// Example 7: Multi-Tenant Document Isolation (Security)
// ============================================================================

use Nexus\Tenant\Contracts\TenantContextInterface;

/** @var TenantContextInterface $tenantContext */

$currentTenantId = $tenantContext->getCurrentTenantId();
echo "Current Tenant: {$currentTenantId}\n";

// This query is automatically scoped to current tenant
$documents = $documentRepository->findByTenantAndType($currentTenantId, 'contract');

// Attempting to access another tenant's document throws exception
try {
    $otherTenantDocId = '01JCQR5Z8H9X6Y2W1V0TMKN3XX'; // From different tenant
    $documentManager->findById($otherTenantDocId);
} catch (\Nexus\Document\Exceptions\DocumentNotFoundException $e) {
    echo "Cross-tenant access prevented ✓\n";
}

// ============================================================================
// Example 8: S3 Lifecycle Policy Integration
// ============================================================================

use Nexus\Document\Core\PathGenerator;

/** @var PathGenerator $pathGenerator */

// Documents are stored in year/month partitions to prevent S3 hot partitions
// Example path: TENANT001/2025/11/DOC123/v1.pdf

$storagePath = $pathGenerator->generateStoragePath(
    tenantId: 'TENANT001',
    documentId: 'DOC123',
    version: 1,
    extension: 'pdf'
);

echo "Storage Path: {$storagePath}\n";
// Output: TENANT001/2025/11/DOC123/v1.pdf

// S3 Lifecycle Policy (configured in AWS):
// - Objects in */2024/* → Glacier after 90 days
// - Objects in */2023/* → Deep Archive after 180 days
// - Objects older than 7 years → Delete (if no retention policy)

// ============================================================================
// Example 9: Concurrent Version Creation Handling
// ============================================================================

// Scenario: Multiple users trying to create versions simultaneously

use Nexus\Document\Exceptions\VersionConflictException;

try {
    // User A creates version
    $versionA = $versionManager->createVersion(
        documentId: $documentId,
        file: '/tmp/version-a.pdf',
        notes: 'Updated by User A'
    );
    
    // User B creates version (database handles concurrency via unique constraint)
    $versionB = $versionManager->createVersion(
        documentId: $documentId,
        file: '/tmp/version-b.pdf',
        notes: 'Updated by User B'
    );
    
    echo "Versions created sequentially: v{$versionA->getVersionNumber()}, v{$versionB->getVersionNumber()}\n";
    
} catch (VersionConflictException $e) {
    // Retry with latest version number
    echo "Version conflict detected, retrying...\n";
}

// ============================================================================
// Example 10: Bulk Metadata Update
// ============================================================================

$documentIds = [
    '01JCQR5Z8H9X6Y2W1V0TMKN3JP',
    '01JCQR5Z8H9X6Y2W1V0TMKN3JQ',
    '01JCQR5Z8H9X6Y2W1V0TMKN3JR',
];

foreach ($documentIds as $docId) {
    $documentManager->updateMetadata($docId, [
        'tags' => ['archived', 'batch-update', '2025'],
    ]);
}

echo "Bulk metadata update completed for " . count($documentIds) . " documents\n";

// ============================================================================
// Example 11: Document State Audit Trail
// ============================================================================

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;

/** @var AuditLogManagerInterface $auditLogger */

// State transitions are automatically logged via AuditLogger integration
$documentManager->transitionState($documentId, DocumentState::Archived);

// Query audit logs to see state history
$auditLogs = $auditLogger->getLogsForEntity($documentId);

echo "Document State History:\n";
foreach ($auditLogs as $log) {
    echo "- {$log->action} by {$log->userId} at {$log->timestamp->format('Y-m-d H:i:s')}\n";
}

/**
 * Output:
 * - state_transition: Draft → Active by USER001 at 2025-11-24 10:00:00
 * - state_transition: Active → Archived by USER001 at 2025-11-24 15:30:00
 */

// ============================================================================
// Example 12: Custom Retention Policy Implementation
// ============================================================================

use Nexus\Document\Contracts\RetentionPolicyInterface;

final readonly class CustomRetentionPolicy implements RetentionPolicyInterface
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository
    ) {}
    
    public function canDelete(string $documentId): bool
    {
        $document = $this->documentRepository->findById($documentId);
        
        // Custom rules:
        // 1. Contracts: 7 years
        // 2. Invoices: 7 years
        // 3. General: 1 year
        
        $retentionPeriods = [
            'contract' => new \DateInterval('P7Y'),
            'invoice' => new \DateInterval('P7Y'),
            'general' => new \DateInterval('P1Y'),
        ];
        
        $retentionPeriod = $retentionPeriods[$document->getType()] ?? new \DateInterval('P1Y');
        
        $retainUntil = $document->getCreatedAt()->add($retentionPeriod);
        
        return new \DateTimeImmutable() >= $retainUntil;
    }
    
    public function getRetentionPeriod(string $documentType): ?\DateInterval
    {
        return match ($documentType) {
            'contract', 'invoice' => new \DateInterval('P7Y'),
            'general' => new \DateInterval('P1Y'),
            default => null,
        };
    }
}

// ============================================================================
// Example 13: Document Search with Filters
// ============================================================================

use Nexus\Document\Contracts\DocumentSearchInterface;

/** @var DocumentSearchInterface $documentSearch */

$results = $documentSearch->search([
    'type' => 'contract',
    'state' => DocumentState::Active->name,
    'tags' => ['acme', 'active'],
    'dateFrom' => '2025-01-01',
    'dateTo' => '2025-12-31',
    'titleContains' => 'Service Agreement',
]);

echo "Search Results: " . count($results) . " documents\n";
foreach ($results as $document) {
    echo "- [{$document->getId()}] {$document->getMetadata()->title}\n";
}

// ============================================================================
// Example 14: Batch Delete with Retention Enforcement
// ============================================================================

$documentsToDelete = $documentRepository->findByTenantAndType(
    $currentTenantId,
    'general'
);

$deletedCount = 0;
$protectedCount = 0;

foreach ($documentsToDelete as $document) {
    if ($retentionPolicy->canDelete($document->getId())) {
        $documentManager->transitionState($document->getId(), DocumentState::Deleted);
        $deletedCount++;
    } else {
        $protectedCount++;
    }
}

echo "Deleted: {$deletedCount}, Protected by retention: {$protectedCount}\n";

// ============================================================================
// Example 15: Document Export to ZIP Archive
// ============================================================================

$documentsToExport = $documentSearch->search([
    'type' => 'contract',
    'tags' => ['2025'],
]);

$zip = new \ZipArchive();
$zipFilename = '/tmp/contracts-2025-export.zip';
$zip->open($zipFilename, \ZipArchive::CREATE);

foreach ($documentsToExport as $document) {
    $content = $documentManager->download($document->getId());
    $filename = "{$document->getMetadata()->title}.pdf";
    $zip->addFromString($filename, $content);
}

$zip->close();

echo "Exported " . count($documentsToExport) . " documents to {$zipFilename}\n";

// ============================================================================
// Example 16: Relationship Deletion Cascade
// ============================================================================

// When deleting a document with relationships, handle cleanup

$documentWithRelationships = '01JCQR5Z8H9X6Y2W1V0TMKN3JP';

// Get all relationships before deletion
$relationships = $relationshipRepository->findRelationships($documentWithRelationships);

// Delete all relationships
foreach ($relationships as $relationship) {
    $relationshipRepository->deleteRelationship(
        documentId: $relationship->getDocumentId(),
        relatedDocumentId: $relationship->getRelatedDocumentId(),
        type: $relationship->getRelationshipType()
    );
}

// Now safe to delete document
$documentManager->transitionState($documentWithRelationships, DocumentState::Deleted);

echo "Document and all relationships deleted\n";

// ============================================================================
// Example 17: Performance Optimization - Batch Version Download
// ============================================================================

use Nexus\Storage\Contracts\StorageInterface;

/** @var StorageInterface $storage */

$versionIds = [1, 2, 3, 4, 5];
$document = $documentManager->findById($documentId);

// Download all versions in parallel (if storage supports batch operations)
$versionContents = [];

foreach ($versionIds as $versionId) {
    $version = $versionRepository->findVersionsForDocument($documentId)[$versionId - 1];
    $versionContents[$versionId] = $storage->get($version->getStoragePath());
}

echo "Downloaded " . count($versionContents) . " versions\n";

// ============================================================================
// Example 18: Document Duplicate Detection
// ============================================================================

$uploadedFile = '/tmp/new-contract.pdf';
$fileChecksum = hash_file('sha256', $uploadedFile);

// Search for existing documents with same checksum
$allDocuments = $documentRepository->findByTenantAndType($currentTenantId, 'contract');
$duplicateFound = false;

foreach ($allDocuments as $existingDoc) {
    if ($existingDoc->getCurrentVersion()->getChecksum() === $fileChecksum) {
        echo "Duplicate document found: {$existingDoc->getId()}\n";
        $duplicateFound = true;
        break;
    }
}

if (!$duplicateFound) {
    $newDoc = $documentManager->create(
        type: 'contract',
        file: $uploadedFile,
        metadata: ['title' => 'New Contract']
    );
    echo "New document created: {$newDoc->getId()}\n";
}
