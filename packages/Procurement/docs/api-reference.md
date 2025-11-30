# API Reference: Procurement

Complete API documentation for all interfaces, services, and exceptions.

---

## Interfaces (12 total)

### Core Entity Interfaces

#### ProcurementManagerInterface

**Location:** `src/Contracts/ProcurementManagerInterface.php`

**Purpose:** Main orchestrator for all procurement operations.

**Methods:**

| Method | Parameters | Returns | Throws |
|--------|------------|---------|--------|
| `createRequisition` | `string $tenantId, string $requesterId, array $data` | `RequisitionInterface` | `InvalidRequisitionDataException` |
| `submitRequisitionForApproval` | `string $requisitionId` | `RequisitionInterface` | `RequisitionNotFoundException`, `InvalidRequisitionStateException` |
| `approveRequisition` | `string $requisitionId, string $approverId` | `RequisitionInterface` | `RequisitionNotFoundException`, `UnauthorizedApprovalException` |
| `rejectRequisition` | `string $requisitionId, string $rejectorId, string $reason` | `RequisitionInterface` | `RequisitionNotFoundException` |
| `convertRequisitionToPO` | `string $tenantId, string $requisitionId, string $creatorId, array $poData` | `PurchaseOrderInterface` | `RequisitionNotFoundException`, `InvalidRequisitionStateException`, `BudgetExceededException` |
| `createDirectPO` | `string $tenantId, string $creatorId, array $data` | `PurchaseOrderInterface` | `InvalidPurchaseOrderDataException` |
| `releasePO` | `string $poId, string $releasedBy` | `PurchaseOrderInterface` | `PurchaseOrderNotFoundException` |
| `recordGoodsReceipt` | `string $tenantId, string $poId, string $receiverId, array $receiptData` | `GoodsReceiptNoteInterface` | `PurchaseOrderNotFoundException`, `InvalidGoodsReceiptDataException` |
| `getRequisition` | `string $id` | `RequisitionInterface` | `RequisitionNotFoundException` |
| `getPurchaseOrder` | `string $id` | `PurchaseOrderInterface` | `PurchaseOrderNotFoundException` |
| `getGoodsReceipt` | `string $id` | `GoodsReceiptNoteInterface` | `GoodsReceiptNotFoundException` |
| `performThreeWayMatch` | `PurchaseOrderLineInterface $poLine, GoodsReceiptLineInterface $grnLine, array $invoiceLineData` | `array` | - |
| `authorizeGrnPayment` | `string $grnId, string $authorizerId` | `GoodsReceiptNoteInterface` | `UnauthorizedApprovalException` |

**Example:**
```php
use Nexus\Procurement\Contracts\ProcurementManagerInterface;

$manager = app(ProcurementManagerInterface::class);
$requisition = $manager->createRequisition($tenantId, $requesterId, $data);
```

---

#### RequisitionInterface

**Location:** `src/Contracts/RequisitionInterface.php`

**Purpose:** Purchase requisition entity contract.

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `getId()` | `string` | Requisition ULID |
| `getRequisitionNumber()` | `string` | Human-readable number (e.g., "REQ-2024-001") |
| `getRequesterId()` | `string` | User ULID who created the requisition |
| `getStatus()` | `string` | draft\|pending_approval\|approved\|rejected\|converted |
| `getTotalEstimate()` | `float` | Sum of line item estimates |
| `getLines()` | `array<RequisitionLineInterface>` | Requisition line items |
| `getApprovedBy()` | `?string` | User ULID who approved |
| `getApprovedAt()` | `?DateTimeImmutable` | Approval timestamp |
| `getCreatedAt()` | `DateTimeImmutable` | Creation timestamp |

**Example:**
```php
$requisition = $manager->getRequisition($id);
echo $requisition->getRequisitionNumber(); // "REQ-2024-001"
echo $requisition->getStatus(); // "approved"
```

---

#### RequisitionLineInterface

**Location:** `src/Contracts/RequisitionLineInterface.php`

**Purpose:** Individual line item on a requisition.

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `getId()` | `string` | Line ULID |
| `getRequisitionId()` | `string` | Parent requisition ULID |
| `getLineNumber()` | `int` | Sequential line number |
| `getItemCode()` | `string` | Product/item code |
| `getDescription()` | `string` | Line description |
| `getQuantity()` | `float` | Requested quantity |
| `getUnit()` | `string` | Unit of measure |
| `getEstimatedUnitPrice()` | `float` | Estimated price per unit |
| `getLineTotal()` | `float` | Quantity × Estimated Price |

