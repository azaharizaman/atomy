# Nexus ProcurementOperations Orchestrator

**Package:** `nexus/procurement-operations`  
**Namespace:** `Nexus\ProcurementOperations`  
**Type:** Orchestrator (Pure PHP)  
**Version:** 2.0.0-phase-c

---

## Overview

The `ProcurementOperations` orchestrator coordinates the complete **Procure-to-Pay (P2P)** cycle across multiple atomic Nexus packages. It provides enterprise-grade procurement capabilities with full **SOX 404 compliance** support.

### Core Capabilities

1. **Requisition Workflow** - Create, validate, approve/reject purchase requisitions
2. **Purchase Order Workflow** - Convert requisitions to POs, manage amendments and blanket POs
3. **Goods Receipt Workflow** - Record partial/full receipts, quality inspection integration
4. **Three-Way Matching** - Match PO ↔ GR ↔ Invoice with configurable tolerances
5. **Payment Processing** - Schedule, batch, and execute vendor payments with early payment discounts
6. **SOX 404 Compliance** - Full compliance framework with control testing and evidence management
7. **Vendor Portal** - Self-service vendor registration, onboarding, and performance management
8. **Spend Analytics** - Real-time spend analysis, policy compliance, and maverick detection
9. **Multi-Entity Operations** - Shared services center and intercompany procurement
10. **Document Retention** - Regulatory-compliant document lifecycle management

---

## ⚠️ Stability & Compatibility Notice

**Pre-Production Status:** This orchestrator is under active development and has **not yet reached production state**. As such:

- **Breaking changes may occur** between minor versions without deprecation notices
- **API signatures may change** as the design evolves based on real-world usage
- **Backward compatibility is not guaranteed** until v1.0.0 stable release
- Some dependencies (e.g., `CryptoManagerInterface` in `HashingService`) are **mandatory** rather than optional, as they are core to the service's functionality

Please pin to specific versions in non-production environments and review the [CHANGELOG](CHANGELOG.md) before upgrading.

---

## Architecture

This orchestrator follows the **Advanced Orchestrator Pattern v1.1** with strict component separation:

```
src/
├── Contracts/        # 28 interfaces for coordinators, services, and workflows
├── Coordinators/     # 12 stateless traffic cops
├── DataProviders/    # 8 cross-package data aggregation providers
├── DTOs/             # 90+ data transfer objects organized by domain
│   ├── Audit/        # Audit and retention DTOs
│   ├── Financial/    # Payment, matching, invoicing DTOs
│   ├── MultiEntity/  # Shared services and intercompany DTOs
│   ├── Sox/          # SOX 404 compliance DTOs
│   ├── SpendPolicy/  # Spend analysis and policy DTOs
│   └── Vendor/       # Vendor portal and onboarding DTOs
├── Enums/            # 22 domain enums
├── Events/           # 30+ domain events
├── Exceptions/       # Domain-specific error scenarios
├── Listeners/        # 15 event reactors
├── Rules/            # 25 business constraint validators
├── Services/         # 25+ pure calculation/formatting services
└── Workflows/        # 8 stateful long-running processes (Sagas)
```

### Component Count Summary

| Component Type | Count | Description |
|---------------|-------|-------------|
| Contracts | 28 | Interface definitions |
| DTOs | 90+ | Data transfer objects |
| Enums | 22 | Type-safe enumerations |
| Events | 30+ | Domain events |
| Services | 25+ | Business logic services |
| Rules | 25 | Validation rules |
| Coordinators | 12 | Flow orchestrators |
| DataProviders | 8 | Data aggregation |
| Workflows | 8 | Stateful processes |
| Listeners | 15 | Event handlers |
| **Total** | **~250+** | Production-ready components |

---

## Package Dependencies

| Package | Purpose |
|---------|---------|
| `Nexus\Procurement` | Requisition, PO, Goods Receipt management |
| `Nexus\Payable` | Vendor bills, invoice matching, payments |
| `Nexus\Inventory` | Stock receipt and management |
| `Nexus\JournalEntry` | GL posting for accruals and payments |
| `Nexus\Budget` | Budget commitment and release |
| `Nexus\Workflow` | Approval workflows |
| `Nexus\Party` | Vendor management |
| `Nexus\Period` | Fiscal period validation |
| `Nexus\Currency` | Multi-currency support |
| `Nexus\Tax` | Tax calculation and withholding |
| `Nexus\Notifier` | Notifications |
| `Nexus\AuditLogger` | Audit trail |
| `Nexus\MachineLearning` | Anomaly detection |
| `Nexus\Connector` | External integrations |
| `Nexus\Setting` | Configuration |

