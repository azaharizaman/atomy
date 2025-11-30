# Getting Started with Nexus Sales

## Prerequisites

- PHP 8.3 or higher
- Composer
- Nexus\Party (for customer management)
- Nexus\Product (for product catalog)

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

Do NOT use this package for:
- ❌ Managing inventory stock levels (use Nexus\Inventory)
- ❌ Generating invoices (use Nexus\Receivable)
- ❌ Managing customer relationships (use Nexus\Crm)

## Core Concepts

### Concept 1: Quotation Lifecycle
Quotations start as drafts, can be versioned (v1, v2), sent to customers, and finally converted to orders or rejected.

### Concept 2: Pricing Engine
The pricing engine calculates the final price based on base price, customer price lists, volume tiers, and applicable discounts.

## Basic Configuration

### Step 1: Implement Required Interfaces

```php
// Example of implementing stock reservation
namespace App\Services;

use Nexus\Sales\Contracts\StockReservationInterface;

final readonly class InventoryAdapter implements StockReservationInterface
{
    public function checkAvailability(string , float ): bool
    {
        // Check Nexus\Inventory
        return true;
    }
    
    public function reserve(string , float , string ): void
    {
        // Reserve in Nexus\Inventory
    }
}
```

### Step 2: Bind Interfaces in Service Provider

```php
// Laravel example
$this->app->bind(
    StockReservationInterface::class,
    InventoryAdapter::class
);
```

### Step 3: Use the Package

```php
use Nexus\Sales\Contracts\QuotationManagerInterface;

final readonly class SalesController
{
    public function __construct(
        private QuotationManagerInterface 
    ) {}
    
    public function createQuote(Request $request): JsonResponse
    {
        $quote = $this->quotationManager->create($request->all());
        return response()->json($quote);
    }
}
```

## Your First Integration

See [Basic Usage Example](examples/basic-usage.php) for a complete walkthrough.

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
- Solution: Accept the quote first
