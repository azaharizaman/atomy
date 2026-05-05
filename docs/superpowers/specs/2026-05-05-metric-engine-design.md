# MetricEngine Design

Date: 2026-05-05
Status: Proposed

## 1. Purpose

`Nexus\MetricEngine` is a new Layer 1 package for stateless, framework-agnostic metric calculation.

Its responsibility is limited to evaluating metrics from prepared inputs. It does not fetch data, define domain semantics, render reports, or own dashboard/query concerns.

The package exists to provide one reusable calculation kernel that domain packages can use when they need:

- mathematical and statistical calculations
- generic business metric calculations
- scalar metric evaluation
- time-series and windowed metric evaluation
- formula composition from reusable primitives

## 2. Problem Statement

The current package catalogue already contains domain-specific KPI and scoring logic:

- `PerformanceReview` for appraisal KPIs and ratings
- `FinancialRatios` for financial ratio calculation
- `AccountVarianceAnalysis` for variance and trend analysis
- `Treasury` for treasury KPI objects and analytics
- `ESG` for sustainability scoring
- `SourcingScoring` for sourcing evaluation

Those packages correctly own business meaning, but calculation mechanics are fragmented. Reusable primitives such as ratio, growth, target attainment, rolling average, weighted score, or period comparison should not be repeatedly reimplemented in each domain package.

`MetricEngine` addresses that gap by centralizing calculation mechanics while leaving business formula ownership in the consuming domain package.

## 3. Goals

- Provide a Layer 1 calculation engine for neutral metric evaluation.
- Support both scalar inputs and time-series inputs.
- Support composable formulas backed by built-in primitives.
- Remain stateless and framework-agnostic.
- Keep dependencies bounded to PHP, PSR contracts, and math-oriented libraries only.
- Return typed, deterministic results with explicit failure semantics.
- Avoid leaking domain-specific business semantics into the package.

## 4. Non-Goals

- No data fetching or query execution.
- No persistence, caching, or stateful runtime.
- No report rendering, export formatting, or distribution.
- No dashboard composition.
- No observability/monitoring metric collection.
- No domain-owned formulas such as employee appraisal score, sourcing award logic, or treasury policy thresholds.
- No framework-specific adapters in Layer 1.

## 5. Recommended Approach

Three approaches were considered:

1. Pure function library only
2. Formula runtime with typed inputs
3. Mini analytics platform

Recommended approach: `MetricEngine` should be a formula runtime with typed inputs.

Reason:

- a pure function library is too rigid and pushes too much assembly work into every caller
- a mini analytics platform would overlap with `QueryEngine` and `Reporting`
- a formula runtime preserves a clean Layer 1 boundary while remaining reusable across domains

## 6. Boundary

`MetricEngine` owns:

- primitive calculations
- formula composition
- scalar evaluation
- time-series evaluation
- window resolution
- comparison logic
- typed metric result objects
- validation and deterministic calculation errors

`MetricEngine` does not own:

- data sourcing
- query execution
- reporting
- presentation
- dashboard semantics
- domain formula meaning

Boundary rule:

Domain packages define metric formulas, semantic labels, and interpretation rules. `MetricEngine` only evaluates the supplied formula against prepared inputs.

## 7. Runtime Shape

The package should be stateless and framework-agnostic.

Suggested structure:

- `src/Contracts/`
  - `FormulaInterface`
  - `FormulaEvaluatorInterface`
  - `WindowResolverInterface`
  - `MetricResultInterface`
- `src/ValueObjects/`
  - `MetricInput`
  - `TimeSeriesPoint`
  - `TimeWindow`
  - `FormulaDefinition`
  - `MetricResult`
  - `ComparisonResult`
- `src/Enums/`
  - `AggregationType`
  - `WindowType`
  - `ComparisonType`
  - `ValueType`
- `src/Services/`
  - `FormulaEvaluator`
  - `ScalarMetricCalculator`
  - `TimeSeriesMetricCalculator`
  - `WindowingEngine`
  - `ComparisonEngine`
- `src/Exceptions/`
  - formula validation errors
  - missing input errors
  - type mismatch errors
  - divide-by-zero errors
  - invalid window errors
  - insufficient data errors

This is a calculation kernel, not a data platform.

## 8. Input Modes

The package should support two input modes:

### 8.1 Scalar Inputs

Used when a domain package already has prepared values such as:

- revenue
- cost
- target
- actual
- weight

### 8.2 Time-Series Inputs

Used when the domain package supplies dated or periodized values such as:

- daily sales
- monthly cash balances
- quarterly operating margin
- weekly delivery performance

Time-series support should enable:

- rolling windows
- period-over-period comparison
- year-over-year and quarter-over-quarter comparison
- year-to-date, quarter-to-date, month-to-date
- custom time ranges

## 9. Primitive Set

The initial primitive set should stay intentionally small but expressive.

### 9.1 Base Math And Statistical Primitives

- `sum`
- `avg`
- `min`
- `max`
- `count`
- `ratio`
- `delta`
- `absolute_delta`
- `pct_change`
- `median`
- `percentile`
- `stddev`
- `weighted_avg`
- `weighted_score`

### 9.2 Generic Business Metric Primitives

- `target_attainment`
- `target_gap`
- `variance_to_target`
- `forecast_accuracy`
- `banded_health_score`

These are allowed because they are reusable calculation patterns and do not require domain meaning.

### 9.3 Time-Aware Primitives

