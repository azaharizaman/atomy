# Getting Started with Nexus Payable

## Prerequisites

- PHP 8.3 or higher
- Composer
- Understanding of Accounts Payable concepts (vendor bills, 3-way matching, payment processing)
- Familiarity with dependency injection and interfaces

## Installation

```bash
composer require nexus/payable:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Managing vendor bills and invoices
- ✅ Implementing 3-way matching (PO, GR, Invoice)
- ✅ Payment scheduling and processing
- ✅ Vendor management and aging reports
- ✅ GL integration for AP transactions
- ✅ Multi-currency vendor bill processing

Do NOT use this package for:
- ❌ Customer invoicing (use `Nexus\Receivable` instead)
- ❌ General ledger management (use `Nexus\Finance` instead)
- ❌ Purchase order creation (use `Nexus\Procurement` instead)
- ❌ Inventory receiving (use `Nexus\Inventory` instead)

---

## Core Concepts

### Concept 1: 3-Way Matching

**The Problem:**
Vendors may overbill or ship incorrect quantities. Without proper controls, companies can overpay or pay for goods not received.

**The Solution:**
3-way matching compares three documents before approving a vendor bill for payment:
1. **Purchase Order (PO)** - What you ordered
2. **Goods Receipt (GR)** - What you received
3. **Vendor Bill (Invoice)** - What the vendor is charging

**How It Works:**
```
PO Line:    100 units × $10.00 = $1,000
GR Line:    98 units received
Bill Line:  98 units × $10.00 = $980

Match Result: ✅ Matched (quantity matches GR, price matches PO)
```

**With Tolerances:**
```php
// Configure tolerances
$tolerance = new MatchingTolerance(
    qtyTolerancePercent: 5.0,   // Allow 5% quantity variance
    priceTolerancePercent: 2.0  // Allow 2% price variance
);

// Example: Bill has 102 units (2% over PO's 100) → Within tolerance → Approved
// Example: Bill has 110 units (10% over PO's 100) → Exceeds tolerance → Variance Review
```

### Concept 2: Payment Terms

Payment terms define when a bill is due and any early payment discounts:

| Term | Description | Due Date |
|------|-------------|----------|
| `NET_15` | Payment due 15 days after bill date | Bill Date + 15 days |
| `NET_30` | Payment due 30 days after bill date | Bill Date + 30 days |
| `NET_60` | Payment due 60 days after bill date | Bill Date + 60 days |
| `2_10_NET_30` | 2% discount if paid within 10 days, otherwise net 30 | Bill Date + 10 days (discount) or +30 days |

**Example:**
```
Bill Date: 2024-01-15
Terms: 2_10_NET_30
Discount Due Date: 2024-01-25 (10 days) - Save 2%
Net Due Date: 2024-02-14 (30 days)

Bill Amount: $1,000
If paid by 2024-01-25: $980 (save $20)
If paid after 2024-01-25: $1,000
```

### Concept 3: Bill Status Lifecycle

Vendor bills flow through the following statuses:

```
draft → pending_matching → matched → variance_review → approved → posted → paid
  ↓         ↓                  ↓            ↓              ↓         ↓       ↓
  └─────────────────────────────────────────────────────────────→ cancelled
```

- **draft** - Bill created but not submitted for matching
- **pending_matching** - Waiting for 3-way match process
- **matched** - Successfully matched (within tolerance)
- **variance_review** - Variance detected, requires approval
- **approved** - Approved for posting to GL
- **posted** - Posted to general ledger (liability recorded)
- **paid** - Payment processed
- **cancelled** - Bill voided

---

## Basic Configuration

### Step 1: Understand Package Architecture

The Nexus\Payable package is **framework-agnostic** and defines only interfaces and business logic:

**Package Provides:**
- ✅ Interfaces (21 interfaces)
- ✅ Service classes (8 services)
- ✅ Value objects (2 VOs)
- ✅ Enums (5 enums)
- ✅ Exceptions (8 exceptions)

**Application Layer Provides (Your Code):**
- ✅ Eloquent models (implementing interfaces)
- ✅ Database migrations
- ✅ Repository implementations
- ✅ Controllers
- ✅ Routes

### Step 2: Implement Required Interfaces

You MUST implement these repository interfaces in your application:

```php
namespace App\Repositories\Payable;

