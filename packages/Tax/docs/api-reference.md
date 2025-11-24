# API Reference: Nexus\Tax

**Package:** `Nexus\Tax`  
**Version:** 0.1.0-dev  
**Last Updated:** 2025-11-24

This document provides comprehensive API documentation for all public interfaces, value objects, enums, and exceptions in the Nexus\Tax package.

---

## Table of Contents

- [Interfaces](#interfaces)
- [Value Objects](#value-objects)
- [Enums](#enums)
- [Exceptions](#exceptions)
- [Services](#services)

---

## Interfaces

### TaxCalculatorInterface

**Namespace:** `Nexus\Tax\Contracts\TaxCalculatorInterface`

**Purpose:** Primary API for tax calculation.

**Methods:**

```php
public function calculate(
    TaxContext $context,
    Money $taxableAmount
): TaxBreakdown;
```

**Parameters:**
- `$context` - Transaction context with addresses, dates, jurisdiction
- `$taxableAmount` - Base amount to calculate tax on (Money VO)

**Returns:** `TaxBreakdown` with hierarchical tax lines

**Throws:**
- `TaxRateNotFoundException` - Invalid tax code
- `NoNexusInJurisdictionException` - No economic presence
- `TaxExemptionExpiredException` - Expired certificate
- `TaxCalculationException` - General calculation errors

**Example:**
```php
$breakdown = $taxCalculator->calculate(
    context: $context,
    taxableAmount: Money::of('100.00', 'USD')
);

echo $breakdown->totalTaxAmount->getAmount(); // "7.2500"
```

---

### TaxRateRepositoryInterface

**Namespace:** `Nexus\Tax\Contracts\TaxRateRepositoryInterface`

**Purpose:** Temporal tax rate lookup with mandatory effective dates.

**Methods:**

```php
public function findRateByCode(
    string $taxCode,
    \DateTimeInterface $effectiveDate
): TaxRate;

public function findApplicableRates(
    TaxJurisdiction $jurisdiction,
    \DateTimeInterface $effectiveDate
): array;
```

**Key Constraint:** All methods MUST require `$effectiveDate` parameter (temporal queries).

**Example:**
```php
$rate = $repository->findRateByCode(
    taxCode: 'US-CA-SALES',
    effectiveDate: new \DateTimeImmutable('2024-11-24')
);
```

---

### TaxJurisdictionResolverInterface

**Namespace:** `Nexus\Tax\Contracts\TaxJurisdictionResolverInterface`

**Purpose:** Determine taxing jurisdiction based on transaction context.

**Methods:**

```php
public function resolve(TaxContext $context): TaxJurisdiction;
```

**Logic:**
- Physical goods: Destination address jurisdiction
- Digital services: Place-of-supply rules (B2B vs B2C)
- Fallback: Origin address jurisdiction

**Example:**
```php
$jurisdiction = $resolver->resolve($context);
// Returns TaxJurisdiction(federalCode: 'US', stateCode: 'CA', localCode: 'SF')
```

---

### TaxNexusManagerInterface

**Namespace:** `Nexus\Tax\Contracts\TaxNexusManagerInterface`

**Purpose:** Economic nexus determination (revenue/transaction thresholds).

**Methods:**

```php
public function hasNexus(
    string $jurisdictionCode,
    \DateTimeInterface $effectiveDate
): bool;

public function getNexusThreshold(
    string $jurisdictionCode,
    \DateTimeInterface $effectiveDate
): ?NexusThreshold;
```

**Example:**
```php
if ($nexusManager->hasNexus('US-CA', new \DateTimeImmutable())) {
    // Collect tax (nexus met)
} else {
    // No tax collection required
}
```

---

### TaxExemptionManagerInterface

**Namespace:** `Nexus\Tax\Contracts\TaxExemptionManagerInterface`

**Purpose:** Exemption certificate validation.

**Methods:**

```php
public function validateExemption(
    string $certificateId,
    \DateTimeInterface $transactionDate
): ExemptionCertificate;

public function getExpiringCertificates(
    \DateTimeInterface $withinDays
): array;
```

**Example:**
```php
$certificate = $exemptionManager->validateExemption(
    certificateId: 'CERT-12345',
    transactionDate: new \DateTimeImmutable()
);

echo $certificate->exemptionPercentage; // "50.0000" (50% exemption)
```

---

### TaxReportingInterface

**Namespace:** `Nexus\Tax\Contracts\TaxReportingInterface`

**Purpose:** Compliance reporting aggregation.

**Methods:**

```php
public function aggregateForCompliance(
    string $jurisdictionCode,
    \DateTimeInterface $periodStart,
    \DateTimeInterface $periodEnd
): array; // Returns ComplianceReportLine[]
```

**Example:**
```php
$reportLines = $reportingService->aggregateForCompliance(
    jurisdictionCode: 'US-CA',
    periodStart: new \DateTimeImmutable('2024-01-01'),
    periodEnd: new \DateTimeImmutable('2024-03-31')
);
```

---

### TaxGLIntegrationInterface

**Namespace:** `Nexus\Tax\Contracts\TaxGLIntegrationInterface`

**Purpose:** Generate GL journal entries for tax postings.

**Methods:**

```php
public function generateJournalEntries(TaxBreakdown $breakdown): array;
```

**Example:**
```php
$journalEntries = $glIntegration->generateJournalEntries($taxBreakdown);
// Returns array of JournalEntry VOs for Finance package
```

---

### TaxAuditPublisherInterface

**Namespace:** `Nexus\Tax\Contracts\TaxAuditPublisherInterface`

**Purpose:** Optional EventStream integration for audit trails.

**Methods:**

```php
public function publishCalculationEvent(
    TaxContext $context,
    TaxBreakdown $breakdown
): void;

public function publishAdjustmentEvent(
    TaxAdjustmentContext $adjustment
): void;
```

---

## Value Objects

All Value Objects are **immutable** (`final readonly` classes).

### TaxContext

**Namespace:** `Nexus\Tax\ValueObjects\TaxContext`

**Purpose:** Transaction context for tax calculation.

**Properties:**

```php
public readonly string $transactionId;
public readonly string $transactionType;
public readonly \DateTimeImmutable $transactionDate;
public readonly string $taxCode;
public readonly array $originAddress;
public readonly array $destinationAddress;
public readonly string $customerId;
public readonly TaxJurisdiction $taxJurisdiction;
public readonly ?string $exemptionCertificateId;
public readonly ?ServiceClassification $serviceClassification;
```

**Constructor:**

```php
public function __construct(
    string $transactionId,
    string $transactionType,
    \DateTimeImmutable $transactionDate,
    string $taxCode,
    array $originAddress,
    array $destinationAddress,
    string $customerId,
    TaxJurisdiction $taxJurisdiction,
    ?string $exemptionCertificateId = null,
    ?ServiceClassification $serviceClassification = null
)
```

**Validation:**
- `$transactionDate` must not be future date
- `$originAddress` and `$destinationAddress` must contain required keys
- `$taxCode` must not be empty

**Example:**
```php
$context = new TaxContext(
    transactionId: 'INV-12345',
    transactionType: 'customer_invoice',
    transactionDate: new \DateTimeImmutable('2024-11-24'),
    taxCode: 'US-CA-SALES',
    originAddress: ['country' => 'US', 'state' => 'CA', 'city' => 'San Francisco'],
    destinationAddress: ['country' => 'US', 'state' => 'CA', 'city' => 'Los Angeles'],
    customerId: 'CUST-001',
    taxJurisdiction: new TaxJurisdiction('US', 'CA', null),
    exemptionCertificateId: null,
    serviceClassification: null
);
```

---

### TaxRate

**Namespace:** `Nexus\Tax\ValueObjects\TaxRate`

**Purpose:** Temporal tax rate with effective date range.

**Properties:**

```php
public readonly string $taxCode;
public readonly TaxType $taxType;
public readonly TaxLevel $taxLevel;
public readonly string $ratePercentage; // BCMath string (4 decimals)
public readonly \DateTimeImmutable $effectiveFrom;
public readonly ?\DateTimeImmutable $effectiveTo;
public readonly string $glAccountCode;
public readonly int $applicationOrder;
```

**Constructor Validation:**
- `$effectiveFrom` must not be null
- `$effectiveTo` must be >= `$effectiveFrom` (if provided)
- `$ratePercentage` must be >= 0.0000
- `$applicationOrder` must be >= 1

**Example:**
```php
$rate = new TaxRate(
    taxCode: 'US-CA-SALES',
    taxType: TaxType::SalesTax,
    taxLevel: TaxLevel::State,
    ratePercentage: '7.2500',
    effectiveFrom: new \DateTimeImmutable('2024-01-01'),
    effectiveTo: null, // Open-ended
    glAccountCode: '2210',
    applicationOrder: 1
);
```

---

### TaxJurisdiction

**Namespace:** `Nexus\Tax\ValueObjects\TaxJurisdiction`

**Purpose:** Hierarchical tax jurisdiction (federal→state→local).

**Properties:**

```php
public readonly string $federalCode;
public readonly ?string $stateCode;
public readonly ?string $localCode;
```

**Example:**
```php
// US California San Francisco
$jurisdiction = new TaxJurisdiction(
    federalCode: 'US',
    stateCode: 'CA',
    localCode: 'SF'
);

// Federal only (e.g., VAT)
$jurisdiction = new TaxJurisdiction(
    federalCode: 'DE',
    stateCode: null,
    localCode: null
);
```

---

### TaxBreakdown

**Namespace:** `Nexus\Tax\ValueObjects\TaxBreakdown`

**Purpose:** Complete tax calculation result with hierarchical lines.

**Properties:**

```php
public readonly Money $netAmount;
public readonly Money $totalTaxAmount;
public readonly Money $grossAmount;
public readonly array $taxLines; // TaxLine[]
public readonly bool $isReverseCharge;
```

**Calculation:**
- `netAmount` = Original taxable amount
- `totalTaxAmount` = Sum of all tax line amounts
- `grossAmount` = netAmount + totalTaxAmount

**Example:**
```php
$breakdown = new TaxBreakdown(
    netAmount: Money::of('100.00', 'USD'),
    totalTaxAmount: Money::of('7.25', 'USD'),
    grossAmount: Money::of('107.25', 'USD'),
    taxLines: [$taxLine1, $taxLine2],
    isReverseCharge: false
);
```

---

### TaxLine

**Namespace:** `Nexus\Tax\ValueObjects\TaxLine`

**Purpose:** Individual tax calculation line (supports nesting).

**Properties:**

```php
public readonly string $taxCode;
public readonly string $description;
public readonly Money $taxableBase;
public readonly string $ratePercentage;
public readonly Money $taxAmount;
public readonly string $glAccountCode;
public readonly int $applicationOrder;
public readonly array $children; // TaxLine[] for nested taxes
```

**Example:**
```php
$taxLine = new TaxLine(
    taxCode: 'US-CA-SALES',
    description: 'California Sales Tax',
    taxableBase: Money::of('100.00', 'USD'),
    ratePercentage: '7.2500',
    taxAmount: Money::of('7.25', 'USD'),
    glAccountCode: '2210',
    applicationOrder: 1,
    children: [] // No nested taxes
);
```

---

### ExemptionCertificate

**Namespace:** `Nexus\Tax\ValueObjects\ExemptionCertificate`

**Purpose:** Tax exemption certificate with partial exemption support.

**Properties:**

```php
public readonly string $certificateId;
public readonly string $customerId;
public readonly TaxExemptionReason $reason;
public readonly string $exemptionPercentage; // 0.0000 to 100.0000
public readonly \DateTimeImmutable $issueDate;
public readonly ?\DateTimeImmutable $expirationDate;
public readonly ?string $storageKey; // PDF reference in Nexus\Storage
```

**Validation:**
- `$exemptionPercentage` must be between 0.0000 and 100.0000
- `$expirationDate` must be > `$issueDate` (if provided)

**Example:**
```php
$certificate = new ExemptionCertificate(
    certificateId: 'CERT-12345',
    customerId: 'CUST-001',
    reason: TaxExemptionReason::Agricultural,
    exemptionPercentage: '50.0000', // 50% exemption
    issueDate: new \DateTimeImmutable('2024-01-01'),
    expirationDate: new \DateTimeImmutable('2025-12-31'),
    storageKey: 'exemption-certs/CERT-12345.pdf'
);
```

---

### NexusThreshold

**Namespace:** `Nexus\Tax\ValueObjects\NexusThreshold`

**Purpose:** Economic nexus revenue/transaction thresholds.

**Properties:**

```php
public readonly string $jurisdictionCode;
public readonly ?Money $revenueThreshold;
public readonly ?int $transactionThreshold;
public readonly \DateTimeImmutable $effectiveFrom;
public readonly ?\DateTimeImmutable $effectiveTo;
```

**Logic:**
- If both thresholds set: OR logic (either threshold triggers nexus)
- If only one set: Single threshold determines nexus

**Example:**
```php
// US California: $100K revenue OR 200 transactions
$threshold = new NexusThreshold(
    jurisdictionCode: 'US-CA',
    revenueThreshold: Money::of('100000.00', 'USD'),
    transactionThreshold: 200,
    effectiveFrom: new \DateTimeImmutable('2024-01-01'),
    effectiveTo: null
);
```

---

### ComplianceReportLine

**Namespace:** `Nexus\Tax\ValueObjects\ComplianceReportLine`

**Purpose:** Generic compliance report output for Nexus\Statutory transformation.

**Properties:**

```php
public readonly string $jurisdictionCode;
public readonly TaxType $taxType;
public readonly Money $taxableAmount;
public readonly Money $taxCollected;
public readonly string $formFieldId; // Government form field mapping
public readonly \DateTimeImmutable $periodStart;
public readonly \DateTimeImmutable $periodEnd;
```

**Example:**
```php
$reportLine = new ComplianceReportLine(
    jurisdictionCode: 'US-CA',
    taxType: TaxType::SalesTax,
    taxableAmount: Money::of('10000.00', 'USD'),
    taxCollected: Money::of('725.00', 'USD'),
    formFieldId: 'CDTFA-401-LINE-1',
    periodStart: new \DateTimeImmutable('2024-01-01'),
    periodEnd: new \DateTimeImmutable('2024-03-31')
);
```

---

### TaxAdjustmentContext

**Namespace:** `Nexus\Tax\ValueObjects\TaxAdjustmentContext`

**Purpose:** Context for contra-transaction adjustments.

**Properties:**

```php
public readonly string $originalTransactionId;
public readonly string $adjustmentReason;
public readonly \DateTimeImmutable $adjustmentDate;
public readonly string $adjustedBy;
```

**Example:**
```php
$adjustment = new TaxAdjustmentContext(
    originalTransactionId: 'INV-12345',
    adjustmentReason: 'Incorrect tax code applied',
    adjustmentDate: new \DateTimeImmutable(),
    adjustedBy: 'USER-001'
);
```

---

## Enums

All enums are native PHP 8.3 enums with business logic methods.

### TaxType

**Namespace:** `Nexus\Tax\Enums\TaxType`

**Cases:**

```php
enum TaxType: string
{
    case VAT = 'vat';
    case GST = 'gst';
    case SST = 'sst';
    case SalesTax = 'sales_tax';
    case Excise = 'excise';
    case Withholding = 'withholding';
}
```

**Methods:**

```php
public function label(): string;
public function isConsumptionTax(): bool; // VAT, GST, SST
public function requiresReverseCharge(): bool; // VAT for B2B cross-border
```

---

### TaxLevel

**Namespace:** `Nexus\Tax\Enums\TaxLevel`

**Cases:**

```php
enum TaxLevel: string
{
    case Federal = 'federal';
    case State = 'state';
    case Local = 'local';
    case Municipal = 'municipal';
}
```

**Methods:**

```php
public function label(): string;
```

---

### TaxExemptionReason

**Namespace:** `Nexus\Tax\Enums\TaxExemptionReason`

**Cases:**

```php
enum TaxExemptionReason: string
{
    case Resale = 'resale';
    case Government = 'government';
    case Nonprofit = 'nonprofit';
    case Export = 'export';
    case Diplomatic = 'diplomatic';
    case Agricultural = 'agricultural';
}
```

---

### TaxCalculationMethod

**Namespace:** `Nexus\Tax\Enums\TaxCalculationMethod`

**Cases:**

```php
enum TaxCalculationMethod: string
{
    case Standard = 'standard';
    case ReverseCharge = 'reverse_charge';
    case Inclusive = 'inclusive'; // Tax included in price
    case Exclusive = 'exclusive'; // Tax added to price
}
```

---

### ServiceClassification

**Namespace:** `Nexus\Tax\Enums\ServiceClassification`

**Cases:**

```php
enum ServiceClassification: string
{
    case DigitalService = 'digital_service';
    case TelecomService = 'telecom_service';
    case ConsultingService = 'consulting_service';
    case PhysicalGoods = 'physical_goods';
    case Other = 'other';
}
```

**Methods:**

```php
public function requiresPlaceOfSupplyLogic(): bool;
```

---

## Exceptions

All exceptions extend PHP native exceptions.

### TaxCalculationException

**Namespace:** `Nexus\Tax\Exceptions\TaxCalculationException`

**Purpose:** Base exception for all tax calculation errors.

**Constructor:**

```php
public function __construct(
    string $message,
    array $context = [],
    ?\Throwable $previous = null
)
```

---

### TaxRateNotFoundException

**Extends:** `TaxCalculationException`

**Purpose:** Tax code not found or invalid for effective date.

**Example:**
```php
throw new TaxRateNotFoundException(
    taxCode: 'US-CA-SALES',
    effectiveDate: new \DateTimeImmutable('2024-11-24')
);
```

---

### NoNexusInJurisdictionException

**Extends:** `TaxCalculationException`

**Purpose:** No economic presence in jurisdiction (below threshold).

---

### TaxExemptionExpiredException

**Extends:** `TaxCalculationException`

**Purpose:** Exemption certificate expired.

---

### InvalidExemptionPercentageException

**Extends:** `TaxCalculationException`

**Purpose:** Exemption percentage out of range (0-100).

---

### JurisdictionResolutionException

**Extends:** `TaxCalculationException`

**Purpose:** Cannot determine jurisdiction from addresses.

---

### InvalidEffectiveDateException

**Extends:** `TaxCalculationException`

**Purpose:** Future date used in historical query.

---

### TaxRateOverlapException

**Extends:** `TaxCalculationException`

**Purpose:** Conflicting temporal tax rates (overlapping effective dates).

---

### MissingGLAccountCodeException

**Extends:** `TaxCalculationException`

**Purpose:** Tax rate missing GL account code for posting.

---

## Services

### TaxCalculator

**Namespace:** `Nexus\Tax\Services\TaxCalculator`

**Implements:** `TaxCalculatorInterface`

**Constructor:**

```php
public function __construct(
    private readonly TaxRateRepositoryInterface $rateRepository,
    private readonly TaxJurisdictionResolverInterface $jurisdictionResolver,
    private readonly ?TaxNexusManagerInterface $nexusManager = null,
    private readonly ?TaxExemptionManagerInterface $exemptionManager = null,
    private readonly ?TelemetryTrackerInterface $telemetry = null,
    private readonly ?AuditLogManagerInterface $auditLogger = null
)
```

**Key Algorithm:**
1. Resolve jurisdiction (if not provided)
2. Check nexus (if manager bound)
3. Validate exemption (if certificate ID provided)
4. Fetch applicable rates
5. Sort by applicationOrder
6. Calculate hierarchical taxes (cascading)
7. Apply exemption percentage
8. Build TaxBreakdown
9. Track metrics (optional)
10. Log audit trail (optional)

---

### JurisdictionResolver

**Namespace:** `Nexus\Tax\Services\JurisdictionResolver`

**Implements:** `TaxJurisdictionResolverInterface`

**Constructor:**

```php
public function __construct(
    private readonly GeocoderInterface $geocoder
)
```

**Logic:**
- Physical goods → Destination jurisdiction
- Digital services → Place-of-supply rules
- B2B cross-border → Origin jurisdiction (reverse charge)
- B2C cross-border → Destination jurisdiction

---

### ExemptionManager

**Namespace:** `Nexus\Tax\Services\ExemptionManager`

**Implements:** `TaxExemptionManagerInterface`

**Constructor:**

```php
public function __construct(
    private readonly ExemptionRepositoryInterface $repository
)
```

---

### TaxReportingService

**Namespace:** `Nexus\Tax\Services\TaxReportingService`

**Implements:** `TaxReportingInterface`

**Constructor:**

```php
public function __construct(
    private readonly TaxAuditRepositoryInterface $auditRepository,
    private readonly CurrencyConverterInterface $currencyConverter
)
```

---

**For complete integration examples, see:**
- [Getting Started](getting-started.md)
- [Integration Guide](integration-guide.md)
- [Examples](examples/)
