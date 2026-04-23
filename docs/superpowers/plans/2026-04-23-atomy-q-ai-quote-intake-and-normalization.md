# Atomy-Q AI Quote Intake And Normalization Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make quote ingestion and normalization genuinely provider-backed in alpha while preserving end-to-end manual continuity when document intelligence or normalization intelligence is degraded or offline.

**Architecture:** Layer 1 defines provider-neutral document-intelligence and procurement-normalization request/result contracts. Layer 2 coordinates quote submission processing, extraction, normalization suggestions, commercial terms extraction, and provenance. Layer 3 wires Hugging Face endpoint adapters, persistence, manual source-line editing endpoints, and quote-intake/normalize workspace UX.

**Tech Stack:** PHP 8.3, Laravel queues/jobs, Nexus packages, orchestrators, Next.js/React, TypeScript, PHPUnit, Vitest, Playwright.

---

## Scope

- Provider-backed document extraction for uploaded quotes
- Provider-backed normalization suggestions and mapping hints
- Commercial term extraction provenance
- Manual source-line creation/edit/correction when extraction fails
- Manual normalization override and conflict resolution continuity
- Truthful AI unavailable messaging in quote intake and normalize screens

## Layer Ownership

- **Layer 1**
  - `packages/MachineLearning`: generic extraction request/response envelopes, provider trace, error taxonomy, quota/timeout mapping.
  - `packages/ProcurementML`: procurement-specific extraction results, normalization suggestion DTOs, commercial term suggestion structures.
  - `packages/Document` and `packages/Storage`: source artifact references and version-safe document access.
  - `packages/Audit` and `packages/AuditLogger`: ingestion and normalization provenance evidence.
- **Layer 2**
  - `orchestrators/QuoteIngestion`: quote processing orchestration, retries, source-line persistence coordination, idempotent reparse semantics.
  - `orchestrators/QuotationIntelligence`: provider-backed extraction, normalization suggestioning, conflict/risk hint generation, decision trail writing.
- **Layer 3**
  - `apps/atomy-q/API`: queue jobs, Eloquent adapters, Hugging Face document/normalization clients, manual edit endpoints, persistence models, migrations, OpenAPI.
  - `apps/atomy-q/WEB`: quote intake, quote detail, normalize workspace, manual edit forms, AI status-driven UX.

## File Structure

- Create or modify in `packages/MachineLearning/src/...`:
  - extraction request/result contracts
  - provider trace metadata VOs
  - provider error-to-domain mapping helpers
- Create or modify in `packages/ProcurementML/src/...`:
  - quote extraction DTOs
  - normalization suggestion DTOs
  - commercial term suggestion DTOs
  - provenance-friendly explanation fragments
- Modify: `orchestrators/QuoteIngestion/src/...`
- Modify: `orchestrators/QuotationIntelligence/src/...`
- Add orchestrator tests for provider-backed and manual-continuity paths

- Modify: `apps/atomy-q/API/app/Jobs/ProcessQuoteSubmissionJob.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/HuggingFaceDocumentIntelligenceClient.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/HuggingFaceNormalizationClient.php`
- Create if needed: `apps/atomy-q/API/app/Http/Requests/ManualNormalizationSourceLineRequest.php`
- Create if needed: `apps/atomy-q/API/app/Http/Requests/ManualCommercialTermsRequest.php`
- Create if needed: manual source-line create/update endpoints in `routes/api.php`
- Modify: `apps/atomy-q/API/database/migrations/...` for AI provenance and manual-edit provenance fields
- Create or modify: API feature tests for quote ingestion, normalization review, and manual fallback flows

- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx`
- Modify: `apps/atomy-q/WEB/src/hooks/use-quote-submission.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-quote-submissions.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`
- Create if needed: manual source-line editor components and tests
- Add WEB tests for AI-on, AI-off, and degraded quote-intake behavior

## Task 1: Define Procurement AI Contracts For Intake And Normalization

- [ ] Add failing tests for procurement-specific extraction and normalization DTOs in `packages/ProcurementML`.
- [ ] Model provider-backed extraction results separately from persisted submission rows so raw provider behavior does not leak into controllers.
- [ ] Include provenance fields for:
  - provider name
  - endpoint group
  - model identifier or revision
  - prompt/template version
  - confidence or reliability hints when available
  - processing timestamp

## Task 2: Upgrade QuoteIngestion And QuotationIntelligence Orchestrators

- [ ] Refactor the ingestion pipeline so provider-backed extraction is the default in `AI_MODE=provider`.
- [ ] Keep deterministic and manual fallback behavior explicit rather than silently mixing outputs.
- [ ] Ensure reparse operations are idempotent and preserve a clear lineage between original extraction and user-edited corrections.
- [ ] Persist decision-trail entries that state whether source lines came from provider extraction, deterministic fallback, or manual entry.

## Task 3: Add API Adapters, Persistence, And Manual Continuity Endpoints

- [ ] Replace dormant LLM ingestion/normalization adapters with real Hugging Face managed endpoint clients.
- [ ] Add request/response normalization at the API edge so provider payload drift does not leak upward.
- [ ] Extend quote-submission and normalization persistence with provenance and error fields required for audit and support.
- [ ] Add or refine endpoints so users can:
  - manually add source lines,
  - edit extracted source lines,
  - re-run provider extraction,
  - override or confirm mapped RFQ lines,
  - manually record missing commercial terms.
- [ ] Return truthful states:
  - extraction unavailable
  - normalization unavailable
  - manual action required

## Task 4: Update WEB Quote Intake And Normalize UX

- [ ] Make quote intake clearly AI-assisted when provider extraction succeeds.
- [ ] When extraction is degraded or unavailable, keep the workspace usable by surfacing manual source-line editing instead of a dead end.
- [ ] Hide AI-only affordances when a manual fallback exists.
- [ ] Keep unavailable messaging scoped to the missing AI behavior, not the entire RFQ screen.
- [ ] Show provenance badges or metadata only where they help the operator debug or review the extraction source.

## Task 5: Verification And Regression Protection

- [ ] Add package, orchestrator, API, and WEB tests for:
  - successful provider extraction,
  - provider timeout/unavailable paths,
  - manual source-line entry after extraction failure,
  - manual normalization override,
  - quote reparse preserving provenance,
  - tenant isolation and not-found behavior.
- [ ] Run:
```bash
./vendor/bin/phpunit orchestrators/QuoteIngestion/tests orchestrators/QuotationIntelligence/tests apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php
cd apps/atomy-q/WEB && npm run test:unit -- src/hooks/use-normalization-source-lines.live.test.ts src/hooks/use-normalization-review.live.test.ts src/app/'(dashboard)'/rfqs/[rfqId]/quote-intake/page.test.tsx src/app/'(dashboard)'/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx
```

## Exit Criteria

- Alpha quote ingestion is genuinely provider-backed when AI is healthy.
- Users can still complete quote intake and normalization manually when AI is down.
- Provider failures are visible, auditable, and non-synthetic.
- The persisted record distinguishes provider output from manual correction.
