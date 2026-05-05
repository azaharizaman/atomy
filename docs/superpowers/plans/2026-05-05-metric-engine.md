# MetricEngine Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `Nexus\MetricEngine` as a new Layer 1 package for deterministic scalar and time-series metric calculation.

**Architecture:** Create a pure PHP package under `packages/MetricEngine` with typed value objects, contracts, calculation services, and package-specific exceptions. Keep all domain meaning in callers: the engine accepts prepared inputs and neutral formula definitions, evaluates v1 primitives, and returns deterministic typed results.

**Tech Stack:** PHP 8.3+, Composer PSR-4 autoloading, PHPUnit, no Laravel/Symfony/framework dependencies.

---

## Source Spec

- Design spec: `docs/superpowers/specs/2026-05-05-metric-engine-design.md`

## File Structure

- Create: `packages/MetricEngine/composer.json` for package metadata, PHP requirement, PHPUnit dev dependency, and PSR-4 autoloading.
- Create: `packages/MetricEngine/phpunit.xml` for framework-free unit tests.
- Create: `packages/MetricEngine/src/Contracts/FormulaInterface.php` to expose formula identifier, operation, operands, precision, and optional window/comparison data.
- Create: `packages/MetricEngine/src/Contracts/FormulaEvaluatorInterface.php` to evaluate a formula against prepared inputs.
- Create: `packages/MetricEngine/src/Contracts/WindowResolverInterface.php` to resolve fixed and explicit windows for time-series inputs.
- Create: `packages/MetricEngine/src/Contracts/MetricResultInterface.php` to expose deterministic result data.
- Create: `packages/MetricEngine/src/Enums/AggregationType.php` for scalar and time-series primitive names.
- Create: `packages/MetricEngine/src/Enums/ComparisonType.php` for previous-period comparison.
- Create: `packages/MetricEngine/src/Enums/InputMode.php` for scalar versus time-series result metadata.
- Create: `packages/MetricEngine/src/Enums/RoundingMode.php` for the v1 rounding policy.
- Create: `packages/MetricEngine/src/Enums/ValueType.php` for result value shape.
- Create: `packages/MetricEngine/src/Enums/WindowType.php` for fixed rolling and explicit range windows.
- Create: `packages/MetricEngine/src/Exceptions/MetricEngineException.php` as the package base exception with stable error code.
- Create: `packages/MetricEngine/src/Exceptions/DivideByZeroMetricException.php`.
- Create: `packages/MetricEngine/src/Exceptions/FormulaValidationException.php`.
- Create: `packages/MetricEngine/src/Exceptions/InsufficientDataException.php`.
- Create: `packages/MetricEngine/src/Exceptions/InvalidNumericValueException.php`.
- Create: `packages/MetricEngine/src/Exceptions/InvalidWindowException.php`.
- Create: `packages/MetricEngine/src/Exceptions/MissingInputException.php`.
- Create: `packages/MetricEngine/src/Exceptions/TypeMismatchException.php`.
- Create: `packages/MetricEngine/src/ValueObjects/ComparisonDefinition.php`.
- Create: `packages/MetricEngine/src/ValueObjects/ComparisonResult.php`.
- Create: `packages/MetricEngine/src/ValueObjects/FormulaDefinition.php`.
- Create: `packages/MetricEngine/src/ValueObjects/MetricInput.php`.
- Create: `packages/MetricEngine/src/ValueObjects/MetricResult.php`.
- Create: `packages/MetricEngine/src/ValueObjects/MetricSeries.php`.
- Create: `packages/MetricEngine/src/ValueObjects/PrecisionPolicy.php`.
- Create: `packages/MetricEngine/src/ValueObjects/TimeSeriesPoint.php`.
- Create: `packages/MetricEngine/src/ValueObjects/TimeWindow.php`.
- Create: `packages/MetricEngine/src/Services/NumericValueService.php` to validate, normalize, and round numeric values.
- Create: `packages/MetricEngine/src/Services/ScalarMetricCalculatorService.php` for v1 scalar primitives.
- Create: `packages/MetricEngine/src/Services/WindowResolverService.php` for fixed rolling and explicit windows.
- Create: `packages/MetricEngine/src/Services/ComparisonService.php` for previous-period comparison.
- Create: `packages/MetricEngine/src/Services/TimeSeriesMetricCalculatorService.php` for v1 time-series primitives.
- Create: `packages/MetricEngine/src/Services/FormulaEvaluatorService.php` to evaluate object-based formulas and nested primitive expressions.
- Create: `packages/MetricEngine/tests/Unit/ValueObjects/*Test.php` for value object invariants.
- Create: `packages/MetricEngine/tests/Unit/Services/*Test.php` for primitive, formula, window, and comparison behavior.
- Modify: `docs/project/NEXUS_PACKAGES_REFERENCE.md` to register `Nexus\MetricEngine`.

