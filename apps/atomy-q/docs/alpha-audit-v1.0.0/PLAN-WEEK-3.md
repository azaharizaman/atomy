# Atomy-Q Alpha — Week 3 Plan (Days 15–21)

**Dates:** Alpha countdown **Days 15–21**  
**Theme:** **AI → DB → user value** — populate normalization lines from extraction, harden errors, align comparison UX with API, implement **awards**, strip mock fallbacks from the demo path.  
**Exit criteria:** Extraction output **persists** `NormalizationSourceLine` rows where applicable; users see clear errors on AI/queue failure; comparison preview either **persisted** or **removed** from UI with docs; **Award** `store`/`index` work with WEB; **`NEXT_PUBLIC_USE_MOCKS`** not required for internal Alpha demo; product/legal stance on AI documented.

---

## 1. Week overview

| Area | Goal |
|------|------|
| **Product** | End-to-end story: quote → normalize → compare → **winner** is storable and visible. |
| **Engineering** | Awards vertical slice; mock removal in hot hooks; comparison matrix from snapshot. |
| **Integration** | Awards API for future ERP handoff (export optional Alpha+). |
| **Deployment** | Feature flags for AI on/off; safe rollback to rule-only normalization. |
| **Infra** | Monitor queue depth, job failure rate, AI latency. |
| **Marketing** | **Alpha narrative** locked: who it’s for, what works, what’s experimental. |
| **Investor relations** | **Milestone letter** prep: “core workflow complete in staging” — contingent on demo recording. |

---

## 2. Day-by-day schedule

### Day 15 — Populate NormalizationSourceLine from extraction

| Track | Actions |
|--------|---------|
| **Engineering** | Map extraction DTO → create/update source lines; link to `quote_submission_id`; set initial conflicts if mapping ambiguous. |
| **AI agent** | DB assertions in tests; idempotent re-run policy (replace vs merge) documented in code. |
| **Integration** | If lines appear via API: event order: upload → queued → lines available via GET. |
| **Deployment** | Long-running jobs: chunking for large PDFs. |
| **Infra** | Disk for temp files; cleanup job. |
| **Marketing** | Screenshots for deck (internal). |
| **IR** | N/A |

**Verification:** Integration test; staging upload end-to-end.

---

### Day 16 — Normalization error handling & UX contract

| Track | Actions |
|--------|---------|
| **Engineering** | Structured API errors (`code`, `message`, `details`); retries with backoff in job; surface **non-sensitive** reason to client. |
| **AI agent** | Unit tests for retry limits; log correlation ids. |
| **Integration** | Error JSON schema stable for clients. |
| **Deployment** | Alert on job failure rate threshold. |
| **Infra** | Centralized logs (e.g. JSON logs) if not already. |
| **Marketing** | FAQ: “What if AI fails?” — honest bullets. |
| **IR** | Risk factor: reliance on third-party AI — mitigations listed. |

**Verification:** Failure injection tests; WEB shows toast/error state.

---

### Day 17 — Comparison preview (persist or deprecate)

| Track | Actions |
|--------|---------|
| **Engineering** | **Option A:** Persist preview runs in `comparison_runs` with `is_preview=true`. **Option B:** Remove preview from UI and keep only final — update OpenAPI + WEB. |
| **AI agent** | No duplicate `uniqid` preview ids in production path if persisting. |
| **Integration** | Clarify idempotency for `preview` if kept. |
| **Deployment** | DB growth if previews stored — TTL/cleanup policy. |
| **Infra** | N/A |
| **Marketing** | Align terminology “preview run” vs “final run”. |
| **IR** | N/A |

**Verification:** API tests + product sign-off on Option A vs B.

---

### Day 18 — Matrix endpoint from snapshot

| Track | Actions |
|--------|---------|
| **Engineering** | `GET /comparison-runs/{id}/matrix` builds matrix from `response_payload.snapshot` / stored snapshot; headers from RFQ line items + vendor columns. |
| **AI agent** | Empty snapshot → 404 or empty matrix — document. |
| **Integration** | Matrix JSON stable for export partners. |
| **Deployment** | Large matrix pagination if needed. |
| **Infra** | CPU for matrix build acceptable. |
| **Marketing** | Demo script: open matrix view. |
| **IR** | N/A |

**Verification:** PHPUnit; manual UI walkthrough.

---

### Day 19 — AwardController: store + index

| Track | Actions |
|--------|---------|
| **Engineering** | Implement against `awards` migration; tenant scope; validate RFQ + comparison run references; status workflow minimal for Alpha. |
| **AI agent** | Feature tests; 404 cross-tenant. |
| **Integration** | `POST /awards`, `GET /awards?rfq_id=` documented. |
| **Deployment** | Migration already exists — verify columns match model. |
| **Infra** | N/A |
| **Marketing** | **“Award”** vs **“Winner”** copy consistency. |
| **IR** | Alpha delivers **operational** value (faster comparison), not financial metrics claims. |

**Verification:** Full API test suite for awards.

---

### Day 20 — WEB award UI wired to API

| Track | Actions |
|--------|---------|
| **Engineering** | `award` page: remove seed-only path when mocks off; mutations to create/select winner; success state + link back to RFQ. |
| **AI agent** | Playwright against **live API** on staging (`NEXT_PUBLIC_USE_MOCKS=false`). |
| **Integration** | N/A |
| **Deployment** | E2E job in CI with staging URL (optional) or nightly. |
| **Infra** | N/A |
| **Marketing** | Record **2–3 min** demo video (internal). |
| **IR** | Snippet for quarterly update: “Alpha feature-complete on golden path (staging).” |

**Verification:** E2E pass; accessibility spot-check.

---

### Day 21 — Remove mock fallbacks (golden path)

| Track | Actions |
|--------|---------|
| **Engineering** | `use-rfqs`, `use-rfq-overview`, award/comparison hooks: no silent seed fallback when API fails unless explicit env `NEXT_PUBLIC_ALLOW_OFFLINE_DEMO`; default **fail loud**. |
| **AI agent** | Vitest updates; document breaking change for devs who relied on silent fallback. |
| **Integration** | Consumers must handle API errors — document. |
| **Deployment** | Environment review: production must have `USE_MOCKS=false`. |
| **Infra** | N/A |
| **Marketing** | Ensure public site doesn’t show “mock account” in prod build. |
| **IR** | Transparency: Alpha may still have rough edges — known issues list only internally. |

**Verification:** Manual regression with mocks off; error states visible.

---

## 3. Cross-functional checkpoints (end of Week 3)

| Checkpoint | Owner | Evidence |
|------------|-------|----------|
| Golden path demo | Product | Recorded video |
| Awards persist | API + WEB | DB row + UI |
| AI stance | Legal + Product | Written paragraph |
| Mock removal | WEB lead | Env audit |
| Integrators updated | Partnerships | Changelog email |

---

## 4. Risks & mitigations

| Risk | Mitigation |
|------|------------|
| AI quality poor | Position as Alpha; human override flows emphasized |
| Award legal implications | Disclaimers; no e-signature in Alpha if not ready |
| Demo flakiness | Dedicated staging tenant + seeded RFQ |

---

## 5. Artifacts this week

- [ ] **Demo script** (Marketing): 5-minute talk track  
- [ ] **Changelog** for integrators (breaking: mocks, awards)  
- [ ] **IR** one-pager: milestone, risks, next 30 days post-Alpha  
- [ ] **Known limitations** list (customer-safe)  

---

*Previous:* [`PLAN-WEEK-2.md`](./PLAN-WEEK-2.md) · *Next:* [`PLAN-WEEK-4.md`](./PLAN-WEEK-4.md)
