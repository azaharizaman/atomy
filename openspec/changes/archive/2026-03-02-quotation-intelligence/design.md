## Context

The `QuotationIntelligence` system is designed to bridge the gap between structured ERP procurement and unstructured real-world vendor quotes. Currently, `Nexus\Procurement` and `Nexus\ProcurementOperations` handle standard RFQ lifecycles but assume data is entered manually or via a structured portal. This design introduces a new intelligence layer to ingest, normalize, and score unstructured documents (PDF/Excel), providing the auditability required by ISO 20400 and GAAP.

## Goals / Non-Goals

**Goals:**
*   Establish a **Layer 2 Orchestrator** (`QuotationIntelligence`) to coordinate ingestion, extraction, normalization, and scoring.
*   Define **Immutable Value Objects** (`QuoteSnippet`, `ExtractionEvidence`) to ensure GAAP/IFRS auditability.
*   Design an **Async Event-Driven Pipeline** for handling potentially slow AI/OCR tasks without blocking user workflows.
*   Integrate **Semantic Mapping** using `Nexus\MachineLearning` to normalize disparate vendor product descriptions to UNSPSC/eCl@ss.
*   Implement **Deep Normalization** for UoM and Currency using `Nexus\Uom` and `Nexus\Currency`.

**Non-Goals:**
*   Replacing the existing `Nexus\Procurement` RFQ domain logic (we extend/feed it).
*   Building a custom OCR/Extraction engine from scratch (we leverage `Nexus\Document` and `Nexus\MachineLearning`).
*   Real-time synchronous processing of large PDF bundles (must be async).

## Decisions

### 1. New Layer 2 Orchestrator (`QuotationIntelligence`)
*   **Decision**: Create a dedicated Orchestrator package `QuotationIntelligence` instead of adding logic to `ProcurementOperations`.
*   **Rationale**: The intelligence workflow (OCR -> Extraction -> Normalization -> Scoring) is distinct from the transactional procurement workflow (Requisition -> PO -> GRN). Segregating them adheres to the Single Responsibility Principle and allows the intelligence layer to scale independently.
*   **Alternatives Considered**: Adding services to `ProcurementOperations`. Rejected due to risk of bloating the transactional core with heavy AI dependencies.

### 2. Immutable Evidence Value Objects
*   **Decision**: Model `ExtractionEvidence` and `QuoteSnippet` as immutable Value Objects.
*   **Rationale**: Auditability is a primary requirement. Evidence (e.g., "Page 3, Line 15, Bounding Box [x,y,w,h]") must never change once captured. VOs enforce this immutability at the language level.

### 3. Async Event-Driven Ingestion
*   **Decision**: Use an event-driven architecture (`QuoteUploaded` -> `QuoteExtractionJob`) for document processing.
*   **Rationale**: OCR and LLM inference are computationally expensive and high-latency. Blocking the HTTP request would lead to timeouts and poor UX.
*   **Implementation**: `QuotationIntelligence` will listen for `DocumentUploaded` events tagged with `type: vendor_quote` and dispatch extraction jobs.

### 4. Taxonomy-Based Semantic Mapping
*   **Decision**: Normalize all products to **UNSPSC** (or configured tenant taxonomy) using BERT-based classification.
*   **Rationale**: String matching ("Laptop" vs "Notebook") fails for heterogeneous tail spend. Semantic mapping is the only viable path to "apples-to-apples" comparison.
*   **Integration**: `Nexus\MachineLearning` will expose a `classify(string $description): TaxonomyCode` method.

## Risks / Trade-offs

*   **Risk**: AI Hallucination (Incorrect extraction or mapping).
    *   **Mitigation**: Implement a "Human-in-the-Loop" (HITL) review step. Any extraction with a confidence score < 0.90 is flagged for manual verification. All AI-generated values are stored separately from user-verified values (`ai_value` vs `verified_value`).
*   **Risk**: Performance overhead of UoM conversion for large quotes.
    *   **Mitigation**: Cache `ConversionRule` lookups in `Nexus\Uom`.
*   **Risk**: Regulatory fragmentation (EU AI Act vs SOX).
    *   **Mitigation**: The "Evidence Traceability" architecture is designed to be a superset of requirements, capturing full lineage (Input -> Model -> Output -> Verification).

## Migration Plan

1.  **Phase 1**: Deploy `QuotationIntelligence` orchestrator and DTOs.
2.  **Phase 2**: Integrate `Nexus\Document` for ingestion.
3.  **Phase 3**: Enable AI extraction and Normalization.
4.  **Rollback**: The system is additive. Disabling the `QuotationIntelligence` feature flag reverts users to manual quote entry.