## Task 1: Package Skeleton And Contracts

**Files:**
- Create: `packages/MetricEngine/composer.json`
- Create: `packages/MetricEngine/phpunit.xml`
- Create: `packages/MetricEngine/src/Contracts/FormulaInterface.php`
- Create: `packages/MetricEngine/src/Contracts/FormulaEvaluatorInterface.php`
- Create: `packages/MetricEngine/src/Contracts/WindowResolverInterface.php`
- Create: `packages/MetricEngine/src/Contracts/MetricResultInterface.php`
- Create: `packages/MetricEngine/src/Enums/AggregationType.php`
- Create: `packages/MetricEngine/src/Enums/ComparisonType.php`
- Create: `packages/MetricEngine/src/Enums/InputMode.php`
- Create: `packages/MetricEngine/src/Enums/RoundingMode.php`
- Create: `packages/MetricEngine/src/Enums/ValueType.php`
- Create: `packages/MetricEngine/src/Enums/WindowType.php`

- [ ] **Step 1: Create package metadata**

Write `packages/MetricEngine/composer.json`:

```json
{
    "name": "azaharizaman/nexus-metric-engine",
    "description": "Deterministic Layer 1 metric calculation engine for Nexus packages.",
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Nexus Architecture Team",
            "email": "architecture@nexus.dev"
        }
    ],
    "require": {
        "php": "^8.3"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "Nexus\\MetricEngine\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Nexus\\MetricEngine\\Tests\\": "tests/"
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

- [ ] **Step 2: Create PHPUnit config**

Write `packages/MetricEngine/phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/11.0/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         failOnRisky="true"
         failOnWarning="true"
         beStrictAboutOutputDuringTests="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
    <php>
        <ini name="display_errors" value="1"/>
        <ini name="error_reporting" value="-1"/>
        <ini name="memory_limit" value="512M"/>
    </php>
</phpunit>
```

- [ ] **Step 3: Add enum definitions**

Create the enum files with these cases:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum AggregationType: string
{
    case SUM = 'sum';
    case AVG = 'avg';
    case MIN = 'min';
    case MAX = 'max';
    case COUNT = 'count';
    case RATIO = 'ratio';
    case DELTA = 'delta';
    case ABSOLUTE_DELTA = 'absolute_delta';
    case PCT_CHANGE = 'pct_change';
    case WEIGHTED_AVG = 'weighted_avg';
    case WEIGHTED_SCORE = 'weighted_score';
    case ROLLING_AVG = 'rolling_avg';
    case ROLLING_SUM = 'rolling_sum';
    case PERIOD_COMPARE = 'period_compare';
}
```

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum ComparisonType: string
{
    case PREVIOUS_PERIOD = 'previous_period';
}
```

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum InputMode: string
{
    case SCALAR = 'scalar';
    case TIME_SERIES = 'time_series';
}
```

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum RoundingMode: string
{
    case HALF_UP = 'half_up';
}
```

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum ValueType: string
{
    case NUMBER = 'number';
    case SERIES = 'series';
    case COMPARISON = 'comparison';
}
```

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Enums;

enum WindowType: string
{
    case FIXED_ROLLING = 'fixed_rolling';
    case EXPLICIT_RANGE = 'explicit_range';
}
```

- [ ] **Step 4: Add contracts**

Create contracts with these signatures:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Contracts;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\ValueObjects\ComparisonDefinition;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use Nexus\MetricEngine\ValueObjects\TimeWindow;

interface FormulaInterface
{
    public function identifier(): string;

    public function operation(): AggregationType;

    /** @return list<mixed> */
    public function operands(): array;

    public function precisionPolicy(): PrecisionPolicy;

    public function window(): ?TimeWindow;

    public function comparison(): ?ComparisonDefinition;
}
```

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Contracts;

use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\MetricResult;
use Nexus\MetricEngine\ValueObjects\MetricSeries;

