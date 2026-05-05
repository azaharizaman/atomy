# MetricEngine Design

Date: 2026-05-05
Status: Proposed

## 1. Purpose

`Nexus\MetricEngine` is a Layer 1 package for deterministic, framework-agnostic metric calculation.

It evaluates prepared scalar and time-series inputs against caller-defined formulas. It does not fetch data, own business semantics, render reports, persist state, or collect observability metrics.

The package exists to centralize reusable calculation mechanics that are currently repeated across domain packages such as `FinancialRatios`, `AccountVarianceAnalysis`, `Treasury`, `ESG`, `SourcingScoring`, and `PerformanceReview`.

## 2. Boundary

`MetricEngine` owns neutral calculation mechanics:

- primitive math/statistical operations
- formula composition
- scalar evaluation
- time-series evaluation
- window resolution
- period comparison
- typed deterministic result objects
- explicit validation and calculation failures

`MetricEngine` does not own:

- data sourcing or query execution
- dashboard/report composition
- persistence, caching, or runtime state
- domain metric names, meanings, labels, thresholds, or interpretation
- observability/system telemetry collection

Boundary invariant:

Domain packages define the formula meaning. `MetricEngine` only evaluates neutral formula definitions against prepared inputs.

## 3. Design Choice

Use a small formula runtime with typed inputs.

A pure function library would push repeated assembly logic into every caller. A mini analytics platform would overlap with `QueryEngine`, `Reporting`, and dashboard concerns. A typed formula runtime gives domains reusable mechanics while preserving Layer 1 purity.

## 4. Package Shape

Suggested structure:

- `src/Contracts/FormulaInterface.php`
- `src/Contracts/FormulaEvaluatorInterface.php`
- `src/Contracts/WindowResolverInterface.php`
- `src/Contracts/MetricResultInterface.php`
- `src/ValueObjects/MetricInput.php`
- `src/ValueObjects/MetricSeries.php`
- `src/ValueObjects/TimeSeriesPoint.php`
- `src/ValueObjects/TimeWindow.php`
- `src/ValueObjects/FormulaDefinition.php`
- `src/ValueObjects/MetricResult.php`
- `src/ValueObjects/ComparisonResult.php`
- `src/Enums/AggregationType.php`
- `src/Enums/WindowType.php`
- `src/Enums/ComparisonType.php`
- `src/Enums/ValueType.php`
- `src/Services/FormulaEvaluatorService.php`
- `src/Services/ScalarMetricCalculatorService.php`
- `src/Services/TimeSeriesMetricCalculatorService.php`
- `src/Services/WindowResolverService.php`
- `src/Services/ComparisonService.php`
- `src/Exceptions/*Exception.php`

All value objects should be `final readonly class`. Service names should follow Layer 1 naming conventions and use the `Service` suffix.

## 5. Input Model

The engine supports two prepared input modes.

Scalar inputs are named values supplied by the caller:

- `revenue`
- `cost`
- `target`
- `actual`
- `weight`

Time-series inputs are ordered points supplied by the caller:

- date or period key
- numeric value
- optional caller-provided metadata

The engine must not normalize domain data. Callers are responsible for turning domain records into `MetricInput` or `MetricSeries`.

## 6. Formula Model

Formulas are declarative and deterministic.

A formula may reference:

- named inputs
- constants
- primitive operations
- nested primitive expressions
- caller-supplied precision policy
- optional time windows or comparison definitions

Formula definitions may be object-based in v1. Array-backed formula loading is deferred unless an immediate package consumer requires config-driven definitions.

Example conceptual formulas:

- `ratio(delta(revenue, cogs), revenue)`
- `weighted_score([quality, delivery, price], [0.4, 0.3, 0.3])`

The engine must not assign those formulas names like "gross margin", "vendor health", or "delivery performance". Those labels belong to consuming packages.

## 7. V1 Primitive Set

V1 should be intentionally small and complete.

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

