# Atomy-Q Alpha — Week 2 Plan (Days 8–14)

**Dates:** Alpha countdown **Days 8–14**  
**Theme:** **Golden-path API hardening** — normalization data visible to clients, RFQ operations honest and tenant-safe, quotation pipeline moves toward async + AI configuration.  
**Exit criteria:** `GET` normalization endpoints return **real** `NormalizationSourceLine` / normalized projections; **RFQ duplicate** creates real rows (new ids); **saveDraft** / **bulkAction** tenant-scoped; **vendor remind** validates invitation belongs to tenant+RFQ; **reparse** exposes a **queue/job boundary** (even if worker is stubbed); **AI provider** config exists in env + `config/services.php`; first **extraction** path callable with **mocked HTTP** in tests.

---

## 1. Week overview

| Area | Goal |
|------|------|
| **Product** | Users and integrations **see** the same normalization data the server uses for readiness. |
| **Engineering** | Remove “lying” idempotent endpoints; introduce job abstraction for quote processing. |
| **Integration** | Document normalization payloads; webhook policy (**if** webhooks added — otherwise explicitly “polling only for Alpha”). |
| **Deployment** | Migrations only if new columns/indexes; queue connection documented. |
| **Infra** | Redis available for queue in staging; worker process **one** command documented. |
| **Marketing** | Prepare **“AI-assisted quotation understanding”** copy variants (factual, no overclaim). |
| **Investor relations** | Milestone: **API integrity week** — internal note that AI wiring has started (not “AI complete”). |

---

## 2. Day-by-day schedule

### Day 8 — Normalization GET endpoints (source-lines & normalized-items)

| Track | Actions |
|--------|---------|
| **Engineering** | Implement `NormalizationController::sourceLines` and `normalizedItems` to query `NormalizationSourceLine` (and joins) for the RFQ; match shapes expected by WEB hooks (`use-normalization-review`, quote-intake pages). |
| **AI agent** | Tenant + RFQ scoping; pagination if lists are large; feature tests with seeded lines. |
| **Integration** | Publish response schema; version bump in OpenAPI (Scramble). |
| **Deployment** | No breaking change to **mutation** routes; verify WEB still works with `NEXT_PUBLIC_USE_MOCKS=false`. |
| **Infra** | Query performance: indexes on `(tenant_id, quote_submission_id)` already — verify EXPLAIN on staging. |
| **Marketing** | N/A |
| **IR** | N/A |

**Verification:** PHPUnit feature tests; manual GET with JWT.

---

### Day 9 — RFQ duplicate (real implementation)

| Track | Actions |
|--------|---------|
| **Engineering** | Replace `stub-duplicate-id`: transaction copying RFQ, line items, optional invitations per product rules; **new ULIDs**; respect idempotency middleware. |
| **AI agent** | Idempotency test: same key → same response; different key → new RFQ. |
| **Integration** | Document `Idempotency-Key` header for `POST .../duplicate`. |
| **Deployment** | Log duplicate operations for audit. |
| **Infra** | DB transaction size OK for typical line counts. |
| **Marketing** | N/A |
| **IR** | N/A |

**Verification:** Feature + idempotency tests.

---

### Day 10 — saveDraft & bulkAction

| Track | Actions |
|--------|---------|
| **Engineering** | `saveDraft`: persist RFQ draft fields per `tenant_id` + RFQ id. `bulkAction`: tenant-scoped bulk close/archive — **no** cross-tenant IDs in request body trusted without lookup. |
| **AI agent** | Use same tenant resolution as other RfqController methods; remove TODO comments once fixed. |
| **Integration** | Bulk action request body schema + max batch size. |
| **Deployment** | Rate limit bulk endpoints. |
| **Infra** | N/A |
| **Marketing** | N/A |
| **IR** | N/A |

**Verification:** Feature tests for happy path + cross-tenant negative cases.

---

### Day 11 — Vendor invitation remind

| Track | Actions |
|--------|---------|
| **Engineering** | Load `VendorInvitation` by `invId` **and** `rfqId` **and** `tenant_id`; return 404 if mismatch; update `reminded_at` or equivalent if column exists. |
| **AI agent** | Idempotency completion aligned with successful response only. |
| **Integration** | If email sending deferred: response still 200 with `status` + **meta** `email_dispatched: false`. |
| **Deployment** | If real email: configure queue + mailer on staging first. |
| **Infra** | Outbound SMTP/Postmark allowlisting. |
| **Marketing** | Email template copy review if sending real reminders. |
| **IR** | N/A |

