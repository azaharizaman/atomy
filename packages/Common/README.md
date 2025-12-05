# Nexus Common

**Shared domain primitives, contracts, and value objects for Nexus packages.**

The Common package contains foundational elements that are shared across multiple Nexus packages, ensuring consistent domain modeling and reducing code duplication.

## Overview

This package provides:

- **Value Objects**: Immutable domain primitives (TenantId, etc.)
- **Contracts**: Common interfaces used across packages (ClockInterface, behavioral contracts)
- **PSR Compliance**: Uses PSR-14 `Psr\EventDispatcher\EventDispatcherInterface` for event dispatching
- **Exceptions**: Shared domain exceptions

## Installation

```bash
composer require nexus/common
```

## Package Contents

### Value Objects (`src/ValueObjects/`)

#### Core Value Objects

| Value Object | Interfaces | Description |
|--------------|------------|-------------|
| `TenantId` | Comparable, SerializableVO | Strongly-typed tenant identifier (ULID) |
| `Money` | Comparable, Addable, Subtractable, Multipliable, Divisible, CurrencyConvertible, SerializableVO, Formattable | Immutable monetary value with precision arithmetic |
| `Percentage` | Comparable, Addable, Subtractable, Multipliable, Divisible, SerializableVO, Formattable | Percentage values (0-100) for taxes, discounts, variance |

#### Measurement Value Objects

| Value Object | Interfaces | Description |
|--------------|------------|-------------|
| `UnitOfMeasurement` | Enumable, SerializableVO | Standard measurement units (kg, m, L, pcs, etc.) |
| `Measurement` | Comparable, Addable, Subtractable, Multipliable, Divisible, Convertible, SerializableVO, Formattable | Measurements with unit conversion |
| `Quantity` | Comparable, Addable, Subtractable, Multipliable, Divisible, Convertible, SerializableVO | Discrete quantities with units |

#### Identifier Value Objects

| Value Object | Interfaces | Description |
|--------------|------------|-------------|
| `EntityId` | Comparable, SerializableVO | Base class for strongly-typed ULID identifiers |
| `CustomerId` | Comparable, SerializableVO | Customer identifier |
| `ProductId` | Comparable, SerializableVO | Product identifier |
| `EmployeeId` | Comparable, SerializableVO | Employee identifier |
| `VendorId` | Comparable, SerializableVO | Vendor identifier |
| `WarehouseId` | Comparable, SerializableVO | Warehouse identifier |

#### Contact Information Value Objects

| Value Object | Interfaces | Description |
|--------------|------------|-------------|
| `Email` | Comparable, SerializableVO | Validated email address with domain extraction |
| `PhoneNumber` | Comparable, SerializableVO | International phone number (E.164 format) |
| `Address` | Comparable, SerializableVO | Physical address with country validation |

#### Temporal Value Objects

| Value Object | Interfaces | Description |
|--------------|------------|-------------|
| `DateRange` | Comparable, Temporal, AdjustableTime, SerializableVO | Date period with overlap detection |
| `AuditMetadata` | Auditable, SerializableVO | Created/updated by and timestamps |

#### Financial Value Objects

| Value Object | Interfaces | Description |
|--------------|------------|-------------|
| `TaxRate` | Comparable, Multipliable, SerializableVO | Tax rate with jurisdiction and effectivity |
| `TaxCode` | Comparable, Multipliable, SerializableVO | Tax code with rate and description |

#### State Management Value Objects

| Value Object | Interfaces | Description |
|--------------|------------|-------------|
| `Status` | Stateful, Enumable, SerializableVO | State machine with transition validation |

#### Advanced Analysis Value Objects

| Value Object | Interfaces | Description |
|--------------|------------|-------------|
| `VarianceResult` | Comparable, Addable, Subtractable, Multipliable, Divisible, Statistical, TrendAnalyzable, SerializableVO | Budget variance with statistical and trend analysis |

#### Metadata Value Objects

| Value Object | Interfaces | Description |
|--------------|-------------|-------------|
| `AttachmentMetadata` | SerializableVO | File metadata for document attachments |

### Behavioral Interfaces (`src/Contracts/`)

#### Arithmetic Interfaces

| Interface | Methods | Description |
|-----------|---------|-------------|
| `Addable` | `add(self $other): static` | Addition capability |
| `Subtractable` | `subtract(self $other): static` | Subtraction capability |
| `Multipliable` | `multiply(float\|int $multiplier): static` | Multiplication by scalar |
| `Divisible` | `divide(float\|int $divisor): static` | Division by scalar |

#### Comparison Interface

