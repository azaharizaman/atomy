<?php

declare(strict_types=1);

/**
 * Basic Usage Examples: Nexus\Document
 * 
 * This file demonstrates common document management operations.
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
// Example 1: Create a New Document
// ============================================================================

$uploadedFile = $_FILES['contract']; // From file upload

$document = $documentManager->create(
    type: 'contract',
    file: $uploadedFile,
    metadata: [
        'title' => 'Master Service Agreement 2025',
        'description' => 'MSA with Acme Corp',
        'tags' => ['contract', 'legal', 'acme'],
    ]
);

echo "Document created with ID: {$document->getId()}\n";
echo "Storage path: {$document->getStoragePath()}\n";

// ============================================================================
// Example 2: Retrieve a Document
// ============================================================================

$documentId = '01JCQR5Z8H9X6Y2W1V0TMKN3JP';

$document = $documentManager->findById($documentId);

echo "Title: {$document->getMetadata()->title}\n";
echo "State: {$document->getState()->name}\n";
echo "Current Version: {$document->getCurrentVersion()->getVersionNumber()}\n";

// ============================================================================
// Example 3: Update Document Metadata
// ============================================================================

$documentManager->updateMetadata($documentId, [
    'title' => 'Master Service Agreement 2025 (Updated)',
    'tags' => ['contract', 'legal', 'acme', 'active'],
]);

echo "Metadata updated successfully\n";

// ============================================================================
// Example 4: Create a New Version
// ============================================================================

$newFile = $_FILES['updated_contract'];

$newVersion = $versionManager->createVersion(
    documentId: $documentId,
    file: $newFile,
    notes: 'Updated payment terms in section 5.2'
);

echo "New version {$newVersion->getVersionNumber()} created\n";
echo "Checksum: {$newVersion->getChecksum()}\n";

// ============================================================================
// Example 5: Download Document with Checksum Verification
// ============================================================================

try {
    $content = $documentManager->download($documentId);
    $version = $document->getCurrentVersion();
    
    // Verify checksum
    $actualChecksum = hash('sha256', $content);
    if ($actualChecksum === $version->getChecksum()) {
        echo "Checksum verified ✓\n";
        file_put_contents('/tmp/downloaded_contract.pdf', $content);
    }
} catch (\Nexus\Document\Exceptions\ChecksumMismatchException $e) {
    echo "Checksum verification failed: {$e->getMessage()}\n";
}

// ============================================================================
// Example 6: Apply Retention Policy
// ============================================================================

use Nexus\Document\Services\RetentionService;

/** @var RetentionService $retentionService */

$retainUntil = new \DateTimeImmutable('+7 years'); // Legal requirement

$retentionService->applyPolicy(
    documentId: $documentId,
    retainUntil: $retainUntil,
    reason: 'SOX compliance - financial records retention'
);

echo "Retention policy applied until {$retainUntil->format('Y-m-d')}\n";

// ============================================================================
// Example 7: Check Permissions
// ============================================================================

use Nexus\Document\Contracts\PermissionCheckerInterface;

/** @var PermissionCheckerInterface $permissionChecker */

$userId = '01JCQR5Z8H9X6Y2W1V0TMKN3JP';

if ($permissionChecker->canView($userId, $documentId)) {
    echo "User can view document\n";
}

if ($permissionChecker->canEdit($userId, $documentId)) {
    echo "User can edit document\n";
}

if ($permissionChecker->canDelete($userId, $documentId)) {
    echo "User can delete document\n";
} else {
    echo "User cannot delete document (retention policy active)\n";
}

// ============================================================================
// Example 8: Transition Document State
// ============================================================================

// Draft → Active
$documentManager->transitionState($documentId, DocumentState::Active);
echo "Document activated\n";

// Active → Archived (after contract expires)
$documentManager->transitionState($documentId, DocumentState::Archived);
echo "Document archived\n";

// Archived → Deleted (soft delete)
$documentManager->transitionState($documentId, DocumentState::Deleted);
echo "Document marked for deletion\n";

