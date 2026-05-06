# MetricEngine Application Refactor Support Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expand `Nexus\MetricEngine` with deterministic catalog, serialization, dependency graph, batch evaluation, status, audit, fingerprint, rounding, period, and neutral banding support for Atomy-Q refactors while preserving Layer 1 purity.

**Architecture:** Keep `FormulaEvaluatorService::evaluate()` as the strict single-formula evaluator that throws package exceptions. Add pure PHP orchestration services around it: serializers build formulas, catalogs hold formulas, graphs order formula references, batch evaluation returns typed outcomes, and helper services provide audit traces, fingerprints, period comparison, rounding, and neutral banding. Laravel bindings and currency semantics remain outside `packages/MetricEngine`.

**Tech Stack:** PHP 8.3+, Composer PSR-4 autoloading, PHPUnit 11, framework-free Layer 1 package under `packages/MetricEngine`.

---

## Source Spec

- Design spec: `docs/superpowers/specs/2026-05-06-metric-engine-application-refactor-support-design.md`
- Existing package: `packages/MetricEngine`
- Verification command: `cd packages/MetricEngine && ./vendor/bin/phpunit`
- Composer validation: `cd packages/MetricEngine && composer validate --strict`

## File Structure

### Existing Files To Modify

- `packages/MetricEngine/src/Enums/AggregationType.php`
  - Add `BANDED_SCORE`.
- `packages/MetricEngine/src/Enums/RoundingMode.php`
  - Add supported rounding modes.
- `packages/MetricEngine/src/Services/NumericValueService.php`
  - Honor `PrecisionPolicy::$roundingMode`.
- `packages/MetricEngine/src/Services/WindowResolverService.php`
  - Use parsed `PeriodKey` comparisons instead of raw string comparison.
- `packages/MetricEngine/src/Services/FormulaEvaluatorService.php`
  - Resolve `FormulaReference` operands through dependency-provided inputs.
- `packages/MetricEngine/src/ValueObjects/FormulaDefinition.php`
  - Add optional unit and metadata while keeping current constructor compatibility through default values.
- `packages/MetricEngine/src/ValueObjects/MetricSeries.php`
  - Validate period keys with `PeriodComparatorService`.
- `packages/MetricEngine/src/ValueObjects/TimeWindow.php`
  - Validate explicit ranges with parsed period keys.
- `packages/MetricEngine/tests/Unit/Architecture/LayerBoundaryTest.php`
  - Extend forbidden strings to cover Laravel provider leakage and domain band labels.
- `docs/project/NEXUS_PACKAGES_REFERENCE.md`
  - Update `Nexus\MetricEngine` entry after public contract expansion.
- `packages/MetricEngine/README.md`
  - Add short usage examples for catalog, batch outcome statuses, and fingerprints.

### New Enums

- `packages/MetricEngine/src/Enums/MetricResultStatus.php`
  - `AVAILABLE`, `NO_DATA`, `NOT_AVAILABLE`, `ERROR`.
- `packages/MetricEngine/src/Enums/PeriodGranularity.php`
  - `DATE`, `MONTH`, `QUARTER`, `YEAR`.

### New Exceptions

- `packages/MetricEngine/src/Exceptions/DuplicateFormulaException.php`
  - Duplicate formula id in catalog.
- `packages/MetricEngine/src/Exceptions/FormulaDependencyException.php`
  - Missing/cyclic formula reference.
- `packages/MetricEngine/src/Exceptions/FormulaSerializationException.php`
  - Invalid serialized formula payload.

### New Value Objects

- `packages/MetricEngine/src/ValueObjects/FormulaCatalog.php`
  - Immutable keyed collection of formulas.
- `packages/MetricEngine/src/ValueObjects/FormulaReference.php`
  - Explicit formula dependency operand.
- `packages/MetricEngine/src/ValueObjects/FormulaGraph.php`
  - Deterministic dependency order and dependency map.
- `packages/MetricEngine/src/ValueObjects/MetricEvaluationBatchResult.php`
  - Ordered collection of outcomes keyed by formula id.
- `packages/MetricEngine/src/ValueObjects/MetricEvaluationOutcome.php`
  - Formula id, status, optional result, optional exception summary, optional audit trace.
- `packages/MetricEngine/src/ValueObjects/MetricAuditTrace.php`
  - Deterministic optional explanation payload.
- `packages/MetricEngine/src/ValueObjects/MetricEvaluationOptions.php`
  - Flags for audit and fingerprint generation.
- `packages/MetricEngine/src/ValueObjects/MetricRunFingerprint.php`
  - Stable hash value and algorithm.
- `packages/MetricEngine/src/ValueObjects/PeriodKey.php`
  - Parsed period key with granularity and sortable numeric key.
- `packages/MetricEngine/src/ValueObjects/BandDefinition.php`
  - Caller-supplied numeric threshold band.
- `packages/MetricEngine/src/ValueObjects/BandedScore.php`
  - Score plus matched band label/value.

### New Services

- `packages/MetricEngine/src/Services/FormulaDefinitionSerializerService.php`
  - Array to formula and formula to array.
- `packages/MetricEngine/src/Services/FormulaCatalogBuilderService.php`
  - Build catalog from formulas or arrays.
- `packages/MetricEngine/src/Services/FormulaGraphService.php`
  - Dependency extraction, cycle detection, topological ordering.
- `packages/MetricEngine/src/Services/MetricStatusInferenceService.php`
  - Exception to `MetricResultStatus` mapping.
- `packages/MetricEngine/src/Services/BatchFormulaEvaluatorService.php`
  - Catalog/list evaluation with outcomes.
- `packages/MetricEngine/src/Services/MetricRunFingerprintService.php`
  - Stable fingerprints for formulas and inputs.
- `packages/MetricEngine/src/Services/PeriodComparatorService.php`
  - Parse and compare period keys.
- `packages/MetricEngine/src/Services/BandedScoreService.php`
  - Neutral caller-defined score band matching.

### New Tests

- `packages/MetricEngine/tests/Unit/Services/RoundingModeTest.php`
- `packages/MetricEngine/tests/Unit/ValueObjects/PeriodKeyTest.php`
- `packages/MetricEngine/tests/Unit/Services/PeriodComparatorServiceTest.php`
- `packages/MetricEngine/tests/Unit/Services/FormulaDefinitionSerializerServiceTest.php`
- `packages/MetricEngine/tests/Unit/ValueObjects/FormulaCatalogTest.php`
- `packages/MetricEngine/tests/Unit/Services/FormulaCatalogBuilderServiceTest.php`
- `packages/MetricEngine/tests/Unit/Services/FormulaGraphServiceTest.php`
- `packages/MetricEngine/tests/Unit/Services/MetricStatusInferenceServiceTest.php`
- `packages/MetricEngine/tests/Unit/Services/BatchFormulaEvaluatorServiceTest.php`
- `packages/MetricEngine/tests/Unit/Services/MetricRunFingerprintServiceTest.php`
- `packages/MetricEngine/tests/Unit/Services/BandedScoreServiceTest.php`

---

## Task 1: Rounding Modes And Numeric Precision

**Files:**
- Modify: `packages/MetricEngine/src/Enums/RoundingMode.php`
- Modify: `packages/MetricEngine/src/Services/NumericValueService.php`
- Test: `packages/MetricEngine/tests/Unit/Services/RoundingModeTest.php`
- Existing test to keep green: `packages/MetricEngine/tests/Unit/Services/NumericValueServiceTest.php`

- [ ] **Step 1: Write failing tests for every rounding mode**

Create `packages/MetricEngine/tests/Unit/Services/RoundingModeTest.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\RoundingMode;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class RoundingModeTest extends TestCase
{
    private NumericValueService $service;

    protected function setUp(): void
    {
        $this->service = new NumericValueService();
    }

    public function test_half_up_rounds_midpoint_away_from_zero(): void
    {
        $this->assertSame(2.5, $this->service->round(2.45, new PrecisionPolicy(1, RoundingMode::HALF_UP)));
        $this->assertSame(-2.5, $this->service->round(-2.45, new PrecisionPolicy(1, RoundingMode::HALF_UP)));
    }

    public function test_half_down_rounds_midpoint_toward_zero(): void
    {
        $this->assertSame(2.4, $this->service->round(2.45, new PrecisionPolicy(1, RoundingMode::HALF_DOWN)));
        $this->assertSame(-2.4, $this->service->round(-2.45, new PrecisionPolicy(1, RoundingMode::HALF_DOWN)));
    }

    public function test_half_even_rounds_midpoint_to_even_digit(): void
    {
        $this->assertSame(2.4, $this->service->round(2.45, new PrecisionPolicy(1, RoundingMode::HALF_EVEN)));
        $this->assertSame(2.6, $this->service->round(2.55, new PrecisionPolicy(1, RoundingMode::HALF_EVEN)));
    }

    public function test_half_odd_rounds_midpoint_to_odd_digit(): void
    {
        $this->assertSame(2.5, $this->service->round(2.45, new PrecisionPolicy(1, RoundingMode::HALF_ODD)));
        $this->assertSame(2.5, $this->service->round(2.55, new PrecisionPolicy(1, RoundingMode::HALF_ODD)));
    }

    public function test_toward_zero_truncates_at_scale(): void
    {
        $this->assertSame(2.4, $this->service->round(2.49, new PrecisionPolicy(1, RoundingMode::TOWARD_ZERO)));
        $this->assertSame(-2.4, $this->service->round(-2.49, new PrecisionPolicy(1, RoundingMode::TOWARD_ZERO)));
    }

    public function test_away_from_zero_rounds_any_fraction_away_from_zero(): void
    {
        $this->assertSame(2.5, $this->service->round(2.41, new PrecisionPolicy(1, RoundingMode::AWAY_FROM_ZERO)));
        $this->assertSame(-2.5, $this->service->round(-2.41, new PrecisionPolicy(1, RoundingMode::AWAY_FROM_ZERO)));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/RoundingModeTest.php
```

Expected: failure because enum cases other than `HALF_UP` do not exist.

- [ ] **Step 3: Expand `RoundingMode` enum**

