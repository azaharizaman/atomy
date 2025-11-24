# API Reference: Assets

**Package:** `Nexus\Assets`  
**Version:** 1.0.0

This document provides comprehensive API documentation for all public interfaces, services, enums, value objects, events, and exceptions in the Nexus\Assets package.

---

## Table of Contents

1. [Contracts (Interfaces)](#contracts-interfaces)
2. [Services](#services)
3. [Enums](#enums)
4. [Value Objects](#value-objects)
5. [Events](#events)
6. [Exceptions](#exceptions)
7. [Depreciation Engines](#depreciation-engines)

---

## Contracts (Interfaces)

### AssetManagerInterface

**Namespace:** `Nexus\Assets\Contracts\AssetManagerInterface`

Main orchestrator for asset management operations with tier-aware feature detection.

#### Methods

```php
/**
 * Create a new fixed asset
 *
 * @param array $data Asset data (name, cost, salvage_value, acquisition_date, etc.)
 * @return AssetInterface Created asset
 * @throws InvalidAssetDataException If validation fails
 * @throws UnsupportedDepreciationMethodException If depreciation method not supported for current tier
 */
public function createAsset(array $data): AssetInterface;

/**
 * Update existing asset
 *
 * @param string $id Asset ID
 * @param array $data Updated data
 * @return AssetInterface Updated asset
 * @throws AssetNotFoundException If asset not found
 */
public function updateAsset(string $id, array $data): AssetInterface;

/**
 * Dispose of an asset
 *
 * @param string $id Asset ID
 * @param DisposalMethod $method Disposal method
 * @param \DateTimeImmutable $disposalDate Date of disposal
 * @param float|null $proceeds Proceeds from disposal (if any)
 * @param string|null $notes Disposal notes
 * @return array Disposal details (gain_loss, final_nbv, etc.)
 * @throws DisposalNotAllowedException If asset cannot be disposed (status validation)
 */
public function disposeAsset(
    string $id,
    DisposalMethod $method,
    \DateTimeImmutable $disposalDate,
    ?float $proceeds = null,
    ?string $notes = null
): array;

/**
 * Record depreciation for a specific period
 *
 * @param string $id Asset ID
 * @param \DateTimeImmutable $periodStart Period start date
 * @param \DateTimeImmutable $periodEnd Period end date
 * @param float|null $unitsConsumed Units consumed (for UOP method only)
 * @return DepreciationRecordInterface Created depreciation record
 * @throws FullyDepreciatedAssetException If asset already fully depreciated
 */
public function recordDepreciation(
    string $id,
    \DateTimeImmutable $periodStart,
    \DateTimeImmutable $periodEnd,
    ?float $unitsConsumed = null
): DepreciationRecordInterface;

/**
 * Get asset by ID
 *
 * @param string $id Asset ID
 * @return AssetInterface
 * @throws AssetNotFoundException
 */
public function getAsset(string $id): AssetInterface;

/**
 * Add warranty information (Tier 2+)
 *
 * @param string $assetId Asset ID
 * @param string $provider Warranty provider
 * @param \DateTimeImmutable $startDate Warranty start date
 * @param \DateTimeImmutable $expiryDate Warranty expiry date
 * @param string $coverageType Type of coverage
 * @return self Fluent interface
 */
public function withWarranty(
    string $assetId,
    string $provider,
    \DateTimeImmutable $startDate,
    \DateTimeImmutable $expiryDate,
    string $coverageType
): self;

/**
 * Record maintenance (Tier 2+)
 *
 * @param string $assetId Asset ID
 * @param MaintenanceType $type Type of maintenance
 * @param string $description Description
 * @param float $cost Cost
 * @param \DateTimeImmutable $performedDate Date performed
 * @return self Fluent interface
 */
public function withMaintenance(
    string $assetId,
    MaintenanceType $type,
    string $description,
    float $cost,
    \DateTimeImmutable $performedDate
): self;
```

---

### AssetRepositoryInterface

**Namespace:** `Nexus\Assets\Contracts\AssetRepositoryInterface`

Data persistence contract. Consuming application implements using Eloquent/Doctrine/etc.

#### Methods

```php
public function create(array $data): AssetInterface;
public function update(string $id, array $data): AssetInterface;
public function findById(string $id): AssetInterface;
public function findByAssetTag(string $tag): ?AssetInterface;
public function findAll(array $filters = []): array;
public function getNextSequence(): int;
public function getMaintenanceRecords(string $assetId): array;
```

---

### MaintenanceAnalyzerInterface (Tier 2+)

**Namespace:** `Nexus\Assets\Contracts\MaintenanceAnalyzerInterface`

Total Cost of Ownership and predictive maintenance analysis.

#### Methods

```php
/**
 * Calculate Total Cost of Ownership
 *
 * @param string $assetId Asset ID
 * @param int $projectedYears Years to project
 * @return array TCO breakdown (acquisition_cost, maintenance_cost, projected_maintenance, total)
 */
public function calculateTCO(string $assetId, int $projectedYears): array;

/**
 * Analyze maintenance pattern
 *
 * @param string $assetId Asset ID
 * @return array Pattern analysis (total_maintenance, avg_cost, preventive_ratio, corrective_ratio)
 */
public function analyzeMaintenancePattern(string $assetId): array;

/**
 * Predict next maintenance date
 *
 * @param string $assetId Asset ID
 * @return array Prediction (predicted_date, average_interval_days, last_maintenance_date)
 */
public function predictNextMaintenance(string $assetId): array;
```

---

### AssetVerifierInterface (Tier 3)

**Namespace:** `Nexus\Assets\Contracts\AssetVerifierInterface`

Physical asset audit workflow.

#### Methods

```php
/**
 * Initiate physical audit
 *
 * @param array $criteria Audit criteria (location_ids, category_ids, scheduled_date)
 * @return PhysicalAuditLogInterface Created audit log
 */
public function initiatePhysicalAudit(array $criteria): PhysicalAuditLogInterface;

/**
 * Record physical verification
 *
 * @param string $auditId Audit ID
 * @param string $assetTag Asset tag
 * @param string $condition Asset condition
 * @param string $actualLocation Actual location found
 * @param string|null $notes Notes
 * @return PhysicalAuditVerificationInterface Verification record
 */
public function recordPhysicalVerification(
    string $auditId,
    string $assetTag,
    string $condition,
    string $actualLocation,
    ?string $notes = null
): PhysicalAuditVerificationInterface;

/**
 * Complete physical audit
 *
 * @param string $auditId Audit ID
 * @return array Results (verified_count, missing_count, extra_count, accuracy_rate)
 * @throws PhysicalAuditException If accuracy below threshold
 */
public function completePhysicalAudit(string $auditId): array;
```

---

### DepreciationEngineInterface

**Namespace:** `Nexus\Assets\Contracts\DepreciationEngineInterface`

Depreciation calculation contract. Implemented by three engines.

#### Methods

```php
/**
 * Calculate depreciation for a period
 *
 * @param AssetInterface $asset Asset to depreciate
 * @param \DateTimeImmutable $periodStart Period start
 * @param \DateTimeImmutable $periodEnd Period end
 * @param float|null $unitsConsumed Units consumed (UOP only)
 * @return float Depreciation amount
 * @throws NegativeBookValueException If calculation would result in negative NBV
 */
public function calculateDepreciation(
    AssetInterface $asset,
    \DateTimeImmutable $periodStart,
    \DateTimeImmutable $periodEnd,
    ?float $unitsConsumed = null
): float;

/**
 * Get accumulated depreciation
 *
 * @param AssetInterface $asset
 * @return float Total accumulated depreciation
 */
public function getAccumulatedDepreciation(AssetInterface $asset): float;

/**
 * Get net book value
 *
 * @param AssetInterface $asset
 * @return float Current net book value (cost - accumulated depreciation)
 */
public function getNetBookValue(AssetInterface $asset): float;
```

---

## Services

### AssetManager

**Namespace:** `Nexus\Assets\Services\AssetManager`

Implements `AssetManagerInterface`. See interface documentation above.

---

### DepreciationScheduler

**Namespace:** `Nexus\Assets\Services\DepreciationScheduler`

Batch depreciation processing engine.

#### Methods

```php
/**
 * Run monthly depreciation for multiple assets
 *
 * @param \DateTimeImmutable $periodStart Period start
 * @param \DateTimeImmutable $periodEnd Period end
 * @param array $filters Filters (category_ids, location_ids, ids)
 * @return array Summary (processed_count, skipped_count, total_depreciation)
 */
public function runMonthlyDepreciation(
    \DateTimeImmutable $periodStart,
    \DateTimeImmutable $periodEnd,
    array $filters = []
): array;
```

---

## Enums

### AssetStatus

**Namespace:** `Nexus\Assets\Enums\AssetStatus`

**Cases:**
- `ACTIVE` - Asset in use
- `INACTIVE` - Asset not in use (but not disposed)
- `UNDER_MAINTENANCE` - Asset being serviced
- `DISPOSED` - Asset disposed

**Methods:**

```php
/**
 * Check if asset can be depreciated in this status
 */
public function canDepreciate(): bool;

/**
 * Get allowed status transitions
 *
 * @return array<AssetStatus>
 */
public function getAllowedTransitions(): array;
```

---

### DepreciationMethod

**Namespace:** `Nexus\Assets\Enums\DepreciationMethod`

**Cases:**
- `STRAIGHT_LINE` - Tier 1 (Basic)
- `DOUBLE_DECLINING_BALANCE` - Tier 2 (Advanced)
- `UNITS_OF_PRODUCTION` - Tier 3 (Enterprise)

**Methods:**

```php
/**
 * Get required tier for this method
 *
 * @return string 'basic'|'advanced'|'enterprise'
 */
public function getRequiredTier(): string;

/**
 * Check if method requires unit tracking
 */
public function requiresUnitTracking(): bool;
```

---

### DisposalMethod

**Namespace:** `Nexus\Assets\Enums\DisposalMethod`

**Cases:**
- `SALE`
- `SCRAP`
- `DONATION`
- `TRADE_IN`

**Methods:**

```php
/**
 * Check if disposal method has proceeds
 */
public function hasProceeds(): bool;

/**
 * Get GL impact type
 *
 * @return string 'gain_loss'|'write_off'|'donation'|'trade'
 */
public function getGLImpact(): string;
```

---

### MaintenanceType

**Namespace:** `Nexus\Assets\Enums\MaintenanceType`

**Cases:**
- `PREVENTIVE`
- `CORRECTIVE`
- `EMERGENCY`
- `ROUTINE`

**Methods:**

```php
/**
 * Get priority level
 *
 * @return int 1 (highest) to 4 (lowest)
 */
public function getPriorityLevel(): int;

/**
 * Check if maintenance is planned
 */
public function isPlanned(): bool;
```

---

## Value Objects

### AssetTag

**Namespace:** `Nexus\Assets\ValueObjects\AssetTag`

Immutable asset tag identifier.

**Static Constructors:**

```php
/**
 * Generate tag from sequence (Tier 1)
 *
 * @param int $sequence Sequence number
 * @param string $prefix Tag prefix (default: 'AST')
 * @return self
 */
public static function fromSequence(int $sequence, string $prefix = 'AST'): self;

/**
 * Parse tag from string (Tier 3)
 *
 * @param string $tag Asset tag string
 * @return self
 * @throws InvalidAssetDataException If format invalid
 */
public static function fromString(string $tag): self;
```

---

### DepreciationSchedule

**Namespace:** `Nexus\Assets\ValueObjects\DepreciationSchedule`

Immutable depreciation schedule details.

**Constructor:**

```php
public function __construct(
    public readonly \DateTimeImmutable $periodStart,
    public readonly \DateTimeImmutable $periodEnd,
    public readonly float $depreciationAmount,
    public readonly float $netBookValue
) {}
```

---

### AssetCustody

**Namespace:** `Nexus\Assets\ValueObjects\AssetCustody`

Tier-aware asset location/custody information.

**Methods:**

```php
/**
 * Get location (string for Tier 1, object for Tier 3)
 *
 * @return string|object
 */
public function getLocation(): string|object;
```

---

## Events

All events implement `DomainEventInterface` from Nexus\EventStream (if used) or are simple DTOs.

### AssetAcquiredEvent

**Namespace:** `Nexus\Assets\Events\AssetAcquiredEvent`

**Severity:** HIGH

**Properties:**
- `string $assetId`
- `string $assetTag`
- `float $cost`
- `\DateTimeImmutable $acquisitionDate`

---

### DepreciationRecordedEvent

**Namespace:** `Nexus\Assets\Events\DepreciationRecordedEvent`

**Severity:** MEDIUM

**Properties:**
- `string $assetId`
- `float $depreciationAmount`
- `float $netBookValueChange`
- `\DateTimeImmutable $periodStart`
- `\DateTimeImmutable $periodEnd`

---

### AssetDisposedEvent

**Namespace:** `Nexus\Assets\Events\AssetDisposedEvent`

**Severity:** CRITICAL

**Properties:**
- `string $assetId`
- `string $assetTag`
- `float $originalCost`
- `float $accumulatedDepreciation`
- `float $proceeds`
- `float $gainLoss`
- `\DateTimeImmutable $disposalDate`
- `bool $shouldPostToGL` (Tier 3)

---

### AssetDepreciatedEvent (Batch)

**Namespace:** `Nexus\Assets\Events\AssetDepreciatedEvent`

**Severity:** MEDIUM

**Properties:**
- `int $processedCount`
- `float $totalDepreciation`
- `\DateTimeImmutable $periodStart`
- `\DateTimeImmutable $periodEnd`

---

### PhysicalAuditFailedEvent (Tier 3)

**Namespace:** `Nexus\Assets\Events\PhysicalAuditFailedEvent`

**Severity:** CRITICAL

**Properties:**
- `string $auditId`
- `float $accuracyRate`
- `int $missingCount`
- `int $extraCount`

---

## Exceptions

All exceptions extend `\Exception` and provide context-specific factory methods.

### AssetNotFoundException

```php
public static function withId(string $id): self;
public static function withTag(string $tag): self;
```

### InvalidAssetDataException

```php
public static function costMustBePositive(float $cost): self;
public static function salvageExceedsCost(float $salvage, float $cost): self;
public static function usefulLifeInvalid(int $months): self;
```

### FullyDepreciatedAssetException

```php
public static function forAsset(string $assetId, string $assetTag): self;
```

### DisposalNotAllowedException

```php
public static function invalidStatus(string $assetId, AssetStatus $status): self;
```

### UnsupportedDepreciationMethodException

```php
public static function requiresTier(DepreciationMethod $method, string $requiredTier, string $currentTier): self;
```

### DuplicateAssetTagException

```php
public static function tag(string $tag): self;
```

### InvalidAssetStatusException

```php
public static function invalidTransition(AssetStatus $from, AssetStatus $to): self;
```

### NegativeBookValueException

```php
public static function forAsset(string $assetId, float $proposedDepreciation, float $currentNBV): self;
```

### PhysicalAuditException (Tier 3)

```php
public static function accuracyBelowThreshold(float $actual, float $required): self;
```

---

## Depreciation Engines

### StraightLineDepreciation

**File:** `src/Core/Engines/StraightLineDepreciation.php`

**Formula:** `(Cost - Salvage Value) / Useful Life`

**Features:**
- Daily proration (GAAP-compliant)
- Optional full-month convention
- Tier 1 (Basic)

---

### DoubleDecliningBalanceDepreciation

**File:** `src/Core/Engines/DoubleDecliningBalanceDepreciation.php`

**Formula:** `Rate × Beginning Book Value` where `Rate = 2 / Useful Life`

**Features:**
- Accelerated depreciation
- Auto-switches to straight-line when optimal
- Tier 2 (Advanced)

---

### UnitsOfProductionDepreciation

**File:** `src/Core/Engines/UnitsOfProductionDepreciation.php`

**Formula:** `(Cost - Salvage) × (Units Consumed / Total Expected Units)`

**Features:**
- Activity-based depreciation
- Integrates with Nexus\Uom for unit conversions
- Tier 3 (Enterprise)

---

**For working examples, see [`docs/examples/`](examples/).**

**For integration patterns, see [`docs/integration-guide.md`](integration-guide.md).**