---

#### PurchaseOrderInterface

**Location:** `src/Contracts/PurchaseOrderInterface.php`

**Purpose:** Purchase order entity contract (compatible with Nexus\Payable).

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `getId()` | `string` | PO ULID |
| `getPoNumber()` | `string` | Human-readable number (e.g., "PO-2024-001") |
| `getVendorId()` | `string` | Vendor ULID |
| `getRequisitionId()` | `?string` | Parent requisition ULID (null for direct POs) |
| `getStatus()` | `string` | draft\|released\|partially_received\|fully_received\|closed |
| `getTotalAmount()` | `float` | Sum of line totals |
| `getCurrency()` | `string` | ISO 4217 currency code |
| `getLines()` | `array<PurchaseOrderLineInterface>` | PO line items |
| `getCreatedAt()` | `DateTimeImmutable` | Creation timestamp |
| `getReleasedAt()` | `?DateTimeImmutable` | Release timestamp |

---

#### PurchaseOrderLineInterface

**Location:** `src/Contracts/PurchaseOrderLineInterface.php`

**Purpose:** Individual line item on a purchase order.

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `getId()` | `string` | Line ULID |
| `getPurchaseOrderId()` | `string` | Parent PO ULID |
| `getLineReference()` | `string` | Unique line reference (e.g., "PO-2024-001-L001") |
| `getLineNumber()` | `int` | Sequential line number |
| `getItemCode()` | `string` | Product/item code |
| `getDescription()` | `string` | Line description |
| `getQuantity()` | `float` | Ordered quantity |
| `getUnit()` | `string` | Unit of measure |
| `getUnitPrice()` | `float` | Price per unit |
| `getLineTotal()` | `float` | Quantity × Unit Price |
| `getQuantityReceived()` | `float` | Quantity received via GRN |

**Note:** `getLineReference()` is the key used for 3-way matching with `Nexus\Payable`.

---

#### GoodsReceiptNoteInterface

**Location:** `src/Contracts/GoodsReceiptNoteInterface.php`

**Purpose:** Goods receipt note entity contract.

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `getId()` | `string` | GRN ULID |
| `getGrnNumber()` | `string` | Human-readable number (e.g., "GRN-2024-001") |
| `getPurchaseOrderId()` | `string` | Parent PO ULID |
| `getReceiverId()` | `string` | User ULID who received goods |
| `getStatus()` | `string` | draft\|confirmed\|payment_authorized |
| `getReceivedDate()` | `DateTimeImmutable` | Date goods were received |
| `getLines()` | `array<GoodsReceiptLineInterface>` | GRN line items |
| `getPaymentAuthorizerId()` | `?string` | User ULID who authorized payment |
| `getPaymentAuthorizedAt()` | `?DateTimeImmutable` | Payment authorization timestamp |

---

#### GoodsReceiptLineInterface

**Location:** `src/Contracts/GoodsReceiptLineInterface.php`

**Purpose:** Individual line item on a goods receipt.

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `getId()` | `string` | Line ULID |
| `getGoodsReceiptNoteId()` | `string` | Parent GRN ULID |
| `getPoLineReference()` | `string` | PO line reference for matching |
| `getLineNumber()` | `int` | Sequential line number |
| `getQuantity()` | `float` | Quantity received |
| `getUnit()` | `string` | Unit of measure |
| `getNotes()` | `?string` | Receiving notes |

---

#### VendorQuoteInterface

**Location:** `src/Contracts/VendorQuoteInterface.php`

**Purpose:** Vendor quote entity for RFQ process.

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `getId()` | `string` | Quote ULID |
| `getQuoteNumber()` | `string` | Quote reference number |
| `getRequisitionId()` | `string` | Parent requisition ULID |
| `getVendorId()` | `string` | Vendor ULID |
| `getStatus()` | `string` | pending\|accepted\|rejected\|expired |
| `getQuotedDate()` | `DateTimeImmutable` | Date quote was submitted |
| `getValidUntil()` | `DateTimeImmutable` | Quote expiration date |
| `getTotalAmount()` | `float` | Total quoted amount |
| `getLines()` | `array` | Quote line items |

---

### Repository Interfaces

#### RequisitionRepositoryInterface

**Location:** `src/Contracts/RequisitionRepositoryInterface.php`

**Purpose:** Persistence operations for requisitions.

**Methods:**

