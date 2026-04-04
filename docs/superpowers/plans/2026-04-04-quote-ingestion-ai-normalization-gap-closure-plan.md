# Quote Ingestion and AI Normalization Gap Closure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the gap in quote ingestion and AI normalization by wiring the `QuotationIntelligence` pipeline and implementing a "Smart Mock" for RFQ-aware extraction and mapping.

**Architecture:** Use the Atomy Three-Layer Architecture. Implement bridge adapters in Layer 3 (API) to connect the `QuotationIntelligenceCoordinator` (Layer 2) to live application models. Update the `QuoteIngestionOrchestrator` to coordinate the full intelligence flow.

**Tech Stack:** PHP 8.3, Laravel 12, Nexus Orchestrators, Eloquent ORM.

---

## File Structure

### New Files (L3 Adapters & Mocks)
- `apps/atomy-q/API/app/Adapters/QuotationIntelligence/OrchestratorDocumentRepository.php`
- `apps/atomy-q/API/app/Adapters/QuotationIntelligence/OrchestratorTenantRepository.php`
- `apps/atomy-q/API/app/Adapters/QuotationIntelligence/OrchestratorProcurementManager.php`
- `apps/atomy-q/API/app/Adapters/QuotationIntelligence/AtomyDecisionTrailWriter.php`
- `apps/atomy-q/API/app/Adapters/QuotationIntelligence/MockContentProcessor.php`
- `apps/atomy-q/API/app/Adapters/QuotationIntelligence/MockSemanticMapper.php`
- `apps/atomy-q/API/app/Adapters/QuotationIntelligence/Concerns/WrapsModels.php`
- `apps/atomy-q/API/database/migrations/2026_04_04_000000_add_intelligence_fields_to_normalization_source_lines.php`

### Modified Files
- `orchestrators/QuoteIngestion/src/QuoteIngestionOrchestrator.php`
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`
- `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- `apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php`

---

## Task 1: Schema & Model Preparation

**Files:**
- Create: `apps/atomy-q/API/database/migrations/2026_04_04_000000_add_intelligence_fields_to_normalization_source_lines.php`

- [ ] **Step 1: Create migration for intelligence fields**
Add `ai_confidence` (decimal 5,2), `taxonomy_code` (string), and `mapping_version` (string) to `normalization_source_lines`.

- [ ] **Step 2: Run migration**
`cd apps/atomy-q/API && php artisan migrate`

- [ ] **Step 3: Update Model $fillable**
Update `apps/atomy-q/API/app/Models/NormalizationSourceLine.php` to include the new fields.

- [ ] **Step 4: Commit**
`git add apps/atomy-q/API/database/migrations/ apps/atomy-q/API/app/Models/NormalizationSourceLine.php && git commit -m "feat(api): add intelligence fields to normalization source lines schema"`

---

## Task 2: End-to-End Test Baseline (TDD)

**Files:**
- Create: `apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php`

- [ ] **Step 1: Write the failing feature test**
Upload a quote, trigger processing, and assert that `normalization_source_lines` are created with correct `rfq_line_item_id` values from the smart mock. Assert `reparse` behavior.

- [ ] **Step 2: Run verification**
`cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/QuoteIngestionIntelligenceTest.php`
Expected: FAIL (No source lines or incorrect mapping).

- [ ] **Step 3: Commit**
`git add apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php && git commit -m "test(atomy-q): end-to-end quote ingestion and normalization baseline"`

---

## Task 3: Bridge Adapters - Core Repositories & Governance

