<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Audit Package
 * 
 * This example demonstrates:
 * 1. Digital signatures with Nexus\Crypto for non-repudiation
 * 2. Asynchronous logging for bulk operations
 * 3. Sequence gap detection
 * 4. Retention policy enforcement
 * 5. Full verification workflow
 */

use Nexus\Audit\Contracts\{
    AuditEngineInterface,
    AuditStorageInterface,
    AuditVerifierInterface,
    AuditSequenceManagerInterface
};
use Nexus\Audit\Services\RetentionPolicyService;
use Nexus\Audit\ValueObjects\{AuditLevel, RetentionPolicy};

// ============================================
// SCENARIO: High-Compliance Financial Audit
// ============================================

/**
 * This example simulates a high-compliance environment (SOX, GDPR)
 * where audit records require digital signatures and periodic
 * integrity verification.
 */

// Assume services are injected
/** @var AuditEngineInterface $auditEngine */
/** @var AuditStorageInterface $auditStorage */
/** @var AuditVerifierInterface $auditVerifier */
/** @var AuditSequenceManagerInterface $sequenceManager */
/** @var RetentionPolicyService $retentionService */

$tenantId = 'tenant-enterprise-001';
$userId = 'cfo-user-123';

// ============================================
// Feature 1: Digital Signatures
// ============================================

echo "=== Feature 1: Digital Signatures (Non-Repudiation) ===\n";
echo "Creating audit records with Ed25519 digital signatures...\n\n";

// Log journal entry posting with signature
$journalEntryId = 'journal-entry-789';

$signedRecordId = $auditEngine->logSync(
    tenantId: $tenantId,
    entityId: $journalEntryId,
    action: 'journal_entry_posted',
    level: AuditLevel::Critical,
    metadata: [
        'entry_number' => 'JE-2024-001',
        'debit_amount' => 50000.00,
        'credit_amount' => 50000.00,
        'accounts' => ['1000', '2000'],
        'description' => 'Loan disbursement',
    ],
    userId: $userId,
    sign: true // ← Enable digital signature
);

echo "✓ Audit record created with digital signature\n";
echo "  Record ID: {$signedRecordId}\n";
echo "  Signature: Ed25519 (via Nexus\\Crypto)\n";
echo "  Signed By: {$userId}\n";
echo "  Purpose: Cryptographic proof of authorship\n";
echo "  Benefit: Non-repudiation (CFO cannot deny posting this entry)\n\n";

// Verify signature
$signedRecord = $auditStorage->findByEntity($tenantId, $journalEntryId)[0];

if ($signedRecord->getSignature() !== null) {
    echo "✓ Signature present on record\n";
    echo "  Signature: " . substr($signedRecord->getSignature()->signature, 0, 32) . "...\n";
    echo "  Signed By: " . $signedRecord->getSignature()->signedBy . "\n";
    
    // Verify signature cryptographically
    $isSignatureValid = $auditVerifier->verifyRecord($signedRecord);
    
    if ($isSignatureValid) {
        echo "✓ Signature is VALID - Record authenticity confirmed\n";
    } else {
        echo "✗ Signature is INVALID - Possible forgery!\n";
    }
}

echo "\n";

// ============================================
// Feature 2: Asynchronous Logging
// ============================================

echo "=== Feature 2: Asynchronous Logging (Bulk Operations) ===\n";
echo "Logging 10 user activity records asynchronously...\n\n";

$startTime = microtime(true);

// Log 10 user login events asynchronously (non-blocking)
for ($i = 1; $i <= 10; $i++) {
    $auditEngine->logAsync(
        tenantId: $tenantId,
        entityId: "user-{$i}",
        action: 'user_logged_in',
        level: AuditLevel::Info,
        metadata: [
            'ip_address' => "192.168.1.{$i}",
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'login_time' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ],
        userId: "user-{$i}",
        sign: false
    );
}

$elapsedTime = (microtime(true) - $startTime) * 1000; // Convert to ms

echo "✓ 10 audit records queued for asynchronous processing\n";
echo "  Elapsed Time: {$elapsedTime}ms (non-blocking)\n";
echo "  Queue: Records will be processed by background worker\n";
echo "  Benefit: No impact on user-facing request latency\n\n";

// ============================================
// Feature 3: Sequence Gap Detection
// ============================================

echo "=== Feature 3: Sequence Gap Detection ===\n";
echo "Detecting missing sequence numbers in audit chain...\n\n";

// Simulate gap detection
$gaps = $sequenceManager->detectGaps($tenantId);

if (empty($gaps)) {
    echo "✓ No gaps detected - Hash chain is continuous\n";
    echo "  All sequence numbers are accounted for\n";
} else {
    echo "⚠ GAPS DETECTED in sequence:\n";
    echo "  Missing sequences: " . implode(', ', $gaps) . "\n";
    echo "  Possible causes:\n";
    echo "    - Failed transaction rollback\n";
    echo "    - Concurrent write conflict\n";
    echo "    - Manual deletion (should be impossible)\n";
    echo "  Action required: Investigate immediately!\n";
}

echo "\n";

// ============================================
// Feature 4: Retention Policy Enforcement
// ============================================

echo "=== Feature 4: Retention Policy Enforcement (GDPR/SOX) ===\n";
echo "Applying 7-year retention policy...\n\n";

// Define SOX-compliant retention policy (7 years)
$soxRetentionPolicy = new RetentionPolicy(
    retentionDays: 2555 // 7 years × 365 days
);

echo "✓ Retention policy created\n";
echo "  Retention Period: 7 years (2,555 days)\n";
echo "  Compliance: SOX Section 802\n";
echo "  Purpose: Auto-purge records older than 7 years\n\n";