| Method | Parameters | Returns |
|--------|------------|---------|
| `findById` | `string $id` | `RequisitionInterface` |
| `findByTenant` | `string $tenantId` | `array<RequisitionInterface>` |
| `findByStatus` | `string $tenantId, string $status` | `array<RequisitionInterface>` |
| `create` | `array $data` | `RequisitionInterface` |
| `updateStatus` | `string $id, string $status` | `RequisitionInterface` |
| `markAsConverted` | `string $id, string $poId` | `RequisitionInterface` |

---

#### PurchaseOrderRepositoryInterface

**Location:** `src/Contracts/PurchaseOrderRepositoryInterface.php`

**Purpose:** Persistence operations for purchase orders.

**Methods:**

| Method | Parameters | Returns |
|--------|------------|---------|
| `findById` | `string $id` | `PurchaseOrderInterface` |
| `findByTenant` | `string $tenantId` | `array<PurchaseOrderInterface>` |
| `findByVendor` | `string $vendorId` | `array<PurchaseOrderInterface>` |
| `findLineByReference` | `string $lineReference` | `?PurchaseOrderLineInterface` |
| `create` | `array $data` | `PurchaseOrderInterface` |
| `updateStatus` | `string $id, string $status` | `PurchaseOrderInterface` |

---

#### GoodsReceiptRepositoryInterface

**Location:** `src/Contracts/GoodsReceiptRepositoryInterface.php`

**Purpose:** Persistence operations for goods receipt notes.

**Methods:**

| Method | Parameters | Returns |
|--------|------------|---------|
| `findById` | `string $id` | `GoodsReceiptNoteInterface` |
| `findByPurchaseOrder` | `string $poId` | `array<GoodsReceiptNoteInterface>` |
| `findLineByReference` | `string $poLineReference` | `?GoodsReceiptLineInterface` |
| `create` | `array $data` | `GoodsReceiptNoteInterface` |
| `authorizePayment` | `string $id, string $authorizerId` | `GoodsReceiptNoteInterface` |

---

#### VendorQuoteRepositoryInterface

**Location:** `src/Contracts/VendorQuoteRepositoryInterface.php`

**Purpose:** Persistence operations for vendor quotes.

**Methods:**

| Method | Parameters | Returns |
|--------|------------|---------|
| `findById` | `string $id` | `VendorQuoteInterface` |
| `findByRequisition` | `string $requisitionId` | `array<VendorQuoteInterface>` |
| `create` | `array $data` | `VendorQuoteInterface` |
| `updateStatus` | `string $id, string $status` | `VendorQuoteInterface` |

---



---

## Services (6 total)

### ProcurementManager

**Location:** `src/Services/ProcurementManager.php`

**Purpose:** Main orchestrator implementing `ProcurementManagerInterface`. Delegates to specialized managers.

**Constructor Dependencies:**
- `RequisitionManager`
- `PurchaseOrderManager`
- `GoodsReceiptManager`
- `MatchingEngine`
- `VendorQuoteManager`

---

### RequisitionManager

**Location:** `src/Services/RequisitionManager.php`

**Purpose:** Requisition lifecycle management.

**Key Methods:**
- `createRequisition(string $tenantId, string $requesterId, array $data): RequisitionInterface`
- `submitForApproval(string $requisitionId): RequisitionInterface`
- `approveRequisition(string $requisitionId, string $approverId): RequisitionInterface`
- `rejectRequisition(string $requisitionId, string $rejectorId, string $reason): RequisitionInterface`
- `markAsConverted(string $requisitionId, PurchaseOrderInterface $po): RequisitionInterface`

**Business Rules Enforced:**
- BUS-PRO-0041: Requisition must have at least one line
- BUS-PRO-0095: Requester cannot approve own requisition
- BUS-PRO-0055: Approved requisitions are immutable

---

### PurchaseOrderManager

**Location:** `src/Services/PurchaseOrderManager.php`

**Purpose:** PO creation and blanket PO management.

**Key Methods:**
- `createFromRequisition(string $tenantId, string $requisitionId, string $creatorId, array $data): PurchaseOrderInterface`
- `createBlanketPo(string $tenantId, string $creatorId, array $data): PurchaseOrderInterface`
- `createBlanketRelease(string $blanketPoId, string $creatorId, array $data): PurchaseOrderInterface`
- `approvePo(string $poId, string $approverId): PurchaseOrderInterface`

