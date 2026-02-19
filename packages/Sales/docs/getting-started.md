# Getting Started with Nexus Sales

## Prerequisites

- PHP 8.3 or higher
- Composer
- Nexus\Party (for customer management)
- Nexus\Product (for product catalog)
- Nexus\Inventory (for stock reservation)
- Nexus\Receivable (for credit checking and invoicing)

## Installation

```bash
composer require nexus/sales:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Managing sales quotations and versions
- ✅ Converting quotations to sales orders
- ✅ Calculating complex pricing with tiers and discounts
- ✅ Validating credit limits and stock availability
- ✅ Processing sales returns (RMA)
- ✅ Generating invoices from orders

Do NOT use this package for:
- ❌ Managing inventory stock levels (use Nexus\Inventory)
- ❌ Generating invoices directly (use ReceivableInvoiceManager via this package)
- ❌ Managing customer relationships (use Nexus\Party)

## Core Concepts

### Concept 1: Quotation Lifecycle
Quotations start as drafts, can be versioned (v1, v2), sent to customers, accepted or rejected, and finally converted to orders.

```text
DRAFT → SENT → ACCEPTED → CONVERTED
              ↓
           REJECTED
```

### Concept 2: Sales Order Workflow
Orders progress through fulfillment stages, with credit checks and stock reservation at confirmation.

```text
DRAFT → CONFIRMED → SHIPPED → INVOICED → PAID
            ↓
         CANCELLED
```

### Concept 3: Integration Points
The Sales package integrates with:
- **Nexus\Inventory** - Stock reservation and availability checking
- **Nexus\Receivable** - Credit limit checking and invoice generation
- **Nexus\Sequencing** - Order and quotation numbering
- **Nexus\AuditLogger** - Audit trail for all state changes

## Basic Configuration

### Step 1: Implement Required Interfaces

The Sales package defines contracts that your application must implement:

```php
// Example: Implementing repositories in your application
namespace App\Repositories;

use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Contracts\SalesOrderInterface;