use Nexus\Payable\Contracts\VendorRepositoryInterface;
use Nexus\Payable\Contracts\VendorInterface;
use App\Models\Vendor;

final readonly class EloquentVendorRepository implements VendorRepositoryInterface
{
    public function findById(string $id): VendorInterface
    {
        return Vendor::findOrFail($id);
    }
    
    public function findByCode(string $code): ?VendorInterface
    {
        return Vendor::where('code', $code)->first();
    }
    
    public function save(VendorInterface $vendor): void
    {
        $vendor->save();
    }
    
    public function delete(string $id): void
    {
        Vendor::destroy($id);
    }
    
    // ... implement other methods
}
```

**Required Repository Implementations:**
1. `VendorRepositoryInterface` - Vendor CRUD
2. `VendorBillRepositoryInterface` - Bill CRUD
3. `PaymentScheduleRepositoryInterface` - Payment schedule CRUD
4. `PaymentRepositoryInterface` - Payment CRUD
5. `PurchaseOrderRepositoryInterface` - PO data (from Nexus\Procurement)
6. `GoodsReceivedRepositoryInterface` - GR data (from Nexus\Inventory)

### Step 3: Bind Interfaces in Service Provider

**Laravel Example:**

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Payable\Contracts\VendorRepositoryInterface;
use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
use Nexus\Payable\Contracts\PayableManagerInterface;
use Nexus\Payable\Services\PayableManager;
use App\Repositories\Payable\EloquentVendorRepository;
use App\Repositories\Payable\EloquentVendorBillRepository;

class PayableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            VendorRepositoryInterface::class,
            EloquentVendorRepository::class
        );
        
        $this->app->singleton(
            VendorBillRepositoryInterface::class,
            EloquentVendorBillRepository::class
        );
        
        // Bind manager
        $this->app->singleton(
            PayableManagerInterface::class,
            PayableManager::class
        );
    }
}
```

**Symfony Example (services.yaml):**

```yaml
services:
    # Repository bindings
    Nexus\Payable\Contracts\VendorRepositoryInterface:
        class: App\Repository\Payable\VendorRepository
        
    Nexus\Payable\Contracts\VendorBillRepositoryInterface:
        class: App\Repository\Payable\VendorBillRepository
        
    # Manager binding
    Nexus\Payable\Contracts\PayableManagerInterface:
        class: Nexus\Payable\Services\PayableManager
        arguments:
            $vendorRepository: '@Nexus\Payable\Contracts\VendorRepositoryInterface'
            $billRepository: '@Nexus\Payable\Contracts\VendorBillRepositoryInterface'
```

### Step 4: Create Eloquent Models

**Vendor Model:**

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Payable\Contracts\VendorInterface;
use Nexus\Payable\Enums\VendorStatus;
use Nexus\Payable\Enums\PaymentTerm;

class Vendor extends Model implements VendorInterface
{
    protected $fillable = [
        'code', 'name', 'status', 'payment_terms',
        'qty_tolerance_percent', 'price_tolerance_percent',
        'currency', 'email', 'tax_id'
    ];
    
    protected $casts = [
        'status' => VendorStatus::class,
        'payment_terms' => PaymentTerm::class,
        'qty_tolerance_percent' => 'float',
        'price_tolerance_percent' => 'float',
    ];
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getCode(): string
    {
        return $this->code;
    }
    
    public function getStatus(): VendorStatus
    {
        return $this->status;
    }
    
    public function getPaymentTerms(): PaymentTerm
    {
        return $this->payment_terms;
    }
    