Replace `packages/MetricEngine/src/Enums/RoundingMode.php` with:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum RoundingMode: string
{
    case HALF_UP = 'half_up';
    case HALF_DOWN = 'half_down';
    case HALF_EVEN = 'half_even';
    case HALF_ODD = 'half_odd';
    case TOWARD_ZERO = 'toward_zero';
    case AWAY_FROM_ZERO = 'away_from_zero';
}
```

- [ ] **Step 4: Honor `PrecisionPolicy::$roundingMode`**

Replace `round()` in `packages/MetricEngine/src/Services/NumericValueService.php`:

```php
public function round(float $value, PrecisionPolicy $policy): float
{
    return match ($policy->roundingMode) {
        \Nexus\MetricEngine\Enums\RoundingMode::HALF_UP => round($value, $policy->scale, PHP_ROUND_HALF_UP),
        \Nexus\MetricEngine\Enums\RoundingMode::HALF_DOWN => round($value, $policy->scale, PHP_ROUND_HALF_DOWN),
        \Nexus\MetricEngine\Enums\RoundingMode::HALF_EVEN => round($value, $policy->scale, PHP_ROUND_HALF_EVEN),
        \Nexus\MetricEngine\Enums\RoundingMode::HALF_ODD => round($value, $policy->scale, PHP_ROUND_HALF_ODD),
        \Nexus\MetricEngine\Enums\RoundingMode::TOWARD_ZERO => $this->roundTowardZero($value, $policy->scale),
        \Nexus\MetricEngine\Enums\RoundingMode::AWAY_FROM_ZERO => $this->roundAwayFromZero($value, $policy->scale),
    };
}

private function roundTowardZero(float $value, int $scale): float
{
    $factor = 10 ** $scale;

    return ($value < 0 ? ceil($value * $factor) : floor($value * $factor)) / $factor;
}

private function roundAwayFromZero(float $value, int $scale): float
{
    $factor = 10 ** $scale;

    return ($value < 0 ? floor($value * $factor) : ceil($value * $factor)) / $factor;
}
```

- [ ] **Step 5: Run rounding and existing numeric tests**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/RoundingModeTest.php tests/Unit/Services/NumericValueServiceTest.php
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add packages/MetricEngine/src/Enums/RoundingMode.php \
    packages/MetricEngine/src/Services/NumericValueService.php \
    packages/MetricEngine/tests/Unit/Services/RoundingModeTest.php
git commit -m "Honor MetricEngine rounding modes"
```

---

## Task 2: Typed Period Keys And Window Comparison

**Files:**
- Create: `packages/MetricEngine/src/Enums/PeriodGranularity.php`
- Create: `packages/MetricEngine/src/ValueObjects/PeriodKey.php`
- Create: `packages/MetricEngine/src/Services/PeriodComparatorService.php`
- Modify: `packages/MetricEngine/src/ValueObjects/MetricSeries.php`
- Modify: `packages/MetricEngine/src/ValueObjects/TimeWindow.php`
- Modify: `packages/MetricEngine/src/Services/WindowResolverService.php`
- Test: `packages/MetricEngine/tests/Unit/ValueObjects/PeriodKeyTest.php`
- Test: `packages/MetricEngine/tests/Unit/Services/PeriodComparatorServiceTest.php`
- Existing tests to keep green: `packages/MetricEngine/tests/Unit/ValueObjects/MetricSeriesTest.php`, `packages/MetricEngine/tests/Unit/ValueObjects/TimeWindowTest.php`, `packages/MetricEngine/tests/Unit/Services/WindowResolverServiceTest.php`

- [ ] **Step 1: Write failing period key tests**

Create `packages/MetricEngine/tests/Unit/ValueObjects/PeriodKeyTest.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\ValueObjects;

use Nexus\MetricEngine\Enums\PeriodGranularity;
use Nexus\MetricEngine\Exceptions\InvalidWindowException;
use Nexus\MetricEngine\ValueObjects\PeriodKey;
use PHPUnit\Framework\TestCase;

class PeriodKeyTest extends TestCase
{
    public function test_parses_supported_period_shapes(): void
    {
        $this->assertSame(PeriodGranularity::DATE, PeriodKey::fromString('2026-05-06')->granularity);
        $this->assertSame(PeriodGranularity::MONTH, PeriodKey::fromString('2026-05')->granularity);
        $this->assertSame(PeriodGranularity::QUARTER, PeriodKey::fromString('2026-Q2')->granularity);
        $this->assertSame(PeriodGranularity::YEAR, PeriodKey::fromString('2026')->granularity);
    }

    public function test_rejects_invalid_period_shape(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Unsupported period key [2026-5].');

        PeriodKey::fromString('2026-5');
    }

    public function test_rejects_invalid_calendar_date(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Unsupported period key [2026-02-31].');

        PeriodKey::fromString('2026-02-31');
    }
}
```

- [ ] **Step 2: Write failing comparator tests**

Create `packages/MetricEngine/tests/Unit/Services/PeriodComparatorServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Exceptions\InvalidWindowException;
use Nexus\MetricEngine\Services\PeriodComparatorService;
use Nexus\MetricEngine\ValueObjects\MetricSeries;
use Nexus\MetricEngine\ValueObjects\TimeSeriesPoint;
use Nexus\MetricEngine\ValueObjects\TimeWindow;
use PHPUnit\Framework\TestCase;

class PeriodComparatorServiceTest extends TestCase
{
    private PeriodComparatorService $service;

    protected function setUp(): void
    {
        $this->service = new PeriodComparatorService();
    }

    public function test_compares_periods_by_parsed_order(): void
    {
        $this->assertTrue($this->service->lessThanOrEqual('2026-Q2', '2026-Q4'));
        $this->assertFalse($this->service->lessThanOrEqual('2026-Q4', '2026-Q2'));
    }

    public function test_rejects_mixed_granularity_in_series(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Metric series period keys must use one granularity.');

        new MetricSeries('sales', [
            new TimeSeriesPoint('2026-01', 10),
            new TimeSeriesPoint('2026-Q2', 20),
        ]);
    }

    public function test_rejects_mixed_granularity_window(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Explicit window periods must use one granularity.');

        TimeWindow::explicitRange('2026-01', '2026-Q2');
    }
}
```

- [ ] **Step 3: Run tests to verify they fail**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/ValueObjects/PeriodKeyTest.php tests/Unit/Services/PeriodComparatorServiceTest.php
```

Expected: failures because `PeriodGranularity`, `PeriodKey`, and `PeriodComparatorService` do not exist.

- [ ] **Step 4: Add `PeriodGranularity`**

Create `packages/MetricEngine/src/Enums/PeriodGranularity.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum PeriodGranularity: string
{
    case DATE = 'date';
    case MONTH = 'month';
    case QUARTER = 'quarter';
    case YEAR = 'year';
}
```

- [ ] **Step 5: Add `PeriodKey`**

Create `packages/MetricEngine/src/ValueObjects/PeriodKey.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Enums\PeriodGranularity;
use Nexus\MetricEngine\Exceptions\InvalidWindowException;

final readonly class PeriodKey
{
    private function __construct(
        public string $value,
        public PeriodGranularity $granularity,
        public int $sortKey
    ) {}

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            [$year, $month, $day] = array_map('intval', explode('-', $value));

            if (! checkdate($month, $day, $year)) {
                throw new InvalidWindowException("Unsupported period key [{$value}].");
            }

            return new self($value, PeriodGranularity::DATE, ($year * 10000) + ($month * 100) + $day);
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            [$year, $month] = array_map('intval', explode('-', $value));

            if ($month < 1 || $month > 12) {
                throw new InvalidWindowException("Unsupported period key [{$value}].");
            }

            return new self($value, PeriodGranularity::MONTH, ($year * 100) + $month);
        }

        if (preg_match('/^(\d{4})-Q([1-4])$/', $value, $matches) === 1) {
            $year = (int) $matches[1];
            $quarter = (int) $matches[2];

            return new self($value, PeriodGranularity::QUARTER, ($year * 10) + $quarter);
        }

        if (preg_match('/^\d{4}$/', $value) === 1) {
            return new self($value, PeriodGranularity::YEAR, (int) $value);
        }

        throw new InvalidWindowException("Unsupported period key [{$value}].");
    }
}
```

- [ ] **Step 6: Add `PeriodComparatorService`**

Create `packages/MetricEngine/src/Services/PeriodComparatorService.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Exceptions\InvalidWindowException;
use Nexus\MetricEngine\ValueObjects\PeriodKey;

class PeriodComparatorService
{
    public function compare(string $left, string $right): int
    {
        $leftKey = PeriodKey::fromString($left);
        $rightKey = PeriodKey::fromString($right);

        if ($leftKey->granularity !== $rightKey->granularity) {
            throw new InvalidWindowException('Period keys must use one granularity.');
        }

        return $leftKey->sortKey <=> $rightKey->sortKey;
    }

    public function lessThanOrEqual(string $left, string $right): bool
    {
        return $this->compare($left, $right) <= 0;
    }

    public function greaterThanOrEqual(string $left, string $right): bool
    {
        return $this->compare($left, $right) >= 0;
    }
}
```

- [ ] **Step 7: Wire parsed period validation into `MetricSeries`**

In `packages/MetricEngine/src/ValueObjects/MetricSeries.php`, import:

```php
use Nexus\MetricEngine\ValueObjects\PeriodKey;
```

Replace the period validation loop with:

```php
$previousPeriodKey = null;
$periodKeys = [];
$granularity = null;

foreach ($points as $point) {
    $parsedPeriod = PeriodKey::fromString($point->periodKey);

    if ($granularity !== null && $parsedPeriod->granularity !== $granularity) {
        throw new InvalidWindowException('Metric series period keys must use one granularity.');
    }

    if (in_array($point->periodKey, $periodKeys, true)) {
        throw new InvalidWindowException('Metric series period keys must be unique.');
    }

    if ($previousPeriodKey !== null && $parsedPeriod->sortKey < $previousPeriodKey->sortKey) {
        throw new InvalidWindowException('Metric series period keys must be sorted in ascending order.');
    }

    $periodKeys[] = $point->periodKey;
    $previousPeriodKey = $parsedPeriod;
    $granularity = $parsedPeriod->granularity;
}
```

- [ ] **Step 8: Wire parsed period validation into `TimeWindow`**

In `packages/MetricEngine/src/ValueObjects/TimeWindow.php`, import:

```php
use Nexus\MetricEngine\ValueObjects\PeriodKey;
```

Replace the explicit range ordering check with:

```php
$start = PeriodKey::fromString($startPeriod);
$end = PeriodKey::fromString($endPeriod);

