# Nexus\Procurement

**Framework-agnostic procurement management package for Nexus ERP**

The Procurement package provides a comprehensive, pure PHP solution for purchase requisitions, purchase orders, goods receipts, 3-way matching, and vendor quote management. It follows strict contract-driven design principles and integrates seamlessly with the Nexus monorepo architecture.

## Features

- ✅ **Pure PHP 8.3+** - No framework dependencies in core logic
- ✅ **Contract-Driven** - All data structures and operations defined via interfaces
- ✅ **Purchase Requisition Management** - Complete workflow from draft to approval to PO conversion
- ✅ **Purchase Order Processing** - Create POs from requisitions or directly with budget validation
- ✅ **Goods Receipt Notes (GRN)** - Record and validate received goods against purchase orders
- ✅ **3-Way Matching Engine** - Validate Invoice-PO-GRN alignment (<500ms for 100 lines)
- ✅ **Vendor Quote Management** - RFQ process and quote comparison
- ✅ **Segregation of Duties** - Requester ≠ Approver ≠ Receiver ≠ Payment Authorizer
- ✅ **Budget Controls** - PO cannot exceed requisition by >10% without re-approval
- ✅ **AI-Powered Analytics** - 7 ML feature extractors for fraud detection and optimization
- ✅ **Multi-Tenant** - Tenant-scoped requisitions, POs, and GRNs

## Installation

```bash
composer require nexus/procurement:"*@dev"
```

## Architecture

### Package Structure

```
packages/Procurement/
├── src/
│   ├── Contracts/              # 19 Interfaces
│   │   ├── ProcurementManagerInterface.php
│   │   ├── RequisitionInterface.php
│   │   ├── RequisitionLineInterface.php
│   │   ├── RequisitionRepositoryInterface.php
│   │   ├── PurchaseOrderInterface.php
│   │   ├── PurchaseOrderLineInterface.php
│   │   ├── PurchaseOrderRepositoryInterface.php
│   │   ├── GoodsReceiptNoteInterface.php
│   │   ├── GoodsReceiptLineInterface.php
│   │   ├── GoodsReceiptRepositoryInterface.php
│   │   ├── VendorQuoteInterface.php
│   │   ├── VendorQuoteRepositoryInterface.php
│   │   └── ... (7 analytics repository interfaces)
│   ├── Services/               # 6 Business Logic Services
│   │   ├── ProcurementManager.php
│   │   ├── RequisitionManager.php
│   │   ├── PurchaseOrderManager.php
│   │   ├── GoodsReceiptManager.php
│   │   ├── MatchingEngine.php
│   │   └── VendorQuoteManager.php
│   ├── MachineLearning/        # 7 ML Feature Extractors
│   │   ├── VendorFraudDetectionExtractor.php
│   │   ├── VendorPricingAnomalyExtractor.php
│   │   ├── RequisitionApprovalRiskExtractor.php
│   │   ├── BudgetOverrunPredictionExtractor.php
│   │   ├── GRNDiscrepancyPredictionExtractor.php
│   │   ├── POConversionEfficiencyExtractor.php
│   │   └── ProcurementPOQtyExtractor.php
│   └── Exceptions/             # 10 Domain Exceptions
│       ├── ProcurementException.php
│       ├── RequisitionNotFoundException.php
│       ├── PurchaseOrderNotFoundException.php
│       ├── GoodsReceiptNotFoundException.php
│       ├── InvalidRequisitionDataException.php
│       ├── InvalidRequisitionStateException.php
│       ├── InvalidPurchaseOrderDataException.php
│       ├── InvalidGoodsReceiptDataException.php
│       ├── BudgetExceededException.php
│       └── UnauthorizedApprovalException.php
├── composer.json
├── LICENSE
└── README.md
```

### Core Principles

1. **Logic in Packages, Implementation in Applications**
   - Package defines **what** (interfaces, services, value objects)
   - Application defines **how** (Eloquent models, repositories, migrations)

2. **Framework Agnostic**
   - Zero Laravel dependencies in `src/`
   - No `Illuminate\*` classes
   - No Eloquent models
   - No database queries

3. **Dependency Injection**
   - Constructor injection for all dependencies
   - Interface-based dependencies only

## Usage Examples

### Create Purchase Requisition