**Business Rules Enforced:**
- BUS-PRO-0069: PO cannot exceed requisition by >10%
- BUS-PRO-0110: Blanket PO releases cannot exceed committed value

---

### GoodsReceiptManager

**Location:** `src/Services/GoodsReceiptManager.php`

**Purpose:** GRN creation and payment authorization.

**Key Methods:**
- `createGoodsReceipt(string $tenantId, string $poId, string $receiverId, array $data): GoodsReceiptNoteInterface`
- `authorizePayment(string $grnId, string $authorizerId): GoodsReceiptNoteInterface`

**Business Rules Enforced:**
- BUS-PRO-0076: GRN quantity ≤ PO quantity
- BUS-PRO-0100: PO creator cannot create GRN
- BUS-PRO-0105: GRN creator cannot authorize payment

---

### MatchingEngine

**Location:** `src/Services/MatchingEngine.php`

**Purpose:** Three-way matching (PO ↔ GRN ↔ Invoice).

**Constructor:**
```php
public function __construct(
    private LoggerInterface $logger,
    private float $quantityTolerancePercent = 5.0,
    private float $priceTolerancePercent = 5.0
)
```

**Key Methods:**

#### performThreeWayMatch()

```php
public function performThreeWayMatch(
    PurchaseOrderLineInterface $poLine,
    GoodsReceiptLineInterface $grnLine,
    array $invoiceLineData
): array
```

**Returns:**
```php
[
    'matched' => bool,
    'discrepancies' => [
        'quantity' => ['po_value' => x, 'grn_value' => y, 'invoice_value' => z, 'variance_percent' => n],
        'unit_price' => [...],
        'line_total' => [...],
    ],
    'recommendation' => 'APPROVE: All values match within tolerance.' | 'REVIEW REQUIRED: ...'
]
```

**Performance Target:** <500ms for 100-line invoices (PER-PRO-0341)

#### performBatchMatch()

```php
public function performBatchMatch(array $matchSet): array
```

**Returns:**
```php
[
    'overall_matched' => bool,
    'total_lines' => int,
    'matched_lines' => int,
    'discrepancy_lines' => int,
    'line_results' => [...],
    'elapsed_ms' => float,
]
```

---

### VendorQuoteManager

**Location:** `src/Services/VendorQuoteManager.php`

**Purpose:** RFQ process and quote comparison.

**Key Methods:**
- `createQuote(string $tenantId, string $requisitionId, array $data): VendorQuoteInterface`
- `acceptQuote(string $quoteId, string $acceptorId): VendorQuoteInterface`
- `rejectQuote(string $quoteId, string $reason): VendorQuoteInterface`
- `compareQuotes(string $requisitionId): array`

---

## Exceptions (10 total)

### ProcurementException

**Location:** `src/Exceptions/ProcurementException.php`

**Purpose:** Base exception for all procurement errors.

---

### RequisitionNotFoundException

**Location:** `src/Exceptions/RequisitionNotFoundException.php`

**Extends:** `ProcurementException`

**Thrown When:** Requisition not found by ID.

```php
throw new RequisitionNotFoundException("Requisition not found: {$id}");
```

---

### PurchaseOrderNotFoundException

**Location:** `src/Exceptions/PurchaseOrderNotFoundException.php`

**Extends:** `ProcurementException`

**Thrown When:** Purchase order not found by ID.

---

### GoodsReceiptNotFoundException

**Location:** `src/Exceptions/GoodsReceiptNotFoundException.php`

**Extends:** `ProcurementException`

**Thrown When:** Goods receipt note not found by ID.

---

### InvalidRequisitionDataException

**Location:** `src/Exceptions/InvalidRequisitionDataException.php`

**Extends:** `ProcurementException`

**Factory Methods:**

| Method | Description |
|--------|-------------|
| `noLines()` | Requisition has no line items (BUS-PRO-0041) |
| `invalidQuantity(string $itemCode)` | Quantity ≤ 0 |
| `invalidPrice(string $itemCode)` | Price < 0 |

---

### InvalidRequisitionStateException

**Location:** `src/Exceptions/InvalidRequisitionStateException.php`

**Extends:** `ProcurementException`

**Factory Methods:**

| Method | Description |
|--------|-------------|
| `notPendingApproval(string $id, string $status)` | Cannot approve requisition not in pending_approval |
| `cannotEditApproved(string $id)` | Cannot edit approved requisition (BUS-PRO-0055) |
| `alreadyConverted(string $id)` | Requisition already converted to PO |