if ($start->granularity !== $end->granularity) {
    throw new InvalidWindowException('Explicit window periods must use one granularity.');
}

if ($start->sortKey > $end->sortKey) {
    throw new InvalidWindowException('Explicit window start period must be before or equal to end period.');
}
```

- [ ] **Step 9: Wire parsed comparison into `WindowResolverService`**

In `packages/MetricEngine/src/Services/WindowResolverService.php`, add constructor and property:

```php
public function __construct(
    private readonly PeriodComparatorService $periodComparator = new PeriodComparatorService()
) {}
```

Replace explicit range filter:

```php
$filtered = array_values(array_filter(
    $series->points,
    fn ($point) => $this->periodComparator->greaterThanOrEqual($point->periodKey, $window->startPeriod)
        && $this->periodComparator->lessThanOrEqual($point->periodKey, $window->endPeriod)
));
```

- [ ] **Step 10: Run period/window tests**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/ValueObjects/PeriodKeyTest.php \
    tests/Unit/Services/PeriodComparatorServiceTest.php \
    tests/Unit/ValueObjects/MetricSeriesTest.php \
    tests/Unit/ValueObjects/TimeWindowTest.php \
    tests/Unit/Services/WindowResolverServiceTest.php
```

Expected: all tests pass.

- [ ] **Step 11: Commit**

```bash
git add packages/MetricEngine/src/Enums/PeriodGranularity.php \
    packages/MetricEngine/src/ValueObjects/PeriodKey.php \
    packages/MetricEngine/src/Services/PeriodComparatorService.php \
    packages/MetricEngine/src/ValueObjects/MetricSeries.php \
    packages/MetricEngine/src/ValueObjects/TimeWindow.php \
    packages/MetricEngine/src/Services/WindowResolverService.php \
    packages/MetricEngine/tests/Unit/ValueObjects/PeriodKeyTest.php \
    packages/MetricEngine/tests/Unit/Services/PeriodComparatorServiceTest.php \
    packages/MetricEngine/tests/Unit/ValueObjects/MetricSeriesTest.php \
    packages/MetricEngine/tests/Unit/ValueObjects/TimeWindowTest.php \
    packages/MetricEngine/tests/Unit/Services/WindowResolverServiceTest.php
git commit -m "Add typed MetricEngine period handling"
```

---

## Task 3: Formula Metadata, References, And Serialization

**Files:**
- Create: `packages/MetricEngine/src/ValueObjects/FormulaReference.php`
- Create: `packages/MetricEngine/src/Exceptions/FormulaSerializationException.php`
- Create: `packages/MetricEngine/src/Services/FormulaDefinitionSerializerService.php`
- Modify: `packages/MetricEngine/src/ValueObjects/FormulaDefinition.php`
- Test: `packages/MetricEngine/tests/Unit/Services/FormulaDefinitionSerializerServiceTest.php`

- [ ] **Step 1: Write failing serializer tests**

Create `packages/MetricEngine/tests/Unit/Services/FormulaDefinitionSerializerServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Enums\ComparisonType;
use Nexus\MetricEngine\Enums\RoundingMode;
use Nexus\MetricEngine\Exceptions\FormulaSerializationException;
use Nexus\MetricEngine\Services\FormulaDefinitionSerializerService;
use Nexus\MetricEngine\ValueObjects\ComparisonDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaReference;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use Nexus\MetricEngine\ValueObjects\TimeWindow;
use PHPUnit\Framework\TestCase;

class FormulaDefinitionSerializerServiceTest extends TestCase
{
    private FormulaDefinitionSerializerService $serializer;

    protected function setUp(): void
    {
        $this->serializer = new FormulaDefinitionSerializerService();
    }

    public function test_round_trips_formula_with_reference_window_comparison_unit_and_metadata(): void
    {
        $formula = new FormulaDefinition(
            identifier: 'metric.margin_ratio',
            operation: AggregationType::RATIO,
            operands: [
                new FormulaReference('metric.margin_delta'),
                'revenue',
            ],
            precisionPolicy: new PrecisionPolicy(4, RoundingMode::HALF_EVEN),
            window: TimeWindow::explicitRange('2026-01', '2026-03'),
            comparison: new ComparisonDefinition(ComparisonType::PREVIOUS_PERIOD),
            unit: 'ratio',
            metadata: ['display_group' => 'finance']
        );

        $array = $this->serializer->toArray($formula);
        $roundTripped = $this->serializer->fromArray($array);

        $this->assertSame('metric.margin_ratio', $roundTripped->identifier());
        $this->assertSame(AggregationType::RATIO, $roundTripped->operation());
        $this->assertSame('ratio', $roundTripped->unit());
        $this->assertSame(['display_group' => 'finance'], $roundTripped->metadata());
        $this->assertInstanceOf(FormulaReference::class, $roundTripped->operands()[0]);
        $this->assertSame('metric.margin_delta', $roundTripped->operands()[0]->identifier);
        $this->assertSame(RoundingMode::HALF_EVEN, $roundTripped->precisionPolicy()->roundingMode);
    }

    public function test_rejects_missing_identifier(): void
    {
        $this->expectException(FormulaSerializationException::class);
        $this->expectExceptionMessage('Serialized formula requires identifier.');

        $this->serializer->fromArray([
            'operation' => 'sum',
            'operands' => [1, 2],
            'precision' => ['scale' => 2, 'rounding_mode' => 'half_up'],
        ]);
    }

    public function test_rejects_invalid_operation(): void
    {
        $this->expectException(FormulaSerializationException::class);
        $this->expectExceptionMessage('Unsupported formula operation [unknown].');

        $this->serializer->fromArray([
            'identifier' => 'metric.bad',
            'operation' => 'unknown',
            'operands' => [1, 2],
            'precision' => ['scale' => 2, 'rounding_mode' => 'half_up'],
        ]);
    }
}
```

- [ ] **Step 2: Run serializer tests to verify they fail**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/FormulaDefinitionSerializerServiceTest.php
```

Expected: failure because serializer, reference, and exception do not exist.

- [ ] **Step 3: Add `FormulaReference`**

Create `packages/MetricEngine/src/ValueObjects/FormulaReference.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Exceptions\FormulaValidationException;

final readonly class FormulaReference
{
    public function __construct(
        public string $identifier
    ) {
        if (trim($identifier) === '') {
            throw new FormulaValidationException('Formula reference identifier is required.');
        }
    }
}
```

- [ ] **Step 4: Add serialization exception**

Create `packages/MetricEngine/src/Exceptions/FormulaSerializationException.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Exceptions;

class FormulaSerializationException extends MetricEngineException
{
    public function __construct(string $message)
    {
        parent::__construct('metric_engine.formula_serialization', $message);
    }
}
```

- [ ] **Step 5: Extend `FormulaDefinition`**

Modify constructor in `packages/MetricEngine/src/ValueObjects/FormulaDefinition.php`:

```php
/**
 * @param list<mixed> $operands
 * @param array<string, mixed> $metadata
 */
public function __construct(
    private string $identifier,
    private AggregationType $operation,
    private array $operands,
    private PrecisionPolicy $precisionPolicy,
    private ?TimeWindow $window = null,
    private ?ComparisonDefinition $comparison = null,
    private ?string $unit = null,
    private array $metadata = []
) {}
```

Add methods:

```php
public function unit(): ?string
{
    return $this->unit;
}

