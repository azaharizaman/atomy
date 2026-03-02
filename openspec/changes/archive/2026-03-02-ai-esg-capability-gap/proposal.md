## Why

The recent implementation of the **Nexus\ESG** package (Layer 1) established the mathematical and domain foundation for sustainability tracking (Carbon, VOs, Scoring). However, to reach the **AI-driven ESG capability** outlined in the research (*openai-esg-deep-research.md*), we lack the critical "Data Collection" and "Analytical" layers that bridge ERP silos. Specifically, the system needs infrastructure for **Automated Integration (IoT/Sensors)**, **Unstructured Ingestion (NLP for Energy Bills)**, and **Scenario Planning (Carbon Price Stress Testing)**. These gaps prevent the transition from static reporting to agentic, real-time sustainability orchestration.

## What Changes

*   **Layer 1 (Atomic) Enhancements**:
    *   **Data Lakehouse Foundation**: Introduce `Nexus\SustainabilityData` package to own the raw, un-normalized sustainability event stream (IoT, external feeds).
    *   **Regulatory Framework Engine**: New package `Nexus\ESGRegulatory` to map raw metrics to specific framework fields (CSRD, NSRF, IFRS S2).
    *   **Stress Testing VO**: New VOs in `Nexus\ESG` for `CarbonPriceSimulation` and `EnergyIntensityProjection`.
*   **Layer 2 (Orchestrator) Innovations**:
    *   **`ESGOperations` Orchestrator**: A new orchestrator to coordinate between `SustainabilityData`, `ESG`, and `MachineLearning`.
    *   **Ingestion Pipeline**: Automated extraction from utility bills/invoices via `Nexus\Document` and `Nexus\MachineLearning`.
    *   **Threshold Alerting**: Cross-domain monitoring (e.g., energy spike detection in `Manufacturing` feeding into `ESG` dashboards).
*   **Agentic Capabilities**:
    *   **Scenario Planning Service**: AI-assisted "what-if" modeling for energy transition and carbon taxation.

## Capabilities

### New Capabilities
- `esg-data-lakehouse`: Capturing and governing the raw sustainability data stream from IoT, utility bills, and manual logs.
- `regulatory-mapping-engine`: Auto-tagging domain metrics to international reporting fields (e.g., matching a Boiler emission to ESRS E1-6).
- `scenario-planning-simulation`: AI-driven modeling of business outcomes based on varying carbon prices and energy efficiencies.
- `unstructured-esg-extraction`: Using NLP/LLMs to parse environmental permits, energy contracts, and utility invoices into structured ESG metrics.

### Modified Capabilities
- `sustainability-metric-definition`: Extending to support raw sensor metadata and temporal frequency (e.g., 15-min IoT intervals).
- `esg-scoring-logic`: Enhancing to support predictive scoring (forecasted vs. current).

## Impact

*   **New Packages**: `Nexus\SustainabilityData`, `Nexus\ESGRegulatory`.
*   **New Orchestrator**: `Nexus\ESGOperations`.
*   **Dependencies**: Deep integration with `Nexus\MachineLearning` for forecasting and `Nexus\Document` for unstructured extraction.
*   **Monorepo Integration**: `Manufacturing` and `SupplyChain` packages will now push events to the `SustainabilityData` lakehouse.
*   **Compliance**: Direct path to meeting Bursa Malaysia NSRF (FY2025) and EU CSRD traceability requirements.
