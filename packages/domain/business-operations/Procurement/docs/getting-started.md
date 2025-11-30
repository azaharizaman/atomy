# Getting Started with Nexus Procurement

## Prerequisites

- **PHP 8.3 or higher** (native enums, readonly properties, constructor property promotion)
- **Composer** for package management
- **PSR-3 compatible logger** (e.g., Monolog)

### Optional
- **Nexus\ProcurementML** for AI-powered fraud detection and analytics
- **Nexus\Workflow** for advanced approval workflows
- **Nexus\Currency** for multi-currency support

---

## When to Use This Package

This package is designed for:

✅ **Multi-tenant ERP/SaaS applications** requiring procurement management  
✅ **Enterprise applications** needing purchase requisition-to-PO-to-GRN workflows  
✅ **Applications with 3-way matching** requirements (PO ↔ GRN ↔ Invoice)  
✅ **Applications requiring segregation of duties** (fraud prevention)  
✅ **Applications needing framework flexibility** (Laravel today, Symfony tomorrow)  
✅ **Procurement with fraud detection and optimization**

Do NOT use this package for:

❌ **Simple e-commerce checkout** (too heavyweight, use simpler cart/order system)  
❌ **Consumer-facing product catalogs** (use `Nexus\Product` instead)  
❌ **Non-PHP applications** (package is PHP-only)  
❌ **Applications without multi-tenant requirements** (unless planning to scale)

---

## Core Concepts

### Concept 1: Framework Agnosticism

**Nexus Procurement** contains ZERO framework dependencies in its core. All business logic is pure PHP 8.3+.

- **The Package** defines WHAT needs to be done (interfaces, services)
- **The Application** defines HOW it's done (Eloquent models, Doctrine entities)

**Example:**
```php
// Package defines the contract
interface RequisitionRepositoryInterface {
    public function findById(string $id): RequisitionInterface;
}

// Laravel app provides the implementation
class EloquentRequisitionRepository implements RequisitionRepositoryInterface {
    public function findById(string $id): RequisitionInterface {
        return Requisition::findOrFail($id); // Eloquent model
    }
}
```

### Concept 2: Multi-Tenancy is Mandatory

EVERY entity in Procurement is **tenant-scoped** by design:
- Requisitions belong to a tenant
- Purchase Orders belong to a tenant
- Goods Receipts belong to a tenant

**You cannot bypass tenant isolation.** This is a security feature, not a bug.

```php
// Correct: Repository auto-scopes by tenant
$requisitions = $requisitionRepository->findByTenant($tenantId);

// Wrong: Trying to access cross-tenant data will fail
$otherTenantReq = $requisitionRepository->findById('req-from-other-tenant'); // Throws exception
```

### Concept 3: Segregation of Duties (3-Person Rule)

The package enforces **strict separation of duties** for fraud prevention:

| Step | Action | Performer | Cannot Be |
|------|--------|-----------|-----------|
| 1 | Create Requisition | Requester | - |
| 2 | Approve Requisition | Approver | Requester |
| 3 | Create PO from Requisition | Buyer | - |
| 4 | Receive Goods (GRN) | Receiver | PO Creator |
| 5 | Authorize Payment | Authorizer | GRN Creator |

This ensures **at least 3 different people** are involved in any purchase above the direct-PO threshold.

### Concept 4: Procurement Workflow States

**Requisition Status Flow:**
```
draft → pending_approval → approved → converted
                        ↘ rejected
```

**Purchase Order Status Flow:**
```
draft → released → partially_received → fully_received → closed
                                      ↘ cancelled
```

**GRN Status Flow:**
```
draft → confirmed → payment_authorized
```

### Concept 5: 3-Way Matching

The **MatchingEngine** validates invoice lines against PO and GRN:

```
Invoice Line ←→ GRN Line ←→ PO Line
    ↓              ↓           ↓
 Quantity      Quantity    Quantity (must match within tolerance)
 Unit Price      N/A      Unit Price (must match within tolerance)
 Line Total    Calculated   Line Total (must match within tolerance)
```

Default tolerances:
- **Quantity**: ±5%
- **Price**: ±5%

---

## Installation