- `rolling_avg`
- `rolling_sum`
- `period_compare`

Deferred primitives:

- `median`
- `percentile`
- `stddev`
- `target_attainment`
- `target_gap`
- `variance_to_target`
- `forecast_accuracy`
- `yoy_change`
- `qoq_change`
- `trend_slope`
- `cagr`

Deferred primitives should be added only when a real consuming package needs them.

`banded_health_score` must not be a `MetricEngine` primitive. If needed later, the neutral primitive should be `banded_score`, with caller-supplied bands and no domain label.

## 8. Windows And Comparison

V1 window support:

- fixed-size rolling windows
- explicit custom ranges
- previous-period comparison

Deferred window support:

- year-to-date
- quarter-to-date
- month-to-date
- year-over-year
- quarter-over-quarter

The caller must provide calendar boundaries or period keys when fiscal semantics matter. `MetricEngine` must not infer fiscal calendars.

## 9. Determinism

For identical formula definitions, inputs, windows, and precision policy, the engine must return identical results.

`MetricEngine` must not generate runtime timestamps inside calculation results. If an evaluation timestamp is needed, it must be caller-supplied as metadata or attached outside the deterministic result.

## 10. Precision Policy

V1 must define precision explicitly.

Recommended default:

- accept `int`, `float`, or numeric string inputs
- normalize numeric strings for decimal-sensitive operations
- return numeric values with caller-configurable scale
- default rounding mode: half-up
- reject non-finite values such as `NaN` and `INF`

Financial packages may wrap or adapt results into money-specific value objects, but `MetricEngine` should not own currency semantics.

## 11. Result Model

Successful evaluation returns typed result objects, not arrays.

Required result data:

- calculated value
- value type
- formula identifier
- input mode
- precision policy applied
- optional unit provided by caller
- optional window or comparison metadata

Optional metadata:

- contributing values
- target or benchmark reference when explicitly supplied
- data sufficiency marker when explicitly computed

The engine must not fabricate confidence, health, quality, or business status labels.

## 12. Failure Semantics

The package fails loudly and deterministically.

Failure cases:

- invalid formula structure
- missing required input
- incompatible input type
- invalid numeric value
- divide-by-zero or undefined operation
- invalid window definition
- insufficient time-series data
- mismatched value and weight counts

Failure requirements:

- no silent fallback values
- no synthetic zero unless formula explicitly defines that behavior
- package-specific exception classes
- stable machine-readable error codes
- stable messages suitable for unit tests

## 13. Dependency Policy

Allowed:

- core PHP 8.3+
- PSR contracts if needed
- math/statistics libraries only after v1 proves necessity

Disallowed:

- Laravel, Symfony, or other frameworks
- ORM/database libraries
- HTTP clients
- queue libraries
- application containers
- reporting, export, or UI libraries

## 14. Testing Strategy

V1 requires unit tests for:

- each scalar primitive
- each time-series primitive
- formula composition
- named input lookup
- precision and rounding
- divide-by-zero
- invalid numeric values
- missing input
- type mismatch
- empty and sparse series
- insufficient window data
- comparison edge cases

Tests must not bootstrap a framework.

## 15. Initial Delivery Scope

V1 includes:

- package skeleton
- typed value objects
- object-based formula definitions
- scalar formula evaluation
- minimal time-series evaluation
- fixed rolling windows
- previous-period comparison
- v1 primitive set
- deterministic result model
- explicit exception model
- full unit coverage for v1 behavior

V1 excludes:

- data connectors
- query builders
- persistence adapters
- report schemas
- dashboard composition
- domain formula packs
- config-file formula DSL
- migration of existing packages onto `MetricEngine`

## 16. Acceptance Criteria

The design is acceptable when:

- no domain-owned metric meaning exists inside `MetricEngine`
- no framework dependency is required
- identical inputs produce identical outputs
- all v1 primitives have explicit tests
- invalid input cannot produce silent success
- consuming packages can define their own formulas without leaking interpretation into the engine