| Interface | Methods | Description |
|-----------|---------|-------------|
| `Comparable` | `compareTo()`, `equals()`, `greaterThan()`, `lessThan()` | Comparison capability |

#### Conversion Interfaces

| Interface | Methods | Description |
|-----------|---------|-------------|
| `Convertible` | `convertTo(string $toUnit)`, `canConvertTo(string $toUnit)` | Unit conversion capability |
| `CurrencyConvertible` | `convertToCurrency()`, `getCurrency()` | Currency conversion capability |

#### Serialization Interfaces

| Interface | Methods | Description |
|-----------|---------|-------------|
| `SerializableVO` | `toArray()`, `toString()`, `fromArray()` | Serialization capability |
| `Formattable` | `format(array $options = [])` | Custom formatting capability |

#### State/Enum Interfaces

| Interface | Methods | Description |
|-----------|---------|-------------|
| `Enumable` | `values()`, `getValue()`, `isValid()` | Enumeration capability |
| `Stateful` | `getState()`, `canTransitionTo()`, `isFinal()` | State machine capability |

#### Time Interfaces

| Interface | Methods | Description |
|-----------|---------|-------------|
| `Temporal` | `getStartDate()`, `getEndDate()`, `contains()`, `overlaps()` | Temporal period capability |
| `AdjustableTime` | `shift()`, `extend()` | Time adjustment capability |

#### Analysis Interfaces

| Interface | Methods | Description |
|-----------|---------|-------------|
| `Auditable` | `getCreatedBy()`, `getCreatedAt()`, `getUpdatedBy()`, `getUpdatedAt()` | Audit trail capability |
| `Statistical` | `average()`, `abs()`, `isWithinRange()` | Statistical analysis capability |
| `TrendAnalyzable` | `getTrendDirection()`, `percentageChange()`, `isSignificantChange()` | Trend analysis capability |

#### System Interfaces

| Interface | Methods | Description |
|-----------|---------|-------------|
| `ClockInterface` | `now()` | Provides current time for testability |
| `UlidInterface` | `generate()`, `isValid()`, `getTimestamp()` | ULID generation for entity identifiers |

> **Note:** For event dispatching, use PSR-14's `Psr\EventDispatcher\EventDispatcherInterface` directly. This package depends on `psr/event-dispatcher`.

### Exceptions (`src/Exceptions/`)

| Exception | Description |
|-----------|-------------|
| `InvalidValueException` | Base exception for invalid value objects |
| `InvalidMoneyException` | Exception for invalid Money operations |
| `CurrencyMismatchException` | Exception when currencies don't match in operations |

## Usage Examples

### Money Value Object

The `Money` value object provides immutable monetary value representation with precision arithmetic.

**Important - Money vs Currency Package Boundary:**
- **`Money` (this package)**: Arithmetic operations, comparison, formatting, allocation
- **`Nexus\Currency` package**: Exchange rate management, cross-currency conversions with rates

Use `Money` for calculations within a single currency. Use `Nexus\Currency` when you need exchange rates and cross-currency operations.

```php
use Nexus\Common\ValueObjects\Money;

// Create money instances
$price = Money::of(10000, 'MYR');  // RM 100.00 (10000 minor units)
$tax = Money::of(600, 'MYR');      // RM 6.00

// Arithmetic operations (immutable - returns new instances)
$total = $price->add($tax);        // RM 106.00
$discount = $price->multiply(0.1); // RM 10.00
$final = $total->subtract($discount);

// Comparison
if ($total->greaterThan($price)) {
    echo "Total exceeds price";
}

// Low-level currency conversion (with known exchange rate)
// For exchange rate management, use Nexus\Currency package
$usd = $price->convertToCurrency('USD', exchangeRate: 4.5);

// Formatting
echo $price->format(['decimals' => 2, 'symbol' => true]); // "MYR 100.00"

// Serialization
$data = $price->toArray(); 
// ['amountInMinorUnits' => 10000, 'amount' => 10000, 'currency' => 'MYR']
$restored = Money::fromArray($data);

// State checks
if ($money->isZero()) { /* ... */ }
if ($money->isPositive()) { /* ... */ }

// Allocate by ratios
$shares = Money::of(10000, 'MYR')->allocate([60, 30, 10]);
// Returns: [6000, 3000, 1000] minor units
```

### Percentage Value Object