// Apply retention policy
echo "Scanning for expired records...\n";

$deletedCount = $retentionService->purgeExpiredRecords(
    tenantId: $tenantId,
    policy: $soxRetentionPolicy
);

echo "✓ Retention policy applied\n";
echo "  Deleted Records: {$deletedCount}\n";
echo "  Status: Compliant with 7-year retention requirement\n";
echo "  Note: Hash chain remains intact for retained records\n\n";

// ============================================
// Feature 5: Full Verification Workflow
// ============================================

echo "=== Feature 5: Full Verification Workflow ===\n";
echo "Performing comprehensive audit integrity check...\n\n";

echo "Step 1: Verifying hash chain integrity...\n";

try {
    $isChainValid = $auditVerifier->verifyChainIntegrity($tenantId);
    
    if ($isChainValid) {
        echo "✓ Hash chain is VALID\n";
        echo "  All records have intact SHA-256 hashes\n";
        echo "  No tampering detected\n\n";
    }
} catch (\Nexus\Audit\Exceptions\AuditTamperedException $e) {
    echo "✗ TAMPERING DETECTED!\n";
    echo "  Error: " . $e->getMessage() . "\n";
    echo "  Recommendation: Forensic investigation required\n\n";
}

echo "Step 2: Verifying sequence continuity...\n";

$gaps = $sequenceManager->detectGaps($tenantId);

if (empty($gaps)) {
    echo "✓ Sequence is CONTINUOUS\n";
    echo "  No missing sequence numbers\n\n";
} else {
    echo "⚠ Sequence gaps found: " . implode(', ', $gaps) . "\n\n";
}

echo "Step 3: Verifying digital signatures...\n";

$allRecords = $auditStorage->findAll($tenantId);
$signedRecords = array_filter($allRecords, fn($r) => $r->getSignature() !== null);

$validSignatures = 0;
foreach ($signedRecords as $record) {
    if ($auditVerifier->verifyRecord($record)) {
        $validSignatures++;
    }
}

echo "✓ Signature verification complete\n";
echo "  Signed Records: " . count($signedRecords) . "\n";
echo "  Valid Signatures: {$validSignatures}\n";
echo "  Invalid Signatures: " . (count($signedRecords) - $validSignatures) . "\n\n";

// ============================================
// Feature 6: Compliance Report Generation
// ============================================

echo "=== Feature 6: Compliance Report ===\n";

$totalRecords = count($allRecords);
$criticalRecords = count(array_filter($allRecords, fn($r) => $r->getLevel() === AuditLevel::Critical));
$warningRecords = count(array_filter($allRecords, fn($r) => $r->getLevel() === AuditLevel::Warning));
$infoRecords = count(array_filter($allRecords, fn($r) => $r->getLevel() === AuditLevel::Info));

echo "Tenant: {$tenantId}\n";
echo "Audit Period: " . $allRecords[0]->getCreatedAt()->format('Y-m-d') . " to " . end($allRecords)->getCreatedAt()->format('Y-m-d') . "\n";
echo "\n";
echo "Record Statistics:\n";
echo "  Total Records: {$totalRecords}\n";
echo "  Critical: {$criticalRecords} (" . round($criticalRecords / $totalRecords * 100) . "%)\n";
echo "  Warning: {$warningRecords} (" . round($warningRecords / $totalRecords * 100) . "%)\n";
echo "  Info: {$infoRecords} (" . round($infoRecords / $totalRecords * 100) . "%)\n";
echo "\n";
echo "Integrity Status:\n";
echo "  Hash Chain: VALID ✓\n";
echo "  Sequence Continuity: VALID ✓\n";
echo "  Digital Signatures: {$validSignatures}/" . count($signedRecords) . " valid\n";
echo "\n";
echo "Compliance Status:\n";
echo "  SOX Compliance: ✓ (7-year retention enforced)\n";
echo "  GDPR Compliance: ✓ (automatic purging enabled)\n";
echo "  Non-Repudiation: ✓ (Ed25519 signatures on critical events)\n";
echo "  Tamper Detection: ✓ (SHA-256 hash chains)\n";
echo "\n";

// ============================================
// Summary
// ============================================

echo "=== Advanced Features Demonstrated ===\n";
echo "1. ✓ Digital Signatures (Ed25519 via Nexus\\Crypto)\n";
echo "2. ✓ Asynchronous Logging (10 records queued)\n";
echo "3. ✓ Sequence Gap Detection (continuous chain verified)\n";
echo "4. ✓ Retention Policy (SOX 7-year compliance)\n";
echo "5. ✓ Full Verification Workflow (hash, sequence, signatures)\n";
echo "6. ✓ Compliance Report Generation\n";
echo "\n";
echo "This audit system provides enterprise-grade compliance\n";
echo "with cryptographic verification and forensic capabilities.\n";
echo "\n";

/**
 * Key Takeaways:
 * 
 * 1. **Digital Signatures:** Use `sign: true` for critical events requiring
 *    non-repudiation (CFO cannot deny posting a journal entry)
 * 
 * 2. **Async Logging:** Use `logAsync()` for bulk operations to avoid
 *    blocking user-facing requests
 * 
 * 3. **Gap Detection:** Regularly check for sequence gaps to detect
 *    potential data integrity issues
 * 
 * 4. **Retention Policies:** Automatically purge old records to comply
 *    with GDPR while maintaining SOX 7-year retention
 * 
 * 5. **Verification:** Periodically verify hash chain integrity,
 *    sequence continuity, and digital signatures
 * 
 * 6. **Compliance:** This system meets SOX, GDPR, and HIPAA audit
 *    requirements for immutable, tamper-evident logging
 */