```php
use Nexus\Procurement\Contracts\ProcurementManagerInterface;

$procurement = app(ProcurementManagerInterface::class);

$requisition = $procurement->createRequisition(
    tenantId: 'tenant-001',
    requesterId: 'user-123',
    data: [
        'number' => 'REQ-2025-001',
        'description' => 'Office supplies for Q1',
        'department' => 'Administration',
        'lines' => [
            [
                'item_code' => 'PAPER-A4',
                'description' => 'A4 Paper 500 sheets',
                'quantity' => 10,
                'unit' => 'box',
                'estimated_unit_price' => 25.00,
            ],
        ],
    ]
);
```

### Approve Requisition (with Segregation of Duties)

```php
use Nexus\Procurement\Exceptions\UnauthorizedApprovalException;

try {
    $approvedRequisition = $procurement->approveRequisition(
        requisitionId: $requisition->getId(),
        approverId: 'manager-456' // Must NOT be the requester
    );
} catch (UnauthorizedApprovalException $e) {
    // BUS-PRO-0095 violation: Requester tried to approve own requisition
}
```

### Convert Requisition to Purchase Order

```php
use Nexus\Procurement\Exceptions\BudgetExceededException;

try {
    $po = $procurement->convertRequisitionToPO(
        tenantId: 'tenant-001',
        requisitionId: $requisition->getId(),
        creatorId: 'buyer-789',
        poData: [
            'number' => 'PO-2025-001',
            'vendor_id' => 'vendor-xyz',
            'lines' => [
                [
                    'requisition_line_id' => $requisition->getLines()[0]->getId(),
                    'quantity' => 10,
                    'unit_price' => 24.50, // Within 10% of estimate
                    'unit' => 'box',
                    'item_code' => 'PAPER-A4',
                    'description' => 'A4 Paper 500 sheets',
                ],
            ],
        ]
    );
} catch (BudgetExceededException $e) {
    // PO exceeds requisition by more than 10%
}
```

### Record Goods Receipt

```php
$grn = $procurement->recordGoodsReceipt(
    tenantId: 'tenant-001',
    poId: $po->getId(),
    receiverId: 'warehouse-clerk-001', // Must NOT be PO creator
    receiptData: [
        'number' => 'GRN-2025-001',
        'received_date' => '2025-11-20',
        'lines' => [
            [
                'po_line_reference' => 'PO-2025-001-L001',
                'quantity_received' => 10, // Cannot exceed PO quantity
                'unit' => 'box',
            ],
        ],
    ]
);
```

### Three-Way Matching

```php
$matchResult = $procurement->performThreeWayMatch(
    poLine: $poLine,
    grnLine: $grnLine,
    invoiceLineData: [
        'quantity' => 10,
        'unit_price' => 24.50,
        'line_total' => 245.00,
    ]
);

if ($matchResult['matched']) {
    echo "✅ Auto-approved: {$matchResult['recommendation']}";
} else {
    echo "⚠️  Manual review: {$matchResult['recommendation']}";
    print_r($matchResult['discrepancies']);
}
```

## Business Rules

### Segregation of Duties (3-Person Rule)

The package enforces strict segregation of duties for fraud prevention:

| Action | Cannot Be Performed By |
|--------|------------------------|
| Approve Requisition | Requester (BUS-PRO-0095) |
| Create GRN | PO Creator (BUS-PRO-0100) |
| Authorize Payment | GRN Creator (BUS-PRO-0105) |

### Budget Controls

- **BUS-PRO-0069**: PO total cannot exceed requisition by >10% without re-approval
- **BUS-PRO-0110**: Blanket PO releases cannot exceed committed value
- **BUS-PRO-0041**: Requisition must have at least one line item
- **BUS-PRO-0076**: GRN quantity cannot exceed PO quantity

## AI Intelligence Features

The package includes **7 production-ready ML feature extractors** for AI-powered procurement optimization:

| Extractor | Features | Purpose |
|-----------|----------|---------|
| **VendorFraudDetectionExtractor** | 25 | Real-time fraud screening on PO creation |
| **VendorPricingAnomalyExtractor** | 22 | Cost optimization through pricing validation |
| **RequisitionApprovalRiskExtractor** | 20 | Predict approval delays and prioritize requisitions |
| **BudgetOverrunPredictionExtractor** | 16 | Prevent budget violations before approval |
| **GRNDiscrepancyPredictionExtractor** | 18 | Predict goods receipt issues before delivery |
| **POConversionEfficiencyExtractor** | 14 | Predict requisition-to-PO conversion time |
| **ProcurementPOQtyExtractor** | 12 | Quantity prediction for reordering |

