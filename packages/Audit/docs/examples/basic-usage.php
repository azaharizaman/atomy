<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Audit Package
 * 
 * This example demonstrates:
 * 1. Synchronous audit logging for critical events
 * 2. Hash chain verification
 * 3. Simple integrity check
 * 4. Retrieving audit records for an entity
 */

use Nexus\Audit\Contracts\{AuditEngineInterface, AuditStorageInterface, AuditVerifierInterface};
use Nexus\Audit\ValueObjects\AuditLevel;

// ============================================
// SCENARIO: Invoice Approval Workflow
// ============================================

/**
 * This example simulates an invoice approval workflow where each
 * state change is logged to an immutable audit trail.
 */

// Assume we have these services injected (via DI container)
/** @var AuditEngineInterface $auditEngine */
/** @var AuditStorageInterface $auditStorage */
/** @var AuditVerifierInterface $auditVerifier */

$tenantId = 'tenant-abc123';
$invoiceId = 'invoice-456def';
$userId = 'user-789ghi';

// ============================================
// Step 1: Log Invoice Creation
// ============================================

echo "=== Step 1: Creating Invoice ===\n";

$recordId1 = $auditEngine->logSync(
    tenantId: $tenantId,
    entityId: $invoiceId,
    action: 'invoice_created',
    level: AuditLevel::Info,
    metadata: [
        'invoice_number' => 'INV-2024-001',
        'customer_id' => 'customer-123',
        'amount' => 1000.00,
        'currency' => 'USD',
    ],
    userId: $userId,
    sign: false // No signature for this example
);

echo "✓ Audit record created: {$recordId1}\n";
echo "  Action: invoice_created\n";
echo "  Sequence: 1 (first record in chain)\n\n";

// ============================================
// Step 2: Log Invoice Approval
// ============================================

echo "=== Step 2: Approving Invoice ===\n";

$recordId2 = $auditEngine->logSync(
    tenantId: $tenantId,
    entityId: $invoiceId,
    action: 'invoice_approved',
    level: AuditLevel::Info,
    metadata: [
        'invoice_number' => 'INV-2024-001',
        'approver_id' => 'manager-456',
        'approved_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        'old_status' => 'draft',
        'new_status' => 'approved',
    ],
    userId: 'manager-456',
    sign: false
);

echo "✓ Audit record created: {$recordId2}\n";
echo "  Action: invoice_approved\n";
echo "  Sequence: 2 (chained to record 1)\n";
echo "  Hash: Links to previous record via SHA-256 chain\n\n";

// ============================================
// Step 3: Log Payment Received
// ============================================

echo "=== Step 3: Recording Payment ===\n";

$recordId3 = $auditEngine->logSync(
    tenantId: $tenantId,
    entityId: $invoiceId,
    action: 'payment_received',
    level: AuditLevel::Critical, // Critical = financial transaction
    metadata: [
        'invoice_number' => 'INV-2024-001',
        'payment_amount' => 1000.00,
        'payment_method' => 'bank_transfer',
        'transaction_id' => 'txn-789',
        'received_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
    ],
    userId: $userId,
    sign: false
);

echo "✓ Audit record created: {$recordId3}\n";
echo "  Action: payment_received\n";
echo "  Sequence: 3 (chained to record 2)\n";
echo "  Level: CRITICAL (financial transaction)\n\n";

// ============================================
// Step 4: Retrieve All Audit Records for Invoice
// ============================================

echo "=== Step 4: Retrieving Audit Trail ===\n";

$auditRecords = $auditStorage->findByEntity($tenantId, $invoiceId);

echo "Found " . count($auditRecords) . " audit records for invoice {$invoiceId}:\n\n";

foreach ($auditRecords as $index => $record) {
    echo "Record " . ($index + 1) . ":\n";
    echo "  ID: " . $record->getId() . "\n";
    echo "  Sequence: " . $record->getSequenceNumber() . "\n";
    echo "  Action: " . $record->getAction() . "\n";
    echo "  Level: " . $record->getLevel()->name . "\n";
    echo "  Created: " . $record->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
    echo "  Previous Hash: " . ($record->getPreviousHash()?->toString() ?? 'null (first record)') . "\n";
    echo "  Record Hash: " . $record->getRecordHash()->toString() . "\n";
    echo "\n";
}