interface FormulaEvaluatorInterface
{
    /** @param array<string, MetricInput|MetricSeries> $inputs */
    public function evaluate(FormulaInterface $formula, array $inputs): MetricResult;
}
```

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Contracts;

use Nexus\MetricEngine\ValueObjects\MetricSeries;
use Nexus\MetricEngine\ValueObjects\TimeWindow;

interface WindowResolverInterface
{
    public function resolve(MetricSeries $series, TimeWindow $window): MetricSeries;
}
```

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Contracts;

use Nexus\MetricEngine\Enums\InputMode;
use Nexus\MetricEngine\Enums\ValueType;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;

interface MetricResultInterface
{
    public function value(): int|float|string|array;

    public function valueType(): ValueType;

    public function formulaIdentifier(): string;

    public function inputMode(): InputMode;

    public function precisionPolicy(): PrecisionPolicy;
}
```

- [ ] **Step 5: Install dependencies and verify skeleton autoloads**

Run:

```bash
cd packages/MetricEngine
composer install
./vendor/bin/phpunit
```

Expected: PHPUnit runs with zero tests and exits without PHP syntax or autoload errors.

- [ ] **Step 6: Commit**

```bash
git add packages/MetricEngine/composer.json packages/MetricEngine/phpunit.xml packages/MetricEngine/src/Contracts packages/MetricEngine/src/Enums
git commit -m "feat(metric-engine): add package skeleton and contracts"
```

## Task 2: Value Objects And Failure Model

**Files:**
- Create: `packages/MetricEngine/src/Exceptions/MetricEngineException.php`
- Create: `packages/MetricEngine/src/Exceptions/DivideByZeroMetricException.php`
- Create: `packages/MetricEngine/src/Exceptions/FormulaValidationException.php`
- Create: `packages/MetricEngine/src/Exceptions/InsufficientDataException.php`
- Create: `packages/MetricEngine/src/Exceptions/InvalidNumericValueException.php`
- Create: `packages/MetricEngine/src/Exceptions/InvalidWindowException.php`
- Create: `packages/MetricEngine/src/Exceptions/MissingInputException.php`
- Create: `packages/MetricEngine/src/Exceptions/TypeMismatchException.php`
- Create: `packages/MetricEngine/src/ValueObjects/ComparisonDefinition.php`
- Create: `packages/MetricEngine/src/ValueObjects/ComparisonResult.php`
- Create: `packages/MetricEngine/src/ValueObjects/FormulaDefinition.php`
- Create: `packages/MetricEngine/src/ValueObjects/MetricInput.php`
- Create: `packages/MetricEngine/src/ValueObjects/MetricResult.php`
- Create: `packages/MetricEngine/src/ValueObjects/MetricSeries.php`
- Create: `packages/MetricEngine/src/ValueObjects/PrecisionPolicy.php`
- Create: `packages/MetricEngine/src/ValueObjects/TimeSeriesPoint.php`
- Create: `packages/MetricEngine/src/ValueObjects/TimeWindow.php`
- Test: `packages/MetricEngine/tests/Unit/ValueObjects/MetricInputTest.php`
- Test: `packages/MetricEngine/tests/Unit/ValueObjects/MetricSeriesTest.php`
- Test: `packages/MetricEngine/tests/Unit/ValueObjects/PrecisionPolicyTest.php`
- Test: `packages/MetricEngine/tests/Unit/ValueObjects/TimeWindowTest.php`

- [ ] **Step 1: Write failing value object tests**

Create tests covering these behaviors:

```php
public function test_metric_input_rejects_empty_name(): void
{
    $this->expectException(FormulaValidationException::class);
    $this->expectExceptionMessage('Metric input name is required.');

    new MetricInput('', 10);
}
```

```php
public function test_metric_series_rejects_empty_points(): void
{
    $this->expectException(InsufficientDataException::class);
    $this->expectExceptionMessage('Metric series requires at least one point.');

    new MetricSeries('sales', []);
}
```

```php
public function test_precision_policy_defaults_to_half_up_scale_two(): void
{
    $policy = PrecisionPolicy::default();

    $this->assertSame(2, $policy->scale);
    $this->assertSame(RoundingMode::HALF_UP, $policy->roundingMode);
}
```

```php
public function test_fixed_rolling_window_rejects_non_positive_size(): void
{
    $this->expectException(InvalidWindowException::class);
    $this->expectExceptionMessage('Fixed rolling window size must be greater than zero.');

    TimeWindow::fixedRolling(0);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/ValueObjects
```

Expected: FAIL because value objects and exceptions do not exist yet.

- [ ] **Step 3: Implement package exceptions**

Implement a base exception with stable package error code and one subclass per failure type:

```php
<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Exceptions;

use RuntimeException;

abstract class MetricEngineException extends RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
```

Each concrete exception should call the parent with a stable code, for example:

```php
final class MissingInputException extends MetricEngineException
{
    public function __construct(string $inputName)
    {
        parent::__construct('metric_engine.missing_input', "Required metric input [{$inputName}] is missing.");
    }
}
```

- [ ] **Step 4: Implement value objects**

Implement all value objects as `final readonly class`. Use constructor validation for invariants and expose data through public readonly properties or small accessor methods. The core shapes are:

```php
final readonly class PrecisionPolicy
{
    public function __construct(
        public int $scale = 2,
        public RoundingMode $roundingMode = RoundingMode::HALF_UP
    ) {
        if ($scale < 0) {
            throw new FormulaValidationException('Precision scale must be zero or greater.');
        }
    }

    public static function default(): self
    {
        return new self();
    }
}
```

```php
final readonly class MetricInput
{
    public function __construct(
        public string $name,
        public int|float|string $value,
        public ?string $unit = null
    ) {
        if (trim($name) === '') {
            throw new FormulaValidationException('Metric input name is required.');
        }
    }
}
```

```php
final readonly class TimeSeriesPoint
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $periodKey,
        public int|float|string $value,
        public array $metadata = []
    ) {
        if (trim($periodKey) === '') {
            throw new InvalidWindowException('Time-series period key is required.');
        }
    }
}
```

```php
final readonly class MetricSeries
{
    /** @param list<TimeSeriesPoint> $points */
    public function __construct(
        public string $name,
        public array $points,
        public ?string $unit = null
    ) {
        if (trim($name) === '') {
            throw new FormulaValidationException('Metric series name is required.');
        }

        if ($points === []) {
            throw new InsufficientDataException('Metric series requires at least one point.');
        }
    }
}
```

```php
final readonly class TimeWindow
{
    private function __construct(
        public WindowType $type,
        public ?int $size,
        public ?string $startPeriod,
        public ?string $endPeriod
    ) {}

    public static function fixedRolling(int $size): self
    {
        if ($size <= 0) {
            throw new InvalidWindowException('Fixed rolling window size must be greater than zero.');
        }

        return new self(WindowType::FIXED_ROLLING, $size, null, null);
    }

    public static function explicitRange(string $startPeriod, string $endPeriod): self
    {
        if (trim($startPeriod) === '' || trim($endPeriod) === '') {
            throw new InvalidWindowException('Explicit window range requires start and end periods.');
        }

        return new self(WindowType::EXPLICIT_RANGE, null, $startPeriod, $endPeriod);
    }
}
```

- [ ] **Step 5: Run tests to verify value objects pass**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/ValueObjects
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/MetricEngine/src/Exceptions packages/MetricEngine/src/ValueObjects packages/MetricEngine/tests/Unit/ValueObjects
git commit -m "feat(metric-engine): add value objects and failure model"
```