```php
use Nexus\Common\ValueObjects\Percentage;

// Create percentage
$taxRate = Percentage::of(6.0);              // 6%
$discount = Percentage::fromDecimal(0.15);   // 15%

// Calculate percentage of amount
$taxAmount = $taxRate->of(1000.0);   // 60.0

// Arithmetic
$totalRate = $taxRate->add(Percentage::of(2.0)); // 8%
$halfRate = $taxRate->divide(2);                 // 3%

// Conversion
$decimal = $taxRate->asDecimal();    // 0.06

// Formatting
echo $taxRate->format(['decimals' => 2]); // "6.00%"
```

### Measurement and Quantity

```php
use Nexus\Common\ValueObjects\Measurement;
use Nexus\Common\ValueObjects\Quantity;
use Nexus\Common\ValueObjects\UnitOfMeasurement;

// Measurement with conversions
$weight = Measurement::of(2.5, UnitOfMeasurement::KG);
$weightInGrams = $weight->convertTo(UnitOfMeasurement::G);   // 2500 g
$weightInPounds = $weight->convertTo(UnitOfMeasurement::LB); // ~5.51 lb

// Arithmetic with auto-conversion
$weight1 = Measurement::of(1000, UnitOfMeasurement::G);
$weight2 = Measurement::of(1, UnitOfMeasurement::KG);
$total = $weight1->add($weight2); // 2000 g (auto-converts to common unit)

// Check conversion compatibility
if ($weight->canConvertTo(UnitOfMeasurement::G)) {
    $converted = $weight->convertTo(UnitOfMeasurement::G);
}

// Quantity for inventory
$stock = Quantity::of(100, UnitOfMeasurement::PCS);
$shipped = Quantity::of(25, UnitOfMeasurement::PCS);
$remaining = $stock->subtract($shipped); // 75 pcs

if ($remaining->isZero()) {
    echo "Out of stock";
}
```

### Entity Identifiers

```php
use Nexus\Common\ValueObjects\CustomerId;
use Nexus\Common\ValueObjects\ProductId;

// Generate new IDs (ULID-based)
$customerId = CustomerId::generate();
$productId = ProductId::generate();

// Type safety prevents mixing
function processOrder(CustomerId $customerId, ProductId $productId) {
    // Can't accidentally pass ProductId where CustomerId is expected
    // PHP type system enforces this at compile time
}

// From string (e.g., from database)
$id = CustomerId::fromString('01HN6Z8Y9C3EXAMPLE123');

// Serialization
$idString = $customerId->toString();
$ulid = $customerId->toUlid(); // Returns Symfony\Component\Uid\Ulid

// Comparison
if ($customerId->equals($otherCustomerId)) {
    echo "Same customer";
}
```

### Contact Information

```php
use Nexus\Common\ValueObjects\Email;
use Nexus\Common\ValueObjects\PhoneNumber;
use Nexus\Common\ValueObjects\Address;

// Email validation (RFC 5322)
try {
    $email = Email::of('customer@example.com');
    $domain = $email->getDomain();      // "example.com"
    $local = $email->getLocalPart();    // "customer"
} catch (InvalidValueException $e) {
    // Invalid email format
}

// Phone number formatting (E.164)
$phone = PhoneNumber::of('+60123456789');
$countryCode = $phone->getCountryCode();  // "+60"
$formatted = $phone->format();             // "+60 12-345 6789"

// Address with ISO country codes
$address = Address::of(
    street: '123 Main Street',
    street2: 'Suite 100',
    city: 'Kuala Lumpur',
    state: 'Federal Territory',
    postalCode: '50000',
    country: 'MY'  // ISO 3166-1 alpha-2
);
$full = $address->getFullAddress(); // Complete formatted address
```

### DateRange and Temporal Operations

```php
use Nexus\Common\ValueObjects\DateRange;

// Create date range
$fiscalPeriod = DateRange::of(
    startDate: new DateTimeImmutable('2024-01-01'),
    endDate: new DateTimeImmutable('2024-12-31')
);

// Check if date is within range
$isInPeriod = $fiscalPeriod->contains(
    new DateTimeImmutable('2024-06-15')
); // true

// Check overlaps
$q1 = DateRange::of(
    new DateTimeImmutable('2024-01-01'),
    new DateTimeImmutable('2024-03-31')
);
$overlaps = $fiscalPeriod->overlaps($q1); // true

// Time adjustments
$extended = $fiscalPeriod->extend(days: 30);   // Extends end date
$shifted = $fiscalPeriod->shift(months: 1);    // Shifts both dates

// Get duration
$days = $fiscalPeriod->getDays(); // 366 (2024 is leap year)

// Check if currently active
if ($fiscalPeriod->isActive()) {
    echo "Period is active";
}
```

### Tax Calculation