    // ... implement other interface methods
}
```

### Step 5: Create Database Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('status', 20); // active, inactive, blocked, pending_approval
            $table->string('payment_terms', 20); // net_15, net_30, etc.
            $table->decimal('qty_tolerance_percent', 5, 2)->default(0);
            $table->decimal('price_tolerance_percent', 5, 2)->default(0);
            $table->string('currency', 3)->default('MYR');
            $table->string('email')->nullable();
            $table->string('tax_id')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
        });
        
        Schema::create('vendor_bills', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('vendor_id', 26);
            $table->string('bill_number', 50);
            $table->date('bill_date');
            $table->date('due_date');
            $table->string('currency', 3);
            $table->string('status', 30); // draft, pending_matching, matched, etc.
            $table->string('matching_status', 30); // pending, matched, variance_review, failed
            $table->decimal('total_amount', 19, 4);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->text('description')->nullable();
            $table->string('gl_journal_id', 26)->nullable();
            $table->timestamps();
            
            $table->foreign('vendor_id')->references('id')->on('vendors');
            $table->unique(['tenant_id', 'vendor_id', 'bill_number']);
            $table->index(['tenant_id', 'status']);
        });
        
        Schema::create('vendor_bill_lines', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('vendor_bill_id', 26);
            $table->integer('line_number');
            $table->text('description');
            $table->decimal('quantity', 19, 4);
            $table->decimal('unit_price', 19, 4);
            $table->decimal('line_amount', 19, 4);
            $table->string('gl_account', 20);
            $table->string('po_line_reference', 50)->nullable();
            $table->string('grn_line_reference', 50)->nullable();
            $table->timestamps();
            
            $table->foreign('vendor_bill_id')->references('id')->on('vendor_bills')->onDelete('cascade');
        });
    }
};
```

---

## Your First Integration

### Complete Example: Create and Process a Vendor Bill

```php
use Nexus\Payable\Contracts\PayableManagerInterface;
use Nexus\Payable\Enums\PaymentMethod;

final readonly class VendorBillController
{
    public function __construct(
        private PayableManagerInterface $payableManager
    ) {}
    
    public function createAndProcessBill(): void
    {
        // Step 1: Create vendor bill
        $billId = $this->payableManager->createVendorBill(
            vendorId: 'vendor-uuid-here',
            billNumber: 'INV-2024-001',
            billDate: new \DateTimeImmutable('2024-01-15'),
            dueDate: new \DateTimeImmutable('2024-02-14'),
            currency: 'MYR',
            lines: [
                [
                    'description' => 'Office Supplies',
                    'quantity' => 100.0,
                    'unit_price' => 10.50,
                    'gl_account' => '6100',
                    'po_line_reference' => 'PO-001-L1',
                    'grn_line_reference' => 'GRN-001-L1',
                ],
            ],
            taxAmount: 0.00,
            description: 'Monthly office supplies'
        );
        
        // Step 2: Perform 3-way matching
        $matchResult = $this->payableManager->submitBillForMatching($billId);
        
        if ($matchResult->isMatched() && $matchResult->isWithinTolerance()) {
            // Step 3: Approve bill (auto-approved if within tolerance)
            $this->payableManager->approveBill($billId);
            
            // Step 4: Post to general ledger
            $this->payableManager->postBillToGL($billId);
            
            // Step 5: Schedule payment
            $this->payableManager->schedulePayment(
                billId: $billId,
                paymentDate: new \DateTimeImmutable('2024-02-14')
            );
            
            // Step 6: Process payment
            $this->payableManager->processPayment(
                paymentDate: new \DateTimeImmutable('2024-02-14'),
                amount: 1050.00,
                currency: 'MYR',
                paymentMethod: PaymentMethod::BANK_TRANSFER,
                bankAccount: '1010',
                reference: 'TXN-20240214-001',
                allocations: [
                    ['bill_id' => $billId, 'amount' => 1050.00]
                ]
            );
            
            echo "Bill processed successfully!\n";
        } else {
            // Variance detected - send to approval workflow
            echo "Variance detected. Variances: " . json_encode($matchResult->getVariances()) . "\n";
        }
    }
}
```