// Invalid transitions throw InvalidDocumentStateException
try {
    $documentManager->transitionState($documentId, DocumentState::Draft);
} catch (\Nexus\Document\Exceptions\InvalidDocumentStateException $e) {
    echo "Invalid state transition: {$e->getMessage()}\n";
}

// ============================================================================
// Example 9: List Documents by Type
// ============================================================================

use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;

/** @var DocumentRepositoryInterface $documentRepository */
/** @var TenantContextInterface $tenantContext */

$tenantId = $tenantContext->getCurrentTenantId();

$contracts = $documentRepository->findByTenantAndType($tenantId, 'contract');

echo "Found " . count($contracts) . " contracts:\n";
foreach ($contracts as $contract) {
    echo "- {$contract->getMetadata()->title} ({$contract->getState()->name})\n";
}

// ============================================================================
// Example 10: Search Documents
// ============================================================================

use Nexus\Document\Contracts\DocumentSearchInterface;

/** @var DocumentSearchInterface $documentSearch */

$results = $documentSearch->search([
    'type' => 'contract',
    'tags' => ['active'],
    'dateFrom' => '2025-01-01',
    'dateTo' => '2025-12-31',
]);

echo "Search found " . count($results) . " documents\n";

// ============================================================================
// Example 11: Create Document Relationship (Amendment)
// ============================================================================

$originalContractId = '01JCQR5Z8H9X6Y2W1V0TMKN3JP';
$amendmentFile = $_FILES['amendment'];

// Create amendment document
$amendment = $documentManager->create(
    type: 'contract',
    file: $amendmentFile,
    metadata: [
        'title' => 'Amendment #1 to MSA 2025',
        'tags' => ['contract', 'amendment'],
    ]
);

// Link to original contract
$relationship = $relationshipManager->createRelationship(
    documentId: $amendment->getId(),
    relatedDocumentId: $originalContractId,
    type: RelationshipType::Amendment
);

echo "Amendment linked to original contract\n";

// ============================================================================
// Example 12: Get Document Relationship Graph
// ============================================================================

$relationshipGraph = $relationshipManager->getRelationshipGraph($originalContractId);

echo "Relationship graph:\n";
print_r($relationshipGraph);

/**
 * Output structure:
 * [
 *     'amendments' => [
 *         ['id' => '...', 'title' => 'Amendment #1 to MSA 2025'],
 *         ['id' => '...', 'title' => 'Amendment #2 to MSA 2025'],
 *     ],
 *     'attachments' => [
 *         ['id' => '...', 'title' => 'Exhibit A - Pricing Schedule'],
 *     ],
 *     'related' => [
 *         ['id' => '...', 'title' => 'Statement of Work #1'],
 *     ],
 * ]
 */

// ============================================================================
// Example 13: Prune Old Versions
// ============================================================================

// Keep only the last 3 versions, delete older ones
$versionManager->pruneOldVersions(
    documentId: $documentId,
    keepCount: 3
);

echo "Old versions pruned (kept last 3)\n";

// ============================================================================
// Example 14: Get All Versions for a Document
// ============================================================================

use Nexus\Document\Contracts\DocumentVersionRepositoryInterface;

/** @var DocumentVersionRepositoryInterface $versionRepository */

$versions = $versionRepository->findVersionsForDocument($documentId);

echo "Document has " . count($versions) . " versions:\n";
foreach ($versions as $version) {
    echo "- v{$version->getVersionNumber()}: {$version->getMimeType()} " .
         "({$version->getFileSize()} bytes) - {$version->getNotes()}\n";
}

// ============================================================================
// Example 15: Enforce Retention Policies (Scheduled Job)
// ============================================================================

// This would typically run as a scheduled job (cron/Laravel scheduler)

$purgedDocuments = $retentionService->enforceRetention();

echo "Purged " . count($purgedDocuments) . " documents:\n";
foreach ($purgedDocuments as $purgedId) {
    echo "- {$purgedId}\n";
}