```php
use Nexus\Common\ValueObjects\TaxRate;
use Nexus\Common\ValueObjects\TaxCode;
use Nexus\Common\ValueObjects\Percentage;

// Tax rate with temporal validity
$taxRate = TaxRate::of(
    rate: Percentage::of(6.0),
    taxType: 'SST',
    jurisdiction: 'MY',
    effectiveFrom: new DateTimeImmutable('2024-01-01'),
    effectiveTo: new DateTimeImmutable('2024-12-31')
);

// Check if rate is valid on specific date
$date = new DateTimeImmutable('2024-06-15');
if ($taxRate->isEffectiveOn($date)) {
    $taxAmount = $taxRate->calculateTax(amountInMinorUnits: 100000);
    // RM 1000 -> RM 60 tax
}

// Tax code system
$taxCode = TaxCode::of(
    code: 'TX',
    description: 'Standard Rate',
    rate: Percentage::of(6.0),
    isActive: true
);

if ($taxCode->isActive()) {
    $tax = $taxCode->calculateTax(amountInMinorUnits: 50000);
    // RM 500 -> RM 30 tax
}
```

### Status State Machine

```php
use Nexus\Common\ValueObjects\Status;

// Factory methods for common states
$draft = Status::draft();
$pending = Status::pending();
$approved = Status::approved();
$rejected = Status::rejected();
$closed = Status::closed();

// Check allowed transitions
if ($draft->canTransitionTo($pending)) {
    $newStatus = $draft->transitionTo($pending);
    echo $newStatus->getState(); // "pending"
}

// Prevent invalid transitions
try {
    $draft->transitionTo($approved); // Draft -> Approved not allowed
} catch (InvalidValueException $e) {
    echo "Invalid state transition";
}

// Check if final state
if ($approved->isFinal()) {
    echo "No further transitions allowed";
}

// Custom workflow
$customStatus = Status::of(
    state: 'in_review',
    allowedTransitions: ['approved', 'rejected'],
    isFinal: false
);
```

### Variance Analysis (Advanced)

```php
use Nexus\Common\ValueObjects\VarianceResult;

// Create variance result
$result = VarianceResult::of(
    actual: 95000.0,
    budget: 100000.0
);

// Get variance (computed property)
$variance = $result->getVariance();           // -5000.0
$percentage = $result->getPercentageVariance(); // -5.0%

// Business analysis
if ($result->isUnfavorable()) {
    echo "Actual is below budget (unfavorable)";
}
if ($result->isFavorable()) {
    echo "Actual exceeds budget (favorable)";
}

// Statistical operations
$results = [
    VarianceResult::of(95000, 100000),
    VarianceResult::of(105000, 100000),
    VarianceResult::of(98000, 100000),
];
$average = VarianceResult::average($results);

// Trend analysis
$lastMonth = VarianceResult::of(90000, 100000);
$thisMonth = VarianceResult::of(95000, 100000);

$trend = $thisMonth->getTrendDirection($lastMonth); // 'up'
$change = $thisMonth->percentageChange($lastMonth); // 5.56%

if ($thisMonth->isSignificantChange($lastMonth, threshold: 0.10)) {
    echo "Significant variance change detected (>10%)";
}

// Range validation
$minResult = VarianceResult::of(80000, 100000);
$maxResult = VarianceResult::of(120000, 100000);
if ($result->isWithinRange($minResult, $maxResult)) {
    echo "Result is within acceptable range";
}

// Arithmetic operations
$combined = $result->add($lastMonth); // Combines actual and budget
$adjusted = $result->multiply(1.1);   // Scale by 110%
```

### Audit Metadata

```php
use Nexus\Common\ValueObjects\AuditMetadata;

// Create audit metadata for new entity
$audit = AuditMetadata::forCreate(userId: 'user-123');

// Update metadata when entity is modified
$updatedAudit = $audit->withUpdate(userId: 'user-456');

// Access audit information
$createdBy = $audit->getCreatedBy();            // 'user-123'
$createdAt = $audit->getCreatedAt();            // DateTimeImmutable
$updatedBy = $updatedAudit->getUpdatedBy();     // 'user-456'
$updatedAt = $updatedAudit->getUpdatedAt();     // DateTimeImmutable

// Serialization for storage
$data = $audit->toArray();
/*
[
    'created_by' => 'user-123',
    'created_at' => '2024-01-15T10:30:00+00:00',
    'updated_by' => null,
    'updated_at' => null,
]
*/

$restored = AuditMetadata::fromArray($data);
```

### Attachment Metadata