---

## Quick Start

### 1. Requisition Workflow

```php
use Nexus\ProcurementOperations\Coordinators\RequisitionCoordinator;
use Nexus\ProcurementOperations\DTOs\CreateRequisitionRequest;

$coordinator = $container->get(RequisitionCoordinator::class);

$result = $coordinator->create(new CreateRequisitionRequest(
    tenantId: 'tenant-123',
    requestedBy: 'user-456',
    departmentId: 'dept-789',
    lineItems: [
        [
            'productId' => 'prod-001',
            'description' => 'Office Supplies',
            'quantity' => 100,
            'estimatedUnitPriceCents' => 1500,
            'uom' => 'EA',
        ],
    ],
    justification: 'Monthly office supplies replenishment',
));

if ($result->success) {
    echo "Requisition created: {$result->requisitionId}";
}
```

### 2. Three-Way Matching

```php
use Nexus\ProcurementOperations\Coordinators\InvoiceMatchingCoordinator;
use Nexus\ProcurementOperations\DTOs\MatchInvoiceRequest;

$coordinator = $container->get(InvoiceMatchingCoordinator::class);

$result = $coordinator->match(new MatchInvoiceRequest(
    tenantId: 'tenant-123',
    vendorBillId: 'bill-001',
    purchaseOrderId: 'po-001',
    goodsReceiptIds: ['gr-001', 'gr-002'],
    performedBy: 'user-456',
));

if ($result->matched) {
    echo "Invoice matched successfully!";
} else {
    echo "Match failed: {$result->failureReason}";
    print_r($result->variances);
}
```

### 3. Payment Processing with Early Payment Discount

```php
use Nexus\ProcurementOperations\Coordinators\PaymentProcessingCoordinator;
use Nexus\ProcurementOperations\DTOs\Financial\ProcessPaymentRequest;

$coordinator = $container->get(PaymentProcessingCoordinator::class);

$result = $coordinator->process(new ProcessPaymentRequest(
    tenantId: 'tenant-123',
    vendorBillIds: ['bill-001', 'bill-002'],
    paymentMethod: 'ACH',
    bankAccountId: 'bank-001',
    scheduledDate: new \DateTimeImmutable('+3 days'),
    processedBy: 'user-456',
    captureEarlyPaymentDiscount: true,
));

if ($result->success) {
    echo "Payment scheduled: {$result->paymentReference}";
    echo "Discount captured: {$result->discountCaptured->format()}";
}
```

### 4. SOX 404 Compliance Validation

```php
use Nexus\ProcurementOperations\Coordinators\SoxComplianceCoordinator;
use Nexus\ProcurementOperations\DTOs\Sox\SoxComplianceRequest;

$coordinator = $container->get(SoxComplianceCoordinator::class);

$result = $coordinator->validateCompliance(new SoxComplianceRequest(
    tenantId: 'tenant-123',
    period: '2024-Q4',
    controlAreas: ['PROCUREMENT', 'PAYMENTS', 'VENDOR_MANAGEMENT'],
    includeEvidencePackage: true,
));

if ($result->isCompliant) {
    echo "SOX 404 Compliant - Score: {$result->complianceScore}%";
} else {
    foreach ($result->findings as $finding) {
        echo "{$finding->severity}: {$finding->description}";
    }
}
```

### 5. Vendor Onboarding

```php
use Nexus\ProcurementOperations\Workflows\VendorOnboardingWorkflow;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorOnboardingRequest;

$workflow = $container->get(VendorOnboardingWorkflow::class);

$result = $workflow->start(new VendorOnboardingRequest(
    tenantId: 'tenant-123',
    vendorName: 'Acme Supplies Ltd',
    registrationNumber: 'REG-12345',
    taxId: 'TAX-67890',
    primaryContact: [
        'name' => 'John Smith',
        'email' => 'john@acme.com',
        'phone' => '+1-555-0100',
    ],
    bankDetails: [
        'bankName' => 'First National Bank',
        'accountNumber' => '****1234',
        'routingNumber' => '021000021',
    ],
    complianceCertifications: ['ISO9001', 'SOC2'],
));

echo "Onboarding workflow started: {$result->workflowId}";
echo "Status: {$result->status->value}"; // PENDING_VERIFICATION
```

