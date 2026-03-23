# Quote Ingestion and AI Normalization Pipeline - Design Spec

**Date:** 2026-03-24  
**Status:** Draft  
**Author:** AI Architect  

---

## 1. Context and Problem Statement

Alpha promises real AI-driven quote normalization, but quote upload currently stops at storage and metadata persistence. The `reparse` endpoint returns stubbed responses, and no extraction/normalization pipeline is wired in the API.

### Current State
- `POST /quote-submissions/upload` creates a record with status `uploaded` and stores the file
- `POST /quote-submissions/{id}/reparse` returns fake 202 response
- `normalization_source_lines` table exists but is empty (no data flows in)
- No async job or AI provider integration exists

### Goal
Ship one real end-to-end quote ingestion pipeline: upload file, enqueue processing, extract vendor quote content using AI, normalize it into persisted source lines, and expose status/errors back to users.

---

## 2. Goals

1. **Real async processing** - Upload triggers async job that performs extraction
2. **AI extraction** - Invoke extraction provider (stubbed for Alpha, Vertex AI ready)
3. **Normalization persistence** - Extracted lines written to `normalization_source_lines`
4. **Status visibility** - API returns processing state, errors, line counts, summary
5. **Reparse functionality** - Re-trigger processing on demand (idempotent)
6. **Retry resilience** - Failed jobs can be retried with backoff

---

## 3. Non-Goals

- Multi-provider provider selection UI
- Complex human-in-the-loop review tooling beyond Alpha essentials
- Full OCR platform abstraction (PDF only for Alpha)
- Vector/semantic search capabilities

---

## 4. Nexus Package-First Reflection

### Existing Packages Considered

| Package | Considered For | Decision |
|---------|---------------|----------|
| MachineLearning | AI provider abstraction | **Reuse** - Extend with QuoteExtractionServiceInterface |
| Document | File handling | Not needed - Laravel Storage sufficient |
| Storage | File retrieval | Not needed - use Laravel local/S3 |
| Idempotency | Mutation protection | Not needed for this flow |

### What is Missing from Nexus

- **Quote extraction service contract** - No contract for document → line items extraction
- **Quote ingestion workflow orchestration** - No Layer 2 for intake → extraction → normalization

### Recommended Ownership

| Capability | Layer | Owner |
|------------|-------|-------|
| QuoteExtractionServiceInterface | L1 (extend MachineLearning) | Nexus ML package |
| QuoteIngestionOrchestrator | L2 | Nexus (new) |
| VertexAI/Provider adapter | L3 | Nexus ML package |
| ProcessQuoteSubmissionJob | App (Laravel) | Atomy-Q |
| Status transition logic | App | Atomy-Q |

### App-Specific Residue
- Laravel route definitions
- API response shaping for UI
- Feature flag configuration

---

## 5. Architecture and Layering Plan

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Atomy-Q API                                 │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │ QuoteSubmission  │  │ ProcessQuoteSub  │  │ Normalization    │  │
│  │ Controller      │  │ missionJob       │  │ Controller       │  │
│  └────────┬─────────┘  └────────┬─────────┘  └────────┬─────────┘  │
└───────────┼─────────────────────┼─────────────────────┼─────────────┘
            │                     │                     │
            ▼                     ▼                     ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    Nexus Layer 2 Orchestrator                       │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │            QuoteIngestionOrchestrator                        │  │
│  │  - Coordinates extraction → normalization → persistence      │  │
│  │  - Handles state transitions                                  │  │
│  │  - Manages retry logic                                        │  │
│  └──────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      Nexus Layer 1 / Layer 3                        │
│  ┌────────────────────────────┐  ┌─────────────────────────────┐ │
│  │ QuoteExtractionService     │  │ VertexAIMockProvider        │ │
│  │ (Interface + Implementation)│  │ (L3 Adapter - stub)         │ │
│  └────────────────────────────┘  └─────────────────────────────┘ │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────────┐ │
│  │              normalization_source_lines (existing table)      │ │
│  └─────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 6. Orchestrator Candidates

### QuoteIngestionOrchestrator (Layer 2)

**Responsibilities:**
1. Accept quote submission ID
2. Retrieve file from storage
3. Call extraction provider
4. Transform extraction output → normalization lines
5. Persist to `normalization_source_lines`
6. Determine final status based on confidence threshold:
   - confidence >= 80% → status = `ready`
   - confidence < 80% → status = `needs_review`
   - extraction failed → status = `failed`
7. Handle retries and error escalation

**Boundaries:**
- Does NOT know about Laravel jobs, queues, or HTTP
- Accepts domain objects, returns domain objects
- Depends on extraction service interface and persistence contracts

---

## 7. Adapter Candidates

### VertexAIMockProvider (Layer 3)

**Purpose:** Stub implementation returning realistic sample data for Alpha testing

**Responsibilities:**
- Implement QuoteExtractionServiceInterface
- Return 3-5 realistic line items from a sample PDF structure
- Simulate latency (500ms)
- Ready for swap with real Vertex AI client

### Laravel Job Adapter

**Purpose:** Bridge between Laravel queue and Nexus orchestrator

**Responsibilities:**
- Dispatch orchestrator from queue context
- Implement sync fallback if queue unavailable
- Handle retry with exponential backoff (3 attempts)

---

## 8. API Contracts

### Upload (existing, no change needed)
```
POST /quote-submissions
- Creates quote_submissions record with status='uploaded'
- Dispatches ProcessQuoteSubmissionJob
- Returns 201 with submission data
```

### Reparse (to implement)
```
POST /quote-submissions/{id}/reparse
- Idempotent: if status is 'extracting' or 'normalizing', return current state (no new job)
- Otherwise, reset to 'uploaded' and re-dispatch job
- Returns 202 with status='extracting'
```