```bash
composer require nexus/procurement:"*@dev"
```

---

## Basic Configuration

### Step 1: Implement Required Interfaces

The package requires 4 core repository interfaces:

#### 1.1 Requisition Repository

```php
namespace App\Repositories;

use Nexus\Procurement\Contracts\RequisitionRepositoryInterface;
use Nexus\Procurement\Contracts\RequisitionInterface;
use App\Models\Requisition;

final readonly class EloquentRequisitionRepository implements RequisitionRepositoryInterface
{
    public function findById(string $id): RequisitionInterface
    {
        return Requisition::with('lines')->findOrFail($id);
    }

    public function findByTenant(string $tenantId): array
    {
        return Requisition::where('tenant_id', $tenantId)
            ->with('lines')
            ->get()
            ->all();
    }

    public function create(array $data): RequisitionInterface
    {
        return Requisition::create($data);
    }

    public function updateStatus(string $id, string $status): RequisitionInterface
    {
        $requisition = Requisition::findOrFail($id);
        $requisition->update(['status' => $status]);
        return $requisition;
    }
}
```

#### 1.2 Purchase Order Repository

```php
namespace App\Repositories;

use Nexus\Procurement\Contracts\PurchaseOrderRepositoryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use App\Models\PurchaseOrder;

final readonly class EloquentPurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function findById(string $id): PurchaseOrderInterface
    {
        return PurchaseOrder::with('lines')->findOrFail($id);
    }

    public function findLineByReference(string $lineReference): ?PurchaseOrderLineInterface
    {
        return PurchaseOrderLine::where('line_reference', $lineReference)->first();
    }

    public function create(array $data): PurchaseOrderInterface
    {
        return PurchaseOrder::create($data);
    }
}
```

#### 1.3 Goods Receipt Repository

```php
namespace App\Repositories;

use Nexus\Procurement\Contracts\GoodsReceiptRepositoryInterface;
use Nexus\Procurement\Contracts\GoodsReceiptNoteInterface;
use App\Models\GoodsReceiptNote;

final readonly class EloquentGoodsReceiptRepository implements GoodsReceiptRepositoryInterface
{
    public function findById(string $id): GoodsReceiptNoteInterface
    {
        return GoodsReceiptNote::with('lines')->findOrFail($id);
    }

    public function findLineByReference(string $poLineReference): ?GoodsReceiptLineInterface
    {
        return GoodsReceiptLine::where('po_line_reference', $poLineReference)->first();
    }

    public function create(array $data): GoodsReceiptNoteInterface
    {
        return GoodsReceiptNote::create($data);
    }
}
```

### Step 2: Bind Interfaces in Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Procurement\Contracts\{
    RequisitionRepositoryInterface,
    PurchaseOrderRepositoryInterface,
    GoodsReceiptRepositoryInterface,
    VendorQuoteRepositoryInterface,
    ProcurementManagerInterface
};
use Nexus\Procurement\Services\{
    ProcurementManager,
    RequisitionManager,
    PurchaseOrderManager,
    GoodsReceiptManager,
    MatchingEngine,
    VendorQuoteManager
};
use App\Repositories\{
    EloquentRequisitionRepository,
    EloquentPurchaseOrderRepository,
    EloquentGoodsReceiptRepository,
    EloquentVendorQuoteRepository
};
use Psr\Log\LoggerInterface;

class ProcurementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->singleton(RequisitionRepositoryInterface::class, EloquentRequisitionRepository::class);
        $this->app->singleton(PurchaseOrderRepositoryInterface::class, EloquentPurchaseOrderRepository::class);
        $this->app->singleton(GoodsReceiptRepositoryInterface::class, EloquentGoodsReceiptRepository::class);
        $this->app->singleton(VendorQuoteRepositoryInterface::class, EloquentVendorQuoteRepository::class);

        // Matching Engine with configurable tolerances
        $this->app->singleton(MatchingEngine::class, function ($app) {
            return new MatchingEngine(
                logger: $app->make(LoggerInterface::class),
                quantityTolerancePercent: config('procurement.quantity_tolerance_percent', 5.0),
                priceTolerancePercent: config('procurement.price_tolerance_percent', 5.0)
            );
        });

        // Package services (auto-wired)
        $this->app->singleton(RequisitionManager::class);
        $this->app->singleton(PurchaseOrderManager::class);
        $this->app->singleton(GoodsReceiptManager::class);
        $this->app->singleton(VendorQuoteManager::class);
        $this->app->singleton(ProcurementManagerInterface::class, ProcurementManager::class);
    }
}
```

Register in `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\ProcurementServiceProvider::class,
],
```

### Step 3: Create Configuration File

Create `config/procurement.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Budget Tolerance
    |--------------------------------------------------------------------------
    | Maximum percentage PO can exceed requisition before requiring re-approval.
    */
    'po_tolerance_percent' => env('PROCUREMENT_PO_TOLERANCE_PERCENT', 10.0),

    /*
    |--------------------------------------------------------------------------
    | 3-Way Matching Tolerances
    |--------------------------------------------------------------------------
    | Tolerance percentages for quantity and price matching.
    */
    'quantity_tolerance_percent' => env('PROCUREMENT_QUANTITY_TOLERANCE_PERCENT', 5.0),
    'price_tolerance_percent' => env('PROCUREMENT_PRICE_TOLERANCE_PERCENT', 5.0),

    /*
    |--------------------------------------------------------------------------
    | Auto-Numbering Patterns
    |--------------------------------------------------------------------------
    | Document number patterns (requires Nexus\Sequencing).
    */
    'requisition_number_pattern' => env('PROCUREMENT_REQ_PATTERN', 'REQ-{YYYY}-{####}'),
    'po_number_pattern' => env('PROCUREMENT_PO_PATTERN', 'PO-{YYYY}-{####}'),
    'grn_number_pattern' => env('PROCUREMENT_GRN_PATTERN', 'GRN-{YYYY}-{####}'),
];
```

### Step 4: Create Eloquent Models

Your Eloquent models must implement package interfaces:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Procurement\Contracts\RequisitionInterface;

class Requisition extends Model implements RequisitionInterface
{
    protected $fillable = [
        'tenant_id',
        'number',
        'requester_id',
        'description',
        'status',
        'total_estimate',
    ];

    protected $casts = [
        'total_estimate' => 'decimal:4',
        'created_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function getId(): string
    {
        return $this->id;
    }

    public function getRequisitionNumber(): string
    {
        return $this->number;
    }

    public function getRequesterId(): string
    {
        return $this->requester_id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTotalEstimate(): float
    {
        return (float) $this->total_estimate;
    }

    public function getLines(): array
    {
        return $this->lines->all();
    }

    public function getApprovedBy(): ?string
    {
        return $this->approved_by;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approved_at 
            ? \DateTimeImmutable::createFromMutable($this->approved_at)
            : null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->created_at);
    }

    // Relationships
    public function lines()
    {
        return $this->hasMany(RequisitionLine::class);
    }
}
```

---

## Your First Integration

### Example 1: Create Requisition

```php
use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Exceptions\InvalidRequisitionDataException;

class RequisitionController
{
    public function __construct(
        private readonly ProcurementManagerInterface $procurement
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'department' => 'required|string',
            'lines' => 'required|array|min:1',
            'lines.*.item_code' => 'required|string',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit' => 'required|string',
            'lines.*.estimated_unit_price' => 'required|numeric|min:0',
        ]);

        try {
            $requisition = $this->procurement->createRequisition(
                tenantId: $request->user()->tenant_id,
                requesterId: $request->user()->id,
                data: [
                    'number' => $this->generateRequisitionNumber(),
                    'description' => $validated['description'],
                    'department' => $validated['department'],
                    'lines' => $validated['lines'],
                ]
            );

            return response()->json([
                'message' => 'Requisition created successfully',
                'requisition' => $requisition,
            ], 201);

        } catch (InvalidRequisitionDataException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
```

### Example 2: Approve Requisition