## Task 3: Numeric Policy And Scalar Primitives

**Files:**
- Create: `packages/MetricEngine/src/Services/NumericValueService.php`
- Create: `packages/MetricEngine/src/Services/ScalarMetricCalculatorService.php`
- Test: `packages/MetricEngine/tests/Unit/Services/NumericValueServiceTest.php`
- Test: `packages/MetricEngine/tests/Unit/Services/ScalarMetricCalculatorServiceTest.php`

- [ ] **Step 1: Write failing numeric policy tests**

Create tests for normalization, rounding, and invalid numeric values:

```php
public function test_rounds_half_up_to_requested_scale(): void
{
    $service = new NumericValueService();

    $this->assertSame(12.35, $service->round(12.345, new PrecisionPolicy(2)));
}
```

```php
public function test_rejects_non_numeric_string(): void
{
    $service = new NumericValueService();

    $this->expectException(InvalidNumericValueException::class);
    $this->expectExceptionMessage('Metric value [abc] is not numeric.');

    $service->normalize('abc');
}
```

- [ ] **Step 2: Write failing scalar primitive tests**

Create tests for every v1 scalar primitive:

```php
public function test_sum_avg_min_max_and_count(): void
{
    $calculator = new ScalarMetricCalculatorService(new NumericValueService());
    $policy = PrecisionPolicy::default();

    $this->assertSame(15.0, $calculator->sum([1, 2, 3, 4, 5], $policy));
    $this->assertSame(3.0, $calculator->avg([1, 2, 3, 4, 5], $policy));
    $this->assertSame(1.0, $calculator->min([1, 2, 3, 4, 5], $policy));
    $this->assertSame(5.0, $calculator->max([1, 2, 3, 4, 5], $policy));
    $this->assertSame(5.0, $calculator->count([1, 2, 3, 4, 5], $policy));
}
```