**Verification:** Feature test; optional mail fake assertion.

---

### Day 12 — Quote reparse → queue job interface

| Track | Actions |
|--------|---------|
| **Engineering** | Introduce `QuoteReparseRequested` job (or similar) implementing `ShouldQueue`; `reparse` controller dispatches job, returns **202** with `job_id` or `status: queued`. Persist quote status transition per existing state machine. |
| **AI agent** | `Queue::fake()` in unit test; integration test with `sync` driver. |
| **Integration** | Document async model: clients **poll** `GET /quote-submissions/{id}` until terminal state. |
| **Deployment** | `php artisan queue:work` in staging systemd/supervisor. |
| **Infra** | Redis queue connection; failed job table / Horizon optional. |
| **Marketing** | UX: “Processing…” states — coordinate with WEB. |
| **IR** | Internal: “async pipeline landed” — not “AI live” yet. |

**Verification:** Tests + manual queue worker on staging.

---

### Day 13 — AI: provider configuration

| Track | Actions |
|--------|---------|
| **Engineering** | Add env vars e.g. `OPENAI_API_KEY`, `OPENAI_MODEL`, timeouts; `config/services.php` + validation on boot or lazy on first use. **Never** log keys. |
| **AI agent** | `.env.example` entries + `config:clear` in deploy docs. |
| **Integration** | If partners host data: document **data residency** — prompts may leave tenant boundary to provider (legal review). |
| **Deployment** | Secrets in vault / CI masked vars. |
| **Infra** | Outbound HTTPS to provider; egress allowlist if corporate network. |
| **Marketing** | **Hold** public claims until Day 14–15 validation. |
| **IR** | Risk disclosure: third-party AI subprocessors if applicable. |

**Verification:** Config unit test or artisan tinker check (staging only).

---

### Day 14 — AI: extraction wired to one endpoint

| Track | Actions |
|--------|---------|
| **Engineering** | Inside queue job (or dedicated service), call **one** path: `nexus/quotation-intelligence` or `MachineLearning` provider with **structured** output contract; map to internal DTO. |
| **AI agent** | Integration test with **HTTP fake** (`Http::fake`) — no real API key in CI. |
| **Integration** | Document **input**: file path or text blob; **output**: line candidates. |
| **Deployment** | Feature flag `FEATURE_AI_EXTRACTION=true` on staging only first. |
| **Infra** | Timeout and retry policy; circuit breaker optional. |
| **Marketing** | Draft **one** approved sentence: e.g. “Uses industry-standard models to assist with line extraction (Alpha).” Legal review if needed. |
| **IR** | **No** “accuracy %” claims; qualitative language only. |

**Verification:** CI integration tests pass; staging dry-run with real key in **isolated** tenant.

---

## 3. Cross-functional checkpoints (end of Week 2)

| Checkpoint | Owner | Evidence |
|------------|-------|----------|
| Normalization GET matches UI needs | WEB + API | Joint demo with mocks off |
| No stub IDs on duplicate | API | Test + code review |
| Queue operational | Infra | Worker logs on staging |
| AI config secret-safe | Security | Grep audit for leaked keys |
| Comms | Marketing/IR | Approved AI sentence v0 |

---

## 4. Risks & mitigations

| Risk | Mitigation |
|------|------------|
| AI provider outage | Job retries + user-visible “failed” + manual re-upload |
| OpenAPI drift | Regenerate client WEB `npm run generate:api` weekly |
| Scope explosion on bulk actions | Cap batch size; ship minimal verbs only |

---

## 5. Artifacts this week

- [ ] OpenAPI / Scramble export after controller changes  
- [ ] **Async API** note for integrators (poll vs future webhooks)  
- [ ] Staging **queue** runbook  
- [ ] AI **subprocessor** one-liner for legal/IR  

---

*Previous:* [`PLAN-WEEK-1.md`](./PLAN-WEEK-1.md) · *Next:* [`PLAN-WEEK-3.md`](./PLAN-WEEK-3.md)
