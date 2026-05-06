# MetricEngine Application Refactor Support Design

Date: 2026-05-06
Status: Proposed

## 1. Purpose

This design expands `Nexus\MetricEngine` so Atomy-Q and other application packages can refactor repeated metric workflows into the Layer 1 package with less screen-local glue code.

The expansion must preserve the existing package boundary:

- `MetricEngine` evaluates prepared inputs against neutral formula definitions.
- Domain packages and applications define metric meaning, labels, thresholds, data sourcing, persistence, and presentation.
- Single-formula evaluation remains strict and fail-loud.
- Batch evaluation becomes the application-friendly API that converts known calculation failures into deterministic statuses.

## 2. Accepted Requests

The package should add support for:

- batch formula evaluation
- formula registry/catalog support
- array/config serialization for `FormulaDefinition`
- dependency graphs for formulas that consume other formula results
- result statuses: `available`, `no_data`, `not_available`, and `error`
- optional audit/explanation output
- neutral score threshold/banding helpers
- cache/fingerprint helpers for reproducible runs
- `RoundingMode` enforcement in `PrecisionPolicy`
- stronger time-series period handling beyond lexical string comparison
- generic precision/unit helpers

## 3. Boundary Rejections And Deferrals

Laravel service provider bindings must not be added to `packages/MetricEngine`.

`MetricEngine` is a Layer 1 package. It must remain framework-agnostic and must not depend on Laravel, Symfony, service containers, config repositories, or application bootstrapping. Laravel bindings belong in a Laravel adapter package or in the Atomy-Q application service provider.

Money/currency-aware behavior is only partially accepted.

The package may provide neutral scale/unit precision helpers, but it must not own ISO currency rules, money value objects, foreign exchange behavior, accounting semantics, or currency-specific interpretation. Those belong in finance/accounting packages or application adapters.

Score threshold helpers are only accepted as neutral banding mechanics.

The package may provide `banded_score`-style mechanics with caller-supplied thresholds and caller-supplied labels. It must not introduce domain labels such as health, risk, preferred, compliant, vendor quality, or scorecard meaning.

## 4. Design Choice

Add orchestration around the current evaluator rather than replacing it.

`FormulaEvaluatorService::evaluate()` should continue to evaluate one formula and throw package-specific exceptions. This keeps the low-level calculation contract precise.

New batch services should sit above the single evaluator and return typed outcomes. They can infer result statuses from known calculation failures without weakening the core evaluator.

This approach preserves existing behavior while giving Atomy-Q a reproducible workflow API for formula catalogs, dependencies, status handling, and audit traces.

## 5. New Package Surfaces

### 5.1 Formula Catalog

Add a pure PHP immutable catalog:

- `ValueObjects/FormulaCatalog.php`
- `Services/FormulaCatalogBuilderService.php`

Responsibilities:

- register formulas by identifier
- reject duplicate identifiers
- expose formulas by id
- expose formulas in insertion order
- build from `FormulaDefinition` objects or serialized arrays

The catalog must not read files, query databases, or load application config. Callers supply arrays or objects.

### 5.2 Formula Serialization

Add deterministic formula serialization:

- `Services/FormulaDefinitionSerializerService.php`

Supported fields:

- `identifier`
- `operation`
- `operands`
- `precision`
- `rounding_mode`
- `window`
- `comparison`
- optional `unit`
- optional caller metadata

Serialization must round-trip every supported formula definition without loss. Unsupported operations, invalid enum values, invalid operand structures, invalid windows, and invalid comparison definitions must fail with package exceptions.

### 5.3 Formula References And Dependency Graphs

Add explicit formula references:

- `ValueObjects/FormulaReference.php`
- `Services/FormulaGraphService.php`
- `ValueObjects/FormulaGraph.php`

String operands remain prepared input names. Formula dependencies must use `FormulaReference`, not string conventions.

Serialized reference shape:

```php
['formula' => 'metric.margin_delta']
```

Graph responsibilities:

- find formula dependencies
- reject missing formula references
- reject circular dependencies
- produce deterministic topological evaluation order

Dependency results should become prepared scalar inputs for downstream formulas during batch evaluation. The generated input name should be formula-id based and stable, but internal to the batch run.

### 5.4 Batch Evaluation

Add:

- `Services/BatchFormulaEvaluatorService.php`
- `ValueObjects/MetricEvaluationBatchResult.php`
- `ValueObjects/MetricEvaluationOutcome.php`
- `Enums/MetricResultStatus.php`

`BatchFormulaEvaluatorService` accepts a catalog or formula list plus prepared inputs and optional evaluation options.

It should:

- evaluate formulas in dependency order
- make successful dependency results available to dependent formulas
- skip dependent formulas when required dependency outcomes are not `available`
- return a typed batch result keyed by formula id
- preserve deterministic ordering
- never fabricate fallback metric values

### 5.5 Result Status Semantics

Add statuses:

- `available`
- `no_data`
- `not_available`
- `error`

Status inference applies only in the batch/outcome layer. Single-formula evaluation continues to throw.

Default status mapping:

| Source | Status | Reason |
|---|---|---|
| Successful `MetricResult` | `available` | Value was calculated. |
| `InsufficientDataException` | `no_data` | Required input exists but usable values/window data are empty or too small. |
| `MissingInputException` | `not_available` | Required prepared input is absent for this run. |
| Valid explicit window with no matching points | `no_data` | Window is valid but the series has no usable points in range. |
| Invalid window definition | `error` | Formula/config is invalid. |
| `DivideByZeroMetricException` | `not_available` | Formula is valid but current inputs make the metric undefined. |
| `InvalidNumericValueException` | `error` | Caller supplied malformed or non-finite numeric data. |
| `TypeMismatchException` | `error` | Formula and input contract are incompatible. |
| `FormulaValidationException` | `error` | Formula definition is invalid. |
| Unexpected `Throwable` | `error` | Defensive batch boundary. |

Dependent formulas should receive `not_available` when one or more required formula dependencies are not `available`.

### 5.6 Audit And Explanation Output

Add optional deterministic audit traces:

- `ValueObjects/MetricAuditTrace.php`
- `ValueObjects/MetricEvaluationOptions.php`

Trace output may include:

- formula id
- operation
- original operands
- resolved operands
- named inputs used
- dependency results used
- excluded values with reason, when exclusion is explicit
- precision policy
- window/comparison metadata
- result value
- inferred status
- exception class/code/message when failed

Trace output must not include runtime timestamps unless supplied by the caller. Trace output must not include domain labels or interpretation.

### 5.7 Fingerprints

Add reproducibility helpers:

- `Services/MetricRunFingerprintService.php`
- `ValueObjects/MetricRunFingerprint.php`

Fingerprint inputs:

- serialized formulas/catalog
- prepared inputs and series points
- precision policies
- windows and comparisons
- formula dependency graph
- package version when provided by caller
- caller-supplied run metadata when requested

The service returns stable hashes only. It must not cache, persist, or invalidate data. Storage and cache backends remain caller-owned.

### 5.8 Rounding Modes

Expand `RoundingMode` and honor it in `NumericValueService`.

Minimum supported modes:

- `half_up`
- `half_down`
- `half_even`
- `half_odd`
- `toward_zero`
- `away_from_zero`

The current implementation always rounds half-up. That must be corrected so `PrecisionPolicy` is authoritative.

### 5.9 Time-Series Period Handling

Replace raw lexical comparison with typed period handling.

Add:

- `ValueObjects/PeriodKey.php`
- `Enums/PeriodGranularity.php`
- `Services/PeriodComparatorService.php`

Supported period shapes:

- date: `YYYY-MM-DD`
- month: `YYYY-MM`
- quarter: `YYYY-QN`
- year: `YYYY`

Rules:

- period keys must parse into a supported granularity
- one series must not mix incompatible period granularities
- ordering must use parsed period values, not raw string comparison
- explicit windows must use compatible period keys
- unsupported fiscal calendars remain caller-owned

## 6. Data Flow

1. Caller prepares scalar inputs and time-series inputs.
2. Caller builds formulas directly or provides arrays to the serializer/catalog builder.
3. Catalog validates unique identifiers.
4. Graph service validates formula references and produces evaluation order.
5. Batch evaluator evaluates formulas through the existing strict evaluator.
6. Successes become `available` outcomes.
7. Package exceptions become inferred status outcomes.
8. Available formula dependency results are made available to downstream formulas.
9. Optional audit traces and fingerprints are generated deterministically.
10. Caller maps outcomes to application display, persistence, or reporting.

## 7. Error Handling

The package must continue to use package-specific exceptions for strict evaluation.

Batch evaluation must capture only calculation-time failures and translate them to outcomes. It must not hide programmer errors during catalog construction, serialization, or graph validation unless explicitly evaluating in batch mode.

No outcome may contain a synthetic successful value for `no_data`, `not_available`, or `error`.

## 8. Testing Strategy

Required tests:

- catalog rejects duplicate formula ids
- array serialization round-trips nested formulas, references, windows, comparisons, precision, and metadata
- graph service orders dependencies deterministically
- graph service rejects missing references and cycles
- batch evaluator evaluates independent formulas
- batch evaluator evaluates dependency formulas in order
- dependency failure marks downstream formulas as `not_available`
- approved exception-to-status mapping is enforced
- audit traces include requested calculation evidence and remain deterministic
- fingerprints are stable for equivalent inputs and differ when formula/input/window/precision changes
- all rounding modes are honored
- period key parsing rejects malformed and mixed periods
- explicit windows use parsed period order
- neutral banding returns caller-supplied bands without domain labels
- Layer 1 architecture test continues to reject Laravel/Symfony dependencies

## 9. Backward Compatibility

This application is still in active development, but package consumers already use the current single-formula API.

Keep the existing single evaluation contract unless implementation proves a hard conflict. Additive APIs are preferred because they let Atomy-Q refactor incrementally while preserving strict calculation behavior.

## 10. Open Implementation Notes

Implementation will be split into these slices:

1. rounding and period-key correctness
2. formula serialization and catalog
3. formula references and dependency graph
4. batch evaluation and status inference
5. audit trace and fingerprint helpers
6. neutral banding helpers
7. adapter-side Laravel bindings outside `packages/MetricEngine`