**Files:**
- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/Concerns/WrapsModels.php`
- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/OrchestratorDocumentRepository.php`
- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/OrchestratorTenantRepository.php`
- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/OrchestratorProcurementManager.php`
- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/AtomyDecisionTrailWriter.php`

- [ ] **Step 1: Create the model wrapping concern**
Implement traits or simple DTOs to map Eloquent models to Orchestrator interfaces (`OrchestratorTenantInterface`, `QuotationDocumentInterface`, `OrchestratorRequisitionInterface`, `OrchestratorRequisitionLineInterface`).

- [ ] **Step 2: Implement Core Repositories**
Implement `OrchestratorDocumentRepository` and `OrchestratorTenantRepository` with strict `tenant_id` scoping.

- [ ] **Step 3: Implement OrchestratorProcurementManager**
Bridge `getRequisition` to the `Rfq` model. **CRITICAL:** Ensure `Rfq` and `RfqLineItem` lookups are strictly scoped by `tenant_id`.

- [ ] **Step 4: Implement AtomyDecisionTrailWriter**
Bridge `DecisionTrailWriterInterface` to the `DecisionTrailEntry` model.

- [ ] **Step 5: Commit**
`git add apps/atomy-q/API/app/Adapters/QuotationIntelligence/ && git commit -m "feat(api-adapters): bridge repositories and governance with strict tenant isolation"`

---

## Task 4: Smart Mock - Content Processor & Semantic Mapper

**Files:**
- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/MockContentProcessor.php`
- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/MockSemanticMapper.php`

- [ ] **Step 1: Implement MockContentProcessor**
Implement `analyze`. Retrieve the `rfq_line_items` for the target RFQ and return 3-5 quote lines. **CRITICAL:** Ensure RFQ lookup is strictly scoped by `tenant_id`.

- [ ] **Step 2: Implement MockSemanticMapper**
Implement `mapToTaxonomy`. Return high-confidence mapping (90%+) and a UNSPSC code for simulated matches.

- [ ] **Step 3: Commit**
`git add apps/atomy-q/API/app/Adapters/QuotationIntelligence/ && git commit -m "feat(api-adapters): smart mock for rfq-aware extraction"`

---

## Task 5: Orchestrator Wiring & Persistence Logic

**Files:**
- Modify: `orchestrators/QuoteIngestion/src/QuoteIngestionOrchestrator.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Update AppServiceProvider bindings**
Bind new repositories, writer, and the coordinator. Swap `VertexAIMockProvider` for the new mock setup.

- [ ] **Step 2: Update Orchestrator Constructor**
Replace `QuoteExtractionServiceInterface` with `QuotationIntelligenceCoordinatorInterface`.

- [ ] **Step 3: Update Orchestrator Process Logic**
Implement status transitions (`extracting` -> `normalizing` -> `ready/needs_review`). Call `processQuote` and handle results.

- [ ] **Step 4: Implement Intelligent Persistence Logic**
Update `persistSourceLines` to:
1.  Persist `rfq_line_item_id` for auto-linking.
2.  Use `updateOrCreate` to handle "Delta Reparse" safely.
3.  Persist `ai_confidence`, `taxonomy_code`, and `mapping_version`.
4.  Populate `DecisionTrail` entries for auto-mapping actions.

- [ ] **Step 5: Commit**
`git add orchestrators/QuoteIngestion/src/QuoteIngestionOrchestrator.php apps/atomy-q/API/app/Providers/AppServiceProvider.php && git commit -m "feat(atomy-q): intelligent quote ingestion wiring and persistence"`

---

## Task 6: Controller Updates - Reparse & Serialization

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`

- [ ] **Step 1: Implement Real Reparse Logic**
Update `QuoteSubmissionController::reparse` to delete only unmapped/unresolved `normalization_source_lines` and re-dispatch the job.

- [ ] **Step 2: Update Normalization Serialization**
Update `NormalizationController::serializeSourceLine` to include the new `ai_confidence`, `taxonomy_code`, and `mapping_version` in the JSON response.

- [ ] **Step 3: Commit**
`git add apps/atomy-q/API/app/Http/Controllers/Api/V1/ && git commit -m "feat(api): real reparse logic and intelligent normalization serialization"`

---

## Task 7: End-to-End Verification & Validation

**Files:**
- Modify: `apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php`

- [ ] **Step 1: Run the feature test**
`cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/QuoteIngestionIntelligenceTest.php`
Expected: PASS.

- [ ] **Step 2: Commit**
`git commit --allow-empty -m "test(atomy-q): final verification of intelligent ingestion flow complete"`