final readonly class EloquentSalesOrderRepository implements SalesOrderRepositoryInterface
{
    public function findById(string $orderId): ?SalesOrderInterface
    {
        return SalesOrder::find($orderId);
    }
    
    public function findByTenant(string $tenantId, int $page = 1, int $perPage = 20): array
    {
        return SalesOrder::where('tenant_id', $tenantId)->paginate($perPage)->items();
    }
    
    public function findByCustomer(string $tenantId, string $customerId): array
    {
        return SalesOrder::where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->get()
            ->all();
    }
    
    public function findByStatus(string $tenantId, string $status): array
    {
        return SalesOrder::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get()
            ->all();
    }
    
    public function save(SalesOrderInterface $order): SalesOrderInterface
    {
        $order->save();
        return $order;
    }
    
    public function delete(string $orderId): void
    {
        SalesOrder::destroy($orderId);
    }
}
```

### Step 2: Bind Interfaces in Service Provider

```php
// Laravel example - AppServiceProvider.php
public function register(): void
{
    // Repositories
    $this->app->bind(
        \Nexus\Sales\Contracts\SalesOrderRepositoryInterface::class,
        \App\Repositories\EloquentSalesOrderRepository::class
    );
    
    $this->app->bind(
        \Nexus\Sales\Contracts\QuotationRepositoryInterface::class,
        \App\Repositories\EloquentQuotationRepository::class
    );
    
    // Integration services - use adapter implementations
    $this->app->singleton(
        \Nexus\Sales\Contracts\StockReservationInterface::class,
        \Nexus\Laravel\Sales\Adapters\InventoryStockReservationAdapter::class
    );
    
    $this->app->singleton(
        \Nexus\Sales\Contracts\CreditLimitCheckerInterface::class,
        \Nexus\Laravel\Sales\Adapters\ReceivableCreditLimitCheckerAdapter::class
    );
    
    $this->app->singleton(
        \Nexus\Sales\Contracts\InvoiceManagerInterface::class,
        \Nexus\Laravel\Sales\Adapters\ReceivableInvoiceManagerAdapter::class
    );
    
    // Core services
    $this->app->singleton(\Nexus\Sales\Services\SalesOrderManager::class);
    $this->app->singleton(\Nexus\Sales\Services\QuotationManager::class);
    $this->app->singleton(\Nexus\Sales\Services\QuoteToOrderConverter::class);
}
```

### Step 3: Database Migrations

Run the migrations to create sales tables:

```php
// database/migrations/2026_02_19_000001_create_sales_tables.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('customer_id')->index();
            $table->string('order_number')->unique();
            $table->string('status', 30)->default('DRAFT')->index();
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->string('currency_code', 3)->default('MYR');
            $table->decimal('exchange_rate', 18, 6)->nullable();
            $table->timestamp('exchange_rate_locked_at')->nullable();
            $table->string('payment_term', 20)->nullable();
            $table->date('payment_due_date')->nullable();
            $table->string('customer_po')->nullable();
            $table->text('notes')->nullable();
            $table->ulid('salesperson_id')->nullable();
            $table->ulid('preferred_warehouse_id')->nullable();
            $table->ulid('source_quotation_id')->nullable();
            $table->string('source_quote_number')->nullable();
            $table->ulid('created_by')->index();
            $table->ulid('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'order_number']);
        });
        
        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('sales_order_id')->index();
            $table->foreign('sales_order_id')
                ->references('id')
                ->on('sales_orders')
                ->onDelete('cascade');
            $table->integer('line_number');
            $table->ulid('product_variant_id')->index();
            $table->string('product_name');
            $table->string('sku', 100);
            $table->decimal('quantity', 18, 4);
            $table->string('uom_code', 10);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->string('tax_code', 20)->nullable();
            $table->decimal('tax_rate', 5, 4)->default(0);
            $table->decimal('line_subtotal', 18, 4);
            $table->decimal('line_total', 18, 4);
            $table->decimal('quantity_shipped', 18, 4)->default(0);
            $table->decimal('quantity_invoiced', 18, 4)->default(0);
            $table->timestamps();
        });
        
        // Additional tables: quotations, quotation_lines, sales_returns, sales_return_lines
    }
};
```

## Your First Integration

### Complete Workflow Example

```php
use Nexus\Sales\Services\QuotationManager;
use Nexus\Sales\Services\QuoteToOrderConverter;
use Nexus\Sales\Services\SalesOrderManager;
use Nexus\Sales\Enums\QuoteStatus;
use Nexus\Sales\Enums\PaymentTerm;