```php
public function test_ratio_and_pct_change_fail_on_zero_denominator(): void
{
    $calculator = new ScalarMetricCalculatorService(new NumericValueService());
    $policy = PrecisionPolicy::default();

    $this->expectException(DivideByZeroMetricException::class);

    $calculator->ratio(10, 0, $policy);
}
```

```php
public function test_weighted_score_rejects_mismatched_values_and_weights(): void
{
    $calculator = new ScalarMetricCalculatorService(new NumericValueService());

    $this->expectException(FormulaValidationException::class);
    $this->expectExceptionMessage('Weighted calculation requires the same number of values and weights.');

    $calculator->weightedScore([80, 90], [0.5], PrecisionPolicy::default());
}
```

- [ ] **Step 3: Run tests to verify they fail**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/NumericValueServiceTest.php tests/Unit/Services/ScalarMetricCalculatorServiceTest.php
```

Expected: FAIL because services do not exist yet.

- [ ] **Step 4: Implement numeric service**

Implement `NumericValueService` with these methods:

```php
public function normalize(int|float|string $value): float
{
    if (is_string($value) && ! is_numeric($value)) {
        throw new InvalidNumericValueException("Metric value [{$value}] is not numeric.");
    }

    $normalized = (float) $value;

    if (! is_finite($normalized)) {
        throw new InvalidNumericValueException('Metric value must be finite.');
    }

    return $normalized;
}

public function round(float $value, PrecisionPolicy $policy): float
{
    return round($value, $policy->scale, PHP_ROUND_HALF_UP);
}
```

- [ ] **Step 5: Implement scalar calculator**

Implement public methods:

- `sum(array $values, PrecisionPolicy $policy): float`
- `avg(array $values, PrecisionPolicy $policy): float`
- `min(array $values, PrecisionPolicy $policy): float`
- `max(array $values, PrecisionPolicy $policy): float`
- `count(array $values, PrecisionPolicy $policy): float`
- `ratio(int|float|string $numerator, int|float|string $denominator, PrecisionPolicy $policy): float`
- `delta(int|float|string $left, int|float|string $right, PrecisionPolicy $policy): float`
- `absoluteDelta(int|float|string $left, int|float|string $right, PrecisionPolicy $policy): float`
- `pctChange(int|float|string $current, int|float|string $previous, PrecisionPolicy $policy): float`
- `weightedAvg(array $values, array $weights, PrecisionPolicy $policy): float`
- `weightedScore(array $values, array $weights, PrecisionPolicy $policy): float`

Implementation rules:

- Normalize every numeric value through `NumericValueService`.
- Reject empty arrays for aggregate primitives with `InsufficientDataException`.
- Reject zero denominator with `DivideByZeroMetricException`.
- Reject mismatched weighted arrays with `FormulaValidationException`.
- Round every returned numeric value through `NumericValueService::round()`.

- [ ] **Step 6: Run tests to verify scalar primitives pass**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/NumericValueServiceTest.php tests/Unit/Services/ScalarMetricCalculatorServiceTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/MetricEngine/src/Services/NumericValueService.php packages/MetricEngine/src/Services/ScalarMetricCalculatorService.php packages/MetricEngine/tests/Unit/Services/NumericValueServiceTest.php packages/MetricEngine/tests/Unit/Services/ScalarMetricCalculatorServiceTest.php
git commit -m "feat(metric-engine): add numeric policy and scalar primitives"
```

## Task 4: Formula Evaluator

