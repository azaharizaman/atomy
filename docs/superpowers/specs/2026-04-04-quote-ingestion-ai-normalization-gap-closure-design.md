# Design Spec: Quote Ingestion and AI Normalization Gap Closure

**Date:** 2026-04-04
**Status:** Draft
**Author:** Gemini CLI
**Gap Reference:** Gap #4 - Quote Ingestion and AI Normalization (Alpha Progress Analysis 2026-03-31)

---

## 1. Context and Problem Statement

The Atomy-Q Alpha analysis identifies a critical gap in the "golden path" of the quote lifecycle. While the UI shells exist, the backend for quote ingestion and normalization is largely stubbed or mocked with static "seed" data. 

Specifically:
- The **LLM/Quotation-Intelligence pipeline** is not wired to the live API.
- The **reparse** functionality is a placeholder that doesn't trigger real extraction or mapping.
- **Normalization data** (source lines) is often empty because no real extraction or auto-mapping occurs during ingestion.

### Goal
Implement a fully functional, end-to-end quote ingestion and normalization pipeline for the Alpha release. This pipeline will use the existing `QuotationIntelligence` orchestrator (L2) and a "Smart Mock" (L3) to demonstrate automatic semantic mapping of quote lines to RFQ lines, real-time normalization, and robust reparse logic.

---

## 2. Goals & Success Criteria

### Primary Goals
1. **Wired Pipeline:** Connect the `QuoteIngestionOrchestrator` to the `QuotationIntelligenceCoordinator` in the API layer.
2. **Smart AI Mock:** Implement a "Smart Mock" that performs RFQ-aware extraction and automatic semantic mapping to demonstrate the full intelligence workflow.
3. **Automatic Linking:** Automatically link extracted quote lines to RFQ line items if confidence is high, populating the "Normalized" view in the UI.
4. **Delta Reparse:** Implement a functional `reparse` action that processes unmapped lines while preserving manual overrides.
5. **Alpha Readiness:** Ensure high code quality, strict typing, and 100% tenant isolation for all new components.

### Success Criteria
- Uploading a quote triggers an async job that populates `normalization_source_lines`.
- Extracted lines are automatically mapped to corresponding `rfq_line_item_id` values based on the "Smart Mock" logic.
- The `reparse` action correctly identifies and processes only unmapped or unresolved lines.
- All operations are strictly scoped to the authenticated tenant.
- Feature tests confirm the end-to-end flow from upload to normalization readiness.

---

## 3. Architecture and Layering

The implementation follows the Atomy Three-Layer Architecture:

### Layer 2: Orchestrators (Nexus)
- **`QuotationIntelligenceCoordinator`:** The core engine for document analysis, mapping, normalization, and risk assessment.
- **`QuoteIngestionOrchestrator`:** High-level coordinator that manages the quote submission state machine and persists results.

### Layer 3: Adapters & Application (Atomy-Q API)
- **Bridge Adapters:** New adapters in the API app that implement the `QuotationIntelligence` domain interfaces:
    - `OrchestratorDocumentRepository`: Read-only bridge to `QuoteSubmission` model.
    - `OrchestratorTenantRepository`: Bridge to `Tenant` model.
    - `OrchestratorProcurementManager`: Bridge to `Rfq` and `RfqLineItem` models.
- **Smart Mock Components:**
    - `MockContentProcessor`: Implements `OrchestratorContentProcessorInterface`. Generates quote data aligned with the target RFQ.
    - `MockSemanticMapper`: Implements `SemanticMapperInterface`. Simulates high-confidence mapping to RFQ line IDs.
- **Laravel Jobs & Controllers:**
    - `ProcessQuoteSubmissionJob`: Dispatches the orchestrator and handles retries.
    - `QuoteSubmissionController` & `NormalizationController`: Expose the wired functionality to the WEB frontend.

---

## 4. Detailed Component Design

### 4.1. The "Smart Mock" Extraction
The `MockContentProcessor` will be "RFQ-aware":
- It retrieves the line items for the specific RFQ linked to the `QuoteSubmission`.
- It generates 3-5 quote lines that mimic the RFQ lines but with realistic variations (e.g., "Pump Model X500" vs "X500 Industrial Pump").
- It assigns high confidence (90%+) to simulated matches.

### 4.2. Automatic Mapping & Linking
The Intelligence pipeline will be configured to:
- Automatically populate `rfq_line_item_id` in the `normalization_source_lines` table when a high-confidence match is returned by the semantic mapper.
- This immediately makes the lines "Normalized" and visible in the comparison workspace.

### 4.3. State Machine Transitions
- `uploaded` -> `extracting`: Job started.
- `extracting` -> `normalizing`: "Smart Mock" extraction complete.
- `normalizing` -> `ready`: All lines successfully mapped to RFQ lines.
- `normalizing` -> `needs_review`: Conflicts detected (e.g., quantity mismatch) or low confidence.
- `failed`: Technical failure or simulated error.

### 4.4. Reparse (Delta Processing)
The `reparse` logic will:
1. Reset `QuoteSubmission` status to `uploaded`.
2. Delete existing `normalization_source_lines` that have **no** manual overrides and **no** resolved conflicts.
3. Re-dispatch the `ProcessQuoteSubmissionJob`.
4. The orchestrator will only attempt to "map" lines that are currently unmapped in the database.

---

## 5. Security and Tenant Isolation

- **Scoped Bridges:** All bridge adapters (`Document`, `Tenant`, `Procurement`) will receive the `tenant_id` and MUST include `where('tenant_id', $tenantId)` in all Eloquent queries.
- **File Access Validation:** Before processing, the orchestrator will verify the `tenant_id` on the `QuoteSubmission` record matches the processing context.

---

## 6. Implementation Plan (High-Level)

1. **Bridge Adapters:** Create the L3 adapters in `apps/atomy-q/API/app/Adapters/QuotationIntelligence`.
2. **Smart Mock:** Implement `MockContentProcessor` and `MockSemanticMapper` in the API app.
3. **Pipeline Wiring:** Update `QuoteSubmissionController` and `ProcessQuoteSubmissionJob` to use the `QuotationIntelligenceCoordinator`.
4. **Normalization Logic:** Update the persistence logic to ensure auto-mapped lines are saved with their `rfq_line_item_id`.
5. **Reparse Logic:** Implement the delta-cleaning logic in the controller/orchestrator.
6. **Verification:** Write end-to-end feature tests covering the successful mapping and the delta reparse flow.

---

## 7. Compliance and Quality

- **Strict Typing:** All new methods will use PHP 8.3 type hints.
- **Auditability:** The `DecisionTrail` will be populated for every auto-mapping action.
- **Test Coverage:** Minimum 80% coverage for new adapters and 100% for the end-to-end feature tests.