final readonly class SalesController
{
    public function __construct(
        private QuotationManager $quotationManager,
        private QuoteToOrderConverter $quoteToOrderConverter,
        private SalesOrderManager $salesOrderManager
    ) {}
    
    /**
     * Step 1: Create a quotation
     */
    public function createQuotation(Request $request): JsonResponse
    {
        $quotation = $this->quotationManager->createQuotation(
            tenantId: $request->tenant_id,
            customerId: $request->customer_id,
            lines: $request->lines,
            data: [
                'quote_date' => new DateTimeImmutable(),
                'valid_until' => new DateTimeImmutable('+30 days'),
                'currency_code' => $request->currency_code ?? 'MYR',
                'prepared_by' => $request->user_id,
            ]
        );
        
        return response()->json([
            'id' => $quotation->getId(),
            'quote_number' => $quotation->getQuoteNumber(),
            'status' => $quotation->getStatus()->value,
        ]);
    }
    
    /**
     * Step 2: Send quotation to customer
     */
    public function sendQuotation(string $quoteId): JsonResponse
    {
        $this->quotationManager->sendQuotation($quoteId, 'user-id');
        
        return response()->json(['status' => 'sent']);
    }
    
    /**
     * Step 3: Accept quotation
     */
    public function acceptQuotation(string $quoteId): JsonResponse
    {
        $this->quotationManager->acceptQuotation($quoteId, 'user-id');
        
        return response()->json(['status' => 'accepted']);
    }
    
    /**
     * Step 4: Convert to order
     */
    public function convertToOrder(string $quoteId): JsonResponse
    {
        $order = $this->quoteToOrderConverter->convertToOrder(
            quotationId: $quoteId,
            orderData: [
                'payment_term' => PaymentTerm::NET_30,
                'shipping_address' => '123 Main Street, Kuala Lumpur',
                'customer_po' => 'PO-12345',
            ]
        );
        
        return response()->json([
            'id' => $order->getId(),
            'order_number' => $order->getOrderNumber(),
            'total' => $order->getTotal(),
        ]);
    }
    
    /**
     * Step 5: Confirm order (credit check + stock reservation)
     */
    public function confirmOrder(string $orderId): JsonResponse
    {
        try {
            $this->salesOrderManager->confirmOrder($orderId, 'user-id');
            
            return response()->json(['status' => 'confirmed']);
        } catch (\Nexus\Sales\Exceptions\CreditLimitExceededException $e) {
            return response()->json([
                'error' => 'Credit limit exceeded',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Nexus\Sales\Exceptions\InsufficientStockException $e) {
            return response()->json([
                'error' => 'Insufficient stock',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Step 6: Generate invoice
     */
    public function generateInvoice(string $orderId): JsonResponse
    {
        $invoiceId = $this->salesOrderManager->generateInvoice($orderId);
        
        return response()->json(['invoice_id' => $invoiceId]);
    }
}
```

## Handling Exceptions

```php
try {
    $this->salesOrderManager->confirmOrder($orderId, $userId);
} catch (\Nexus\Sales\Exceptions\CreditLimitExceededException $e) {
    // Log and notify customer
    Log::warning('Credit limit exceeded', [
        'customer_id' => $e->getCustomerId(),
        'order_total' => $e->getOrderTotal(),
        'credit_limit' => $e->getCreditLimit(),
    ]);
    
    return response()->json([
        'error' => 'Credit limit exceeded',
        'current_balance' => $e->getCurrentBalance(),
        'available_credit' => $e->getCreditLimit() - $e->getCurrentBalance(),
    ], 422);
    
} catch (\Nexus\Sales\Exceptions\InsufficientStockException $e) {
    // Log and show unavailable items
    Log::warning('Insufficient stock', [
        'order_id' => $e->getOrderId(),
        'message' => $e->getMessage(),
    ]);
    
    return response()->json([
        'error' => 'Insufficient stock',
        'details' => $e->getMessage(),
    ], 422);
    
} catch (\Nexus\Sales\Exceptions\InvalidOrderStatusException $e) {
    // Invalid status transition
    return response()->json([
        'error' => 'Invalid status',
        'current_status' => $e->getCurrentStatus()->value,
    ], 422);
}
```

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples
- See [Examples](examples/) for more code samples

## Troubleshooting

### Common Issues

**Issue 1: Price calculation is wrong**
- Cause: Missing price list for customer
- Solution: Ensure customer is assigned a valid price list

**Issue 2: Cannot convert quote to order**
- Cause: Quote is not in 'accepted' status
- Solution: Accept the quote first using `acceptQuotation()`

**Issue 3: Credit limit check fails**
- Cause: Customer has exceeded credit limit
- Solution: Increase credit limit in Nexus\Receivable or reduce order amount

**Issue 4: Stock reservation fails**
- Cause: Insufficient inventory in warehouse
- Solution: Check inventory levels in Nexus\Inventory

**Issue 5: Invoice generation fails**
- Cause: Order not in CONFIRMED or SHIPPED status
- Solution: Confirm or ship the order first

### Debugging Tips

Enable debug logging to trace issues:

```php
// In your logging configuration
'channels' => [
    'sales' => [
        'driver' => 'daily',
        'path' => storage_path('logs/sales.log'),
        'level' => 'debug',
    ],
],
```
