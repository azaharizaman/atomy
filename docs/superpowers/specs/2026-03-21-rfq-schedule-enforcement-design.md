# RFQ schedule, milestone ordering, and enforcement (tenant policy + RFQ overrides)

**Date:** 2026-03-21  
**Product / area:** Atomy-Q (buyer workspace; API + WEB); vendor portal **out of scope** for alpha  
**Status:** Design (brainstorm consolidated); **not yet implemented**  
**Implementation plan:** [`../plans/2026-03-21-rfq-schedule-enforcement-implementation-plan.md`](../plans/2026-03-21-rfq-schedule-enforcement-implementation-plan.md)  
**Related:** [`2026-03-21-atomy-q-horizontal-process-track-design.md`](2026-03-21-atomy-q-horizontal-process-track-design.md) (presentation only; date-anchored track + today cursor)

---

## 1. Summary

Define **typed lifecycle milestones** for an RFQ with **planned dates**, separate **actual timestamps** (or derived events), and **enforcement** that governs whether actions are blocked, warned, or informational only.

- **Tenant-wide policy** is the default; **RFQ owner/editor** may apply **sparse per-RFQ overrides** for allowlisted constraints. Every override is **append-only audit** and **visible to all internal users** who can view the RFQ.
- **Relaxed closing** is not open-ended: when enabled, a **grace window** (configurable **calendar** days, **capped at 5** at product level) extends **buyer-only** quote intake after **`submission_deadline`** (see §5.1). **`closing_date`** is a separate milestone and does **not** extend vendor or grace logic unless product explicitly adds a future rule.
- **Alpha:** No vendor portal; quotes enter via **buyer-side intake**; this spec still locks rules for API/domain so future vendor UX does not leak enforcement internals.

---

## 2. Problem

Without an explicit model, teams mix **intended schedule**, **what actually happened**, and **whether late actions are allowed**—leading to inconsistent UX, weak audit, and either rigid or lawless deadlines. Procurement also needs **bounded** exception paths (outages, holidays) without turning grace into indefinite slack.

---

## 3. Goals

1. **Planned milestone dates** on an RFQ with **validation** against a documented **partial order** (optional branches for optional events).
2. **Tenant policy** defining default **enforcement** (`hard` | `soft` | `off`) per **constraint kind**, plus which constraints **RFQs may override**.
3. **Per-RFQ overrides** (sparse deltas only), editable by **RFQ owner/editor**; **logged** and **visible** internally.
4. **Relaxed closing:** tenant-configurable **grace days** (within product cap **≤ 5**); **effective buyer intake deadline** = **`submission_deadline` + grace**; **no** vendor self-upload after **`submission_deadline`** (see §5.1).
5. **Clear separation** for future vendor surfaces: no exposure of relaxation flags/reasons; show honest **receipt timestamps** without internal policy narrative.
6. **Multi-tenancy:** all policy and RFQ schedule data **scoped by `tenant_id`**; no cross-tenant leakage in queries or API responses.

---

## 4. Non-goals (v1 of this spec)

- Full **configurable DAG editor** in the admin UI (tenant graph defined in data/migration or constrained templates first).
- **Vendor portal** implementation (principles here only).
- **Automatic** rescheduling when public holidays differ by region (defer; document manual date edits).
- Replacing vertical **Timeline** / **ActivityFeed**—schedule rail uses **HorizontalProcessTrack** where product needs a horizontal milestone view.

---

## 5. Core concepts

| Concept | Meaning |
|--------|---------|
| **Planned** | Dates (or datetimes) the buyer sets for milestone kinds on this RFQ. |
| **Actual** | When something really happened (`finalized_at`, first invitation sent, first quote received, etc.)—from events or columns. |
| **Enforcement** | What the system does when an action conflicts with planned dates or ordering: **hard** (block), **soft** (allow + warn + audit; may require role/reason per tenant), **off** (informational only). |
| **Constraint kind** | Named rule, e.g. `invite_after_lock`, `briefing_between_invite_and_close`, `eval_after_close`, `late_submission`, etc. (final enum in implementation plan). |
| **Relaxed closing** | RFQ flag + **grace_days**; extends **buyer** intake only; capped by tenant and product. |

### 5.1 Submission deadline vs closing date (**locked: keep separate**)

Atomy-Q persists two distinct timestamps on `rfqs` (see migration + `Rfq` model). **Do not merge** them; enforcement and copy must use the right field.