```php
use Nexus\Common\ValueObjects\AttachmentMetadata;

// Track file attachment
$attachment = AttachmentMetadata::of(
    fileName: 'invoice_2024_001.pdf',
    mimeType: 'application/pdf',
    sizeInBytes: 524288,  // 512 KB
    uploadedAt: new DateTimeImmutable(),
    uploadedBy: 'user-123',
    storagePath: 'invoices/2024/01/invoice_2024_001.pdf'
);

// Get file information
$extension = $attachment->getFileExtension();     // 'pdf'
$humanSize = $attachment->getHumanReadableSize(); // '512.00 KB'

// Serialization for database storage
$data = $attachment->toArray();
$restored = AttachmentMetadata::fromArray($data);
```

### TenantId

```php
use Nexus\Common\ValueObjects\TenantId;

// Create a new TenantId
$tenantId = TenantId::generate();

// Create from existing ULID string
$tenantId = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');

// Compare tenant IDs
if ($tenantId->equals($otherTenantId)) {
    echo "Same tenant";
}

// Get string value
$tenantIdString = $tenantId->toString();
```

### ClockInterface (System Interface)

```php
use Nexus\Common\Contracts\ClockInterface;

final readonly class MyService
{
    public function __construct(
        private ClockInterface $clock
    ) {}
    
    public function isExpired(\DateTimeImmutable $expiresAt): bool
    {
        return $expiresAt < $this->clock->now();
    }
}
```

### Logging (PSR-3)

```php
use Psr\Log\LoggerInterface;

final readonly class MyService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
    
    public function doSomething(): void
    {
        $this->logger->info('Operation completed', [
            'context' => 'additional data'
        ]);
    }
}
```

## Design Principles

### 1. Immutability
All value objects are immutable (`readonly` classes). Operations return new instances:

```php
$money = Money::of(100, 'MYR');
$newMoney = $money->add(Money::of(50, 'MYR')); // Returns new instance
// $money still contains 100, $newMoney contains 150
```

### 2. Type Safety
Strong typing with PHP 8.3+ features:
- `declare(strict_types=1)` in all files
- Readonly properties
- Native enums where appropriate
- Interface-based polymorphism

```php
// Type system prevents mixing different entity IDs
function processOrder(CustomerId $customerId, ProductId $productId) {
    // Can't pass ProductId where CustomerId is expected
}
```

### 3. Validation
Invalid states are impossible to create:

```php
// Throws InvalidValueException immediately
$email = Email::of('invalid-email'); // Exception!
$percentage = Percentage::of(150.0); // Exception! (must be 0-100)
```

### 4. Interface Segregation Principle (ISP)
Small, focused interfaces that can be composed:

```php
// Money implements 8 focused interfaces
class Money implements 
    Comparable,           // Comparison operations
    Addable,              // Addition
    Subtractable,         // Subtraction
    Multipliable,         // Multiplication
    Divisible,            // Division
    CurrencyConvertible,  // Currency operations
    SerializableVO,       // Serialization
    Formattable           // Formatting
{
    // Each interface adds specific capability
}
```

### 5. Framework Agnostic
No framework dependencies - works with any PHP framework:
- Laravel
- Symfony
- Slim
- Pure PHP

### 6. Interface Composition Patterns

**Arithmetic Group** (Addable + Subtractable + Multipliable + Divisible):
- `Money`, `Percentage`, `Measurement`, `Quantity`, `VarianceResult`

**Comparison + Serialization** (Comparable + SerializableVO):
- `Email`, `PhoneNumber`, `Address`, `EntityId` and subclasses

**Temporal Group** (Temporal + AdjustableTime):
- `DateRange`

**State Management** (Stateful + Enumable):
- `Status`

**Analysis Group** (Statistical + TrendAnalyzable):
- `VarianceResult`

**Conversion Capabilities**:
- `Convertible` - Unit conversion (Measurement, Quantity)
- `CurrencyConvertible` - Currency conversion (Money)

### 7. Behavior Contracts, Not Markers
Interfaces define actual capabilities with methods, not just markers:

```php
// ✅ GOOD: Defines behavior
interface Addable {
    public function add(self $other): static;
}

// ❌ BAD: Just a marker (not used in this package)
interface ValueObjectInterface {
    // No methods - just marks a class
}
```

## Dependencies

- **PHP** ^8.3
- **psr/log** ^3.0 (PSR-3 Logger Interface)
- **symfony/uid** ^7.0 (ULID generation for identifiers)

All dependencies are minimal and framework-agnostic.

## License

MIT License - see LICENSE file for details.

---

**Part of the Nexus Package Monorepo**
