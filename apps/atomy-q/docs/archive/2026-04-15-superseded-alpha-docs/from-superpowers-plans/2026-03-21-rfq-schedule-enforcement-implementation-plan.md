# RFQ schedule enforcement — implementation plan

> **For agentic workers:** Implement task-by-task; use checkboxes (`- [ ]`) for tracking. **Spec (source of truth):** [`../specs/2026-03-21-rfq-schedule-enforcement-design.md`](../specs/2026-03-21-rfq-schedule-enforcement-design.md).

**Goal:** Implement **buyer-side** schedule rules aligned with the spec: separate **`submission_deadline`** vs **`closing_date`**, **`closing_date` ≥ `submission_deadline`** validation, **relaxed closing** with **grace days** (product max **5**, calendar days) extending **buyer quote intake** only after **`submission_deadline`**. Defer **tenant policy JSON**, **per-RFQ enforcement overrides**, and **override audit API** to Phase 2 unless a task explicitly includes them.

**Architecture:** `AGENTS.md` / `docs/project/ARCHITECTURE.md`. Atomy-Q API is largely Laravel (L3): keep controllers thin; put **pure date/boundary math** in a **small, testable PHP class** (e.g. `App\Services\Rfq\RfqQuoteIntakeSchedule` or `App\Domain\Rfq\...`) with **no** `Illuminate\*` inside the pure part if split into a dedicated file—otherwise a single service class with injected `Carbon`/`now` for tests is acceptable for this vertical.

**Tech:** Laravel 12, PHP 8.3 `declare(strict_types=1);`, PHPUnit feature tests, existing `Rfq` / `QuoteSubmission` models.

---

## §13 open decisions — **V1 defaults (pinned for this plan)**

| Item | Decision for Phase 1 |
|------|----------------------|
| Milestone enum | Use **existing** `rfqs` columns only (`submission_deadline`, `closing_date`, review/award fields). No `rfq_milestones` table in Phase 1. Optional events (briefing, etc.) **deferred**. **`submission_deadline` is mandatory** (implemented 2026-03-21: validation + NOT NULL + WEB create/details). |
| Datetime precision | **Instant semantics:** compare **`now()`** to stored timestamps **as-is** (UTC in DB). **Display** timezone remains a WEB concern. **End-of-day** UX is a **follow-up** (spec §13). |
| RFQ type/template policy layer | **Deferred** (Phase 3). |
| Soft path / reason | Phase 1 is **hard deny** after `submission_deadline + grace` for buyer upload; **soft + reason** when overrides exist — **Phase 2**. |

---

## Phase 1 — Intake gate + RFQ date validation (P0)

- [ ] **1. Migration — RFQ relaxed closing**  
  Add nullable/boolean + tinyint (or smallint) on `rfqs`: e.g. `relaxed_submission_closing` (bool, default false), `grace_days_after_submission` (nullable, 0–5; null treated as 0 when relaxed is false). Tenant-scoped; index not required for v1.

- [ ] **2. Model + API contract**  
  Update `App\Models\Rfq` `$fillable` / `$casts`; `RfqController` validation on create/update for new fields; expose in `show`/`update` responses and OpenAPI if generated. Rules: `grace_days_after_submission` required when relaxed true (or default 0), **max 5**, **min 0**; when relaxed false, clear or ignore grace.

- [ ] **3. Ordering validation**  
  On RFQ store/update/patch paths that accept `submission_deadline` and `closing_date`: if **both** non-null, assert **`closing_date` >= `submission_deadline`** (spec §5.1). Return **422** with clear field errors.

- [ ] **4. Quote intake service**  
  Implement **single** helper used by `QuoteSubmissionController::upload` (and any other quote-create path):  
  - **`submission_deadline` is always set** (NOT NULL + required on create); no “null means open” branch.  
  - If `now > submission_deadline`: allow only if `relaxed_submission_closing` and `now <= submission_deadline + grace_days` (**calendar days** from deadline instant: Carbon `addDays` on parsed instant—**document** DST edge cases; acceptable for v1).  
  - Else **422** with stable error code/message for WEB.

- [ ] **5. Wire `QuoteSubmissionController::upload`**  
  After RFQ resolved, call intake service; do not persist submission if denied.

- [ ] **6. Tests**  
  - Feature: upload allowed before deadline; denied after deadline with relaxed false; allowed after deadline within grace when relaxed true; denied after grace end.  
  - Feature: RFQ update rejects `closing_date` before `submission_deadline`.  
  - Tenant isolation: another tenant’s RFQ id → **404** (existing pattern).

- [ ] **7. WEB (minimal)**  
  RFQ details form: toggles/fields for relaxed closing + grace (within 0–5); show validation errors from API. Optional copy tooltip referencing internal policy (no vendor strings).

- [ ] **8. Docs**  
  Update `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md` (and WEB if UI added) with intake rules + fields.

---

## Phase 2 — Tenant defaults + RFQ overrides + audit (P1)

- [ ] **9. Tenant policy storage**  
  Table or JSON on `tenants`: allowed `grace_days` set, default relaxed behavior, **allowlist** of overrideable constraint kinds (spec §7–8).

- [ ] **10. RFQ override deltas + append-only audit**  
  Table `rfq_schedule_override_events` (tenant_id, rfq_id, actor_id, payload, created_at). Owner/editor only; visible to all internal RFQ viewers (new `GET` or embed in activity—spec).

- [ ] **11. Resolution order**  
  Implement resolver: RFQ override → tenant default → product default for enforcement kinds relevant to intake.

- [ ] **12. Soft path**  
  When enforcement is soft: allow intake with warning + require **reason** when relaxing from hard (spec §13 — choose “reason only when relaxing from hard”).

---

## Phase 3 — Optional follow-ons

- [ ] **13. Normalized `rfq_milestones`** (spec §12.3 recommendation) + migrate from columns.  
- [ ] **14. RFQ type / template** policy layer between tenant and RFQ.  
- [ ] **15. Vendor portal** hard stop at `submission_deadline` only; no leakage of relaxed flags.  
- [ ] **16. End-of-day** and org timezone for **boundary** (not only display).

---

## Verification (before claiming done)

```bash
cd apps/atomy-q/API && php artisan test --filter=<new test class names>
```

WEB: `npm test` or targeted Vitest for any new schedule/grace helpers.

---

## Status

| Phase | Ready to start? |
|-------|------------------|
| **Phase 1** | **Yes** — spec + this plan + §13 V1 defaults are sufficient. |
| **Phase 2+** | Requires product confirmation on tenant schema and activity vs dedicated audit endpoint. |