### 6. Spend Analytics

```php
use Nexus\ProcurementOperations\Coordinators\SpendAnalyticsCoordinator;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendAnalysisRequest;

$coordinator = $container->get(SpendAnalyticsCoordinator::class);

$result = $coordinator->analyze(new SpendAnalysisRequest(
    tenantId: 'tenant-123',
    periodStart: new \DateTimeImmutable('2024-01-01'),
    periodEnd: new \DateTimeImmutable('2024-12-31'),
    dimensions: ['category', 'vendor', 'department'],
    includeMaverickAnalysis: true,
));

echo "Total Spend: {$result->totalSpend->format()}";
echo "Contract Compliance: {$result->contractComplianceRate}%";
echo "Maverick Spend: {$result->maverickSpend->format()} ({$result->maverickRate}%)";
```

### 7. Multi-Entity Shared Services

```php
use Nexus\ProcurementOperations\Services\SharedServicesCenter;
use Nexus\ProcurementOperations\DTOs\MultiEntity\SharedProcurementRequest;

$sharedServices = $container->get(SharedServicesCenter::class);

$result = $sharedServices->processRequest(new SharedProcurementRequest(
    requestingEntityId: 'entity-001',
    serviceType: 'PROCUREMENT',
    items: [
        ['productId' => 'prod-001', 'quantity' => 1000],
    ],
    deliveryEntities: ['entity-001', 'entity-002'],
));

echo "Shared request ID: {$result->requestId}";
echo "Allocated to entities: " . implode(', ', $result->allocations);
```

---

## SOX 404 Compliance Framework

### COSO Framework Mapping

The orchestrator maps all controls to COSO framework components:

| COSO Component | Control Areas |
|----------------|---------------|
| Control Environment | Vendor Management, User Access |
| Risk Assessment | Budget Control, Spend Policies |
| Control Activities | Approvals, Matching, Segregation of Duties |
| Information & Communication | Audit Trail, Notifications |
| Monitoring Activities | Control Testing, Analytics |

### Control Testing

```php
use Nexus\ProcurementOperations\Services\ControlTestingService;
use Nexus\ProcurementOperations\DTOs\Sox\ControlTestRequest;

$testingService = $container->get(ControlTestingService::class);

$result = $testingService->executeTest(new ControlTestRequest(
    tenantId: 'tenant-123',
    controlId: 'CTRL-PR-001',
    testingPeriod: '2024-Q4',
    sampleSize: 25, // PCAOB standard
    testProcedures: ['vouching', 'recalculation', 'observation'],
));

if ($result->isEffective) {
    echo "Control is operating effectively";
    echo "Exceptions: {$result->exceptionCount}/{$result->sampleSize}";
} else {
    echo "Control deficiency detected: {$result->deficiencyType}";
}
```

### Evidence Package Generation

```php
use Nexus\ProcurementOperations\Services\Sox404EvidenceService;
use Nexus\ProcurementOperations\DTOs\Sox\EvidencePackageRequest;

$evidenceService = $container->get(Sox404EvidenceService::class);

$package = $evidenceService->generatePackage(new EvidencePackageRequest(
    tenantId: 'tenant-123',
    auditPeriod: '2024',
    controlAreas: ['ALL'],
    format: 'PDF',
));

echo "Evidence package generated: {$package->packageId}";
echo "Total documents: {$package->documentCount}";
echo "Ready for external audit: " . ($package->isComplete ? 'Yes' : 'No');
```

---

## Segregation of Duties (SoD)

### Enforced Conflicts

| Duty 1 | Duty 2 | Conflict Type |
|--------|--------|---------------|
| Create Requisition | Approve Requisition | Same person cannot approve their own request |
| Create PO | Approve PO | Same person cannot approve their own PO |
| Receive Goods | Approve Invoice | Prevents collusion |
| Create Vendor | Approve Vendor | Prevents ghost vendors |
| Process Payment | Approve Payment | Prevents unauthorized payments |

### SoD Validation

```php
use Nexus\ProcurementOperations\Rules\SegregationOfDutiesRule;
use Nexus\ProcurementOperations\DTOs\Sox\SodCheckRequest;

$sodRule = $container->get(SegregationOfDutiesRule::class);

$result = $sodRule->validate(new SodCheckRequest(
    userId: 'user-123',
    action: 'APPROVE_PAYMENT',
    relatedActions: [
        ['action' => 'CREATE_INVOICE', 'performedBy' => 'user-123'],
    ],
));

if (!$result->passed) {
    throw new SodViolationException($result->conflictDetails);
}
```