| Field | Role | Enforcement / UX anchor |
|--------|------|-------------------------|
| **`submission_deadline`** | End of the **quotation / submission** window—the date buyers communicate to vendors as “quotes in by.” **Required on every RFQ** (API + DB NOT NULL) so records cannot linger without an intake cut-off. | **Vendor self-upload (future):** hard stop at this instant. **Buyer intake grace:** `effective_intake_deadline = submission_deadline + grace_days` when relaxed closing is on. **Ordering:** briefing (if any) must precede this; evaluation milestones follow this in the default graph. |
| **`closing_date`** | **Formal RFQ / tender closing** milestone—when the event is expected to be **closed** as a process state (aligns with overview rail: overdue if past while RFQ not yet `closed` / `awarded` / `cancelled`). | Not used for quote upload cut-off. Use for schedule, reporting, and validation relative to **workflow state** (e.g. buyer expects to move off “open” reception by this date). |

**Default validation when both are set:** require **`closing_date` ≥ `submission_deadline`** (same instant allowed unless tenant policy requires a gap). Reject or warn on inversion per tenant **hard/soft** for constraint kind `closing_after_submission` (name in plan).

**Public / vendor-visible “deadline” copy (future):** refer to **`submission_deadline`** only for “last moment to submit”; do not conflate with **`closing_date`** unless the buyer explicitly chooses identical values and UI still labels them distinctly for internal users.

---

## 6. Milestone ordering (normative intent)

Default **partial order** among milestones **that exist** for the RFQ (optional milestones omitted from the graph for that instance):

1. **RFQ finalized / locked** — prerequisite for starting vendor invitation in strict modes.  
2. **Vendor invitation** — opens after lock (exact predicate: “locked” vs “first send” is an implementation detail; document chosen predicate).  
3. **Briefing (optional)** — if present: after invitation predicate satisfied, before quote submission **close** (or before evaluation start if modeled separately).  
4. **Quote submission closing** — **`submission_deadline`**; published close for **vendor self-service** and buyer grace baseline.  
5. **RFQ closing (formal)** — **`closing_date`**; distinct milestone, on or after step 4 by default (§5.1).  
6. **Technical evaluation** — after **`submission_deadline`** (when enforcement not `off`).  
7. **Financial evaluation** — after technical or **parallel** if tenant template allows (parallelism as explicit template edge in later revision).  
8. **Normalization / comparison** — after relevant eval or freeze milestone.  
9. **Negotiations (optional)** — window after shortlist / pre-award as product defines.  
10. **Award** — terminal or near-terminal.

**Validation:** on **write** of planned dates, reject impossible orderings relative to **present** milestones. **Tenant** may forbid certain optional milestones or fix ordering variants via policy template.

---

## 7. Policy resolution

When evaluating an action (e.g. register quote, open evaluation):

1. Apply **RFQ override** for that **constraint kind** if present and tenant allows override for that kind.  
2. Else apply **tenant default** for that constraint kind.  
3. Else apply **product safe default** (documented per constraint; prefer conservative for buyer-only alpha if unset).

**Sparse overrides:** RFQ stores only **deltas** from tenant policy, not a full copy of all rules—so tenant policy updates remain meaningful until the RFQ pins an exception.

**Allowlist:** tenant configuration lists which **constraint kinds** may be overridden at RFQ level (e.g. allow `late_submission` enforcement change; disallow “invite before lock”).

---

## 8. Permissions and audit

- **Who may set RFQ overrides:** **RFQ owner** and **RFQ editor** only (same capability as editing RFQ metadata).  
- **Audit (append-only):** `tenant_id`, `rfq_id`, `actor_user_id`, `occurred_at`, `constraint_kind`, `previous`, `new`, optional `reason` text.  
- **Visibility:** any internal user who can **view** the RFQ can read the **override / schedule policy history** (dedicated subsection or merged into activity—product choice; content must be equivalent).

---

## 9. Relaxed closing and grace window

| Layer | Rule |
|--------|------|
| **Product** | `grace_days_after_close` **≤ 5** always. |
| **Tenant** | Allowed grace set, e.g. `{0, 3, 5}` or range `0..min(tenant_max, 5)`; `0` = feature disabled for RFQs unless product allows per-RFQ enable (product decision: typically tenant enables band first). |
| **RFQ** | Owner/editor may enable **relaxed closing** and pick **grace_days** within tenant-allowed values. |

**Effective buyer intake deadline:** **`submission_deadline`** (as stored, with documented timezone/EOD semantics) **+ grace_days** (calendar days unless product later adds business-day mode). **`closing_date` does not replace or shift this anchor.**