### Status Check (existing, to enhance)
```
GET /quote-submissions/{id}
- Returns current status, error_code, error_message
- Returns line_items_count, warnings_count, errors_count
- Returns parsed_at timestamp if complete
```

### Source Lines (existing)
```
GET /normalization/source-lines?quote_submission_id={id}
- Returns extracted lines for this submission
- Includes raw_data JSON blob for original extraction
```

---

## 9. Data Model and Migrations

### Required Schema Changes

| Migration | Columns Added | Purpose |
|-----------|---------------|---------|
| Add processing fields | error_code (varchar, nullable) | Store error type |
| Add processing fields | error_message (text, nullable) | Store error details |
| Add processing fields | processing_started_at (timestamp, nullable) | Set when first entering processing flow (uploaded→extracting) |
| Add processing fields | processing_completed_at (timestamp, nullable) | Set when terminal state reached (ready/needs_review/failed) |
| Add processing fields | parsed_at (timestamp, nullable) | Alias for processing_completed_at - when extraction done |
| Add processing fields | retry_count (integer, default 0) | Track attempts |

**Note:** `processing_started_at` marks when the job starts (transition from `uploaded` to `extracting`). `processing_completed_at` marks when processing ends (transition to `ready`, `needs_review`, or `failed`). Intermediate states (extracting, extracted, normalizing) don't need individual timestamps - they're tracked via the status field.

### Existing Tables

**quote_submissions** (existing, columns added)
- Primary key: id (ULID)
- Tenant-scoped by tenant_id
- Status machine: uploaded → extracting → extracted → normalizing → needs_review → ready → failed

**normalization_source_lines** (existing)
- Already supports hybrid structured + JSON (raw_data)
- Linked to quote_submission_id
- Ready for extracted data

### Hybrid Structured/Unstructured Pattern

The design leverages the existing pattern in `normalization_source_lines`:
- **Structured columns** for normalized fields: source_description, source_quantity, source_uom, source_unit_price
- **JSON blob (raw_data)** for raw AI extraction - allows flexibility as provider evolves

No vector/pgvector needed for Alpha - extraction is handled by Vertex AI on their infrastructure.

---

## 10. Security and Tenant Isolation

- All queries MUST filter by tenant_id
- Quote submission retrieval: `where('tenant_id', $tenantId)->where('id', $id)`
- Normalization source lines: `where('tenant_id', $tenantId)->where('quote_submission_id', $submissionId)`
- File access: validate tenant owns the submission before reading file path

---

## 11. Idempotency and Concurrency

### Reparse Idempotency
- If status is already 'extracting' or 'normalizing', return current state (no new job)
- Otherwise reset status to 'uploaded' and dispatch fresh job

### Job Concurrency
- Use database lock on quote_submission_id during processing
- Prevent duplicate jobs for same submission

---

## 12. Error Handling

### Error States

| Error Type | Status | Storage |
|-----------|--------|---------|
| File not found | failed | error_code='FILE_NOT_FOUND' |
| Extraction failed | failed | error_code='EXTRACTION_FAILED', error_message='...' |
| Timeout | failed | error_code='TIMEOUT' |
| Normalization conflict | needs_review | warnings_count incremented |

### Retry Policy
- Max 3 attempts with exponential backoff (10s, 60s, 300s)
- After max retries, status = 'failed' with error_code='MAX_RETRIES_EXCEEDED'

### Sync Fallback
- If queue unavailable, execute ProcessQuoteSubmissionJob::handle() inline
- Log warning that sync fallback was used

---

## 13. Testing Strategy

### Unit Tests
- QuoteIngestionOrchestrator - all method paths
- Status transition validation
- Retry logic

### Feature Tests
- Upload triggers async job
- Reparse re-triggers job (idempotent)
- Status updates reflect job completion
- Tenant isolation enforced
- Error states returned correctly

### Manual Testing
- Upload PDF, verify extraction runs
- Check source lines populated
- Test retry behavior

---

## 14. Rollout Plan

### Phase 1: Infrastructure
1. Add migration for processing fields
2. Implement QuoteExtractionServiceInterface
3. Implement VertexAIMockProvider

### Phase 2: Orchestrator
1. Create QuoteIngestionOrchestrator
2. Implement extraction → normalization flow
3. Add status transition logic

### Phase 3: Job Integration
1. Create ProcessQuoteSubmissionJob
2. Wire to upload endpoint
3. Implement sync fallback

### Phase 4: API Enhancement
1. Implement real reparse endpoint
2. Enhance status response with error fields
3. Test end-to-end flow

---

## 15. Acceptance Criteria

- [ ] Upload triggers real processing path (async job dispatched)
- [ ] Reparse does real work and updates persisted state
- [ ] Successful parsing creates retrievable normalization source lines
- [ ] Failures are visible through API and UI (error_code, error_message)
- [ ] Tenant isolation enforced on all queries
- [ ] Sync fallback works when queue unavailable
- [ ] Status state machine transitions correctly

---

## 16. Resolved Design Decisions

| Decision | Resolution |
|----------|-------------|
| PDF library for extraction | Use **spatie/pdf-to-text** (Poppler wrapper) - reliable for text extraction from PDF |
| Mock provider output format | JSON array matching `normalization_source_lines` columns: source_vendor, source_description, source_quantity, source_uom, source_unit_price, raw_data |
| Confidence threshold for auto-ready | **80%** - above this status = `ready`, below = `needs_review` |
| Concurrent job locking | Use Laravel's **unique job** with Redis/database lock via `uniqueId` on quote_submission_id |