**Files:**
- Create: `packages/MetricEngine/src/Services/FormulaEvaluatorService.php`
- Test: `packages/MetricEngine/tests/Unit/Services/FormulaEvaluatorServiceTest.php`

- [ ] **Step 1: Write failing formula evaluator tests**

Cover named inputs, constants, nested formulas, missing input, and no business labels:

```php
public function test_evaluates_nested_scalar_formula(): void
{
    $evaluator = new FormulaEvaluatorService(new ScalarMetricCalculatorService(new NumericValueService()));

    $formula = new FormulaDefinition(
        identifier: 'metric.margin_ratio',
        operation: AggregationType::RATIO,
        operands: [
            new FormulaDefinition('metric.margin_delta', AggregationType::DELTA, ['revenue', 'cogs']),
            'revenue',
        ],
        precisionPolicy: new PrecisionPolicy(4)
    );

    $result = $evaluator->evaluate($formula, [
        'revenue' => new MetricInput('revenue', 1000),
        'cogs' => new MetricInput('cogs', 600),
    ]);

    $this->assertSame(0.4, $result->value());
    $this->assertSame('metric.margin_ratio', $result->formulaIdentifier());
    $this->assertSame(InputMode::SCALAR, $result->inputMode());
}
```

```php
public function test_missing_named_input_fails_loudly(): void
{
    $evaluator = new FormulaEvaluatorService(new ScalarMetricCalculatorService(new NumericValueService()));

    $this->expectException(MissingInputException::class);
    $this->expectExceptionMessage('Required metric input [revenue] is missing.');

    $evaluator->evaluate(new FormulaDefinition('metric.ratio', AggregationType::RATIO, ['revenue', 100]), []);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/FormulaEvaluatorServiceTest.php
```

Expected: FAIL because evaluator behavior is not implemented yet.

- [ ] **Step 3: Implement formula evaluation**

Implement `FormulaEvaluatorService` to:

- Accept `FormulaInterface` and `array<string, MetricInput|MetricSeries>`.
- Resolve string operands as named inputs when the input exists.
- Treat numeric operands as constants.
- Resolve nested `FormulaInterface` operands recursively.
- Dispatch scalar operations to `ScalarMetricCalculatorService`.
- Return `MetricResult` with `ValueType::NUMBER`, `InputMode::SCALAR`, formula identifier, precision policy, and optional unit from caller input only.
- Throw `MissingInputException` for unknown string operands.
- Throw `TypeMismatchException` when a scalar operation receives a `MetricSeries`.

- [ ] **Step 4: Run evaluator tests**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/FormulaEvaluatorServiceTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/MetricEngine/src/Services/FormulaEvaluatorService.php packages/MetricEngine/tests/Unit/Services/FormulaEvaluatorServiceTest.php
git commit -m "feat(metric-engine): add scalar formula evaluator"
```

## Task 5: Time-Series Windows And Comparison

**Files:**
- Create: `packages/MetricEngine/src/Services/WindowResolverService.php`
- Create: `packages/MetricEngine/src/Services/ComparisonService.php`
- Create: `packages/MetricEngine/src/Services/TimeSeriesMetricCalculatorService.php`
- Test: `packages/MetricEngine/tests/Unit/Services/WindowResolverServiceTest.php`
- Test: `packages/MetricEngine/tests/Unit/Services/ComparisonServiceTest.php`
- Test: `packages/MetricEngine/tests/Unit/Services/TimeSeriesMetricCalculatorServiceTest.php`

- [ ] **Step 1: Write failing window resolver tests**

Create tests for fixed rolling and explicit range:

```php
public function test_fixed_rolling_window_returns_last_n_points(): void
{
    $series = new MetricSeries('sales', [
        new TimeSeriesPoint('2026-01', 10),
        new TimeSeriesPoint('2026-02', 20),
        new TimeSeriesPoint('2026-03', 30),
    ]);

    $resolved = (new WindowResolverService())->resolve($series, TimeWindow::fixedRolling(2));

    $this->assertSame(['2026-02', '2026-03'], array_map(
        static fn (TimeSeriesPoint $point): string => $point->periodKey,
        $resolved->points
    ));
}
```

```php
public function test_explicit_range_filters_by_period_key(): void
{
    $series = new MetricSeries('sales', [
        new TimeSeriesPoint('2026-01', 10),
        new TimeSeriesPoint('2026-02', 20),
        new TimeSeriesPoint('2026-03', 30),
    ]);

    $resolved = (new WindowResolverService())->resolve($series, TimeWindow::explicitRange('2026-02', '2026-03'));

    $this->assertCount(2, $resolved->points);
}
```

- [ ] **Step 2: Write failing time-series primitive tests**

```php
public function test_rolling_sum_and_rolling_avg(): void
{
    $calculator = new TimeSeriesMetricCalculatorService(
        new NumericValueService(),
        new WindowResolverService()
    );

    $series = new MetricSeries('sales', [
        new TimeSeriesPoint('2026-01', 10),
        new TimeSeriesPoint('2026-02', 20),
        new TimeSeriesPoint('2026-03', 30),
    ]);

    $this->assertSame(50.0, $calculator->rollingSum($series, TimeWindow::fixedRolling(2), PrecisionPolicy::default()));
    $this->assertSame(25.0, $calculator->rollingAvg($series, TimeWindow::fixedRolling(2), PrecisionPolicy::default()));
}
```

- [ ] **Step 3: Write failing comparison tests**

```php
public function test_previous_period_comparison_returns_delta_and_pct_change(): void
{
    $service = new ComparisonService(new NumericValueService());

    $result = $service->previousPeriod(
        currentValue: 120,
        previousValue: 100,
        policy: new PrecisionPolicy(2)
    );

    $this->assertSame(20.0, $result->delta);
    $this->assertSame(0.2, $result->percentChange);
}
```

- [ ] **Step 4: Run tests to verify they fail**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/WindowResolverServiceTest.php tests/Unit/Services/ComparisonServiceTest.php tests/Unit/Services/TimeSeriesMetricCalculatorServiceTest.php
```