- **Buyer-side intake** (upload / manual register): allowed **after** published close **only until** effective buyer intake deadline, when relaxed closing is on; after that, **hard deny** even for buyer in v1 (separate “break-glass” admin flow is **out of scope** unless explicitly added later).
- **Vendor self-service (future):** upload **disabled** at **`submission_deadline`** regardless of relaxed closing; vendors never see relaxation labels.

**Rationale:** leeway for **disruption** (outages, festive periods), not indefinite slack; **5-day cap** enforces good procurement discipline.

**Edge cases (implementation must state one rule):**

- **Shorten grace** after late intakes exist: forbid or require admin—pick in plan.  
- **Move submission close** after grace was computed: recompute `effective_intake_deadline` or freeze—pick in plan.  
- **Calendar vs business days:** v1 **calendar days** unless tenant policy explicitly requests business days in a later phase.

---

## 10. Vendor-facing principles (future portal)

- Do **not** expose internal enforcement modes, override reasons, or “soft” wording.  
- Published **submission** end (**`submission_deadline`**) remains the **hard** stop for **vendor-initiated** upload.  
- If buyer registers a quote after close (within grace), vendor-visible copy shows **factual receipt time** without explaining buyer-only policy.

---

## 11. Relation to HorizontalProcessTrack

- **Planned** milestone dates feed **schedule mode** props (anchors + today cursor).  
- **State** (upcoming / current / completed / issue / blocked) derives from **actuals vs planned** and enforcement—**blocked/overdue** semantics must not leak vendor-only details on buyer screens.

---

## 12. Implementation brainstorming (for planning)

### 12.1 Suggested phasing

1. **Domain + API:** milestone kinds enum, planned date fields (or normalized `rfq_milestone` rows), tenant policy storage, validation service (ordering only), no override UI.  
2. **Enforcement hooks:** quote intake and key transitions call a single **ScheduleEnforcement** (or equivalent) with constraint kinds; return `allow | warn | deny` + machine code for UI.  
3. **RFQ override UI + audit read API:** owner/editor; append-only events; internal visibility.  
4. **Relaxed closing + grace:** RFQ fields + intake deadline check on buyer upload path.  
5. **WEB:** schedule editor + HorizontalProcessTrack schedule view + override history panel.  
6. **Future:** vendor portal wired to **hard** close only; buyer grace unchanged.

### 12.2 Architecture alignment (Nexus / Atomy)

- **Layer 1 (packages):** pure PHP types, constraint definitions, validation and resolution **without** framework imports.  
- **Layer 2:** orchestration if needed (stateless).  
- **Layer 3 (Laravel):** persistence, HTTP, policies; **tenant_id** on all reads/writes; **404** for wrong-tenant resource access per project rules.

### 12.3 Data model options

- **A (normalized):** `rfq_milestones` (`tenant_id`, `rfq_id`, `kind`, `planned_at`, optional `notes`).  
- **B (JSON column):** single `schedule` JSON with versioned schema—faster to ship, harder to query and migrate.

**Recommendation:** **A** for reporting and integrity; optional generated column or view for API shape.

### 12.4 Constraint catalog (starter list for plan)

Wire names to match product copy later: `invite_after_lock`, `briefing_order`, `closing_after_submission` (`closing_date` ≥ `submission_deadline`), `evaluation_after_submission_close`, `financial_after_technical_or_parallel`, `late_quote_intake` (buyer path + grace), `buyer_intake_after_grace` (hard stop).

### 12.5 Testing

- Unit: ordering validation, resolution order (tenant vs override), grace boundary (timezone/EOD), deny after **`submission_deadline` + grace**; **`closing_date` ≥ `submission_deadline`** when both set.  
- Feature/API: tenant isolation, owner/editor-only override, audit append-only, intake behavior pre/post close with relaxed flag.

---

## 13. Open decisions (capture in implementation plan)

1. Exact **milestone enum** and which are optional v1.  
2. **Datetime precision** for `submission_deadline` / `closing_date`: instant vs end-of-day; **org timezone** source for display and boundaries.  
3. Whether **RFQ type/template** sits between tenant and RFQ for policy (recommended follow-on).  
4. Soft path: **reason required** always vs only when relaxing from hard.

**Locked (no longer open):** **`submission_deadline`** and **`closing_date`** remain **separate** fields with roles in §5.1; quote intake and grace use **`submission_deadline`** only.

---

## 14. Approval

Product/engineering: confirm §6 ordering, §9 grace cap (5 calendar days), and §8 audit visibility. **Implementation plan** (checklist + Phase 1 §13 defaults) is in [`../plans/2026-03-21-rfq-schedule-enforcement-implementation-plan.md`](../plans/2026-03-21-rfq-schedule-enforcement-implementation-plan.md).
