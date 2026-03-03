## Why

The current procurement landscape faces a significant "Enterprise Gap": legacy systems (SAP Ariba, Coupa) demand structured data and rigid templates, while real-world "tail spend" relies on messy, unstructured vendor quotes (PDFs, Excel). This manual friction prevents true "apples-to-apples" comparison, leading to sub-optimal sourcing decisions and a lack of auditability required by modern standards like ISO 20400 (Sustainable Procurement) and GAAP/IFRS. **QuotationIntelligence** solves this by using AI to autonomously ingest, normalize, and score unstructured quotes, providing a transparent, auditable, and mathematically rigorous decision support system.

## What Changes

*   **New Layer 2 Orchestrator**: Introduce `QuotationIntelligence` to coordinate the end-to-end flow from document ingestion to scored comparison.
*   **Unstructured Ingestion**: Enable the system to accept raw PDF and Excel files from vendors, bypassing the need for supplier portals or templates.
*   **AI-Driven Extraction**: Implement `Nexus\MachineLearning` pipelines to extract line items, quantities, and prices from diverse formats.
*   **Semantic Mapping**: Use BERT-based classification to map disparate vendor descriptions (e.g., "Notebook" vs. "Laptop") to a unified UNSPSC/eCl@ss taxonomy.
*   **Deep Normalization**:
    *   **UoM**: Convert all vendor-specific units (e.g., "Box of 12") to the RFQ's base unit using `Nexus\Uom`.
    *   **Currency**: Normalize all prices to the tenant's base currency using `Nexus\Currency` with lock-date support.
*   **Evidence Traceability**: Introduce `QuoteSnippet` and `ExtractionEvidence` Value Objects to link every normalized data point back to its specific source region (page/coordinates) in the original document.
*   **Risk & Anomaly Detection**: Analyze quotes for "predatory pricing" (outliers), hidden costs (Incoterms deviations), and non-standard terms using `Nexus\MachineLearning`.
*   **Multi-Criteria Scoring**: Implement a weighted scoring engine (Price, Quality, Risk, ESG) to support ISO 20400 "Best Value" decision-making.

## Capabilities

### New Capabilities
<!-- Capabilities being introduced. Replace <name> with kebab-case identifier (e.g., user-auth, data-export, api-rate-limiting). Each creates specs/<name>/spec.md -->
- `quotation-ingestion`: Handling the upload, validation, and initial processing of unstructured vendor quote files.
- `semantic-mapping`: The AI-driven process of mapping extracted line items to standard taxonomies (UNSPSC/eCl@ss).
- `quote-normalization`: The mathematical conversion of diverse units (UoM) and currencies into a unified comparison basis.
- `risk-assessment`: The detection of pricing anomalies, term deviations, and vendor risk signals within quotes.
- `evidence-traceability`: The creation and management of audit trails linking normalized data to source document snippets.

### Modified Capabilities
<!-- Existing capabilities whose REQUIREMENTS are changing (not just implementation).
     Only list here if spec-level behavior changes. Each needs a delta spec file.
     Use existing spec names from openspec/specs/. Leave empty if no requirement changes. -->
- `rfq-management`: Extending the RFQ process to support unstructured submissions and multi-criteria evaluation.

## Impact

*   **Packages Affected**:
    *   `Nexus\Procurement`: New DTOs/VOs for normalized quotes and snippets.
    *   `Nexus\MachineLearning`: New inference pipelines for extraction and classification.
    *   `Nexus\Document`: Enhanced OCR and coordinate extraction.
    *   `Nexus\Uom` & `Nexus\Currency`: Deep integration for normalization logic.
*   **Orchestrators**:
    *   `QuotationIntelligence`: New orchestrator.
    *   `ProcurementOperations`: Integration points for RFQ lifecycle.
*   **Data Models**: New tables/collections for `normalized_quote_lines`, `quote_snippets`, and `extraction_evidence`.
*   **Compliance**: Direct support for ISO 20400 (Total Cost of Ownership) and SOX/GAAP audit requirements via traceability.