Expected: FAIL because services do not exist yet.

- [ ] **Step 5: Implement window, comparison, and time-series services**

Implementation rules:

- `WindowResolverService::resolve()` returns a new `MetricSeries`.
- Fixed rolling windows select the last `size` points and fail with `InsufficientDataException` when the series has fewer points than requested.
- Explicit ranges compare `periodKey` lexicographically and fail with `InsufficientDataException` when no points match.
- `ComparisonService::previousPeriod()` rejects zero previous value with `DivideByZeroMetricException`.
- `TimeSeriesMetricCalculatorService::rollingSum()` and `rollingAvg()` use `WindowResolverService` and `NumericValueService`.
- `periodCompare()` supports `ComparisonType::PREVIOUS_PERIOD` only in v1.

- [ ] **Step 6: Run tests to verify time-series support passes**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Services/WindowResolverServiceTest.php tests/Unit/Services/ComparisonServiceTest.php tests/Unit/Services/TimeSeriesMetricCalculatorServiceTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/MetricEngine/src/Services/WindowResolverService.php packages/MetricEngine/src/Services/ComparisonService.php packages/MetricEngine/src/Services/TimeSeriesMetricCalculatorService.php packages/MetricEngine/tests/Unit/Services/WindowResolverServiceTest.php packages/MetricEngine/tests/Unit/Services/ComparisonServiceTest.php packages/MetricEngine/tests/Unit/Services/TimeSeriesMetricCalculatorServiceTest.php
git commit -m "feat(metric-engine): add time-series windows and comparisons"
```

## Task 6: Boundary And Acceptance Tests

**Files:**
- Test: `packages/MetricEngine/tests/Unit/Architecture/LayerBoundaryTest.php`
- Test: `packages/MetricEngine/tests/Unit/Services/DeterminismTest.php`
- Modify: `packages/MetricEngine/src/Services/FormulaEvaluatorService.php`
- Modify: `packages/MetricEngine/src/Services/TimeSeriesMetricCalculatorService.php`

- [ ] **Step 1: Write failing deterministic result test**

```php
public function test_identical_inputs_produce_identical_results_without_runtime_timestamp(): void
{
    $evaluator = new FormulaEvaluatorService(new ScalarMetricCalculatorService(new NumericValueService()));
    $formula = new FormulaDefinition('metric.ratio', AggregationType::RATIO, ['actual', 'target']);
    $inputs = [
        'actual' => new MetricInput('actual', 75),
        'target' => new MetricInput('target', 100),
    ];

    $first = $evaluator->evaluate($formula, $inputs);
    $second = $evaluator->evaluate($formula, $inputs);

    $this->assertEquals($first, $second);
    $this->assertObjectNotHasProperty('evaluationTimestamp', $first);
}
```

- [ ] **Step 2: Write failing Layer 1 boundary test**

```php
public function test_metric_engine_does_not_depend_on_frameworks_or_domain_packages(): void
{
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__ . '/../../../src')
    );

    $forbidden = [
        'Illuminate\\',
        'Symfony\\',
        'Nexus\\FinancialRatios\\',
        'Nexus\\Treasury\\',
        'Nexus\\ESG\\',
        'Nexus\\SourcingScoring\\',
        'Nexus\\PerformanceReview\\',
        'banded_health_score',
        'vendor health',
        'gross margin',
    ];

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        foreach ($forbidden as $needle) {
            $this->assertStringNotContainsString($needle, $contents, $file->getPathname());
        }
    }
}
```

- [ ] **Step 3: Run boundary tests to verify failures or passes**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit tests/Unit/Architecture/LayerBoundaryTest.php tests/Unit/Services/DeterminismTest.php
```

