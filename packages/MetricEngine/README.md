# Nexus MetricEngine

Deterministic Layer 1 metric calculation engine for Nexus packages.

`Nexus\MetricEngine` evaluates prepared scalar and time-series inputs against neutral formula definitions. It does not fetch data, persist state, render reports, or attach domain meaning to metrics.

## Installation

```bash
composer require azaharizaman/nexus-metric-engine
```

## Requirements

- PHP 8.3 or newer

## Basic Usage

```php
<?php

declare(strict_types=1);

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Services\FormulaEvaluatorService;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\Services\ScalarMetricCalculatorService;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;

$numeric = new NumericValueService();
$evaluator = new FormulaEvaluatorService(
    new ScalarMetricCalculatorService($numeric)
);

$formula = new FormulaDefinition(
    identifier: 'example.ratio',
    operation: AggregationType::RATIO,
    operands: ['actual', 'target'],
    precisionPolicy: new PrecisionPolicy(2)
);

$result = $evaluator->evaluate($formula, [
    'actual' => new MetricInput('actual', 75),
    'target' => new MetricInput('target', 100),
]);

$result->value(); // 0.75
```

## Supported Calculations

Scalar primitives:

- `sum`
- `avg`
- `min`
- `max`
- `count`
- `ratio`
- `delta`
- `absolute_delta`
- `pct_change`
- `weighted_avg`
- `weighted_score`

Time-series primitives:

- `rolling_sum`
- `rolling_avg`
- `period_compare`

## Failure Semantics

MetricEngine fails loudly with package-specific exceptions for invalid formula structure, missing inputs, incompatible input types, invalid numeric values, divide-by-zero operations, invalid windows, and insufficient time-series data.

It does not return fallback values or synthetic zeros unless a caller-defined formula explicitly models that behavior.

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

## Boundaries

MetricEngine is framework-agnostic. Laravel service providers belong in Laravel adapters or applications.

MetricEngine supports neutral units and precision policies, but currency-specific rules and money value objects belong in finance or accounting packages.

## Development

```bash
composer install
./vendor/bin/phpunit
composer validate --strict
```