/** @return array<string, mixed> */
public function metadata(): array
{
    return $this->metadata;
}
```

- [ ] **Step 6: Add serializer service**

Create `packages/MetricEngine/src/Services/FormulaDefinitionSerializerService.php` with:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Enums\ComparisonType;
use Nexus\MetricEngine\Enums\RoundingMode;
use Nexus\MetricEngine\Exceptions\FormulaSerializationException;
use Nexus\MetricEngine\ValueObjects\ComparisonDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaReference;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use Nexus\MetricEngine\ValueObjects\TimeWindow;

class FormulaDefinitionSerializerService
{
    /** @return array<string, mixed> */
    public function toArray(FormulaDefinition $formula): array
    {
        return [
            'identifier' => $formula->identifier(),
            'operation' => $formula->operation()->value,
            'operands' => array_map(fn (mixed $operand): mixed => $this->serializeOperand($operand), $formula->operands()),
            'precision' => [
                'scale' => $formula->precisionPolicy()->scale,
                'rounding_mode' => $formula->precisionPolicy()->roundingMode->value,
            ],
            'window' => $this->serializeWindow($formula->window()),
            'comparison' => $this->serializeComparison($formula->comparison()),
            'unit' => $formula->unit(),
            'metadata' => $formula->metadata(),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function fromArray(array $payload): FormulaDefinition
    {
        $identifier = $this->requireString($payload, 'identifier', 'Serialized formula requires identifier.');
        $operationValue = $this->requireString($payload, 'operation', 'Serialized formula requires operation.');
        $operation = AggregationType::tryFrom($operationValue);

        if ($operation === null) {
            throw new FormulaSerializationException("Unsupported formula operation [{$operationValue}].");
        }

        if (! isset($payload['operands']) || ! is_array($payload['operands'])) {
            throw new FormulaSerializationException('Serialized formula requires operands array.');
        }

        return new FormulaDefinition(
            identifier: $identifier,
            operation: $operation,
            operands: array_map(fn (mixed $operand): mixed => $this->deserializeOperand($operand), array_values($payload['operands'])),
            precisionPolicy: $this->deserializePrecision($payload['precision'] ?? []),
            window: $this->deserializeWindow($payload['window'] ?? null),
            comparison: $this->deserializeComparison($payload['comparison'] ?? null),
            unit: isset($payload['unit']) && is_string($payload['unit']) ? $payload['unit'] : null,
            metadata: isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : []
        );
    }

    private function serializeOperand(mixed $operand): mixed
    {
        if ($operand instanceof FormulaReference) {
            return ['formula' => $operand->identifier];
        }

        if (is_array($operand)) {
            return array_map(fn (mixed $nested): mixed => $this->serializeOperand($nested), $operand);
        }

        return $operand;
    }

    private function deserializeOperand(mixed $operand): mixed
    {
        if (is_array($operand) && array_key_exists('formula', $operand)) {
            if (! is_string($operand['formula'])) {
                throw new FormulaSerializationException('Formula reference must contain a string identifier.');
            }

            return new FormulaReference($operand['formula']);
        }

        if (is_array($operand)) {
            return array_map(fn (mixed $nested): mixed => $this->deserializeOperand($nested), $operand);
        }

        return $operand;
    }

    /** @param array<string, mixed>|mixed $payload */
    private function deserializePrecision(mixed $payload): PrecisionPolicy
    {
        if (! is_array($payload)) {
            throw new FormulaSerializationException('Serialized formula precision must be an array.');
        }

        $scale = isset($payload['scale']) && is_int($payload['scale']) ? $payload['scale'] : 2;
        $roundingValue = isset($payload['rounding_mode']) && is_string($payload['rounding_mode'])
            ? $payload['rounding_mode']
            : RoundingMode::HALF_UP->value;

        $roundingMode = RoundingMode::tryFrom($roundingValue);

        if ($roundingMode === null) {
            throw new FormulaSerializationException("Unsupported rounding mode [{$roundingValue}].");
        }

        return new PrecisionPolicy($scale, $roundingMode);
    }

    /** @return array<string, mixed>|null */
    private function serializeWindow(?TimeWindow $window): ?array
    {
        if ($window === null) {
            return null;
        }

        return [
            'type' => $window->type->value,
            'size' => $window->size,
            'start_period' => $window->startPeriod,
            'end_period' => $window->endPeriod,
        ];
    }

    /** @param array<string, mixed>|mixed $payload */
    private function deserializeWindow(mixed $payload): ?TimeWindow
    {
        if ($payload === null) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['type']) || ! is_string($payload['type'])) {
            throw new FormulaSerializationException('Serialized window requires type.');
        }

        return match ($payload['type']) {
            'fixed_rolling' => TimeWindow::fixedRolling((int) ($payload['size'] ?? 0)),
            'explicit_range' => TimeWindow::explicitRange(
                (string) ($payload['start_period'] ?? ''),
                (string) ($payload['end_period'] ?? '')
            ),
            default => throw new FormulaSerializationException("Unsupported window type [{$payload['type']}]."),
        };
    }

    /** @return array<string, mixed>|null */
    private function serializeComparison(?ComparisonDefinition $comparison): ?array
    {
        if ($comparison === null) {
            return null;
        }

        return ['type' => $comparison->type->value];
    }

    /** @param array<string, mixed>|mixed $payload */
    private function deserializeComparison(mixed $payload): ?ComparisonDefinition
    {
        if ($payload === null) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['type']) || ! is_string($payload['type'])) {
            throw new FormulaSerializationException('Serialized comparison requires type.');
        }

        $type = ComparisonType::tryFrom($payload['type']);

        if ($type === null) {
            throw new FormulaSerializationException("Unsupported comparison type [{$payload['type']}].");
        }

        return new ComparisonDefinition($type);
    }

    /** @param array<string, mixed> $payload */
    private function requireString(array $payload, string $key, string $message): string
    {
        if (! isset($payload[$key]) || ! is_string($payload[$key]) || trim($payload[$key]) === '') {
            throw new FormulaSerializationException($message);
        }

        return $payload[$key];
    }
}
```

- [ ] **Step 7: Run serializer tests**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/FormulaDefinitionSerializerServiceTest.php tests/Unit/Services/FormulaEvaluatorServiceTest.php
```

Expected: all tests pass.

- [ ] **Step 8: Commit**

```bash
git add packages/MetricEngine/src/ValueObjects/FormulaReference.php \
    packages/MetricEngine/src/Exceptions/FormulaSerializationException.php \
    packages/MetricEngine/src/Services/FormulaDefinitionSerializerService.php \
    packages/MetricEngine/src/ValueObjects/FormulaDefinition.php \
    packages/MetricEngine/tests/Unit/Services/FormulaDefinitionSerializerServiceTest.php
git commit -m "Add MetricEngine formula serialization"
```

---

## Task 4: Formula Catalog

**Files:**
- Create: `packages/MetricEngine/src/Exceptions/DuplicateFormulaException.php`
- Create: `packages/MetricEngine/src/ValueObjects/FormulaCatalog.php`
- Create: `packages/MetricEngine/src/Services/FormulaCatalogBuilderService.php`
- Test: `packages/MetricEngine/tests/Unit/ValueObjects/FormulaCatalogTest.php`
- Test: `packages/MetricEngine/tests/Unit/Services/FormulaCatalogBuilderServiceTest.php`

- [ ] **Step 1: Write failing catalog tests**

Create `packages/MetricEngine/tests/Unit/ValueObjects/FormulaCatalogTest.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\ValueObjects;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Exceptions\DuplicateFormulaException;
use Nexus\MetricEngine\Exceptions\MissingInputException;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class FormulaCatalogTest extends TestCase
{
    public function test_catalog_exposes_formulas_by_id_and_order(): void
    {
        $first = new FormulaDefinition('metric.one', AggregationType::SUM, [1], PrecisionPolicy::default());
        $second = new FormulaDefinition('metric.two', AggregationType::SUM, [2], PrecisionPolicy::default());

        $catalog = new FormulaCatalog([$first, $second]);

        $this->assertSame($first, $catalog->get('metric.one'));
        $this->assertSame(['metric.one', 'metric.two'], array_keys($catalog->all()));
    }

    public function test_catalog_rejects_duplicate_ids(): void
    {
        $this->expectException(DuplicateFormulaException::class);
        $this->expectExceptionMessage('Formula [metric.one] is already registered.');

        new FormulaCatalog([
            new FormulaDefinition('metric.one', AggregationType::SUM, [1], PrecisionPolicy::default()),
            new FormulaDefinition('metric.one', AggregationType::SUM, [2], PrecisionPolicy::default()),
        ]);
    }

    public function test_get_missing_formula_fails_loudly(): void
    {
        $this->expectException(MissingInputException::class);
        $this->expectExceptionMessage('Required metric input [metric.missing] is missing.');

        (new FormulaCatalog([]))->get('metric.missing');
    }
}
```

- [ ] **Step 2: Write failing catalog builder tests**

Create `packages/MetricEngine/tests/Unit/Services/FormulaCatalogBuilderServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Services\FormulaCatalogBuilderService;
use Nexus\MetricEngine\Services\FormulaDefinitionSerializerService;
use PHPUnit\Framework\TestCase;

class FormulaCatalogBuilderServiceTest extends TestCase
{
    public function test_builds_catalog_from_serialized_formulas(): void
    {
        $builder = new FormulaCatalogBuilderService(new FormulaDefinitionSerializerService());

        $catalog = $builder->fromArrays([
            [
                'identifier' => 'metric.one',
                'operation' => 'sum',
                'operands' => [1, 2],
                'precision' => ['scale' => 2, 'rounding_mode' => 'half_up'],
            ],
        ]);

        $this->assertSame('metric.one', $catalog->get('metric.one')->identifier());
    }
}
```

- [ ] **Step 3: Run catalog tests to verify they fail**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/ValueObjects/FormulaCatalogTest.php tests/Unit/Services/FormulaCatalogBuilderServiceTest.php
```

Expected: failure because catalog classes do not exist.

- [ ] **Step 4: Add duplicate exception**

Create `packages/MetricEngine/src/Exceptions/DuplicateFormulaException.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Exceptions;

class DuplicateFormulaException extends MetricEngineException
{
    public function __construct(string $identifier)
    {
        parent::__construct('metric_engine.duplicate_formula', "Formula [{$identifier}] is already registered.");
    }
}
```

- [ ] **Step 5: Add immutable catalog**

Create `packages/MetricEngine/src/ValueObjects/FormulaCatalog.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Contracts\FormulaInterface;
use Nexus\MetricEngine\Exceptions\DuplicateFormulaException;
use Nexus\MetricEngine\Exceptions\MissingInputException;

final readonly class FormulaCatalog
{
    /** @var array<string, FormulaInterface> */
    private array $formulas;

    /** @param list<FormulaInterface> $formulas */
    public function __construct(array $formulas)
    {
        $indexed = [];

        foreach ($formulas as $formula) {
            $identifier = $formula->identifier();

            if (isset($indexed[$identifier])) {
                throw new DuplicateFormulaException($identifier);
            }

            $indexed[$identifier] = $formula;
        }

        $this->formulas = $indexed;
    }

    public function get(string $identifier): FormulaInterface
    {
        if (! isset($this->formulas[$identifier])) {
            throw new MissingInputException($identifier);
        }

        return $this->formulas[$identifier];
    }

    public function has(string $identifier): bool
    {
        return isset($this->formulas[$identifier]);
    }

    /** @return array<string, FormulaInterface> */
    public function all(): array
    {
        return $this->formulas;
    }
}
```

- [ ] **Step 6: Add catalog builder**

Create `packages/MetricEngine/src/Services/FormulaCatalogBuilderService.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Contracts\FormulaInterface;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;

class FormulaCatalogBuilderService
{
    public function __construct(
        private readonly FormulaDefinitionSerializerService $serializer = new FormulaDefinitionSerializerService()
    ) {}

    /** @param list<FormulaInterface> $formulas */
    public function fromFormulas(array $formulas): FormulaCatalog
    {
        return new FormulaCatalog($formulas);
    }

    /** @param list<array<string, mixed>> $payloads */
    public function fromArrays(array $payloads): FormulaCatalog
    {
        $formulas = array_map(
            fn (array $payload) => $this->serializer->fromArray($payload),
            $payloads
        );

        return new FormulaCatalog($formulas);
    }
}
```

