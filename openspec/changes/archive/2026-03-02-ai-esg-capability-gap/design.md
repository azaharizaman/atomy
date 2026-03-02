## Context

The research document (*openai-esg-deep-research.md*) identifies that true AI-driven ESG in ERP requires moving from "manual spreadsheet marathons" to an integrated "single source of truth." This design addresses the missing technical layers in the Atomy monorepo to enable this transition. We need to bridge the gap between atomic calculations (Layer 1) and cross-departmental AI analytics (Layer 2).

## Goals / Non-Goals

**Goals:**
*   **Decouple Raw Data from Domain Metrics**: Establish `Nexus\SustainabilityData` to handle the noise of IoT/sensor feeds before they are promoted to audited ESG scores.
*   **Standardize Regulatory Mapping**: Use `Nexus\ESGRegulatory` to decouple business logic from volatile reporting frameworks (CSRD vs. NSRF).
*   **Enable AI Scenario Planning**: Design an orchestrator pipeline that can feed current ERP state (Finance/Manufacturing) into ML models for ESG forecasting.
*   **Automate Unstructured Ingestion**: Leverage `Nexus\Document` and `Nexus\MachineLearning` to extract data from utility invoices and environmental permits.

**Non-Goals:**
*   Developing custom IoT firmware (we handle the ingest API/schema).
*   Implementing real-time blockchain for Scope 3 (blockchain logic is deferred to specific adapters).
*   Replacing existing `Nexus\ESG` logic (we extend it with data sources).

## Decisions

### 1. The "Lakehouse" Atomic Package (`SustainabilityData`)
*   **Decision**: Create a dedicated Layer 1 package for raw sustainability events.
*   **Rationale**: Sustainability data is high-volume and high-velocity (e.g., IoT sensors). It should not pollute the audited `Nexus\ESG` domain until it has been cleaned and aggregated.
*   **Structure**: Uses a `SustainabilityEventInterface` to capture `timestamp`, `source_id`, `value`, and `unit`.

### 2. Strategy-Based Regulatory Mapping
*   **Decision**: Use a "Registry" pattern in `ESGRegulatory` to map `MetricID` to `FrameworkTag`.
*   **Rationale**: Regulatory requirements change annually. By mapping at an abstract layer, we can update the "CSRD 2025" tag set without changing the "Carbon Emission" entity logic.

### 3. Asynchronous "Extraction -> Normalization" Pipeline
*   **Decision**: Use the existing `Nexus\EventStream` to trigger ESG extraction from `Document` uploads.
*   **Rationale**: LLM extraction from energy bills is high-latency. An async pipeline ensures the ERP remains responsive while the "AI ESG Agent" processes unstructured files in the background.

### 4. Predictive VOs
*   **Decision**: Add `ForecastedSustainabilityScore` to the `ESG` package.
*   **Rationale**: Research shows executives need to model "carbon prices alongside cash costs." This VO will store `target_date`, `confidence_interval`, and `simulation_parameters`.

## Risks / Trade-offs

*   **Risk**: Data Overload (Too many IoT events crashing the system).
    *   **Mitigation**: Implement a "Sampling/Aggregation" layer in `SustainabilityData` that collapses high-frequency events into hourly/daily summaries before persistence.
*   **Risk**: AI Hallucination in Utility Invoices.
    *   **Mitigation**: Mandatory "Confidence Score" metadata on all extractions. Values with confidence < 0.85 are sent to a "Human-in-the-Loop" (HITL) queue in `ESGOperations`.
*   **Risk**: Framework Volatility.
    *   **Mitigation**: The `ESGRegulatory` package will strictly use semver for framework mapping sets (e.g., `nsrf:v1.0`).

## Open Questions
*   Should `SustainabilityData` support binary blobs for satellite/geospatial data, or just numerical/textual sensor data? (Decision: Numerical/Textual for V1).
*   How do we handle multi-tenant carbon tax simulation? (Decision: Parameters passed via the `ESGOperations` coordinator).
