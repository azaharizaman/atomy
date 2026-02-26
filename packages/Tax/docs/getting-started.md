# Getting Started with Nexus\Tax

Welcome to **Nexus\Tax**, a comprehensive, framework-agnostic multi-jurisdiction tax calculation engine for PHP 8.3+ ERP systems. This guide will help you integrate the package into your application.

---

## Prerequisites

### Required

- **PHP 8.3+** - Native enums and readonly properties required
- **BCMath Extension** - For arbitrary precision decimal arithmetic
- **Composer** - For package management

### Recommended

- **PSR-3 Logger** - For error logging (optional)
- **PSR-6 Cache** - For performance optimization (optional)

### Nexus Package Dependencies

The Tax package requires these Nexus packages:

```json
{
  "require": {
    "nexus/finance": "*@dev",
    "nexus/currency": "*@dev",
    "nexus/geo": "*@dev",
    "nexus/party": "*@dev",
    "nexus/product": "*@dev",
    "nexus/tenant": "*@dev"
  }
}
```

**Optional Dependencies** (for enhanced features):
- `nexus/audit-logger` - Audit trail logging
- `nexus/telemetry` - Telemetry and metrics
- `nexus/storage` - Exemption certificate PDF storage

---

## Installation

### Step 1: Install Package

```bash
composer require nexus/tax:"*@dev"
```

### Step 2: Verify BCMath Extension

```bash
php -m | grep bcmath
```

If not installed:

```bash
# Ubuntu/Debian
sudo apt-get install php8.3-bcmath

# macOS (Homebrew)
brew install php@8.3

# Windows
# Enable in php.ini: extension=bcmath
```

---

## Quick Start (5 Minutes)

### 1. Create Tax Rate Repository

The package defines interfaces; you implement them for your database:

```php
<?php

declare(strict_types=1);

namespace App\Tax\Repositories;

use Nexus\Tax\Contracts\TaxRateRepositoryInterface;
use Nexus\Tax\ValueObjects\TaxRate;
use Nexus\Tax\Enums\{TaxType, TaxLevel};
use Nexus\Tax\Exceptions\TaxRateNotFoundException;

final readonly class EloquentTaxRateRepository implements TaxRateRepositoryInterface
{
    public function findRateByCode(string $taxCode, \DateTimeInterface $effectiveDate): TaxRate
    {
        $model = \App\Models\TaxRate::query()
            ->where('tax_code', $taxCode)
            ->where('effective_from', '<=', $effectiveDate)
            ->where(function ($query) use ($effectiveDate) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $effectiveDate);
            })
            ->first();
        
        if (!$model) {
            throw new TaxRateNotFoundException($taxCode);
        }
        
        return new TaxRate(
            taxCode: $model->tax_code,
            taxType: TaxType::from($model->tax_type),
            taxLevel: TaxLevel::from($model->tax_level),
            ratePercentage: $model->rate_percentage,
            effectiveFrom: new \DateTimeImmutable($model->effective_from),
            effectiveTo: $model->effective_to ? new \DateTimeImmutable($model->effective_to) : null,
            glAccountCode: $model->gl_account_code,
            applicationOrder: $model->application_order
        );
    }
    
    public function findApplicableRates(
        \Nexus\Tax\ValueObjects\TaxJurisdiction $jurisdiction,
        \DateTimeInterface $effectiveDate
    ): array {
        $models = \App\Models\TaxRate::query()
            ->where('jurisdiction_code', $jurisdiction->stateCode ?? $jurisdiction->federalCode)
            ->where('effective_from', '<=', $effectiveDate)
            ->where(function ($query) use ($effectiveDate) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $effectiveDate);
            })
            ->orderBy('application_order')
            ->get();
        
        return $models->map(fn($model) => new TaxRate(
            taxCode: $model->tax_code,
            taxType: TaxType::from($model->tax_type),
            taxLevel: TaxLevel::from($model->tax_level),
            ratePercentage: $model->rate_percentage,
            effectiveFrom: new \DateTimeImmutable($model->effective_from),
            effectiveTo: $model->effective_to ? new \DateTimeImmutable($model->effective_to) : null,
            glAccountCode: $model->gl_account_code,
            applicationOrder: $model->application_order
        ))->toArray();
    }
}
```

### 2. Bind Interfaces in Service Provider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Tax\Contracts\{
    TaxCalculatorInterface,
    TaxRateRepositoryInterface,
    TaxJurisdictionResolverInterface
};
use Nexus\Tax\Services\{TaxCalculator, JurisdictionResolver};
use App\Tax\Repositories\EloquentTaxRateRepository;