### Integration with Nexus\Intelligence

```php
use Nexus\Procurement\MachineLearning\VendorFraudDetectionExtractor;
use Nexus\Intelligence\Contracts\IntelligenceManagerInterface;

$features = $fraudExtractor->extract($poTransaction);
$result = $intelligence->evaluate('procurement_fraud_check', $features);

if ($result->isFlagged() && $result->getSeverity() === SeverityLevel::CRITICAL) {
    throw new FraudDetectedException($result->getReason());
}
```

## Integration Points

- **Nexus\Payable**: Provides PO and GRN data for 3-way matching
- **Nexus\Uom**: Unit of measurement validation
- **Nexus\Currency**: Multi-currency support
- **Nexus\Workflow**: Requisition approval workflows
- **Nexus\AuditLogger**: Comprehensive change tracking
- **Nexus\Intelligence**: AI-powered analytics and predictions
- **Nexus\Sequencing**: Auto-numbering for REQ/PO/GRN

## Exception Handling

All domain exceptions extend `ProcurementException`:

```php
use Nexus\Procurement\Exceptions\{
    RequisitionNotFoundException,
    PurchaseOrderNotFoundException,
    GoodsReceiptNotFoundException,
    InvalidRequisitionDataException,
    InvalidRequisitionStateException,
    InvalidPurchaseOrderDataException,
    InvalidGoodsReceiptDataException,
    BudgetExceededException,
    UnauthorizedApprovalException
};

try {
    $requisition = $procurement->getRequisition($requisitionId);
} catch (RequisitionNotFoundException $e) {
    // Handle requisition not found
}

try {
    $approved = $procurement->approveRequisition($id, $approverId);
} catch (UnauthorizedApprovalException $e) {
    // Handle unauthorized approval attempt
}
```

## Performance

- **3-Way Matching**: <500ms for 100-line invoices (PER-PRO-0341)
- **Requisition Creation**: <200ms with eager loading
- **PO Generation**: <300ms with budget validation

## Requirements Addressed

This package addresses all requirements in REQUIREMENTS.md:

- ✅ BUS-PRO-0041 to BUS-PRO-0124: 15 Business requirements
- ✅ FUN-PRO-0235 to FUN-PRO-0271: 7 Functional requirements
- ✅ PER-PRO-0327 to PER-PRO-0353: 5 Performance requirements
- ✅ REL-PRO-0389 to REL-PRO-0407: 4 Reliability requirements
- ✅ SEC-PRO-0441 to SEC-PRO-0470: 6 Security requirements
- ✅ USE-PRO-0508 to USE-PRO-0548: 7 User stories

**Total:** 44 requirements

## Testing

Package tests use mocks for all repository implementations:

```php
use Nexus\Procurement\Services\ProcurementManager;
use Nexus\Procurement\Contracts\RequisitionRepositoryInterface;
use PHPUnit\Framework\TestCase;

class ProcurementManagerTest extends TestCase
{
    public function test_create_requisition(): void
    {
        $mockRepo = $this->createMock(RequisitionRepositoryInterface::class);
        $mockRepo->expects($this->once())
            ->method('create')
            ->willReturn($this->createMock(RequisitionInterface::class));
        
        $manager = new ProcurementManager($mockRepo, ...);
        // ... test logic
    }
}
```

## 📖 Documentation

### Package Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide with prerequisites, core concepts, and first integration
- **[API Reference](docs/api-reference.md)** - Complete documentation of all 19 interfaces, 6 services, and 10 exceptions
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples with complete setup instructions
- **[Basic Usage Example](docs/examples/basic-usage.php)** - Simple usage patterns for requisitions, POs, and GRNs
- **[Advanced Usage Example](docs/examples/advanced-usage.php)** - Advanced scenarios including ML extractors and batch matching

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress, metrics, and key design decisions
- `REQUIREMENTS.md` - All 44 requirements with status tracking
- `TEST_SUITE_SUMMARY.md` - Test coverage metrics and test inventory
- `VALUATION_MATRIX.md` - Package valuation metrics for funding assessment
- See root `../../ARCHITECTURE.md` for overall system architecture
- See `../../docs/NEXUS_PACKAGES_REFERENCE.md` for package ecosystem reference

## License

MIT License. See [LICENSE](LICENSE) file for details.
