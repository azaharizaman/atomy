## Why

Sustainability and Environmental, Social, and Governance (ESG) compliance are rapidly evolving from optional "corporate responsibility" into mandatory regulatory requirements (e.g., EU CSRD, ISO 20400). Currently, the Atomy (Nexus) ecosystem lacks a centralized "Sustainability Truth" package, leading to fragmented metrics across Procurement, HRM, and Finance. Creating a dedicated **Nexus\ESG** Layer 1 package establishes a framework-agnostic foundation for carbon footprinting, labor compliance tracking, and governance auditing, enabling the whole enterprise to source and report sustainably.

## What Changes

*   **New Layer 1 Package**: Create `packages/ESG` as a pure PHP, framework-agnostic package.
*   **Sustainability Metrics Domain**: Define core entities and value objects for Environmental (Carbon, Energy, Water), Social (Labor, Safety, Diversity), and Governance (Ethics, Corruption, Transparency) data.
*   **ISO 20400 Foundation**: Implement standard-aligned calculation logic for composite ESG scoring.
*   **Carbon Math Utility**: Specialized logic for Scope 1, 2, and 3 emissions normalization.
*   **Certification Lifecycle**: Infrastructure to track and validate ISO 14001, 45001, and other industry-standard certifications.

## Capabilities

### New Capabilities
- `sustainability-metric-definition`: Defining and managing standardized ESG KPIs across the enterprise.
- `esg-scoring-logic`: Implementing the mathematical weighting and aggregation of diverse ESG data into unified scores.
- `carbon-footprint-modeling`: Normalizing and calculating tCO2e across Scope 1, 2, and 3 emissions.
- `certification-management`: Tracking and validating the authenticity and validity of supplier/asset sustainability certificates.

### Modified Capabilities
- (None)

## Impact

*   **New Package**: `packages/ESG`
*   **Dependencies**: Adds `nexus/esg` as a dependency for future orchestrators.
*   **Compliance**: Provides the technical backbone for ISO 20400, EU CSRD, and GAAP sustainability reporting.
*   **Data Models**: Establishes schemas for `sustainability_metrics`, `esg_ratings`, and `carbon_emissions`.