- [ ] **Step 7: Run catalog tests**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/ValueObjects/FormulaCatalogTest.php tests/Unit/Services/FormulaCatalogBuilderServiceTest.php
```

Expected: all tests pass.

- [ ] **Step 8: Commit**

```bash
git add packages/MetricEngine/src/Exceptions/DuplicateFormulaException.php \
    packages/MetricEngine/src/ValueObjects/FormulaCatalog.php \
    packages/MetricEngine/src/Services/FormulaCatalogBuilderService.php \
    packages/MetricEngine/tests/Unit/ValueObjects/FormulaCatalogTest.php \
    packages/MetricEngine/tests/Unit/Services/FormulaCatalogBuilderServiceTest.php
git commit -m "Add MetricEngine formula catalog"
```

---

## Task 5: Formula Dependency Graph

**Files:**
- Create: `packages/MetricEngine/src/Exceptions/FormulaDependencyException.php`
- Create: `packages/MetricEngine/src/ValueObjects/FormulaGraph.php`
- Create: `packages/MetricEngine/src/Services/FormulaGraphService.php`
- Test: `packages/MetricEngine/tests/Unit/Services/FormulaGraphServiceTest.php`

- [ ] **Step 1: Write failing graph tests**

Create `packages/MetricEngine/tests/Unit/Services/FormulaGraphServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Exceptions\FormulaDependencyException;
use Nexus\MetricEngine\Services\FormulaGraphService;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaReference;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class FormulaGraphServiceTest extends TestCase
{
    private FormulaGraphService $service;

    protected function setUp(): void
    {
        $this->service = new FormulaGraphService();
    }

    public function test_orders_dependencies_before_dependents(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.ratio', AggregationType::RATIO, [new FormulaReference('metric.delta'), 'revenue'], PrecisionPolicy::default()),
            new FormulaDefinition('metric.delta', AggregationType::DELTA, ['revenue', 'cost'], PrecisionPolicy::default()),
        ]);

        $graph = $this->service->build($catalog);

        $this->assertSame(['metric.delta', 'metric.ratio'], $graph->orderedFormulaIds());
        $this->assertSame(['metric.delta'], $graph->dependenciesFor('metric.ratio'));
    }

    public function test_rejects_missing_formula_reference(): void
    {
        $this->expectException(FormulaDependencyException::class);
        $this->expectExceptionMessage('Formula [metric.ratio] references missing formula [metric.delta].');

        $this->service->build(new FormulaCatalog([
            new FormulaDefinition('metric.ratio', AggregationType::RATIO, [new FormulaReference('metric.delta'), 'revenue'], PrecisionPolicy::default()),
        ]));
    }

    public function test_rejects_circular_formula_reference(): void
    {
        $this->expectException(FormulaDependencyException::class);
        $this->expectExceptionMessage('Formula dependency cycle detected.');

        $this->service->build(new FormulaCatalog([
            new FormulaDefinition('metric.a', AggregationType::SUM, [new FormulaReference('metric.b')], PrecisionPolicy::default()),
            new FormulaDefinition('metric.b', AggregationType::SUM, [new FormulaReference('metric.a')], PrecisionPolicy::default()),
        ]));
    }
}
```

- [ ] **Step 2: Run graph tests to verify they fail**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/FormulaGraphServiceTest.php
```

Expected: failure because graph classes do not exist.

- [ ] **Step 3: Add dependency exception**

Create `packages/MetricEngine/src/Exceptions/FormulaDependencyException.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Exceptions;

class FormulaDependencyException extends MetricEngineException
{
    public function __construct(string $message)
    {
        parent::__construct('metric_engine.formula_dependency', $message);
    }
}
```

- [ ] **Step 4: Add `FormulaGraph`**

Create `packages/MetricEngine/src/ValueObjects/FormulaGraph.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

final readonly class FormulaGraph
{
    /**
     * @param list<string> $orderedFormulaIds
     * @param array<string, list<string>> $dependencies
     */
    public function __construct(
        private array $orderedFormulaIds,
        private array $dependencies
    ) {}

    /** @return list<string> */
    public function orderedFormulaIds(): array
    {
        return $this->orderedFormulaIds;
    }

    /** @return list<string> */
    public function dependenciesFor(string $identifier): array
    {
        return $this->dependencies[$identifier] ?? [];
    }
}
```

- [ ] **Step 5: Add graph service**

Create `packages/MetricEngine/src/Services/FormulaGraphService.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Contracts\FormulaInterface;
use Nexus\MetricEngine\Exceptions\FormulaDependencyException;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaGraph;
use Nexus\MetricEngine\ValueObjects\FormulaReference;

class FormulaGraphService
{
    public function build(FormulaCatalog $catalog): FormulaGraph
    {
        $dependencies = [];

        foreach ($catalog->all() as $identifier => $formula) {
            $dependencies[$identifier] = $this->extractDependencies($formula);

            foreach ($dependencies[$identifier] as $dependency) {
                if (! $catalog->has($dependency)) {
                    throw new FormulaDependencyException("Formula [{$identifier}] references missing formula [{$dependency}].");
                }
            }
        }

        return new FormulaGraph($this->topologicalSort($dependencies), $dependencies);
    }

    /** @return list<string> */
    private function extractDependencies(FormulaInterface $formula): array
    {
        $dependencies = [];
        $this->collectReferences($formula->operands(), $dependencies);

        return array_values(array_unique($dependencies));
    }

    /**
     * @param list<mixed> $operands
     * @param list<string> $dependencies
     */
    private function collectReferences(array $operands, array &$dependencies): void
    {
        foreach ($operands as $operand) {
            if ($operand instanceof FormulaReference) {
                $dependencies[] = $operand->identifier;
                continue;
            }

            if (is_array($operand)) {
                $this->collectReferences($operand, $dependencies);
            }
        }
    }

    /**
     * @param array<string, list<string>> $dependencies
     * @return list<string>
     */
    private function topologicalSort(array $dependencies): array
    {
        $ordered = [];
        $temporary = [];
        $permanent = [];

        foreach (array_keys($dependencies) as $identifier) {
            $this->visit($identifier, $dependencies, $ordered, $temporary, $permanent);
        }

        return $ordered;
    }

    /**
     * @param array<string, list<string>> $dependencies
     * @param list<string> $ordered
     * @param array<string, bool> $temporary
     * @param array<string, bool> $permanent
     */
    private function visit(
        string $identifier,
        array $dependencies,
        array &$ordered,
        array &$temporary,
        array &$permanent
    ): void {
        if (isset($permanent[$identifier])) {
            return;
        }

        if (isset($temporary[$identifier])) {
            throw new FormulaDependencyException('Formula dependency cycle detected.');
        }

        $temporary[$identifier] = true;

        foreach ($dependencies[$identifier] ?? [] as $dependency) {
            $this->visit($dependency, $dependencies, $ordered, $temporary, $permanent);
        }

        unset($temporary[$identifier]);
        $permanent[$identifier] = true;
        $ordered[] = $identifier;
    }
}
```

- [ ] **Step 6: Run graph tests**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/FormulaGraphServiceTest.php
```

Expected: all tests pass.

- [ ] **Step 7: Commit**

```bash
git add packages/MetricEngine/src/Exceptions/FormulaDependencyException.php \
    packages/MetricEngine/src/ValueObjects/FormulaGraph.php \
    packages/MetricEngine/src/Services/FormulaGraphService.php \
    packages/MetricEngine/tests/Unit/Services/FormulaGraphServiceTest.php
git commit -m "Add MetricEngine formula dependency graph"
```

---

## Task 6: Status Outcomes And Batch Evaluation

**Files:**
- Create: `packages/MetricEngine/src/Enums/MetricResultStatus.php`
- Create: `packages/MetricEngine/src/ValueObjects/MetricEvaluationOutcome.php`
- Create: `packages/MetricEngine/src/ValueObjects/MetricEvaluationBatchResult.php`
- Create: `packages/MetricEngine/src/Services/MetricStatusInferenceService.php`
- Create: `packages/MetricEngine/src/Services/BatchFormulaEvaluatorService.php`
- Modify: `packages/MetricEngine/src/Services/FormulaEvaluatorService.php`
- Test: `packages/MetricEngine/tests/Unit/Services/MetricStatusInferenceServiceTest.php`
- Test: `packages/MetricEngine/tests/Unit/Services/BatchFormulaEvaluatorServiceTest.php`

- [ ] **Step 1: Write failing status inference tests**

Create `packages/MetricEngine/tests/Unit/Services/MetricStatusInferenceServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\MetricResultStatus;
use Nexus\MetricEngine\Exceptions\DivideByZeroMetricException;
use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\Exceptions\InsufficientDataException;
use Nexus\MetricEngine\Exceptions\InvalidNumericValueException;
use Nexus\MetricEngine\Exceptions\InvalidWindowException;
use Nexus\MetricEngine\Exceptions\MissingInputException;
use Nexus\MetricEngine\Exceptions\TypeMismatchException;
use Nexus\MetricEngine\Services\MetricStatusInferenceService;
use PHPUnit\Framework\TestCase;

class MetricStatusInferenceServiceTest extends TestCase
{
    private MetricStatusInferenceService $service;

    protected function setUp(): void
    {
        $this->service = new MetricStatusInferenceService();
    }

