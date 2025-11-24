# Architectural Decisions: Nexus\Tax

**Package:** Nexus\Tax  
**Purpose:** Document critical design decisions and their rationale  
**Last Updated:** 2025-11-24

This document explains the **"why"** behind major architectural decisions in the Nexus\Tax package. Understanding these decisions helps developers integrate the package correctly and avoid anti-patterns.

---

## Table of Contents

1. [Temporal Repository Pattern](#1-temporal-repository-pattern)
2. [Immutable Audit Log](#2-immutable-audit-log)
3. [Stateless Calculation Engine](#3-stateless-calculation-engine)
4. [BCMath for Precision](#4-bcmath-for-precision)
5. [Hierarchical Tax Structure](#5-hierarchical-tax-structure)
6. [Decorator Pattern for Caching](#6-decorator-pattern-for-caching)
7. [Partial Exemptions](#7-partial-exemptions)
8. [Reverse Charge Mechanism](#8-reverse-charge-mechanism)
9. [Optional Telemetry and Audit Logging](#9-optional-telemetry-and-audit-logging)
10. [Place-of-Supply Rules](#10-place-of-supply-rules)
11. [Economic Nexus Determination](#11-economic-nexus-determination)
12. [Repository Validation Strategy](#12-repository-validation-strategy)
13. [Preview vs Finalization Pattern](#13-preview-vs-finalization-pattern)
14. [Multi-Tenant Inheritance NOT Implemented](#14-multi-tenant-inheritance-not-implemented)
15. [EventStream Integration Optional](#15-eventstream-integration-optional)

---

## 1. Temporal Repository Pattern

### Decision
**All tax rate lookup methods MUST require `\DateTimeInterface $effectiveDate` parameter.**

### Rationale

**Problem:**
Tax rates change over time. A common bug in tax systems is using the current date when calculating taxes for backdated or historical transactions, resulting in incorrect amounts and audit failures.

**Example of the Bug:**
```php
// ❌ WRONG: Uses current date implicitly
$rate = $repository->findByCode('US-CA-SALES'); 
// If rate was 7% in 2023 but 7.25% today, historical invoice gets wrong rate!

// ✅ CORRECT: Explicit effective date
$rate = $repository->findByCode('US-CA-SALES', $invoiceDate);
// Always uses rate that was active on invoice date
```

**Benefits:**
1. **Prevents Backdating Fraud:** Cannot accidentally use current rate for old transactions
2. **Audit Accuracy:** Tax calculations reproducible at any point in history
3. **Compliance:** Meets requirements for "What was the tax on 2023-06-15?"
4. **Explicit Intent:** Developers forced to think about temporal aspects

**Trade-offs:**
- Slightly more verbose API (extra parameter)
- Repository implementations must enforce temporal logic

**Alternative Considered:** Optional `$effectiveDate` parameter defaulting to current date.  
**Rejected Because:** Would allow accidental misuse. Explicit is better than implicit for financial calculations.

---

## 2. Immutable Audit Log

### Decision
**Tax audit log has no UPDATE or DELETE operations. Corrections use contra-transactions.**

### Rationale

**Problem:**
Tax authorities require complete audit trails. If audit log records can be updated or deleted, compliance is compromised and fraud is possible.

**Solution:**
```php
// Original calculation (incorrect tax code)
tax_audit_log:
  transaction_id: INV-12345
  tax_amount: 10.00
  is_adjustment: false

// Correction via contra-transaction (negative amount)
tax_audit_log:
  transaction_id: INV-12345-ADJ
  tax_amount: -10.00
  is_adjustment: true
  original_transaction_id: INV-12345

// New correct calculation
tax_audit_log:
  transaction_id: INV-12345-CORRECTED
  tax_amount: 12.50
  is_adjustment: false
```

**Benefits:**
1. **Complete History:** Every calculation preserved, including errors
2. **Compliance:** Meets SOX, GDPR, tax authority requirements
3. **Fraud Prevention:** Cannot tamper with historical records
4. **Audit Trail:** Clear path from error to correction

**Implementation:**
```sql
-- Revoke dangerous permissions
REVOKE UPDATE, DELETE ON tax_audit_log FROM app_user;
GRANT INSERT, SELECT ON tax_audit_log TO app_user;
```

**Trade-offs:**
- Storage grows continuously (mitigated by archival after 7-10 years)
- Corrections require 2-3 records instead of 1 UPDATE

**Alternative Considered:** Soft deletes with `deleted_at` column.  
**Rejected Because:** Still allows data modification, doesn't preserve full history.

---

## 3. Stateless Calculation Engine

### Decision
**Package defines interfaces only; application layer implements repositories. No database queries or file I/O in package services.**

### Rationale

**Problem:**
Framework coupling prevents package reuse. If package contains Laravel Eloquent models or Symfony Doctrine entities, it only works with that framework.

**Solution:**
```php
// Package: Define what you need
interface TaxRateRepositoryInterface {
    public function findRateByCode(string $code, \DateTimeInterface $date): TaxRate;
}

// Application: Provide implementation
class EloquentTaxRateRepository implements TaxRateRepositoryInterface {
    // Laravel-specific implementation
}

class DoctrineTaxRateRepository implements TaxRateRepositoryInterface {
    // Symfony-specific implementation
}
```

**Benefits:**
1. **Framework Agnostic:** Works with Laravel, Symfony, Slim, any PHP framework
2. **No Vendor Lock-In:** Can switch frameworks without changing tax logic
3. **Testability:** Easy to mock repositories for unit tests
4. **Package Purity:** Pure business logic, no infrastructure concerns

**Trade-offs:**
- Consumers must implement repositories (more work upfront)
- Documentation must show implementation patterns for each framework

**Alternative Considered:** Include Laravel Eloquent models in package.  
**Rejected Because:** Violates framework agnosticism principle, limits reusability.

---

## 4. BCMath for Precision

### Decision
**All monetary calculations use BCMath extension with 4 decimal places. No float arithmetic.**

### Rationale

**Problem:**
PHP float arithmetic has rounding errors:

```php
// ❌ WRONG: Float precision issues
$tax = 100.00 * 0.0725; // 7.25
$tax2 = 100.00 * 0.0726; // 7.26... or 7.2599999999? 😱

// ✅ CORRECT: BCMath
$tax = bcmul('100.00', '0.0725', 4); // "7.2500" (exact)
```

**Benefits:**
1. **Audit Accuracy:** Calculations reproducible to the cent
2. **Compliance:** Financial regulations require exact arithmetic
3. **No Rounding Errors:** Eliminates float precision bugs

**Trade-offs:**
- ~10-15% performance overhead vs float
- All amounts stored as strings (e.g., "7.2500")

**Why 4 Decimals?**
- Tax rates can be precise (e.g., 7.2500%)
- Supports fractional cent calculations for invoices with many lines
- Standard in accounting systems

**Alternative Considered:** Use Money library (brick/money, moneyphp/money).  
**Decision:** These libraries use BCMath internally anyway. We use BCMath directly for transparency.

---

## 5. Hierarchical Tax Structure

### Decision
**`TaxBreakdown` contains array of `TaxLine` objects with `children` support for nested taxes.**

### Rationale

**Problem:**
Some jurisdictions have compound taxes (federal→state→local cascading). Flat structure cannot represent this:

```php
// ❌ WRONG: Flat structure loses cascading info
tax_lines: [
    {description: "Federal GST", amount: 5.00},
    {description: "Provincial PST", amount: 7.00}
]
// Did PST apply to base or base+GST? Unclear!

// ✅ CORRECT: Hierarchical structure
tax_lines: [
    {
        description: "Federal GST (5%)",
        taxable_base: 100.00,
        amount: 5.00,
        children: []
    },
    {
        description: "Provincial PST (7% on base)",
        taxable_base: 100.00,
        amount: 7.00,
        children: []
    }
]
```

**Malaysian SST Example (Sales + Service Tax):**
```php
tax_lines: [
    {
        description: "Sales Tax (10%)",
        taxable_base: 100.00,
        amount: 10.00,
        children: [
            {
                description: "Service Tax (6% on gross)",
                taxable_base: 110.00, // Base + sales tax
                amount: 6.60,
                children: []
            }
        ]
    }
]
```

**Benefits:**
1. **Accurate Representation:** Preserves tax calculation logic
2. **Detailed Reporting:** Can break down by tax level
3. **GL Posting:** Each tax line has GL account code

**Implementation:**
Recursive calculation in `TaxCalculator::calculate()` method.

---

## 6. Decorator Pattern for Caching

### Decision
**Package core is cache-agnostic. Application layer adds caching via decorator pattern.**

### Rationale

**Problem:**
Different applications use different caching strategies (Redis, Memcached, file, none). Hard-coding caching in package violates framework agnosticism.

**Solution:**
```php
// Package: Pure calculation engine (no cache)
class TaxCalculator implements TaxCalculatorInterface {
    public function calculate(TaxContext $context, Money $amount): TaxBreakdown {
        // Pure business logic
    }
}

// Application: Decorator adds caching
class CachedTaxCalculator implements TaxCalculatorInterface {
    public function __construct(
        private TaxCalculatorInterface $decorated,
        private CacheInterface $cache
    ) {}
    
    public function calculate(TaxContext $context, Money $amount): TaxBreakdown {
        $key = $this->generateKey($context, $amount);
        
        if ($cached = $this->cache->get($key)) {
            return $cached;
        }
        
        $result = $this->decorated->calculate($context, $amount);
        $this->cache->set($key, $result, 3600); // 1 hour TTL
        
        return $result;
    }
}
```

**Benefits:**
1. **Framework Agnostic:** Package doesn't depend on cache implementation
2. **Consumer Control:** Application chooses caching strategy
3. **Open/Closed Principle:** Package closed for modification, open for extension
4. **Testability:** Can test with/without cache easily

**When to Cache:**
- Jurisdiction resolution (geocoding API calls) - 24 hours
- Tax rate lookups (database queries) - 1 hour
- Tax calculations (frequently repeated) - 15-30 minutes

**Cache Invalidation:**
```php
// Invalidate on tax rate update
$this->cache->delete("tax_rate_{$taxCode}");

// Invalidate on jurisdiction boundary change
$this->cache->delete("jurisdiction_{$addressHash}");
```

---

## 7. Partial Exemptions

### Decision
**`ExemptionCertificate` includes `exemptionPercentage` property (0.0000 to 100.0000).**

### Rationale

**Problem:**
Some organizations qualify for partial tax exemptions (not binary exempt/taxable):

- Agricultural cooperatives: 50% exemption on equipment
- Educational nonprofits: 75% exemption on supplies
- Government agencies: 100% exemption on services

**Solution:**
```php
$certificate = new ExemptionCertificate(
    certificateId: 'CERT-AG-001',
    customerId: 'CUST-FARM-123',
    reason: TaxExemptionReason::Agricultural,
    exemptionPercentage: '50.0000', // 50% exemption
    issueDate: new \DateTimeImmutable('2024-01-01'),
    expirationDate: new \DateTimeImmutable('2025-12-31')
);

// Calculation:
$taxableBase = $originalAmount * (1 - $exemptionPercentage / 100);
$taxableBase = 100.00 * (1 - 50 / 100) = 50.00
$taxAmount = 50.00 * 0.0725 = 3.625 (instead of 7.25)
```

**Benefits:**
1. **Flexibility:** Handles all exemption scenarios (0-100%)
2. **Real-World Compliance:** Matches tax authority rules
3. **Accurate Calculations:** Exemption applied before tax rates

**Alternative Considered:** Boolean `isExempt` field.  
**Rejected Because:** Cannot represent partial exemptions (common in agriculture, education sectors).

---

## 8. Reverse Charge Mechanism

### Decision
**`TaxCalculationMethod::ReverseCharge` returns $0.00 tax amount with deferred liability GL code.**

### Rationale

**Problem:**
EU VAT cross-border B2B transactions use "reverse charge" - seller doesn't collect tax, buyer self-assesses.

**Example:**
- German company sells services to French company
- Normal VAT: German company collects 19% VAT, remits to German tax authority
- Reverse Charge: German company collects 0% VAT, French company self-assesses French VAT

**Implementation:**
```php
if ($context->calculationMethod === TaxCalculationMethod::ReverseCharge) {
    return new TaxBreakdown(
        netAmount: $taxableAmount,
        totalTaxAmount: Money::of('0.00', $currency),
        grossAmount: $taxableAmount, // No tax collected
        taxLines: [],
        isReverseCharge: true
    );
}
```

**GL Posting (Buyer Side):**
```php
// Application layer handles buyer-side accrual
if ($breakdown->isReverseCharge) {
    $glManager->post([
        'debit' => ['account' => '5100', 'amount' => $taxAmount], // Tax expense
        'credit' => ['account' => '2300', 'amount' => $taxAmount], // Tax liability
    ]);
}
```

**Benefits:**
1. **EU VAT Compliance:** Meets regulatory requirements
2. **Clear Indicator:** `isReverseCharge` flag signals special handling
3. **Correct Amounts:** Seller invoice shows $0 tax (correct)

---

## 9. Optional Telemetry and Audit Logging

### Decision
**Constructor accepts nullable `TelemetryTrackerInterface` and `AuditLogManagerInterface`.**

### Rationale

**Problem:**
Not all deployments need observability or audit trails. Forcing dependencies increases coupling and deployment complexity.

**Solution:**
```php
public function __construct(
    private readonly TaxRateRepositoryInterface $rateRepository,
    private readonly ?TelemetryTrackerInterface $telemetry = null,
    private readonly ?AuditLogManagerInterface $auditLogger = null
) {}

public function calculate(TaxContext $context, Money $amount): TaxBreakdown {
    $startTime = microtime(true);
    
    $result = $this->performCalculation($context, $amount);
    
    // Optional tracking (graceful degradation)
    $this->telemetry?->timing('tax.calculation_duration_ms', (microtime(true) - $startTime) * 1000);
    $this->auditLogger?->log($context->transactionId, 'tax_calculated', 'Tax calculation completed');
    
    return $result;
}
```

**Benefits:**
1. **Flexible Deployment:** Can run without monitoring infrastructure
2. **Graceful Degradation:** Null-safe operator prevents errors if not bound
3. **Production-Ready:** Easy to enable observability later

**When to Enable:**
- **Telemetry:** Production environments for performance monitoring
- **Audit Logger:** Compliance-critical deployments (financial services, healthcare)

---

## 10. Place-of-Supply Rules

### Decision
**`TaxContext` accepts optional `ServiceClassification` enum for cross-border logic.**

### Rationale

**Problem:**
Digital services vs physical goods have different tax jurisdiction rules:

- **Physical Goods:** Taxed at destination (where goods delivered)
- **Digital Services:** EU VAT - taxed at customer location (B2C) or supplier location (B2B)
- **Consulting Services:** May be taxed at performance location

**Solution:**
```php
$context = new TaxContext(
    // ... other params
    serviceClassification: ServiceClassification::DigitalService
);

// In JurisdictionResolver:
public function resolve(TaxContext $context): TaxJurisdiction {
    if ($context->serviceClassification?->requiresPlaceOfSupplyLogic()) {
        return $this->applyPlaceOfSupplyRules($context);
    }
    
    // Physical goods: destination jurisdiction
    return $this->resolveFromAddress($context->destinationAddress);
}
```

**Benefits:**
1. **Cross-Border Compliance:** Handles EU VAT, UK VAT, GST rules correctly
2. **Flexibility:** Can add new service types without breaking API
3. **Explicit Intent:** Developer specifies service type upfront

---

## 11. Economic Nexus Determination

### Decision
**Nexus determination delegated to `TaxNexusManagerInterface`. Package does not implement tracking logic.**

### Rationale

**Problem:**
Economic nexus requires stateful tracking of revenue/transactions per jurisdiction over time. This violates package statelessness.

**Solution:**
```php
// Package: Define interface
interface TaxNexusManagerInterface {
    public function hasNexus(string $jurisdictionCode, \DateTimeInterface $date): bool;
}

// Application: Implement tracking
class EloquentNexusManager implements TaxNexusManagerInterface {
    public function hasNexus(string $jurisdictionCode, \DateTimeInterface $date): bool {
        $threshold = $this->getThreshold($jurisdictionCode, $date);
        $revenue = $this->calculateRevenueInJurisdiction($jurisdictionCode, $date);
        
        return $revenue->greaterThan($threshold->revenueThreshold);
    }
}
```

**Benefits:**
1. **Package Statelessness:** No revenue tracking in package
2. **Flexibility:** Application chooses tracking strategy (real-time, batch, cache)
3. **Clear Responsibility:** Package = calculation, Application = state management

---

## 12. Repository Validation Strategy

### Decision
**Repository throws `TaxRateNotFoundException` for invalid codes. Calculator does not validate.**

### Rationale

**Problem:**
Where should tax code validation occur? In repository or in calculator?

**Decision:** Repository validates.

**Rationale:**
```php
// Repository: Validates tax code exists
public function findRateByCode(string $taxCode, \DateTimeInterface $date): TaxRate {
    $rate = $this->fetchFromDatabase($taxCode, $date);
    
    if (!$rate) {
        throw new TaxRateNotFoundException($taxCode, $date);
    }
    
    return $rate;
}

// Calculator: Assumes valid rate
public function calculate(TaxContext $context, Money $amount): TaxBreakdown {
    $rate = $this->rateRepository->findRateByCode($context->taxCode, $context->transactionDate);
    // If we reach here, rate is valid (repository validated)
    
    return $this->buildBreakdown($rate, $amount);
}
```

**Benefits:**
1. **Single Responsibility:** Calculator focuses on calculation logic
2. **Clear Error Boundary:** Repository owns validation
3. **Testability:** Can mock repository to test calculation without database

---

## 13. Preview vs Finalization Pattern

### Decision
**Same `calculate()` method for preview and finalization. Persistence decision in application layer.**

### Rationale

**Problem:**
Do we need separate methods for "preview tax" vs "finalize tax"?

**Decision:** No. Use same method.

**Rationale:**
```php
// Preview (don't save to audit log)
$taxBreakdown = $taxCalculator->calculate($context, $amount);
echo "Estimated tax: " . $taxBreakdown->totalTaxAmount;

// Finalize (save to audit log)
$taxBreakdown = $taxCalculator->calculate($context, $amount);
$this->saveTaxAuditLog($context, $taxBreakdown);
$this->saveInvoiceTaxLines($invoiceId, $taxBreakdown);
```

**Benefits:**
1. **Simpler API:** One method, not two
2. **Application Control:** Application decides when to persist
3. **Flexible Usage:** Same calculation for quotes, invoices, reports

**Alternative Considered:** `preview()` and `finalize()` methods.  
**Rejected Because:** Calculation logic identical; persistence is orthogonal concern.

---

## 14. Multi-Tenant Inheritance NOT Implemented

### Decision
**Multi-tenant tax rate inheritance (global→tenant overrides) NOT implemented in MVP.**

### Rationale

**Problem:**
Should tenants inherit global tax rates with optional overrides?

**Decision:** Not in v1.0. Planned for Phase 2.

**Rationale:**
- Adds significant complexity (merge logic, override precedence)
- Not all deployments are multi-tenant SaaS
- Can be implemented in application layer if needed now

**Phase 2 Design:**
```php
// Future: Hierarchical lookup
$rate = $repository->findRate($taxCode, $date, TenantScope::GlobalFirst);
// 1. Check tenant-specific rate
// 2. Fall back to global rate
// 3. Throw exception if neither exists
```

---

## 15. EventStream Integration Optional

### Decision
**EventStream publishing via optional `TaxAuditPublisherInterface`, not built-in.**

### Rationale

**Problem:**
Should tax calculations be published to EventStream for GL posting audit trail?

**Decision:** Optional, not mandatory.

**Rationale:**
- Not all deployments use event sourcing
- Audit log table (INSERT-only) sufficient for most compliance needs
- EventStream adds complexity and dependencies

**When to Use EventStream:**
- Large enterprises with complex GL workflows
- Event-driven architectures
- Regulatory requirements for immutable event logs

**Implementation:**
```php
// Optional: Bind publisher
$this->app->bind(TaxAuditPublisherInterface::class, TaxEventStreamPublisher::class);

// In TaxCalculator (if bound):
$this->auditPublisher?->publishCalculationEvent($context, $breakdown);
```

---

## Summary of Key Principles

1. **Temporal Accuracy First:** Always require effective dates
2. **Immutability for Compliance:** No updates/deletes on audit log
3. **Framework Agnosticism:** Package defines interfaces, application implements
4. **Precision Over Performance:** BCMath for exact calculations
5. **Flexibility via Optional Dependencies:** Telemetry, audit logging, EventStream all optional
6. **Clear Separation of Concerns:** Package = logic, Application = state/persistence
7. **Real-World Tax Rules:** Partial exemptions, reverse charge, place-of-supply, economic nexus

---

**For Implementation Guidance:**
- [Getting Started](getting-started.md)
- [Integration Guide](integration-guide.md)
- [API Reference](api-reference.md)