final class TaxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            TaxRateRepositoryInterface::class,
            EloquentTaxRateRepository::class
        );
        
        // Bind services
        $this->app->singleton(
            TaxCalculatorInterface::class,
            TaxCalculator::class
        );
        
        $this->app->singleton(
            TaxJurisdictionResolverInterface::class,
            JurisdictionResolver::class
        );
    }
}
```

### 3. Calculate Tax

```php
<?php

use Nexus\Tax\Contracts\TaxCalculatorInterface;
use Nexus\Tax\ValueObjects\{TaxContext, TaxJurisdiction};
use Nexus\Currency\ValueObjects\Money;

// In your controller/service
final readonly class InvoiceService
{
    public function __construct(
        private TaxCalculatorInterface $taxCalculator
    ) {}
    
    public function calculateInvoiceTax(array $invoiceData): array
    {
        // Build tax context
        $context = new TaxContext(
            transactionId: $invoiceData['id'],
            transactionType: 'customer_invoice',
            transactionDate: new \DateTimeImmutable($invoiceData['date']),
            taxCode: 'US-CA-SALES', // From invoice line item
            originAddress: $invoiceData['warehouse_address'],
            destinationAddress: $invoiceData['customer_address'],
            customerId: $invoiceData['customer_id'],
            taxJurisdiction: new TaxJurisdiction(
                federalCode: 'US',
                stateCode: 'CA',
                localCode: 'SF'
            ),
            exemptionCertificateId: $invoiceData['exemption_cert_id'] ?? null,
            serviceClassification: null // Physical goods
        );
        
        // Calculate tax
        $taxBreakdown = $this->taxCalculator->calculate(
            context: $context,
            taxableAmount: Money::of($invoiceData['subtotal'], 'USD')
        );
        
        return [
            'net_amount' => $taxBreakdown->netAmount->getAmount(),
            'total_tax' => $taxBreakdown->totalTaxAmount->getAmount(),
            'gross_amount' => $taxBreakdown->grossAmount->getAmount(),
            'tax_lines' => array_map(fn($line) => [
                'description' => $line->description,
                'rate_percentage' => $line->ratePercentage,
                'taxable_base' => $line->taxableBase->getAmount(),
                'tax_amount' => $line->taxAmount->getAmount(),
            ], $taxBreakdown->taxLines),
        ];
    }
}
```

**Output:**
```json
{
  "net_amount": "100.00",
  "total_tax": "7.25",
  "gross_amount": "107.25",
  "tax_lines": [
    {
      "description": "California Sales Tax",
      "rate_percentage": "7.2500",
      "taxable_base": "100.00",
      "tax_amount": "7.25"
    }
  ]
}
```

---

## Basic Configuration

### Database Schema

Create tax rate tables using the provided schema:

```bash
# See docs/TAX_AUDIT_LOG_SCHEMA.md for complete SQL
```

**Minimal Schema:**

```sql
CREATE TABLE tax_rates (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    tax_code VARCHAR(50) NOT NULL,
    tax_type VARCHAR(20) NOT NULL,
    tax_level VARCHAR(20) NOT NULL,
    jurisdiction_code VARCHAR(50),
    rate_percentage DECIMAL(10,4) NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE,
    gl_account_code VARCHAR(50) NOT NULL,
    application_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (tenant_id, tax_code, effective_from)
);

CREATE INDEX idx_tax_rates_lookup 
ON tax_rates (tenant_id, tax_code, effective_from, effective_to);

CREATE INDEX idx_tax_rates_jurisdiction
ON tax_rates (tenant_id, jurisdiction_code, effective_from);
```

### Seed Tax Rates

```php
<?php

use Nexus\Tax\Enums\{TaxType, TaxLevel};

// US California Sales Tax
DB::table('tax_rates')->insert([
    'id' => Str::ulid(),
    'tenant_id' => $tenantId,
    'tax_code' => 'US-CA-SALES',
    'tax_type' => TaxType::SalesTax->value,
    'tax_level' => TaxLevel::State->value,
    'jurisdiction_code' => 'US-CA',
    'rate_percentage' => '7.2500',
    'effective_from' => '2024-01-01',
    'effective_to' => null,
    'gl_account_code' => '2210',
    'application_order' => 1,
]);