    public function test_maps_known_exceptions_to_statuses(): void
    {
        $this->assertSame(MetricResultStatus::NO_DATA, $this->service->infer(new InsufficientDataException('empty')));
        $this->assertSame(MetricResultStatus::NOT_AVAILABLE, $this->service->infer(new MissingInputException('revenue')));
        $this->assertSame(MetricResultStatus::NOT_AVAILABLE, $this->service->infer(new DivideByZeroMetricException()));
        $this->assertSame(MetricResultStatus::ERROR, $this->service->infer(new InvalidNumericValueException('bad')));
        $this->assertSame(MetricResultStatus::ERROR, $this->service->infer(new InvalidWindowException('bad window')));
        $this->assertSame(MetricResultStatus::ERROR, $this->service->infer(new TypeMismatchException('bad type')));
        $this->assertSame(MetricResultStatus::ERROR, $this->service->infer(new FormulaValidationException('bad formula')));
        $this->assertSame(MetricResultStatus::ERROR, $this->service->infer(new \RuntimeException('unexpected')));
    }
}
```

- [ ] **Step 2: Write failing batch evaluator tests**

Create `packages/MetricEngine/tests/Unit/Services/BatchFormulaEvaluatorServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Enums\MetricResultStatus;
use Nexus\MetricEngine\Services\BatchFormulaEvaluatorService;
use Nexus\MetricEngine\Services\FormulaEvaluatorService;
use Nexus\MetricEngine\Services\FormulaGraphService;
use Nexus\MetricEngine\Services\MetricStatusInferenceService;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\Services\ScalarMetricCalculatorService;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaReference;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class BatchFormulaEvaluatorServiceTest extends TestCase
{
    private BatchFormulaEvaluatorService $service;

    protected function setUp(): void
    {
        $numeric = new NumericValueService();

        $this->service = new BatchFormulaEvaluatorService(
            new FormulaEvaluatorService(new ScalarMetricCalculatorService($numeric)),
            new FormulaGraphService(),
            new MetricStatusInferenceService()
        );
    }

    public function test_evaluates_independent_formulas(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.total', AggregationType::SUM, ['a', 'b'], PrecisionPolicy::default()),
        ]);

        $result = $this->service->evaluate($catalog, [
            'a' => new MetricInput('a', 10),
            'b' => new MetricInput('b', 5),
        ]);

        $outcome = $result->get('metric.total');
        $this->assertSame(MetricResultStatus::AVAILABLE, $outcome->status);
        $this->assertSame(15.0, $outcome->result?->value());
    }

    public function test_evaluates_formula_dependencies(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.delta', AggregationType::DELTA, ['revenue', 'cost'], PrecisionPolicy::default()),
            new FormulaDefinition('metric.ratio', AggregationType::RATIO, [new FormulaReference('metric.delta'), 'revenue'], PrecisionPolicy::default()),
        ]);

        $result = $this->service->evaluate($catalog, [
            'revenue' => new MetricInput('revenue', 100),
            'cost' => new MetricInput('cost', 60),
        ]);

        $this->assertSame(40.0, $result->get('metric.delta')->result?->value());
        $this->assertSame(0.4, $result->get('metric.ratio')->result?->value());
    }

    public function test_missing_input_becomes_not_available(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.total', AggregationType::SUM, ['missing'], PrecisionPolicy::default()),
        ]);

        $result = $this->service->evaluate($catalog, []);

        $this->assertSame(MetricResultStatus::NOT_AVAILABLE, $result->get('metric.total')->status);
        $this->assertNull($result->get('metric.total')->result);
    }

    public function test_dependency_failure_marks_dependent_not_available(): void
    {
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.delta', AggregationType::DELTA, ['missing', 'cost'], PrecisionPolicy::default()),
            new FormulaDefinition('metric.ratio', AggregationType::RATIO, [new FormulaReference('metric.delta'), 'cost'], PrecisionPolicy::default()),
        ]);

        $result = $this->service->evaluate($catalog, [
            'cost' => new MetricInput('cost', 60),
        ]);

        $this->assertSame(MetricResultStatus::NOT_AVAILABLE, $result->get('metric.delta')->status);
        $this->assertSame(MetricResultStatus::NOT_AVAILABLE, $result->get('metric.ratio')->status);
    }
}
```

- [ ] **Step 3: Run batch tests to verify they fail**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/MetricStatusInferenceServiceTest.php tests/Unit/Services/BatchFormulaEvaluatorServiceTest.php
```

Expected: failure because statuses, outcomes, and batch evaluator do not exist.

- [ ] **Step 4: Add status enum**

Create `packages/MetricEngine/src/Enums/MetricResultStatus.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum MetricResultStatus: string
{
    case AVAILABLE = 'available';
    case NO_DATA = 'no_data';
    case NOT_AVAILABLE = 'not_available';
    case ERROR = 'error';
}
```

- [ ] **Step 5: Add outcome and batch result value objects**

Create `packages/MetricEngine/src/ValueObjects/MetricEvaluationOutcome.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Enums\MetricResultStatus;

final readonly class MetricEvaluationOutcome
{
    public function __construct(
        public string $formulaIdentifier,
        public MetricResultStatus $status,
        public ?MetricResult $result = null,
        public ?string $reasonCode = null,
        public ?string $message = null,
        public ?MetricAuditTrace $auditTrace = null
    ) {}

    public static function available(MetricResult $result, ?MetricAuditTrace $auditTrace = null): self
    {
        return new self($result->formulaIdentifier(), MetricResultStatus::AVAILABLE, $result, null, null, $auditTrace);
    }

    public static function unavailable(string $formulaIdentifier, MetricResultStatus $status, \Throwable $error): self
    {
        return new self(
            formulaIdentifier: $formulaIdentifier,
            status: $status,
            result: null,
            reasonCode: $error instanceof \Nexus\MetricEngine\Exceptions\MetricEngineException ? $error->errorCode() : 'unexpected_error',
            message: $error->getMessage()
        );
    }

    public static function dependencyUnavailable(string $formulaIdentifier, string $dependencyIdentifier): self
    {
        return new self(
            formulaIdentifier: $formulaIdentifier,
            status: MetricResultStatus::NOT_AVAILABLE,
            result: null,
            reasonCode: 'dependency_not_available',
            message: "Formula [{$formulaIdentifier}] depends on unavailable formula [{$dependencyIdentifier}]."
        );
    }
}
```

Create `packages/MetricEngine/src/ValueObjects/MetricEvaluationBatchResult.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Exceptions\MissingInputException;

final readonly class MetricEvaluationBatchResult
{
    /** @param array<string, MetricEvaluationOutcome> $outcomes */
    public function __construct(
        private array $outcomes
    ) {}

    public function get(string $formulaIdentifier): MetricEvaluationOutcome
    {
        if (! isset($this->outcomes[$formulaIdentifier])) {
            throw new MissingInputException($formulaIdentifier);
        }

        return $this->outcomes[$formulaIdentifier];
    }

    /** @return array<string, MetricEvaluationOutcome> */
    public function all(): array
    {
        return $this->outcomes;
    }
}
```

- [ ] **Step 6: Add status inference service**

Create `packages/MetricEngine/src/Services/MetricStatusInferenceService.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Enums\MetricResultStatus;
use Nexus\MetricEngine\Exceptions\DivideByZeroMetricException;
use Nexus\MetricEngine\Exceptions\InsufficientDataException;
use Nexus\MetricEngine\Exceptions\MissingInputException;

class MetricStatusInferenceService
{
    public function infer(\Throwable $error): MetricResultStatus
    {
        return match (true) {
            $error instanceof InsufficientDataException => MetricResultStatus::NO_DATA,
            $error instanceof MissingInputException => MetricResultStatus::NOT_AVAILABLE,
            $error instanceof DivideByZeroMetricException => MetricResultStatus::NOT_AVAILABLE,
            default => MetricResultStatus::ERROR,
        };
    }
}
```

- [ ] **Step 7: Add reference resolution in `FormulaEvaluatorService`**

In `packages/MetricEngine/src/Services/FormulaEvaluatorService.php`, import:

```php
use Nexus\MetricEngine\ValueObjects\FormulaReference;
```

In `resolveOperand()`, add this before the string operand block:

```php
if ($operand instanceof FormulaReference) {
    if (! isset($inputs[$operand->identifier])) {
        throw new MissingInputException($operand->identifier);
    }

    $input = $inputs[$operand->identifier];

    if ($input instanceof MetricSeries) {
        return $input;
    }

    return $input->value;
}
```

- [ ] **Step 8: Add batch evaluator**

Create `packages/MetricEngine/src/Services/BatchFormulaEvaluatorService.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Enums\MetricResultStatus;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\MetricEvaluationBatchResult;
use Nexus\MetricEngine\ValueObjects\MetricEvaluationOutcome;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\MetricSeries;

class BatchFormulaEvaluatorService
{
    public function __construct(
        private readonly FormulaEvaluatorService $formulaEvaluator,
        private readonly FormulaGraphService $graphService = new FormulaGraphService(),
        private readonly MetricStatusInferenceService $statusInference = new MetricStatusInferenceService()
    ) {}

    /**
     * @param array<string, MetricInput|MetricSeries> $inputs
     */
    public function evaluate(FormulaCatalog $catalog, array $inputs): MetricEvaluationBatchResult
    {
        $graph = $this->graphService->build($catalog);
        $outcomes = [];
        $runtimeInputs = $inputs;

        foreach ($graph->orderedFormulaIds() as $formulaIdentifier) {
            $unavailableDependency = $this->firstUnavailableDependency($graph->dependenciesFor($formulaIdentifier), $outcomes);

            if ($unavailableDependency !== null) {
                $outcomes[$formulaIdentifier] = MetricEvaluationOutcome::dependencyUnavailable($formulaIdentifier, $unavailableDependency);
                continue;
            }

            $formula = $catalog->get($formulaIdentifier);

            try {
                $result = $this->formulaEvaluator->evaluate($formula, $runtimeInputs);
                $outcomes[$formulaIdentifier] = MetricEvaluationOutcome::available($result);

                if (! is_int($result->value()) && ! is_float($result->value()) && ! is_string($result->value())) {
                    continue;
                }

                $runtimeInputs[$formulaIdentifier] = new MetricInput($formulaIdentifier, $result->value(), $result->unit());
            } catch (\Throwable $error) {
                $outcomes[$formulaIdentifier] = MetricEvaluationOutcome::unavailable(
                    $formulaIdentifier,
                    $this->statusInference->infer($error),
                    $error
                );
            }
        }

        return new MetricEvaluationBatchResult($outcomes);
    }

    /**
     * @param list<string> $dependencies
     * @param array<string, MetricEvaluationOutcome> $outcomes
     */
    private function firstUnavailableDependency(array $dependencies, array $outcomes): ?string
    {
        foreach ($dependencies as $dependency) {
            if (! isset($outcomes[$dependency]) || $outcomes[$dependency]->status !== MetricResultStatus::AVAILABLE) {
                return $dependency;
            }
        }

        return null;
    }
}
```