---

## Document Retention

### Retention Categories

| Category | Retention Period | Disposal Method |
|----------|-----------------|-----------------|
| SOX Financial Data | 7 years | Secure Shred |
| Tax Records | 7 years | Secure Deletion |
| Vendor Contracts | 7 years post-expiry | Archive |
| Purchase Orders | 7 years | Archive |
| Invoices | 7 years | Archive |
| Goods Receipts | 7 years | Archive |
| Vendor Correspondence | 3 years | Standard |
| RFQ/RFP Documents | 5 years | Archive |

### Legal Hold Management

```php
use Nexus\ProcurementOperations\Services\DocumentRetentionService;
use Nexus\ProcurementOperations\DTOs\Audit\LegalHoldRequest;

$retentionService = $container->get(DocumentRetentionService::class);

// Place documents on legal hold
$holdResult = $retentionService->placeLegalHold(new LegalHoldRequest(
    tenantId: 'tenant-123',
    matterReference: 'Litigation #2024-001',
    documentTypes: ['VENDOR_CONTRACTS', 'PURCHASE_ORDERS'],
    vendorIds: ['vendor-456'],
    initiatedBy: 'legal-counsel',
    reason: 'Ongoing vendor dispute litigation',
));

echo "Legal hold placed: {$holdResult->holdId}";
echo "Documents affected: {$holdResult->documentCount}";
```

---

## Compliance Services (Phase B)

### ApprovalLimitsManager

Manages configurable approval thresholds by role, department, and user.

```php
use Nexus\ProcurementOperations\Services\Approval\ApprovalLimitsManager;
use Nexus\ProcurementOperations\DTOs\ApprovalLimitCheckRequest;

$manager = $container->get(ApprovalLimitsManager::class);

// Check if user can approve a requisition
$result = $manager->checkApprovalLimit(new ApprovalLimitCheckRequest(
    tenantId: 'tenant-123',
    approverId: 'user-456',
    documentType: 'requisition',
    amountCents: 500000, // $5,000
    currency: 'USD',
));

if ($result->canApprove) {
    echo "User authorized to approve";
    echo "Authority level: {$result->authorityLevel}";
} else {
    echo "Approval limit exceeded: {$result->reason}";
}

// Get user's approval authority
$authority = $manager->getApprovalAuthority('tenant-123', 'user-456');
echo "Max requisition: {$authority->getMaxAmountForType('requisition')} cents";
```

### DocumentRetentionService

Manages document lifecycle with regulatory compliance.

```php
use Nexus\ProcurementOperations\Services\Compliance\DocumentRetentionService;

$service = $container->get(DocumentRetentionService::class);

// Apply retention policy to a document
$result = $service->applyRetentionPolicy(
    tenantId: 'tenant-123',
    documentId: 'doc-001',
    documentType: 'purchase_order',
    createdDate: new \DateTimeImmutable('2024-01-15'),
);

echo "Retention expires: {$result['expiresAt']->format('Y-m-d')}";

// Check disposal eligibility (respects legal holds)
$eligibility = $service->checkDisposalEligibility(
    tenantId: 'tenant-123',
    documentId: 'doc-001',
);

if ($eligibility['eligible']) {
    echo "Document can be disposed";
} else {
    echo "Blocked: {$eligibility['reason']}";
    // e.g., "Document is under legal hold: LH-2024-001"
}

// Apply legal hold
$service->applyLegalHold(
    tenantId: 'tenant-123',
    legalHoldId: 'LH-2024-002',
    documentIds: ['doc-001', 'doc-002', 'doc-003'],
    reason: 'Pending litigation - Jones v. Acme Corp',
);
```

### ProcurementAuditService

SOX 404 compliance audit and evidence generation.