```php
use Nexus\Procurement\Exceptions\{
    RequisitionNotFoundException,
    UnauthorizedApprovalException,
    InvalidRequisitionStateException
};

public function approve(Request $request, string $requisitionId)
{
    try {
        $approved = $this->procurement->approveRequisition(
            requisitionId: $requisitionId,
            approverId: $request->user()->id
        );

        return response()->json([
            'message' => 'Requisition approved',
            'requisition' => $approved,
        ]);

    } catch (UnauthorizedApprovalException $e) {
        // Requester tried to approve own requisition
        return response()->json(['error' => $e->getMessage()], 403);

    } catch (InvalidRequisitionStateException $e) {
        // Requisition not in pending_approval state
        return response()->json(['error' => $e->getMessage()], 422);

    } catch (RequisitionNotFoundException $e) {
        return response()->json(['error' => 'Requisition not found'], 404);
    }
}
```

### Example 3: 3-Way Matching (called by Nexus\Payable)

```php
use Nexus\Procurement\Services\MatchingEngine;
use Nexus\Procurement\Contracts\{
    PurchaseOrderRepositoryInterface,
    GoodsReceiptRepositoryInterface
};

class BillController
{
    public function __construct(
        private readonly MatchingEngine $matchingEngine,
        private readonly PurchaseOrderRepositoryInterface $poRepository,
        private readonly GoodsReceiptRepositoryInterface $grnRepository
    ) {}

    public function validateInvoiceLine(array $invoiceLine)
    {
        $poLine = $this->poRepository->findLineByReference($invoiceLine['po_line_reference']);
        $grnLine = $this->grnRepository->findLineByReference($invoiceLine['po_line_reference']);

        if (!$poLine || !$grnLine) {
            return ['error' => 'PO or GRN line not found'];
        }

        $result = $this->matchingEngine->performThreeWayMatch(
            poLine: $poLine,
            grnLine: $grnLine,
            invoiceLineData: [
                'quantity' => $invoiceLine['quantity'],
                'unit_price' => $invoiceLine['unit_price'],
                'line_total' => $invoiceLine['line_total'],
            ]
        );

        return $result;
    }
}
```

---

## Next Steps

- **Read the [API Reference](api-reference.md)** for detailed interface documentation
- **Check [Integration Guide](integration-guide.md)** for complete Laravel/Symfony examples
- **See [Examples](examples/)** for more code samples
- **Review [REQUIREMENTS.md](../REQUIREMENTS.md)** for all 44 requirements

---

## Troubleshooting

### Common Issues

#### Issue 1: "Interface not bound"

**Error:**
```
Target [Nexus\Procurement\Contracts\RequisitionRepositoryInterface] is not instantiable.
```

**Cause:** Interface not bound in service provider

**Solution:**
```php
$this->app->singleton(
    RequisitionRepositoryInterface::class,
    EloquentRequisitionRepository::class
);
```

#### Issue 2: "Requester cannot approve own requisition"

**Error:**
```
UnauthorizedApprovalException: User 'user-123' cannot approve requisition 'req-456' - requester cannot approve own requisition.
```

**Cause:** User trying to approve their own requisition (BUS-PRO-0095 violation)

**Solution:** This is by design - have a different user (manager/supervisor) approve the requisition.

#### Issue 3: "PO exceeds requisition budget"

**Error:**
```
BudgetExceededException: PO amount exceeds requisition by more than 10%
```

**Cause:** PO total > requisition total × 1.10

**Solution:**
- Reduce PO quantity or unit price
- Increase tolerance in `config/procurement.php`
- Create a new requisition for the additional amount

#### Issue 4: "GRN quantity exceeds PO"

**Error:**
```
InvalidGoodsReceiptDataException: GRN quantity exceeds PO quantity
```

**Cause:** Trying to receive more than ordered (BUS-PRO-0076 violation)

**Solution:** Create multiple GRNs or correct the GRN quantity to match PO.

#### Issue 5: "3-Way match failing"

**Error:**
```
Recommendation: REVIEW REQUIRED: Quantity variance: 15.00%; Unit price variance: 8.50%
```

**Cause:** Invoice values don't match PO/GRN within tolerance

**Solution:**
- Check if values are correct
- Adjust tolerance in config if business allows
- Process as manual override with authorization

---

**Last Updated:** 2025-11-26  
**Package Version:** 1.0.0  
**Maintained By:** Nexus Architecture Team