---

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples
- See [Examples](examples/) for more code samples
- Review `REQUIREMENTS.md` for complete feature list
- Review `IMPLEMENTATION_SUMMARY.md` for architecture details

---

## Troubleshooting

### Common Issues

**Issue 1: Interface not bound**

**Error:**
```
Target interface [Nexus\Payable\Contracts\PayableManagerInterface] is not instantiable.
```

**Cause:** Service provider not registered or interface not bound

**Solution:**
```php
// In Laravel's AppServiceProvider or PayableServiceProvider
$this->app->singleton(
    PayableManagerInterface::class,
    PayableManager::class
);
```

---

**Issue 2: 3-way match fails**

**Error:**
```
MatchingFailedException: PO line not found for bill line
```

**Cause:** Bill line references PO/GRN that doesn't exist

**Solution:**
- Verify `po_line_reference` matches an actual PO line ID
- Verify `grn_line_reference` matches an actual GRN line ID
- Ensure Nexus\Procurement and Nexus\Inventory are properly integrated

---

**Issue 3: Tolerance exceeded**

**Error:**
```
Matching status: variance_review (Quantity variance: 10%)
```

**Cause:** Bill quantity/price exceeds configured tolerances

**Solution:**
- Adjust vendor tolerance configuration:
  ```php
  $vendor->setQtyTolerancePercent(10.0); // Allow 10% variance
  ```
- Or manually override the match:
  ```php
  $this->payableManager->overrideMatchingFailure($billId, 'Approved by manager');
  ```

---

**Issue 4: Duplicate bill detection**

**Error:**
```
DuplicateBillException: Bill INV-2024-001 already exists for this vendor
```

**Cause:** Bill number already exists for the same vendor

**Solution:**
- Use unique bill numbers per vendor
- Or append suffix: `INV-2024-001-R1` (revision 1)

---

**Issue 5: Bill state transition error**

**Error:**
```
InvalidBillStateException: Cannot approve bill in status 'draft'
```

**Cause:** Attempting to approve bill before matching

**Solution:**
- Submit bill for matching first: `submitBillForMatching()`
- Then approve: `approveBill()`

---

## Configuration

### Matching Tolerances

Configure default tolerances in your service provider:

```php
use Nexus\Payable\ValueObjects\MatchingTolerance;

// Global default
$defaultTolerance = new MatchingTolerance(
    qtyTolerancePercent: 5.0,
    priceTolerancePercent: 2.0
);

// Per-vendor override (stored in vendor master data)
$vendor->setQtyTolerancePercent(10.0);  // Allow 10% for this vendor
```

### Payment Terms

Configure available payment terms via enum:

```php
use Nexus\Payable\Enums\PaymentTerm;

// Available terms
PaymentTerm::NET_15;
PaymentTerm::NET_30;
PaymentTerm::NET_60;
PaymentTerm::NET_90;
PaymentTerm::DUE_ON_RECEIPT;
PaymentTerm::COD;
PaymentTerm::TWO_10_NET_30;  // 2% discount if paid within 10 days

// Calculate due date
$term = PaymentTerm::NET_30;
$billDate = new \DateTimeImmutable('2024-01-15');
$dueDate = $term->calculateDueDate($billDate); // 2024-02-14
```

### Early Payment Discounts

```php
// 2/10 Net 30: 2% discount if paid within 10 days
$term = PaymentTerm::TWO_10_NET_30;
$billDate = new \DateTimeImmutable('2024-01-15');
$billAmount = 1000.00;

$discountDeadline = $term->calculateDiscountDeadline($billDate); // 2024-01-25
$discountAmount = $term->calculateDiscount($billAmount); // 20.00
$netAmount = $billAmount - $discountAmount; // 980.00
```

---

**Last Updated:** 2024-11-25  
**Package Version:** 1.0.0