- [ ] **Step 9: Run batch tests**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/MetricStatusInferenceServiceTest.php tests/Unit/Services/BatchFormulaEvaluatorServiceTest.php
```

Expected: all tests pass.

- [ ] **Step 10: Commit**

```bash
git add packages/MetricEngine/src/Enums/MetricResultStatus.php \
    packages/MetricEngine/src/ValueObjects/MetricEvaluationOutcome.php \
    packages/MetricEngine/src/ValueObjects/MetricEvaluationBatchResult.php \
    packages/MetricEngine/src/Services/MetricStatusInferenceService.php \
    packages/MetricEngine/src/Services/BatchFormulaEvaluatorService.php \
    packages/MetricEngine/src/Services/FormulaEvaluatorService.php \
    packages/MetricEngine/tests/Unit/Services/MetricStatusInferenceServiceTest.php \
    packages/MetricEngine/tests/Unit/Services/BatchFormulaEvaluatorServiceTest.php
git commit -m "Add MetricEngine batch evaluation outcomes"
```

---

## Task 7: Audit Traces And Evaluation Options

**Files:**
- Create: `packages/MetricEngine/src/ValueObjects/MetricAuditTrace.php`
- Create: `packages/MetricEngine/src/ValueObjects/MetricEvaluationOptions.php`
- Modify: `packages/MetricEngine/src/ValueObjects/MetricEvaluationOutcome.php`
- Modify: `packages/MetricEngine/src/Services/BatchFormulaEvaluatorService.php`
- Test: `packages/MetricEngine/tests/Unit/Services/BatchFormulaEvaluatorServiceTest.php`

- [ ] **Step 1: Add failing audit trace tests to `BatchFormulaEvaluatorServiceTest`**

Append to `packages/MetricEngine/tests/Unit/Services/BatchFormulaEvaluatorServiceTest.php`:

```php
public function test_batch_evaluation_can_include_audit_trace(): void
{
    $catalog = new \Nexus\MetricEngine\ValueObjects\FormulaCatalog([
        new \Nexus\MetricEngine\ValueObjects\FormulaDefinition(
            'metric.total',
            \Nexus\MetricEngine\Enums\AggregationType::SUM,
            ['a', 'b'],
            \Nexus\MetricEngine\ValueObjects\PrecisionPolicy::default()
        ),
    ]);

    $result = $this->service->evaluate($catalog, [
        'a' => new \Nexus\MetricEngine\ValueObjects\MetricInput('a', 10),
        'b' => new \Nexus\MetricEngine\ValueObjects\MetricInput('b', 5),
    ], \Nexus\MetricEngine\ValueObjects\MetricEvaluationOptions::withAuditTrace());

    $trace = $result->get('metric.total')->auditTrace;

    $this->assertNotNull($trace);
    $this->assertSame('metric.total', $trace->formulaIdentifier);
    $this->assertSame('sum', $trace->operation);
    $this->assertSame(['a', 'b'], $trace->operands);
    $this->assertSame(15.0, $trace->resultValue);
    $this->assertSame('available', $trace->status);
}
```

- [ ] **Step 2: Run audit test to verify it fails**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/BatchFormulaEvaluatorServiceTest.php --filter audit
```

Expected: failure because options/trace do not exist and batch evaluator accepts only two arguments.

- [ ] **Step 3: Add evaluation options**

Create `packages/MetricEngine/src/ValueObjects/MetricEvaluationOptions.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

final readonly class MetricEvaluationOptions
{
    public function __construct(
        public bool $includeAuditTrace = false
    ) {}

    public static function default(): self
    {
        return new self();
    }

    public static function withAuditTrace(): self
    {
        return new self(includeAuditTrace: true);
    }
}
```

- [ ] **Step 4: Add audit trace value object**

Create `packages/MetricEngine/src/ValueObjects/MetricAuditTrace.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

final readonly class MetricAuditTrace
{
    /**
     * @param list<mixed> $operands
     * @param array<string, mixed> $inputs
     * @param array<string, mixed> $dependencyResults
     * @param list<array<string, mixed>> $excludedValues
     */
    public function __construct(
        public string $formulaIdentifier,
        public string $operation,
        public array $operands,
        public array $inputs,
        public array $dependencyResults,
        public array $excludedValues,
        public mixed $resultValue,
        public string $status,
        public ?string $reasonCode = null,
        public ?string $message = null
    ) {}
}
```

- [ ] **Step 5: Update batch evaluator signature and trace creation**

Change `BatchFormulaEvaluatorService::evaluate()` signature:

```php
public function evaluate(
    FormulaCatalog $catalog,
    array $inputs,
    MetricEvaluationOptions $options = new MetricEvaluationOptions()
): MetricEvaluationBatchResult {
```

When creating an available outcome, pass trace:

```php
$outcomes[$formulaIdentifier] = MetricEvaluationOutcome::available(
    $result,
    $options->includeAuditTrace ? new MetricAuditTrace(
        formulaIdentifier: $formulaIdentifier,
        operation: $formula->operation()->value,
        operands: $formula->operands(),
        inputs: array_keys($inputs),
        dependencyResults: $this->dependencyResults($graph->dependenciesFor($formulaIdentifier), $outcomes),
        excludedValues: [],
        resultValue: $result->value(),
        status: MetricResultStatus::AVAILABLE->value
    ) : null
);
```

Add helper:

```php
/**
 * @param list<string> $dependencies
 * @param array<string, MetricEvaluationOutcome> $outcomes
 * @return array<string, mixed>
 */
private function dependencyResults(array $dependencies, array $outcomes): array
{
    $results = [];

    foreach ($dependencies as $dependency) {
        if (isset($outcomes[$dependency]) && $outcomes[$dependency]->result !== null) {
            $results[$dependency] = $outcomes[$dependency]->result->value();
        }
    }

    return $results;
}
```

For failed outcomes, construct trace after `MetricEvaluationOutcome::unavailable()` or add a factory accepting trace. Use the same fields with `resultValue: null`, `status: $status->value`, and error reason/message.

- [ ] **Step 6: Run batch tests**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/BatchFormulaEvaluatorServiceTest.php
```

Expected: all tests pass.

- [ ] **Step 7: Commit**

```bash
git add packages/MetricEngine/src/ValueObjects/MetricAuditTrace.php \
    packages/MetricEngine/src/ValueObjects/MetricEvaluationOptions.php \
    packages/MetricEngine/src/ValueObjects/MetricEvaluationOutcome.php \
    packages/MetricEngine/src/Services/BatchFormulaEvaluatorService.php \
    packages/MetricEngine/tests/Unit/Services/BatchFormulaEvaluatorServiceTest.php
git commit -m "Add MetricEngine audit traces"
```

---

## Task 8: Metric Run Fingerprints

**Files:**
- Create: `packages/MetricEngine/src/ValueObjects/MetricRunFingerprint.php`
- Create: `packages/MetricEngine/src/Services/MetricRunFingerprintService.php`
- Test: `packages/MetricEngine/tests/Unit/Services/MetricRunFingerprintServiceTest.php`

- [ ] **Step 1: Write failing fingerprint tests**

Create `packages/MetricEngine/tests/Unit/Services/MetricRunFingerprintServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Services\FormulaDefinitionSerializerService;
use Nexus\MetricEngine\Services\MetricRunFingerprintService;
use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class MetricRunFingerprintServiceTest extends TestCase
{
    public function test_fingerprint_is_stable_for_equivalent_inputs(): void
    {
        $service = new MetricRunFingerprintService(new FormulaDefinitionSerializerService());
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.total', AggregationType::SUM, ['a', 'b'], PrecisionPolicy::default()),
        ]);

        $first = $service->fingerprint($catalog, [
            'a' => new MetricInput('a', 10),
            'b' => new MetricInput('b', 5),
        ]);

        $second = $service->fingerprint($catalog, [
            'b' => new MetricInput('b', 5),
            'a' => new MetricInput('a', 10),
        ]);

        $this->assertSame('sha256', $first->algorithm);
        $this->assertSame($first->hash, $second->hash);
    }

    public function test_fingerprint_changes_when_input_changes(): void
    {
        $service = new MetricRunFingerprintService(new FormulaDefinitionSerializerService());
        $catalog = new FormulaCatalog([
            new FormulaDefinition('metric.total', AggregationType::SUM, ['a', 'b'], PrecisionPolicy::default()),
        ]);

        $first = $service->fingerprint($catalog, ['a' => new MetricInput('a', 10)]);
        $second = $service->fingerprint($catalog, ['a' => new MetricInput('a', 11)]);

        $this->assertNotSame($first->hash, $second->hash);
    }
}
```

- [ ] **Step 2: Run fingerprint tests to verify they fail**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/MetricRunFingerprintServiceTest.php
```

Expected: failure because fingerprint classes do not exist.

- [ ] **Step 3: Add fingerprint value object**

Create `packages/MetricEngine/src/ValueObjects/MetricRunFingerprint.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

final readonly class MetricRunFingerprint
{
    public function __construct(
        public string $algorithm,
        public string $hash
    ) {}
}
```

- [ ] **Step 4: Add fingerprint service**