// EU Germany VAT
DB::table('tax_rates')->insert([
    'id' => Str::ulid(),
    'tenant_id' => $tenantId,
    'tax_code' => 'EU-DE-VAT-STANDARD',
    'tax_type' => TaxType::VAT->value,
    'tax_level' => TaxLevel::Federal->value,
    'jurisdiction_code' => 'DE',
    'rate_percentage' => '19.0000',
    'effective_from' => '2020-01-01',
    'effective_to' => null,
    'gl_account_code' => '2310',
    'application_order' => 1,
]);
```

---

## Your First Integration

### Scenario: Customer Invoice Tax Calculation

**Business Rules:**
- Customer in California
- Invoice subtotal: $100.00
- California sales tax: 7.25%
- Expected tax: $7.25
- Expected gross: $107.25

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Tax\Contracts\TaxCalculatorInterface;
use Nexus\Tax\ValueObjects\{TaxContext, TaxJurisdiction};
use Nexus\Currency\ValueObjects\Money;

final readonly class CustomerInvoiceTaxService
{
    public function __construct(
        private TaxCalculatorInterface $taxCalculator
    ) {}
    
    public function calculateTax(array $invoice): array
    {
        $context = new TaxContext(
            transactionId: $invoice['id'],
            transactionType: 'customer_invoice',
            transactionDate: new \DateTimeImmutable($invoice['date']),
            taxCode: 'US-CA-SALES',
            originAddress: $invoice['warehouse_address'],
            destinationAddress: $invoice['customer_shipping_address'],
            customerId: $invoice['customer_id'],
            taxJurisdiction: new TaxJurisdiction(
                federalCode: 'US',
                stateCode: 'CA',
                localCode: null
            ),
            exemptionCertificateId: null,
            serviceClassification: null
        );
        
        $taxBreakdown = $this->taxCalculator->calculate(
            context: $context,
            taxableAmount: Money::of($invoice['subtotal'], 'USD')
        );
        
        // Save tax breakdown to invoice_tax_lines table
        foreach ($taxBreakdown->taxLines as $taxLine) {
            \App\Models\InvoiceTaxLine::create([
                'invoice_id' => $invoice['id'],
                'tax_code' => $taxLine->taxCode,
                'description' => $taxLine->description,
                'taxable_base' => $taxLine->taxableBase->getAmount(),
                'rate_percentage' => $taxLine->ratePercentage,
                'tax_amount' => $taxLine->taxAmount->getAmount(),
                'gl_account_code' => $taxLine->glAccountCode,
            ]);
        }
        
        return [
            'subtotal' => $invoice['subtotal'],
            'tax_amount' => $taxBreakdown->totalTaxAmount->getAmount(),
            'total' => $taxBreakdown->grossAmount->getAmount(),
        ];
    }
}
```

---

## Next Steps

### Essential Reading

1. **[API Reference](api-reference.md)** - Complete interface and VO documentation
2. **[Integration Guide](integration-guide.md)** - Advanced patterns (caching, decorators)
3. **[Tax Audit Log Schema](TAX_AUDIT_LOG_SCHEMA.md)** - Database design
4. **[Migration Guide](MIGRATION.md)** - Temporal data backfill strategies

### Advanced Topics

- **Multi-Level Compound Taxes** - Federal + State + Local cascading
- **Partial Exemptions** - 0-100% exemption percentage
- **Reverse Charge Mechanism** - EU VAT B2B cross-border
- **Place-of-Supply Rules** - Digital services jurisdiction determination
- **Economic Nexus** - Revenue threshold-based tax collection
- **Temporal Queries** - Historical tax rate lookups

### Common Use Cases

- **Customer Invoice Tax** - Calculate and record sales tax
- **Vendor Bill Tax** - Handle reverse charge VAT
- **Sales Order Preview** - Estimate tax before invoicing
- **Compliance Reporting** - Generate tax authority reports
- **Exemption Management** - Validate and track certificates

---

## Troubleshooting

### Common Issues

**Issue:** `TaxRateNotFoundException` thrown

**Solution:** Ensure tax rate exists for specified code and effective date:

```sql
SELECT * FROM tax_rates 
WHERE tax_code = 'US-CA-SALES' 
AND effective_from <= '2024-11-24' 
AND (effective_to IS NULL OR effective_to >= '2024-11-24');
```

**Issue:** BCMath precision errors

**Solution:** Always use string amounts in Money VO:

```php
// ✅ CORRECT
$amount = Money::of('100.00', 'USD');

// ❌ WRONG (float precision issues)
$amount = Money::of(100.0, 'USD');
```

**Issue:** Compound tax calculation incorrect

**Solution:** Verify `application_order` in tax rates:

```sql
SELECT tax_code, application_order, rate_percentage 
FROM tax_rates 
WHERE jurisdiction_code = 'US-CA' 
ORDER BY application_order;
```

---

## Getting Help

- **Documentation:** See `docs/` folder for comprehensive guides
- **Examples:** See `docs/examples/` for working code
- **Issues:** Check GitHub Issues for known problems
- **Community:** Join Nexus Slack channel for support

---

**Happy Taxing! 🧾**