Expected: PASS if earlier tasks preserved the spec boundary; otherwise FAIL with a concrete forbidden dependency, timestamp, or domain label to remove.

- [ ] **Step 4: Fix any boundary failures**

If a failure occurs, remove the reported forbidden dependency, timestamp property, or domain label. Keep error messages neutral and package-specific, for example use `Metric value` rather than finance, vendor, health, or ESG language.

- [ ] **Step 5: Run full package tests**

Run:

```bash
cd packages/MetricEngine
./vendor/bin/phpunit
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/MetricEngine
git commit -m "test(metric-engine): enforce deterministic layer boundary"
```

## Task 7: Package Reference And Final Verification

**Files:**
- Modify: `docs/project/NEXUS_PACKAGES_REFERENCE.md`
- Modify: `docs/superpowers/specs/2026-05-05-metric-engine-design.md` only if implementation reveals a design contradiction.

- [ ] **Step 1: Register the package reference**

Add `Nexus\MetricEngine` to the closest package catalogue section in `docs/project/NEXUS_PACKAGES_REFERENCE.md`:

```markdown
| `Nexus\MetricEngine` | Deterministic Layer 1 metric calculation engine. | Prepared scalar/time-series input evaluation, neutral formula composition, v1 primitive calculations, window resolution, comparison results, and typed deterministic metric results. | Not a data/query/reporting platform; use `QueryEngine` for query execution, `Reporting` for report composition, and domain packages for formula meaning and thresholds. Not observability metrics; use `Telemetry`. | You need reusable metric calculation mechanics without domain interpretation. |
```

- [ ] **Step 2: Run package verification**

Run:

```bash
cd packages/MetricEngine
composer install
./vendor/bin/phpunit
```

Expected: PASS.

- [ ] **Step 3: Run lightweight boundary scans**

Run:

```bash
rg -n "Illuminate\\\\|Symfony\\\\|banded_health_score|vendor health|gross margin|evaluationTimestamp" packages/MetricEngine/src packages/MetricEngine/tests
```

Expected: no matches except test assertions that intentionally look for forbidden strings.

- [ ] **Step 4: Review git diff**

Run:

```bash
git diff -- packages/MetricEngine docs/project/NEXUS_PACKAGES_REFERENCE.md docs/superpowers/specs/2026-05-05-metric-engine-design.md
```

Expected: diff contains only the new `MetricEngine` package, package reference update, and any necessary spec correction.

- [ ] **Step 5: Commit final docs and verification closure**

```bash
git add docs/project/NEXUS_PACKAGES_REFERENCE.md docs/superpowers/specs/2026-05-05-metric-engine-design.md
git commit -m "docs(metric-engine): register package reference"
```

If `docs/superpowers/specs/2026-05-05-metric-engine-design.md` has no changes, omit it from `git add`.

## Self-Review Notes

- Spec coverage: package skeleton, contracts, typed value objects, deterministic result model, scalar primitives, minimal time-series support, fixed rolling windows, previous-period comparison, failure semantics, dependency boundaries, and acceptance criteria are each mapped to a task.
- Scope: migration of existing packages onto `MetricEngine` is intentionally excluded from this plan.
- Dependency posture: the plan uses only PHP, Composer, and PHPUnit.
- Drift watch: if implementation proves that numeric-string decimal handling needs a math library, pause and update the spec before adding the dependency.
