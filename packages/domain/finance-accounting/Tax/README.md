# Nexus\Tax

A framework-agnostic, stateless tax calculation engine with multi-jurisdiction support, temporal rate resolution, hierarchical tax structure, reverse charge mechanism, economic nexus determination, partial exemptions, place-of-supply rules for cross-border services, currency conversion for compliance reporting, and audit-ready immutable logging.

## Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Core Concepts](#core-concepts)
  - [Tax Jurisdiction](#tax-jurisdiction)
  - [Economic Nexus](#economic-nexus)
  - [Place of Supply](#place-of-supply)
  - [Effective Dating & Temporal Queries](#effective-dating--temporal-queries)
  - [Reverse Charge Mechanism](#reverse-charge-mechanism)
  - [Tax Holidays](#tax-holidays)
  - [Partial Exemptions](#partial-exemptions)
  - [Hierarchical Tax Structure](#hierarchical-tax-structure)
  - [Immutable Audit Log](#immutable-audit-log)
- [Architecture](#architecture)
- [Folder Structure](#folder-structure)
- [Value Objects](#value-objects)
- [Enums](#enums)
- [Interfaces](#interfaces)
- [Usage Examples](#usage-examples)
  - [Basic Tax Calculation](#basic-tax-calculation)
  - [Jurisdiction Resolution with Place of Supply](#jurisdiction-resolution-with-place-of-supply)
  - [Nexus Checking](#nexus-checking)
  - [Partial Exemption Application](#partial-exemption-application)
  - [Reverse Charge Scenario](#reverse-charge-scenario)
  - [Multi-Currency Compliance Reporting](#multi-currency-compliance-reporting)
  - [Tax Adjustment via Contra-Transaction](#tax-adjustment-via-contra-transaction)
  - [Preview Mode Pattern](#preview-mode-pattern)
- [Integration Patterns](#integration-patterns)
- [Performance Characteristics](#performance-characteristics)
- [Compliance Features](#compliance-features)
- [Future Enhancements](#future-enhancements)
- [License](#license)

---

## Overview

The **Nexus\Tax** package provides a comprehensive, framework-agnostic tax calculation engine designed for global ERP systems requiring multi-jurisdiction tax compliance. It handles complex scenarios including:

- **Multi-level compound taxes** (Federal → State → Local cascading)
- **Temporal rate lookups** with effective dating for historical accuracy
- **Economic nexus determination** for US state sales tax compliance
- **Place-of-supply rules** for cross-border digital services (EU VAT, Australia GST)
- **Reverse charge mechanism** for B2B cross-border transactions
- **Partial tax exemptions** with certificate management
- **Multi-currency compliance reporting** with automatic conversion
- **Immutable audit logs** ensuring 7-10 year retention compliance

**Key Principle:** This package is a **pure calculation engine**. It defines **what** needs to be calculated but remains **stateless** regarding data persistence. The consuming application layer implements repositories and handles audit log persistence.

---

## Installation

```bash
composer require nexus/tax:"*@dev"
```

### Dependencies

This package requires the following Nexus packages:

- `nexus/finance` - GL account integration
- `nexus/currency` - Multi-currency support and conversion
- `nexus/geo` - Geocoding for jurisdiction resolution
- `nexus/party` - Customer/vendor address data
- `nexus/product` - Product tax categories
- `nexus/tenant` - Multi-tenancy context
- `nexus/audit-logger` - Optional audit trail logging
- `nexus/monitoring` - Optional telemetry tracking
- `nexus/storage` - Optional exemption certificate PDF storage

---

## Core Concepts

### Tax Jurisdiction

A **tax jurisdiction** represents the geographic and administrative scope where a specific tax regime applies. Jurisdictions can be hierarchical:

- **Federal/National** (e.g., Canada GST, Malaysia SST)
- **State/Province** (e.g., California sales tax, Ontario PST)
- **Local/Municipal** (e.g., Denver city tax, Toronto municipal tax)

The `TaxJurisdictionResolverInterface` determines the applicable jurisdiction based on transaction details (ship-from address, ship-to address, service classification).

### Economic Nexus

**Economic nexus** is the legal obligation to collect and remit sales tax in a jurisdiction where the business has sufficient "economic presence," even without physical presence.

**Example:** A US state may require sales tax collection if annual revenue exceeds $100,000 OR transaction count exceeds 200.

The `TaxNexusManagerInterface` checks historical transaction data against jurisdiction-specific thresholds via the `NexusThreshold` Value Object.

**Key Decision:** Nexus determination is **stateful** (requires historical data analysis) and therefore belongs in the application layer implementation, not the stateless tax engine.

### Place of Supply

**Place of supply** rules determine *where* a transaction is considered to occur for tax purposes. This is critical for cross-border services:

- **Digital Services** (e.g., SaaS, streaming): Destination-based (taxed where customer is located)
- **Physical Goods**: Origin or destination-based depending on jurisdiction rules
- **Professional Services** (e.g., consulting): Supplier location-based

The `ServiceClassification` enum drives place-of-supply resolution in the `TaxJurisdictionResolver`.

### Effective Dating & Temporal Queries

Tax rates change frequently. **All tax rate lookups MUST include an effective date** to ensure historical accuracy for audits and reporting.

```php
// ❌ WRONG - No effective date
$rate = $rateRepository->findRateByCode('SR');

// ✅ CORRECT - Temporal query
$rate = $rateRepository->findRateByCode('SR', new \DateTimeImmutable('2024-10-15'));
```

**Tax holidays** (temporary rate reductions) are modeled as standard `TaxRate` records with 0.00% or reduced rate during the holiday period's `effectiveStartDate` and `effectiveEndDate`.

### Reverse Charge Mechanism

**Reverse charge** (RCM) is a tax deferral mechanism for B2B cross-border transactions (common in EU VAT). Instead of the supplier charging VAT, the **customer self-assesses** the tax.

**Critical:** Reverse charge is **NOT an exemption** (tax still applies), it's a **calculation method** where:
- Tax amount = $0.00 on invoice
- Liability deferred to buyer
- Special GL account tracks deferred liability

The `TaxCalculationMethod::ReverseCharge` enum case triggers this logic.

### Tax Holidays

Temporary tax rate reductions (e.g., back-to-school sales tax holidays). Modeled as standard `TaxRate` records with:

```php
TaxRate::create([
    'code' => 'HOLIDAY_2024',
    'rate' => '0.00', // 0% during holiday
    'effectiveStartDate' => new \DateTimeImmutable('2024-08-01'),
    'effectiveEndDate' => new \DateTimeImmutable('2024-08-15'),
]);
```

The temporal query system automatically applies the correct rate based on transaction date.

### Partial Exemptions

Some entities have **partial tax exemptions** (e.g., 50% exempt for agricultural cooperatives). The `ExemptionCertificate` Value Object includes:

```php
readonly class ExemptionCertificate
{
    public float $exemptionPercentage; // 0.0 to 100.0
    public string $storageKey; // Reference to PDF certificate in storage
}
```

The calculation engine **reduces the taxable base** by the exemption percentage before applying tax rates:

```
Taxable Base = Line Amount × (100 - Exemption%) / 100
Tax Amount = Taxable Base × Tax Rate
```

### Hierarchical Tax Structure

**Compound taxes** (tax-on-tax) require hierarchical calculation. The `TaxBreakdown` Value Object supports nested `TaxLine` objects:

```
TaxBreakdown
├── Federal Tax Line ($50.00 on $1000 base @ 5%)
│   ├── State Tax Line ($52.50 on $1050 base @ 5%)
│   │   └── Local Tax Line ($55.13 on $1102.50 base @ 5%)
```

Each `TaxRate` has an `applicationOrder: int` property. The calculator:
1. Fetches applicable rates
2. Sorts by `applicationOrder`
3. Applies sequentially, building hierarchy

### Immutable Audit Log

**The Tax Audit Log is immutable.** No UPDATE or DELETE operations are permitted. Adjustments require:

1. **Contra-Transaction Pattern:** Create a new transaction with negative amounts
2. **New Calculation:** Pass updated `TaxContext` to calculator
3. **Negative Result:** Returns `TaxBreakdown` with negative amounts
4. **Persist as New Record:** Application layer saves new audit log entry

This ensures complete audit trail with original calculation + correction linkage.

---

## Architecture

**Stateless Calculation Engine Pattern**

```
┌─────────────────────────────────────────────────────────────┐
│ Application Layer (Symfony/Laravel)                         │
│ ┌──────────────────────────────────────────────────────┐   │
│ │ 1. Constructs TaxContext from Domain Entities        │   │
│ │ 2. Calls TaxCalculatorInterface                      │   │
│ │ 3. Persists TaxResult to Audit Log (if finalizing)   │   │
│ │ 4. Publishes TaxCalculatedEvent to EventStream       │   │
│ │ 5. Implements Repositories (Doctrine/Eloquent)       │   │
│ └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ Nexus\Tax Package (Stateless)                               │
│ ┌──────────────────────────────────────────────────────┐   │
│ │ TaxCalculator                                        │   │
│ │  ├─ Inject: TaxRateRepositoryInterface              │   │
│ │  ├─ Inject: TaxJurisdictionResolverInterface        │   │
│ │  ├─ Inject: TaxNexusManagerInterface                │   │
│ │  ├─ Inject: TaxExemptionManagerInterface            │   │
│ │  └─ Returns: TaxBreakdown (immutable VO)            │   │
│ └──────────────────────────────────────────────────────┘   │
│                                                              │
│ Decorator Patterns (Application Layer Implements):          │
│  ├─ CachingJurisdictionResolver (wraps resolver)            │
│  ├─ StorageExemptionManager (wraps storage)                │
│  └─ CurrencyConvertingReportingService (wraps reporting)   │
└─────────────────────────────────────────────────────────────┘
```

**Key Principles:**
1. **Dependency Inversion:** Package defines interfaces, application implements
2. **Statelessness:** No database queries, no file I/O, pure calculation
3. **Temporal Queries:** All repository methods require `\DateTimeInterface`
4. **Decorator Pattern:** Caching, storage, notifications added by application

---

## Folder Structure

```
packages/Tax/
├── composer.json
├── LICENSE
├── .gitignore
├── README.md                          # This file
├── REQUIREMENTS.md                    # Detailed requirements (standardized format)
├── IMPLEMENTATION_SUMMARY.md          # Implementation tracking & metrics
├── TEST_SUITE_SUMMARY.md              # Test coverage & results
├── VALUATION_MATRIX.md                # Package valuation for funding
├── docs/
│   ├── getting-started.md             # Quick start guide
│   ├── api-reference.md               # Complete API documentation
│   ├── integration-guide.md           # Application layer integration
│   ├── TAX_AUDIT_LOG_SCHEMA.md        # Database schema reference
│   ├── MIGRATION.md                   # Temporal data backfill guide
│   ├── ARCHITECTURAL_DECISIONS.md     # Design decision rationale
│   └── examples/
│       ├── basic-usage.php
│       ├── advanced-usage.php
│       └── application-integration.php
├── src/
│   ├── Contracts/                     # Framework-agnostic interfaces
│   │   ├── TaxCalculatorInterface.php
│   │   ├── TaxManagerInterface.php
│   │   ├── TaxRateRepositoryInterface.php
│   │   ├── TaxJurisdictionResolverInterface.php
│   │   ├── TaxNexusManagerInterface.php
│   │   ├── TaxExemptionManagerInterface.php
│   │   ├── TaxReportingInterface.php
│   │   └── TaxGLIntegrationInterface.php
│   ├── Enums/                         # Native PHP 8.3 enums
│   │   ├── TaxType.php
│   │   ├── TaxCalculationMethod.php
│   │   ├── TaxLevel.php
│   │   ├── TaxExemptionReason.php
│   │   └── ServiceClassification.php
│   ├── ValueObjects/                  # Immutable domain data
│   │   ├── TaxContext.php
│   │   ├── TaxRate.php
│   │   ├── TaxBreakdown.php
│   │   ├── TaxLine.php
│   │   ├── TaxAmount.php
│   │   ├── TaxJurisdiction.php
│   │   ├── ExemptionCertificate.php
│   │   ├── NexusThreshold.php
│   │   └── ComplianceReportLine.php
│   ├── Services/                      # Business logic
│   │   ├── TaxCalculator.php
│   │   ├── JurisdictionResolver.php
│   │   ├── ExemptionManager.php
│   │   └── TaxReportingService.php
│   └── Exceptions/                    # Domain exceptions
│       ├── TaxRateNotFoundException.php
│       ├── InvalidTaxJurisdictionException.php
│       ├── TaxCalculationException.php
│       ├── TaxExemptionExpiredException.php
│       ├── CompoundTaxRuleViolationException.php
│       ├── EffectiveDateRequiredException.php
│       ├── CurrencyConversionRequiredException.php
│       ├── NoNexusInJurisdictionException.php
│       └── InvalidExemptionPercentageException.php
└── tests/
    ├── Unit/
    └── Feature/
```

---

## Value Objects

All Value Objects are `final readonly` classes using BCMath for precision.

### TaxContext

Encapsulates all inputs required for tax calculation.

```php
final readonly class TaxContext
{
    public \DateTimeImmutable $transactionDate;
    public string $transactionType;           // 'sale', 'purchase', 'import'
    public ?string $serviceClassification;    // ServiceClassification enum value
    public array $shipFromAddress;            // ['country' => 'US', 'state' => 'CA', ...]
    public array $shipToAddress;
    public string $customerType;              // 'B2B', 'B2C', 'Government'
    public string $itemCategory;              // 'Goods', 'Services', 'Digital'
}
```

### TaxRate

Represents a single tax rate with temporal validity.

```php
final readonly class TaxRate
{
    public string $rate;                      // BCMath string '5.00'
    public string $code;                      // 'SR', 'ZEROR', 'GST_5'
    public TaxType $type;                     // Enum: VAT, GST, SST, etc.
    public TaxLevel $level;                   // Enum: Federal, State, Local
    public int $applicationOrder;             // 1, 2, 3 for compound taxes
    public string $glAccountCode;             // '2200.01.MY.SST'
    public \DateTimeImmutable $effectiveStartDate;
    public ?\DateTimeImmutable $effectiveEndDate;
}
```

**Key Property:** `applicationOrder` controls calculation sequence for compound taxes.

### TaxBreakdown

Hierarchical structure representing calculated tax.

```php
final readonly class TaxBreakdown
{
    /** @var TaxLine[] */
    public array $lines;                      // Hierarchical tax lines
    public Money $totalTaxAmount;             // Sum of all tax
    public Money $netAmount;                  // Original amount before tax
    public Money $grossAmount;                // Net + Tax
    public TaxCalculationMethod $calculationMethod;
    public bool $isReverseCharge;
}
```

### TaxLine

Individual tax line within breakdown (supports nesting).

```php
final readonly class TaxLine
{
    public TaxRate $taxRate;
    public Money $taxableBase;                // Amount this rate applies to
    public Money $taxAmount;                  // Calculated tax
    public TaxLevel $level;
    public string $glAccountCode;
    /** @var TaxLine[] */
    public array $children;                   // Nested tax lines for compound taxes
}
```

### ExemptionCertificate

Tax exemption certificate metadata.

```php
final readonly class ExemptionCertificate
{
    public string $certificateId;
    public string $customerId;
    public TaxExemptionReason $reason;
    public float $exemptionPercentage;        // 0.0 to 100.0
    public \DateTimeImmutable $issueDate;
    public ?\DateTimeImmutable $expirationDate;
    public string $storageKey;                // Reference to PDF in Nexus\Storage
}
```

### NexusThreshold

Economic nexus threshold for a jurisdiction.

```php
final readonly class NexusThreshold
{
    public string $jurisdictionCode;          // 'US-CA', 'US-TX'
    public ?Money $revenueThreshold;          // $100,000 USD
    public ?int $transactionThreshold;        // 200 transactions
    public \DateTimeImmutable $effectiveDate;
}
```

---

## Enums

All enums use native PHP 8.3 `enum` with business logic methods.

### TaxType

```php
enum TaxType: string
{
    case VAT = 'vat';                         // Value Added Tax (EU)
    case GST = 'gst';                         // Goods & Services Tax (Canada, Australia, Malaysia)
    case SST = 'sst';                         // Sales & Service Tax (Malaysia)
    case SalesTax = 'sales_tax';              // US State Sales Tax
    case Excise = 'excise';                   // Excise duties
    case Withholding = 'withholding';         // Withholding tax
    
    public function label(): string;
    public function isConsumptionTax(): bool;
    public function requiresReverseCharge(): bool;
}
```

### TaxCalculationMethod

```php
enum TaxCalculationMethod: string
{
    case Exclusive = 'exclusive';             // Tax added to base (US)
    case Inclusive = 'inclusive';             // Tax included in price (AU)
    case Compound = 'compound';               // Tax-on-tax (Canada HST)
    case ReverseCharge = 'reverse_charge';    // B2B cross-border (EU)
}
```

### ServiceClassification

```php
enum ServiceClassification: string
{
    case DigitalService = 'digital_service';
    case ProfessionalService = 'professional_service';
    case PhysicalService = 'physical_service';
    case Transport = 'transport';
    case Financial = 'financial';
}
```

---

## Interfaces

### TaxCalculatorInterface

```php
interface TaxCalculatorInterface
{
    /**
     * Calculate tax for a given context and amount.
     * 
     * Application layer decides whether to persist result (preview vs finalization).
     *
     * @throws NoNexusInJurisdictionException If no economic nexus exists
     * @throws TaxRateNotFoundException If rate code invalid or not effective
     * @throws TaxCalculationException For calculation errors
     */
    public function calculate(TaxContext $context, Money $amount): TaxBreakdown;
}
```

### TaxRateRepositoryInterface

```php
interface TaxRateRepositoryInterface
{
    /**
     * Find a tax rate by code at specific effective date.
     * 
     * CRITICAL: effectiveDate parameter is MANDATORY for temporal queries.
     *
     * @throws TaxRateNotFoundException
     */
    public function findRateByCode(
        string $code,
        \DateTimeInterface $effectiveDate
    ): TaxRate;
    
    /**
     * Find all applicable rates for jurisdiction at effective date.
     * 
     * Returns UNSORTED array. TaxCalculator sorts by applicationOrder.
     *
     * @return TaxRate[]
     */
    public function findApplicableRates(
        TaxJurisdiction $jurisdiction,
        \DateTimeInterface $effectiveDate
    ): array;
}
```

### TaxJurisdictionResolverInterface

```php
interface TaxJurisdictionResolverInterface
{
    /**
     * Resolve tax jurisdiction from transaction context.
     * 
     * Implements place-of-supply rules based on serviceClassification.
     * 
     * CACHE-AGNOSTIC: Application layer wraps with caching decorator.
     *
     * @throws InvalidTaxJurisdictionException
     */
    public function resolve(TaxContext $context): TaxJurisdiction;
}
```

### TaxNexusManagerInterface

```php
interface TaxNexusManagerInterface
{
    /**
     * Check if business has economic nexus in jurisdiction.
     * 
     * STATEFUL: Requires historical transaction analysis.
     * Application layer implements using database queries.
     */
    public function hasNexus(
        string $jurisdictionCode,
        \DateTimeInterface $date
    ): bool;
    
    /**
     * Get nexus threshold for jurisdiction.
     */
    public function getNexusThreshold(
        string $jurisdictionCode,
        \DateTimeInterface $date
    ): NexusThreshold;
}
```

### TaxExemptionManagerInterface

```php
interface TaxExemptionManagerInterface
{
    /**
     * Validate exemption certificate and return exemption percentage.
     * 
     * VALIDATION ONLY: Workflow (approval/revocation) in application layer.
     *
     * @return float Exemption percentage (0.0 to 100.0)
     * @throws TaxExemptionExpiredException
     * @throws InvalidExemptionPercentageException
     */
    public function validateExemption(
        string $certificateId,
        \DateTimeInterface $transactionDate
    ): float;
    
    /**
     * Get certificates expiring within specified days.
     * 
     * Application layer triggers notifications via Nexus\Notifier.
     *
     * @return ExemptionCertificate[]
     */
    public function getExpiringCertificates(\DateTimeInterface $withinDays): array;
}
```

### TaxReportingInterface

```php
interface TaxReportingInterface
{
    /**
     * Aggregate tax breakdowns for compliance reporting.
     * 
     * Converts all amounts to reporting currency (e.g., EUR for EU VAT).
     * Outputs generic structure for Nexus\Statutory format transformation.
     *
     * @param TaxBreakdown[] $breakdowns
     * @return ComplianceReportLine[]
     */
    public function aggregateForCompliance(
        array $breakdowns,
        string $reportingCurrency,
        \DateTimeInterface $periodStart,
        \DateTimeInterface $periodEnd
    ): array;
}
```

---

## Usage Examples

### Basic Tax Calculation

```php
use Nexus\Tax\Contracts\TaxCalculatorInterface;
use Nexus\Tax\ValueObjects\TaxContext;
use Nexus\Currency\ValueObjects\Money;

// Injected via DI
private readonly TaxCalculatorInterface $taxCalculator;

public function calculateInvoiceTax(array $invoiceData): TaxBreakdown
{
    // Construct TaxContext from invoice data
    $context = new TaxContext(
        transactionDate: new \DateTimeImmutable($invoiceData['date']),
        transactionType: 'sale',
        serviceClassification: null, // Physical goods
        shipFromAddress: [
            'country' => 'MY',
            'state' => 'Selangor',
            'city' => 'Petaling Jaya',
        ],
        shipToAddress: [
            'country' => 'MY',
            'state' => 'Johor',
            'city' => 'Johor Bahru',
        ],
        customerType: 'B2C',
        itemCategory: 'Goods'
    );
    
    $amount = Money::of($invoiceData['line_total'], 'MYR');
    
    // Calculate tax (stateless operation)
    $taxBreakdown = $this->taxCalculator->calculate($context, $amount);
    
    // Returns hierarchical TaxBreakdown with:
    // - Federal SST @ 10%
    // - Total tax amount
    // - GL account codes
    
    return $taxBreakdown;
}
```

### Jurisdiction Resolution with Place of Supply

```php
use Nexus\Tax\Contracts\TaxJurisdictionResolverInterface;
use Nexus\Tax\Enums\ServiceClassification;

private readonly TaxJurisdictionResolverInterface $jurisdictionResolver;

public function resolveDigitalServiceJurisdiction(array $customerData): TaxJurisdiction
{
    $context = new TaxContext(
        transactionDate: new \DateTimeImmutable(),
        transactionType: 'sale',
        serviceClassification: ServiceClassification::DigitalService->value, // KEY!
        shipFromAddress: ['country' => 'MY'], // Supplier in Malaysia
        shipToAddress: ['country' => 'GB'],   // Customer in UK
        customerType: 'B2C',
        itemCategory: 'Digital'
    );
    
    // Place-of-supply rule: Digital services taxed at DESTINATION
    $jurisdiction = $this->jurisdictionResolver->resolve($context);
    
    // Result: jurisdiction->countryCode === 'GB' (UK VAT applies)
    
    return $jurisdiction;
}
```

### Nexus Checking

```php
use Nexus\Tax\Contracts\TaxNexusManagerInterface;
use Nexus\Tax\Exceptions\NoNexusInJurisdictionException;

private readonly TaxNexusManagerInterface $nexusManager;

public function shouldCollectTax(string $stateCode): bool
{
    try {
        $hasNexus = $this->nexusManager->hasNexus(
            jurisdictionCode: "US-{$stateCode}",
            date: new \DateTimeImmutable()
        );
        
        if (!$hasNexus) {
            // No economic presence - don't collect tax
            return false;
        }
        
        return true;
        
    } catch (NoNexusInJurisdictionException $e) {
        // Log and skip tax collection
        $this->logger->warning("No nexus in {$stateCode}", [
            'customer_id' => $customerId,
        ]);
        return false;
    }
}
```

### Partial Exemption Application

```php
use Nexus\Tax\Contracts\TaxExemptionManagerInterface;

private readonly TaxExemptionManagerInterface $exemptionManager;

public function calculateWithExemption(
    string $certificateId,
    Money $lineAmount
): TaxBreakdown {
    // Validate certificate and get exemption percentage
    $exemptionPercentage = $this->exemptionManager->validateExemption(
        certificateId: $certificateId,
        transactionDate: new \DateTimeImmutable()
    );
    // Returns: 50.0 (50% exempt)
    
    // Reduce taxable base
    $taxableBase = $lineAmount->multiply((100 - $exemptionPercentage) / 100);
    
    // Calculate tax on reduced base
    $taxBreakdown = $this->taxCalculator->calculate($context, $taxableBase);
    
    // Example: $1000 line × 50% exempt = $500 taxable
    //          $500 × 10% tax = $50 tax (instead of $100)
    
    return $taxBreakdown;
}
```

### Reverse Charge Scenario

```php
use Nexus\Tax\Enums\TaxCalculationMethod;

public function handleCrossBorderB2B(array $invoiceData): TaxBreakdown
{
    $context = new TaxContext(
        transactionDate: new \DateTimeImmutable(),
        transactionType: 'sale',
        serviceClassification: ServiceClassification::ProfessionalService->value,
        shipFromAddress: ['country' => 'DE'], // Germany
        shipToAddress: ['country' => 'FR'],   // France
        customerType: 'B2B',                  // KEY: Business customer
        itemCategory: 'Services'
    );
    
    $amount = Money::of(10000, 'EUR');
    
    $taxBreakdown = $this->taxCalculator->calculate($context, $amount);
    
    // Result:
    // - taxBreakdown->isReverseCharge === true
    // - taxBreakdown->totalTaxAmount === Money::of(0, 'EUR')
    // - taxBreakdown->lines[0]->glAccountCode === '2300.VAT.DEFERRED'
    
    // Invoice shows: €10,000 + €0 VAT (Reverse Charge)
    // Customer self-assesses French VAT
    
    return $taxBreakdown;
}
```

### Multi-Currency Compliance Reporting

```php
use Nexus\Tax\Contracts\TaxReportingInterface;

private readonly TaxReportingInterface $taxReporting;

public function generateVATReturn(
    \DateTimeInterface $periodStart,
    \DateTimeInterface $periodEnd
): array {
    // Fetch historical tax breakdowns from audit log
    $breakdowns = $this->taxAuditLogRepository->findByPeriod($periodStart, $periodEnd);
    
    // Aggregate and convert to reporting currency
    $reportLines = $this->taxReporting->aggregateForCompliance(
        breakdowns: $breakdowns,
        reportingCurrency: 'EUR', // EU VAT returns in EUR
        periodStart: $periodStart,
        periodEnd: $periodEnd
    );
    
    // Result: ComplianceReportLine[] with amounts in EUR
    // [
    //   {formFieldId: 'Box 1', taxType: 'VAT', totalAmount: Money(45000, 'EUR')},
    //   {formFieldId: 'Box 6', taxType: 'VAT', totalAmount: Money(9000, 'EUR')},
    // ]
    
    // Pass to Nexus\Statutory for XBRL transformation
    
    return $reportLines;
}
```

### Tax Adjustment via Contra-Transaction

```php
/**
 * IMMUTABLE AUDIT LOG PATTERN
 * 
 * Original transaction tax was incorrect. Create correction.
 */
public function correctTaxCalculation(string $originalTransactionId): void
{
    // 1. Fetch original tax result (immutable)
    $originalResult = $this->taxAuditLog->findByTransactionId($originalTransactionId);
    
    // 2. Create contra-transaction with NEGATIVE amount
    $correctionContext = new TaxContext(
        transactionDate: new \DateTimeImmutable(), // Today
        transactionType: 'adjustment',
        serviceClassification: $originalResult->serviceClassification,
        shipFromAddress: $originalResult->shipFromAddress,
        shipToAddress: $originalResult->shipToAddress,
        customerType: $originalResult->customerType,
        itemCategory: $originalResult->itemCategory
    );
    
    // NEGATIVE amount to reverse original tax
    $negativeAmount = $originalResult->netAmount->negate();
    
    $correctionBreakdown = $this->taxCalculator->calculate(
        $correctionContext,
        $negativeAmount
    );
    
    // 3. Persist as NEW audit log entry (not UPDATE)
    $this->taxAuditLog->create([
        'transaction_id' => $this->generateTransactionId(),
        'original_transaction_id' => $originalTransactionId,
        'transaction_type' => 'tax_adjustment',
        'tax_breakdown_json' => json_encode($correctionBreakdown),
        'total_tax_amount' => $correctionBreakdown->totalTaxAmount->negate(),
    ]);
    
    // 4. Calculate corrected tax with new context
    $correctedAmount = Money::of(1200, 'MYR'); // Corrected amount
    $correctedBreakdown = $this->taxCalculator->calculate(
        $correctedContext,
        $correctedAmount
    );
    
    // 5. Persist corrected calculation
    $this->taxAuditLog->create([
        'transaction_id' => $this->generateTransactionId(),
        'original_transaction_id' => $originalTransactionId,
        'transaction_type' => 'tax_recalculation',
        'tax_breakdown_json' => json_encode($correctedBreakdown),
    ]);
    
    // Audit trail: Original + Reversal + Corrected = Complete history
}
```

### Preview Mode Pattern

```php
/**
 * APPLICATION LAYER DECISION
 * 
 * Same calculate() method, different persistence strategy.
 */
public function previewTax(array $quoteData): TaxBreakdown
{
    $context = $this->buildTaxContext($quoteData);
    $amount = Money::of($quoteData['total'], $quoteData['currency']);
    
    // Calculate tax (stateless)
    $taxBreakdown = $this->taxCalculator->calculate($context, $amount);
    
    // SKIP audit log persistence for preview
    // (no call to $this->taxAuditLog->create())
    
    return $taxBreakdown;
}

public function finalizeTax(array $invoiceData): TaxBreakdown
{
    $context = $this->buildTaxContext($invoiceData);
    $amount = Money::of($invoiceData['total'], $invoiceData['currency']);
    
    // Same calculate() method
    $taxBreakdown = $this->taxCalculator->calculate($context, $amount);
    
    // PERSIST to audit log for finalized invoice
    $this->taxAuditLog->create([
        'transaction_id' => $invoiceData['id'],
        'tax_breakdown_json' => json_encode($taxBreakdown),
        'total_tax_amount' => $taxBreakdown->totalTaxAmount,
    ]);
    
    // Publish event for data warehouse sync
    $this->eventDispatcher->dispatch(new TaxCalculatedEvent($taxBreakdown));
    
    return $taxBreakdown;
}
```

---

## Integration Patterns

### Sales Package Adapter

```php
// Application Layer: App\Services\Sales\SalesTaxAdapter

use Nexus\Sales\Contracts\TaxCalculatorInterface as SalesTaxCalculatorInterface;
use Nexus\Tax\Contracts\TaxCalculatorInterface;
use Nexus\Tax\ValueObjects\TaxContext;

/**
 * Adapter bridging Sales package to Tax engine.
 */
final readonly class SalesTaxAdapter implements SalesTaxCalculatorInterface
{
    public function __construct(
        private TaxCalculatorInterface $taxCalculator,
        private PartyRepositoryInterface $partyRepository
    ) {}
    
    public function calculateLineTax(
        string $tenantId,
        string $productVariantId,
        float $lineSubtotal,
        string $customerId,
        string $currencyCode
    ): float {
        // Fetch customer for address data
        $customer = $this->partyRepository->findById($customerId);
        
        // Construct TaxContext from Sales domain
        $context = new TaxContext(
            transactionDate: new \DateTimeImmutable(),
            transactionType: 'sale',
            serviceClassification: null, // Assume goods
            shipFromAddress: $this->getWarehouseAddress(),
            shipToAddress: $customer->getBillingAddress()->toArray(),
            customerType: $customer->isBusinessEntity() ? 'B2B' : 'B2C',
            itemCategory: 'Goods'
        );
        
        $amount = Money::of($lineSubtotal, $currencyCode);
        
        // Delegate to tax engine
        $taxBreakdown = $this->taxCalculator->calculate($context, $amount);
        
        // Return flat tax amount for Sales compatibility
        return (float) $taxBreakdown->totalTaxAmount->getAmount();
    }
}
```

### Caching Decorator for Jurisdiction Resolver

```php
// Application Layer: App\Services\Tax\CachingJurisdictionResolver

use Nexus\Tax\Contracts\TaxJurisdictionResolverInterface;
use Psr\Cache\CacheItemPoolInterface;

final readonly class CachingJurisdictionResolver implements TaxJurisdictionResolverInterface
{
    public function __construct(
        private TaxJurisdictionResolverInterface $inner,
        private CacheItemPoolInterface $cache
    ) {}
    
    public function resolve(TaxContext $context): TaxJurisdiction
    {
        // Build cache key from addresses
        $cacheKey = sprintf(
            'tax_jurisdiction_%s_%s_%s',
            $context->shipFromAddress['country'],
            $context->shipToAddress['country'],
            $context->serviceClassification ?? 'goods'
        );
        
        $cacheItem = $this->cache->getItem($cacheKey);
        
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }
        
        // Cache miss - delegate to inner resolver
        $jurisdiction = $this->inner->resolve($context);
        
        // Cache for 24 hours
        $cacheItem->set($jurisdiction);
        $cacheItem->expiresAfter(86400);
        $this->cache->save($cacheItem);
        
        return $jurisdiction;
    }
}

// Service Provider binding
$container->bind(
    TaxJurisdictionResolverInterface::class,
    fn() => new CachingJurisdictionResolver(
        new JurisdictionResolver($geocoder),
        $cachePool
    )
);
```

### Exemption Certificate Storage Decorator

```php
// Application Layer: App\Services\Tax\StorageExemptionManager

use Nexus\Tax\Contracts\TaxExemptionManagerInterface;
use Nexus\Storage\Contracts\StorageInterface;

final readonly class StorageExemptionManager implements TaxExemptionManagerInterface
{
    public function __construct(
        private TaxExemptionManagerInterface $inner,
        private StorageInterface $storage
    ) {}
    
    public function validateExemption(
        string $certificateId,
        \DateTimeInterface $transactionDate
    ): float {
        // Delegate validation to core manager
        return $this->inner->validateExemption($certificateId, $transactionDate);
    }
    
    public function getExpiringCertificates(\DateTimeInterface $withinDays): array
    {
        return $this->inner->getExpiringCertificates($withinDays);
    }
    
    /**
     * Extended method: Retrieve PDF certificate from storage.
     */
    public function retrieveCertificatePDF(string $certificateId): string
    {
        $certificate = $this->certificateRepository->findById($certificateId);
        
        // Use storageKey to fetch PDF
        return $this->storage->get($certificate->storageKey);
    }
}
```

---

## Performance Characteristics

### BCMath Precision Overhead

Tax calculations use `bcmath` extension for arbitrary precision arithmetic to avoid floating-point errors. This adds ~10-20% overhead vs native float operations but ensures **audit-accurate calculations**.

### Caching Recommendations

**High Cache Value:**
- **Jurisdiction Resolution:** Geocoding API calls expensive (100-500ms). Cache by address hash for 24 hours.
- **Nexus Threshold Lookup:** Rarely changes. Cache by jurisdiction for 7 days.
- **Tax Rate Lookup:** Moderate cache value. Cache by code+date for 1 hour (invalidate on rate updates).

**Low Cache Value:**
- **Exemption Validation:** Fast database lookup (<10ms). Caching optional.
- **Tax Calculation:** Pure computation (<5ms). Don't cache results.

### Recommended Indexes

```sql
-- Tax Rate Repository
CREATE INDEX idx_tax_rates_code_effective 
ON tax_rates (code, effective_start_date, effective_end_date);

CREATE INDEX idx_tax_rates_jurisdiction_effective
ON tax_rates (jurisdiction_code, effective_start_date);

-- Tax Audit Log
CREATE INDEX idx_tax_audit_tenant_date 
ON tax_audit_log (tenant_id, transaction_date);

CREATE INDEX idx_tax_audit_transaction
ON tax_audit_log (transaction_id);

CREATE INDEX idx_tax_audit_customer_date
ON tax_audit_log (customer_id, transaction_date);
```

---

## Compliance Features

### 7-10 Year Retention

**MANDATE:** Tax audit logs must be retained for 7-10 years depending on jurisdiction.

**Implementation:** Use `Nexus\Scheduler` to automate archival:

```php
// Application Layer: Scheduled task
$this->scheduler->yearly(function() {
    $cutoffDate = now()->subYears(10);
    
    // Archive old records to cold storage
    $this->taxAuditLog->archiveOlderThan($cutoffDate, 'glacier-storage');
});
```

### EventStream Publishing Mandate

**REQUIREMENT:** Application layer MUST publish `TaxCalculatedEvent` after audit log persistence for real-time data warehouse sync.

```php
// After persisting to tax_audit_log table
$this->eventDispatcher->dispatch(new TaxCalculatedEvent(
    aggregateId: $taxBreakdown->transactionId,
    eventType: 'tax_calculated',
    payload: [
        'transaction_id' => $transactionId,
        'tax_breakdown' => $taxBreakdown->toArray(),
        'total_tax_amount' => $taxBreakdown->totalTaxAmount,
        'jurisdiction' => $jurisdiction->toArray(),
    ],
    occurredAt: new \DateTimeImmutable()
));
```

### Rate Change Monitoring

**REQUIREMENT:** Application layer monitors future-dated tax rates and triggers notifications.

```php
// Application Layer: Scheduled task
$this->scheduler->weekly(function() {
    $futureRates = $this->taxRateRepository->findFutureDated(
        startDate: now(),
        endDate: now()->addMonths(3)
    );
    
    foreach ($futureRates as $rate) {
        $this->notifier->send(
            recipient: 'finance-team@company.com',
            channel: 'email',
            template: 'tax.rate_change_upcoming',
            data: [
                'rate_code' => $rate->code,
                'new_rate' => $rate->rate,
                'effective_date' => $rate->effectiveStartDate,
            ]
        );
    }
});
```

---

## Future Enhancements

### Phase 2 Roadmap

1. **Tax Treaty Support** - International withholding tax treaties (`TaxTreatyManagerInterface`)
2. **Tax Incentive Zones** - SEZ/Free Zone reduced rates (extend `TaxJurisdiction` VO)
3. **Cascading Tax** - Tax-on-tax for specific jurisdictions (extend calculation logic)
4. **VAT Registration Validation** - VIES number checking (`TaxRegistrationValidatorInterface`)
5. **Split Payment Mechanism** - Italy VAT split payment (extend calculation method)
6. **Real-time Rate API Integration** - Avalara/TaxJar sync (`TaxRateProviderInterface`)
7. **Tax Exemption OCR** - Certificate PDF parsing (integrate `Nexus\DataProcessor`)

---

## 📖 Documentation

### Package Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide with prerequisites and basic configuration
- **[API Reference](docs/api-reference.md)** - Complete documentation of all interfaces and components
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples
- **[Basic Usage Example](docs/examples/basic-usage.php)** - Simple usage patterns
- **[Advanced Usage Example](docs/examples/advanced-usage.php)** - Advanced scenarios

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress and metrics
- `REQUIREMENTS.md` - Detailed requirements
- `TEST_SUITE_SUMMARY.md` - Test coverage and results
- `VALUATION_MATRIX.md` - Package valuation metrics
- See root `ARCHITECTURE.md` for overall system architecture


## License

MIT License. See `LICENSE` file for details.

---

**Package Version:** 1.0.0  
**Last Updated:** November 24, 2025  
**Maintained By:** Nexus Architecture Team