- `rolling_avg`
- `rolling_sum`
- `period_compare`
- `yoy_change`
- `qoq_change`
- `trend_slope`

Possible later primitive:

- `cagr`

This should not be in the initial scope unless a consuming package proves the need.

## 10. Formula Model

The package should support a composable formula model with built-in primitives.

Formula rules:

- domain packages define formulas using neutral primitives
- formulas may reference named inputs, constants, weights, and optional windows
- formulas may be scalar or time-series based
- formulas may compose other primitive expressions
- formulas remain declarative and deterministic

Example conceptual formulas:

- `gross_margin = ratio(delta(revenue, cogs), revenue)`
- `delivery_health = weighted_score(on_time_rate, defect_rate, lead_time_variance)`

Important boundary:

`MetricEngine` evaluates these formulas but does not own what "gross margin" or "delivery health" means in a business context.

## 11. Existing Package Boundaries

`MetricEngine` must not collapse existing package boundaries.

### 11.1 QueryEngine

`QueryEngine` prepares, executes, and aggregates analytical queries across models and sources.

`MetricEngine` only evaluates formulas on prepared inputs.

### 11.2 Reporting

`Reporting` renders and distributes report outputs.

`MetricEngine` only returns calculation results.

### 11.3 Telemetry

`Telemetry` owns system and application observability metrics.

`MetricEngine` owns business-metric calculation mechanics.

### 11.4 Domain Packages

Packages such as `PerformanceReview`, `FinancialRatios`, `AccountVarianceAnalysis`, `Treasury`, `ESG`, and `SourcingScoring` remain the public owners of domain semantics.

They may later delegate internal calculation mechanics to `MetricEngine`, but they should still define:

- formula meaning
- domain labels
- interpretation rules
- thresholds tied to business semantics

## 12. Composition Model

Expected usage pattern:

1. domain package or repository prepares normalized inputs
2. domain package selects or defines a formula
3. `MetricEngine` evaluates the formula
4. domain package interprets the result in domain language
5. `QueryEngine`, `Reporting`, dashboards, or orchestrators consume the interpreted result as needed

This preserves Layer 1 purity while enabling reuse.

## 13. Validation And Failure Semantics

The package should fail loudly and deterministically.

Expected failure cases:

- invalid formula structure
- missing required input
- incompatible input type
- invalid window definition
- divide-by-zero or undefined mathematical operation
- insufficient time-series data for the requested operation

Failure requirements:

- no silent fallback values
- no synthetic zero unless explicitly configured by formula semantics
- machine-readable exception types
- stable error messages suitable for testing

## 14. Result Model

Success should return typed result objects, not loose arrays.

Recommended result data:

- calculated value
- optional unit
- precision or scale
- formula identifier
- evaluation timestamp
- source input mode
- applied window or comparison metadata when relevant

Optional secondary result metadata:

- contributing values
- benchmark or target reference
- confidence or data sufficiency markers if explicitly computed by the formula

`MetricEngine` should not fabricate confidence or quality labels unless the formula explicitly defines them.

## 15. Dependency Policy

Dependencies must remain tightly bounded.

Allowed dependency categories:

- core PHP
- PSR contracts
- math-oriented or statistics-oriented libraries if truly needed

Disallowed dependency categories:

- frameworks
- ORM/database libraries
- HTTP clients
- UI/reporting libraries
- queue libraries
- application containers

## 16. Test Strategy

The package should be heavily unit-tested.

Minimum coverage areas:

- primitive calculation tests
- scalar formula evaluation tests
- time-series formula evaluation tests
- window resolution tests
- comparison logic tests
- invalid formula tests
- missing input tests
- divide-by-zero tests
- sparse/empty series tests
- type mismatch tests

Test policy:

- no framework bootstrapping
- deterministic fixtures
- edge cases must be first-class, especially numeric and array safety

## 17. Risks And Guardrails

### 17.1 Scope Creep

Risk:

The package becomes a general analytics or reporting platform.

Guardrail:

Reject any feature that fetches data, plans queries, persists state, or renders user-facing output.

### 17.2 Domain Leakage

Risk:

The package accumulates business-specific primitives.

Guardrail:

If a primitive name sounds domain-owned, it belongs in the consuming package, not in `MetricEngine`.

### 17.3 Over-Abstracted Formula DSL

Risk:

The formula model becomes too complex to understand or maintain.

Guardrail:

Start with a small declarative model and only expand when real consumers require it.

## 18. Initial Delivery Scope

The first version should include:

- stateless formula runtime
- scalar input support
- time-series input support
- core math/stat primitives
- generic business metric primitives
- window and comparison support
- typed result objects
- explicit error model
- full unit-test coverage for the initial primitive set

The first version should not include:

- data connectors
- query builders
- persistence adapters
- report schemas
- domain-specific formula packs
- cross-package migration work

## 19. Open Follow-Up For Planning

The implementation plan should decide:

- exact PHP API for `FormulaDefinition`
- whether formulas are object-based, array-backed, or both
- exact list of initial primitives in v1
- precision and rounding policy
- unit handling strategy
- whether optional math libraries are needed on day one or deferred

## 20. Recommendation Summary

Proceed with `Nexus\MetricEngine` as:

- a Layer 1 stateless metric calculation runtime
- framework-agnostic
- composable formula model with built-in primitives
- support for both scalar and time-series evaluation
- strict separation from data/query/reporting concerns
- strict separation from domain-owned business semantics