Create `packages/MetricEngine/src/Services/MetricRunFingerprintService.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\MetricRunFingerprint;
use Nexus\MetricEngine\ValueObjects\MetricSeries;

class MetricRunFingerprintService
{
    public function __construct(
        private readonly FormulaDefinitionSerializerService $serializer = new FormulaDefinitionSerializerService()
    ) {}

    /**
     * @param array<string, MetricInput|MetricSeries> $inputs
     * @param array<string, mixed> $metadata
     */
    public function fingerprint(FormulaCatalog $catalog, array $inputs, array $metadata = []): MetricRunFingerprint
    {
        $payload = [
            'formulas' => array_map(
                fn ($formula) => $this->serializer->toArray($formula),
                $catalog->all()
            ),
            'inputs' => $this->normalizeInputs($inputs),
            'metadata' => $metadata,
        ];

        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);

        return new MetricRunFingerprint('sha256', hash('sha256', $json));
    }

    /**
     * @param array<string, MetricInput|MetricSeries> $inputs
     * @return array<string, mixed>
     */
    private function normalizeInputs(array $inputs): array
    {
        ksort($inputs);
        $normalized = [];

        foreach ($inputs as $key => $input) {
            if ($input instanceof MetricInput) {
                $normalized[$key] = [
                    'name' => $input->name,
                    'value' => $input->value,
                    'unit' => $input->unit,
                ];
                continue;
            }

            $normalized[$key] = [
                'name' => $input->name,
                'unit' => $input->unit,
                'points' => array_map(
                    static fn ($point) => [
                        'period_key' => $point->periodKey,
                        'value' => $point->value,
                        'metadata' => $point->metadata,
                    ],
                    $input->points
                ),
            ];
        }

        return $normalized;
    }
}
```

- [ ] **Step 5: Run fingerprint tests**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/MetricRunFingerprintServiceTest.php
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add packages/MetricEngine/src/ValueObjects/MetricRunFingerprint.php \
    packages/MetricEngine/src/Services/MetricRunFingerprintService.php \
    packages/MetricEngine/tests/Unit/Services/MetricRunFingerprintServiceTest.php
git commit -m "Add MetricEngine run fingerprints"
```

---

## Task 9: Neutral Banded Score Helpers

**Files:**
- Modify: `packages/MetricEngine/src/Enums/AggregationType.php`
- Create: `packages/MetricEngine/src/ValueObjects/BandDefinition.php`
- Create: `packages/MetricEngine/src/ValueObjects/BandedScore.php`
- Create: `packages/MetricEngine/src/Services/BandedScoreService.php`
- Test: `packages/MetricEngine/tests/Unit/Services/BandedScoreServiceTest.php`
- Modify: `packages/MetricEngine/tests/Unit/Architecture/LayerBoundaryTest.php`

- [ ] **Step 1: Write failing banded score tests**

Create `packages/MetricEngine/tests/Unit/Services/BandedScoreServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\Services\BandedScoreService;
use Nexus\MetricEngine\ValueObjects\BandDefinition;
use PHPUnit\Framework\TestCase;

class BandedScoreServiceTest extends TestCase
{
    private BandedScoreService $service;

    protected function setUp(): void
    {
        $this->service = new BandedScoreService();
    }

    public function test_matches_caller_supplied_band(): void
    {
        $score = $this->service->score(82, [
            new BandDefinition('low', 0, 49.99),
            new BandDefinition('medium', 50, 79.99),
            new BandDefinition('high', 80, 100),
        ]);

        $this->assertSame(82.0, $score->value);
        $this->assertSame('high', $score->bandLabel);
    }

    public function test_rejects_score_without_matching_band(): void
    {
        $this->expectException(FormulaValidationException::class);
        $this->expectExceptionMessage('Score [120] does not match any supplied band.');

        $this->service->score(120, [
            new BandDefinition('low', 0, 100),
        ]);
    }
}
```

- [ ] **Step 2: Run banded tests to verify they fail**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/BandedScoreServiceTest.php
```

Expected: failure because band classes do not exist.

- [ ] **Step 3: Add `BANDED_SCORE` aggregation**

Add to `packages/MetricEngine/src/Enums/AggregationType.php`:

```php
case BANDED_SCORE = 'banded_score';
```

- [ ] **Step 4: Add band value objects**

Create `packages/MetricEngine/src/ValueObjects/BandDefinition.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Exceptions\FormulaValidationException;

final readonly class BandDefinition
{
    public function __construct(
        public string $label,
        public int|float|string $minimum,
        public int|float|string $maximum
    ) {
        if (trim($label) === '') {
            throw new FormulaValidationException('Band label is required.');
        }
    }
}
```

Create `packages/MetricEngine/src/ValueObjects/BandedScore.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

final readonly class BandedScore
{
    public function __construct(
        public float $value,
        public string $bandLabel
    ) {}
}
```

- [ ] **Step 5: Add banded score service**

Create `packages/MetricEngine/src/Services/BandedScoreService.php`:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\ValueObjects\BandDefinition;
use Nexus\MetricEngine\ValueObjects\BandedScore;

class BandedScoreService
{
    public function __construct(
        private readonly NumericValueService $numericValueService = new NumericValueService()
    ) {}

    /** @param list<BandDefinition> $bands */
    public function score(int|float|string $value, array $bands): BandedScore
    {
        $score = $this->numericValueService->normalize($value);

        foreach ($bands as $band) {
            $minimum = $this->numericValueService->normalize($band->minimum);
            $maximum = $this->numericValueService->normalize($band->maximum);

            if ($minimum > $maximum) {
                throw new FormulaValidationException("Band [{$band->label}] minimum must be less than or equal to maximum.");
            }

            if ($score >= $minimum && $score <= $maximum) {
                return new BandedScore($score, $band->label);
            }
        }

        throw new FormulaValidationException("Score [{$value}] does not match any supplied band.");
    }
}
```

- [ ] **Step 6: Strengthen architecture boundary test**

Add these forbidden strings in `packages/MetricEngine/tests/Unit/Architecture/LayerBoundaryTest.php`:

```php
'ServiceProvider',
'Illuminate\\',
'Symfony\\',
'healthy',
'risk',
'preferred vendor',
'vendor quality',
```

Keep existing forbidden values and avoid duplicates.

- [ ] **Step 7: Run banding and architecture tests**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/BandedScoreServiceTest.php tests/Unit/Architecture/LayerBoundaryTest.php
```

Expected: all tests pass.

- [ ] **Step 8: Commit**

```bash
git add packages/MetricEngine/src/Enums/AggregationType.php \
    packages/MetricEngine/src/ValueObjects/BandDefinition.php \
    packages/MetricEngine/src/ValueObjects/BandedScore.php \
    packages/MetricEngine/src/Services/BandedScoreService.php \
    packages/MetricEngine/tests/Unit/Services/BandedScoreServiceTest.php \
    packages/MetricEngine/tests/Unit/Architecture/LayerBoundaryTest.php
git commit -m "Add neutral MetricEngine banded scores"
```

---

## Task 10: Documentation, Package Reference, And Full Verification

**Files:**
- Modify: `docs/project/NEXUS_PACKAGES_REFERENCE.md`
- Modify: `packages/MetricEngine/README.md`
- Modify: `packages/MetricEngine/composer.json` if new keywords are missing
- Test: full package test suite

- [ ] **Step 1: Update package reference**

In `docs/project/NEXUS_PACKAGES_REFERENCE.md`, update the `Nexus\MetricEngine` row to mention:

```markdown
Prepared scalar/time-series input evaluation, formula catalogs, array-backed formula definitions, dependency graph evaluation, batch outcomes, strict numeric operand validation, ordered typed period series, window resolution, comparison results, audit traces, fingerprints, neutral banded scores, and typed deterministic metric results.
```

Keep the boundary text explicit:

```markdown
Not Laravel bindings; keep framework integration in adapters or applications. Not currency semantics; use finance/accounting packages for money rules.
```

- [ ] **Step 2: Update README with batch usage**

Add this section to `packages/MetricEngine/README.md`:

````markdown
## Batch Evaluation

Use `FormulaCatalog` and `BatchFormulaEvaluatorService` when an application needs status-aware metric runs.

```php
$catalog = new FormulaCatalog([
    new FormulaDefinition('metric.delta', AggregationType::DELTA, ['revenue', 'cost'], PrecisionPolicy::default()),
    new FormulaDefinition('metric.ratio', AggregationType::RATIO, [new FormulaReference('metric.delta'), 'revenue'], PrecisionPolicy::default()),
]);

$batch = $batchEvaluator->evaluate($catalog, [
    'revenue' => new MetricInput('revenue', 100),
    'cost' => new MetricInput('cost', 60),
]);

$batch->get('metric.ratio')->status; // MetricResultStatus::AVAILABLE
```
````

- [ ] **Step 3: Update README with boundary note**

Add:

```markdown
## Boundaries

MetricEngine is framework-agnostic. Laravel service providers belong in Laravel adapters or applications.

MetricEngine supports neutral units and precision policies, but currency-specific rules and money value objects belong in finance or accounting packages.
```

- [ ] **Step 4: Run full verification**

Run:

```bash
cd packages/MetricEngine
composer validate --strict
./vendor/bin/phpunit
```

Expected:

- `./composer.json is valid`
- PHPUnit exits `0`
- no architecture boundary failure

- [ ] **Step 5: Commit docs and verification updates**

```bash
git add docs/project/NEXUS_PACKAGES_REFERENCE.md \
    packages/MetricEngine/README.md \
    packages/MetricEngine/composer.json
git commit -m "Document MetricEngine refactor support"
```

---

## Implementation Order

Execute tasks in this order:

1. Task 1: Rounding Modes And Numeric Precision
2. Task 2: Typed Period Keys And Window Comparison
3. Task 3: Formula Metadata, References, And Serialization
4. Task 4: Formula Catalog
5. Task 5: Formula Dependency Graph
6. Task 6: Status Outcomes And Batch Evaluation
7. Task 7: Audit Traces And Evaluation Options
8. Task 8: Metric Run Fingerprints
9. Task 9: Neutral Banded Score Helpers
10. Task 10: Documentation, Package Reference, And Full Verification

## Final Verification Checklist

- [ ] `cd packages/MetricEngine && composer validate --strict`
- [ ] `cd packages/MetricEngine && ./vendor/bin/phpunit`
- [ ] `git diff --check`
- [ ] Confirm `packages/MetricEngine/src` contains no Laravel/Symfony imports.
- [ ] Confirm no Laravel service provider was added to `packages/MetricEngine`.
- [ ] Confirm no domain band labels were hard-coded in package source.
- [ ] Confirm no cache storage, persistence, HTTP, ORM, or framework dependencies were added.
- [ ] Confirm the split repo source files remain rooted in `packages/MetricEngine`.
