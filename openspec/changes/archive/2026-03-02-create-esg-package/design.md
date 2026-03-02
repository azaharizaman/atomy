## Context

The Atomy (Nexus) project requires a framework-agnostic foundation for sustainability tracking. This package, `Nexus\ESG`, will be a Layer 1 package responsible for defining the domain language of ESG: metrics, ratings, and certifications. It will provide the mathematical and validation utilities used by Layer 2 orchestrators (like a future `ESGOperations` or existing `QuotationIntelligence`).

## Goals / Non-Goals

**Goals:**
*   Establish **Atomic Value Objects** for sustainability data (e.g., `EmissionsAmount`, `SafetyIncidentRate`).
*   Define **Pure Domain Contracts** for scoring and validation.
*   Implement a **Weighting Engine** for composite ESG scores (Environmental vs Social vs Governance).
*   Provide a **Carbon Normalizer** utility for CO2e calculations.
*   Ensure **Zero Framework Dependencies** (no Laravel/Symfony code).

**Non-Goals:**
*   Building UI dashboards for sustainability (handled by apps).
*   Implementing persistent database models (handled by Layer 3 adapters).
*   Performing actual OCR or PDF extraction (handled by `Nexus\Document` and orchestrators).
*   Integrating with 3rd party ESG APIs (handled by Layer 3 adapters or connectors).

## Decisions

### 1. Dimension-Based Scoring
*   **Decision**: Structure the domain around the three core dimensions: Environmental, Social, and Governance.
*   **Rationale**: This matches international standards (CSRD, GRI) and allows orchestrators to request sub-scores (e.g., "Give me just the Environmental score for this vendor").

### 2. Weighted Aggregation Engine
*   **Decision**: Use a strategy pattern for scoring weights.
*   **Rationale**: Different industries have different ESG priorities. A manufacturing tenant might weight Environmental at 60%, while a law firm might weight Governance at 60%. The engine must be configurable per tenant.

### 3. Metric Value Objects
*   **Decision**: Use specialized Value Objects for every metric type rather than generic floats.
*   **Rationale**: To prevent "Unit Mismatch" errors. An `EmissionsAmount` VO can ensure that "grams of CO2" aren't accidentally added to "tons of CO2" without explicit normalization.

### 4. ISO 20400 Compliance Interface
*   **Decision**: Define an `EvaluatableInterface` that domain entities (like `Vendor` or `Product`) can implement to be processed by the ESG engine.
*   **Rationale**: This allows the `ESG` package to score anything in the system without needing to know about `Procurement` or `Product` packages directly.

## Risks / Trade-offs

*   **Risk**: Mathematical overflow in large-scale carbon aggregation.
    *   **Mitigation**: Use `bcmath` or high-precision math strings for core calculations, encapsulated within VOs.
*   **Risk**: Over-engineering for simple cases.
    *   **Mitigation**: Focus on the core ISO 20400 metrics first; allow custom metrics via an extensible interface.
*   **Risk**: Data fragmentation.
    *   **Mitigation**: Ensure all VOs are `Auditable` and `Serializable` to maintain consistency across orchestrators.