```php
use Nexus\ProcurementOperations\Services\Compliance\ProcurementAuditService;

$service = $container->get(ProcurementAuditService::class);

// Generate SOX 404 evidence package
$evidence = $service->generateSox404EvidencePackage(
    tenantId: 'tenant-123',
    periodStart: new \DateTimeImmutable('2024-01-01'),
    periodEnd: new \DateTimeImmutable('2024-12-31'),
);

echo "Control areas covered: " . implode(', ', $evidence['controlAreas']);
echo "Evidence items: {$evidence['evidenceCount']}";
echo "Compliant: " . ($evidence['overallCompliant'] ? 'Yes' : 'No');

// Get Segregation of Duties report
$sodReport = $service->getSegregationOfDutiesReport('tenant-123');

foreach ($sodReport['conflictGroups'] as $group) {
    echo "Users with conflicts in {$group['conflictType']}: ";
    echo implode(', ', $group['users']);
}

// Perform control test
$testResult = $service->performControlTest(
    tenantId: 'tenant-123',
    controlId: 'CTRL-PR-001',
    sampleSize: 25,
    testProcedures: ['vouching', 'recalculation'],
);

echo "Control effective: " . ($testResult['isEffective'] ? 'Yes' : 'No');
echo "Exceptions found: {$testResult['exceptionsFound']}";
```

---

## Event-Driven Integration

### Published Events

| Event | Description |
|-------|-------------|
| `RequisitionCreatedEvent` | New requisition submitted |
| `RequisitionApprovedEvent` | Requisition approved |
| `PurchaseOrderIssuedEvent` | PO sent to vendor |
| `GoodsReceiptCompletedEvent` | Goods received |
| `InvoiceMatchedEvent` | 3-way match successful |
| `PaymentExecutedEvent` | Payment processed |
| `SoxControlTestedEvent` | Control test completed |
| `SodViolationDetectedEvent` | SoD conflict found |
| `SpendPolicyViolatedEvent` | Policy breach detected |
| `MaverickSpendDetectedEvent` | Off-contract spend identified |
| `VendorOnboardedEvent` | Vendor activated |
| `VendorSuspendedEvent` | Vendor put on hold |

### Event Listeners

| Event | Listener | Action |
|-------|----------|--------|
| `RequisitionApprovedEvent` | `CreatePurchaseOrderListener` | Auto-create PO |
| `GoodsReceiptCompletedEvent` | `TriggerMatchingListener` | Initiate 3-way match |
| `InvoiceMatchedEvent` | `SchedulePaymentListener` | Queue for payment |
| `PaymentExecutedEvent` | `PostJournalEntryListener` | Post GL entries |
| `SpendPolicyViolatedEvent` | `NotifyComplianceTeamListener` | Send alerts |
| `SodViolationDetectedEvent` | `CreateAuditFindingListener` | Log for audit |

---

## Configuration

Configure via `Nexus\Setting`:

| Setting Key | Default | Description |
|-------------|---------|-------------|
| `procurement.matching.price_tolerance_percent` | `5.0` | Max price variance (%) |
| `procurement.matching.quantity_tolerance_percent` | `2.0` | Max quantity variance (%) |
| `procurement.matching.auto_match_enabled` | `true` | Auto-match on GR complete |
| `procurement.payment.batch_size` | `50` | Max invoices per payment batch |
| `procurement.payment.default_method` | `ACH` | Default payment method |
| `procurement.payment.early_discount_enabled` | `true` | Capture early payment discounts |
| `procurement.sox.control_test_sample_size` | `25` | PCAOB standard sample size |
| `procurement.sox.evidence_retention_years` | `7` | Evidence retention period |
| `procurement.vendor.onboarding_auto_approve` | `false` | Auto-approve vendors |
| `procurement.spend.maverick_threshold_percent` | `5.0` | Maverick spend alert threshold |

---

## Testing

Run the test suite:

```bash
# Run all tests
./vendor/bin/phpunit orchestrators/ProcurementOperations/tests

# Run specific test category
./vendor/bin/phpunit orchestrators/ProcurementOperations/tests/Unit/Rules

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage orchestrators/ProcurementOperations/tests
```

### Test Coverage Summary

| Component | Test Files | Test Cases |
|-----------|-----------|------------|
| Rules | 25 | 200+ |
| Services | 20+ | 150+ |
| Coordinators | 12 | 80+ |
| Workflows | 8 | 60+ |
| DTOs | 20+ | 100+ |
| **Total** | **85+** | **590+** |

---

## Migration from v1.x

If upgrading from ProcurementOperations v1.x:

1. **Namespace changes**: No breaking namespace changes
2. **New dependencies**: Add `Nexus\MachineLearning` for anomaly detection
3. **Configuration migration**: See `docs/MIGRATION_GUIDE.md`
4. **Database migrations**: Run new migrations for audit tables

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidelines.

---

## License

MIT License - see [LICENSE](LICENSE) file.