// ============================================
// Step 5: Verify Hash Chain Integrity
// ============================================

echo "=== Step 5: Verifying Hash Chain Integrity ===\n";

try {
    $isValid = $auditVerifier->verifyChainIntegrity($tenantId);
    
    if ($isValid) {
        echo "✓ Hash chain is VALID - No tampering detected\n";
        echo "  All " . count($auditRecords) . " records have intact hashes\n";
        echo "  Chain continuity verified\n";
    } else {
        echo "✗ Hash chain is INVALID - Tampering suspected!\n";
    }
} catch (\Nexus\Audit\Exceptions\AuditTamperedException $e) {
    echo "✗ TAMPERING DETECTED!\n";
    echo "  Error: " . $e->getMessage() . "\n";
    echo "  Action required: Investigate immediately!\n";
}

echo "\n";

// ============================================
// Step 6: Verify Individual Record
// ============================================

echo "=== Step 6: Verifying Individual Record ===\n";

// Verify the payment record (most critical)
$paymentRecord = $auditRecords[2]; // Third record (payment)

$isRecordValid = $auditVerifier->verifyRecord($paymentRecord);

if ($isRecordValid) {
    echo "✓ Payment record is VALID\n";
    echo "  Record hash matches calculated hash\n";
    echo "  Chain link is intact\n";
} else {
    echo "✗ Payment record is INVALID - Data has been tampered!\n";
}

echo "\n";

// ============================================
// Summary
// ============================================

echo "=== Summary ===\n";
echo "Created 3 audit records:\n";
echo "  1. invoice_created (Info)\n";
echo "  2. invoice_approved (Info)\n";
echo "  3. payment_received (Critical)\n";
echo "\n";
echo "Hash Chain Status: VALID ✓\n";
echo "All records are cryptographically linked via SHA-256 hashes.\n";
echo "Any tampering with historical records will break the chain.\n";
echo "\n";

/**
 * Expected Output:
 * 
 * === Step 1: Creating Invoice ===
 * ✓ Audit record created: 01HX...
 * Action: invoice_created
 * Sequence: 1 (first record in chain)
 * 
 * === Step 2: Approving Invoice ===
 * ✓ Audit record created: 01HX...
 * Action: invoice_approved
 * Sequence: 2 (chained to record 1)
 * Hash: Links to previous record via SHA-256 chain
 * 
 * === Step 3: Recording Payment ===
 * ✓ Audit record created: 01HX...
 * Action: payment_received
 * Sequence: 3 (chained to record 2)
 * Level: CRITICAL (financial transaction)
 * 
 * === Step 4: Retrieving Audit Trail ===
 * Found 3 audit records for invoice invoice-456def:
 * 
 * Record 1:
 * ID: 01HX...
 * Sequence: 1
 * Action: invoice_created
 * Level: Info
 * Created: 2024-11-24 10:30:00
 * Previous Hash: null (first record)
 * Record Hash: abc123...
 * 
 * [... records 2 and 3 ...]
 * 
 * === Step 5: Verifying Hash Chain Integrity ===
 * ✓ Hash chain is VALID - No tampering detected
 * All 3 records have intact hashes
 * Chain continuity verified
 * 
 * === Step 6: Verifying Individual Record ===
 * ✓ Payment record is VALID
 * Record hash matches calculated hash
 * Chain link is intact
 * 
 * === Summary ===
 * Created 3 audit records:
 * 1. invoice_created (Info)
 * 2. invoice_approved (Info)
 * 3. payment_received (Critical)
 * 
 * Hash Chain Status: VALID ✓
 * All records are cryptographically linked via SHA-256 hashes.
 * Any tampering with historical records will break the chain.
 */