---

### InvalidPurchaseOrderDataException

**Location:** `src/Exceptions/InvalidPurchaseOrderDataException.php`

**Extends:** `ProcurementException`

**Factory Methods:**

| Method | Description |
|--------|-------------|
| `noLines()` | PO has no line items |
| `invalidVendor(string $vendorId)` | Invalid vendor ID |

---

### InvalidGoodsReceiptDataException

**Location:** `src/Exceptions/InvalidGoodsReceiptDataException.php`

**Extends:** `ProcurementException`

**Factory Methods:**

| Method | Description |
|--------|-------------|
| `quantityExceedsPo(string $lineRef, float $grnQty, float $poQty)` | GRN qty > PO qty (BUS-PRO-0076) |
| `noLines()` | GRN has no line items |

---

### BudgetExceededException

**Location:** `src/Exceptions/BudgetExceededException.php`

**Extends:** `ProcurementException`

**Factory Methods:**

| Method | Description |
|--------|-------------|
| `poExceedsRequisition(string $poNumber, float $poTotal, float $reqTotal, float $tolerance)` | PO exceeds requisition by >tolerance% (BUS-PRO-0069) |
| `blanketPoReleaseExceedsTotal(string $blanketPoId, float $releaseAmount, float $remaining)` | Release exceeds remaining blanket amount (BUS-PRO-0110) |

---

### UnauthorizedApprovalException

**Location:** `src/Exceptions/UnauthorizedApprovalException.php`

**Extends:** `ProcurementException`

**Factory Methods:**

| Method | Description |
|--------|-------------|
| `cannotApproveOwnRequisition(string $requisitionId, string $userId)` | Requester ≠ Approver (BUS-PRO-0095) |
| `cannotCreateGrnForOwnPo(string $poId, string $userId)` | PO Creator ≠ GRN Creator (BUS-PRO-0100) |
| `cannotAuthorizePaymentForOwnGrn(string $grnId, string $userId)` | GRN Creator ≠ Payment Authorizer (BUS-PRO-0105) |

---

## Usage Patterns

### Pattern 1: Complete Procurement Workflow

```php
use Nexus\Procurement\Contracts\ProcurementManagerInterface;

// Step 1: Create requisition
$req = $manager->createRequisition($tenantId, $requesterId, $reqData);

// Step 2: Submit for approval
$req = $manager->submitRequisitionForApproval($req->getId());

// Step 3: Approve (by different user)
$req = $manager->approveRequisition($req->getId(), $approverId);

// Step 4: Convert to PO
$po = $manager->convertRequisitionToPO($tenantId, $req->getId(), $buyerId, $poData);

// Step 5: Release PO
$po = $manager->releasePO($po->getId(), $buyerId);

// Step 6: Record goods receipt (by different user)
$grn = $manager->recordGoodsReceipt($tenantId, $po->getId(), $receiverId, $grnData);

// Step 7: Authorize payment (by different user)
$grn = $manager->authorizeGrnPayment($grn->getId(), $authorizerId);
```

### Pattern 2: 3-Way Matching Integration

```php
use Nexus\Procurement\Services\MatchingEngine;

// Called by Nexus\Payable when processing invoice
$result = $matchingEngine->performThreeWayMatch($poLine, $grnLine, [
    'quantity' => $invoiceLine->getQuantity(),
    'unit_price' => $invoiceLine->getUnitPrice(),
    'line_total' => $invoiceLine->getLineTotal(),
]);

if ($result['matched']) {
    // Auto-approve invoice line
} else {
    // Flag for manual review
}
```

### Pattern 3: Batch Matching

```php
$matchSet = [];
foreach ($invoiceLines as $index => $invoiceLine) {
    $matchSet[] = [
        'po_line' => $poRepository->findLineByReference($invoiceLine['po_line_ref']),
        'grn_line' => $grnRepository->findLineByReference($invoiceLine['po_line_ref']),
        'invoice_line' => $invoiceLine,
    ];
}

$batchResult = $matchingEngine->performBatchMatch($matchSet);

if ($batchResult['overall_matched']) {
    // All lines matched - approve bill
} else {
    // Review discrepancies
    foreach ($batchResult['line_results'] as $index => $result) {
        if (!$result['matched']) {
            // Flag line for review
        }
    }
}
```

---

**Note:** For complete API documentation, refer to source code docblocks. All public methods have comprehensive PHPDoc annotations.

**Last Updated:** 2025-11-26
