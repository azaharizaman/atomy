# RFQ workspace — mission control & composite health

**Date:** 2026-03-21  
**Product / area:** `apps/atomy-q/WEB` — RFQ workspace (`/rfqs/[rfqId]/overview` and shell)  
**Status:** Design (brainstorm locked)  
**Related:** [`2026-03-21-atomy-q-horizontal-process-track-design.md`](2026-03-21-atomy-q-horizontal-process-track-design.md) (schedule rail)  
**Constraints:** [AGENTS.md](../../../AGENTS.md) — Atomy-Q WEB is **desktop-only** (no responsive/mobile layout unless explicitly requested).

---

## 1. Summary

The RFQ overview is the **mission control** surface: users scan **health**, **time pressure**, and **where to act** in one place. **Activity** remains deep context (e.g. flyover), not the primary scan layer.

**Locked decision:** headline status is a **single composite** health indicator — not only a stack of per-area badges. Per-area chips/tiles **may** still show detail beneath the headline.

---

## 2. Composite health: `mission_health`

**Enum (string):** `nominal` | `attention` | `blocked`

**Precedence (highest wins):** `blocked` → `attention` → `nominal`

Evaluation order: compute **blocked** conditions first; if none, **attention**; otherwise **nominal**.

---

## 3. Definitions (v1 rules — refine when wiring API)

These map to data already available or derivable from **`GET /rfqs/:id/overview`** (see `use-rfq-overview` / API overview payload).

### 3.1 `blocked`

Work cannot proceed on a **primary path** without intervention. Examples (product may narrow the list):

- Normalization / comparison path: **blocking issues** present when the product defines “freeze” or “run comparison” as impossible (e.g. unresolved conflicts / blocking flags from normalization readiness — align with existing **comparison readiness** rules when implemented end-to-end).
- **Optional later:** hard vendor or compliance gate if such flags exist in API.

**v1 minimal definition (WEB-only, overview-shaped):** use **explicit** signals only — do **not** infer “blocked” from missing optional data alone.

- If **`needs_review_count` > 0** and product policy treats that as blocking forward progress on the golden path → classify as **attention**, not blocked, unless API adds a dedicated `blocked_reason` (defer).
- Prefer **`blocked`** when API exposes a boolean or enum (future). Until then, **`blocked`** may be **unused** or mapped only from documented API fields (e.g. comparison cannot proceed per `use-comparison-readiness` when wired on overview).

*Implementation note:* Start with **no `blocked`** until backend sends an unambiguous signal; then enable rules without changing the enum.

### 3.2 `attention`

Progress is possible but **someone should look soon** — risk of slip or rework.

Candidate conditions (any → `attention`; ordered check after `blocked`):

- `approvals.overall === 'pending'` **and** `approvals.pending_count > 0`
- `normalization.needs_review_count` **defined and** `> 0`
- RFQ **schedule “late”** for submission deadline while status is still “open” in the same sense as `rfq-schedule-milestones` (draft/published/active/pending) — reuse shared helper or duplicate rule for parity
- `comparison != null && comparison.is_preview === true` **and** product treats preview as “not yet final” for mission readiness (optional flag)
- `expected_quotes > 0 && quotes_received < expected_quotes` **and** **within N days of submission deadline** (optional — adds “fill rate” pressure)

**Do not** set `attention` solely because `activity.length > 0`.

### 3.3 `nominal`

No `blocked` and no `attention` condition fired.

---

## 4. UI behavior

- **Single headline** control (badge, pill, or status strip): label + color token (**success / warning / danger** aligned to DS — e.g. green / amber / red).
- **Optional one-line reason** under the headline (first matching rule in fixed priority order) for accessibility and clarity.
- **Per-area** KPIs/tiles remain; they **subordinate** to the composite — never duplicate the composite as three separate “health” badges at the same level.

---

## 5. API evolution (optional)

Long-term, prefer **`mission_health`** (or `composite_health`) computed **server-side** for one source of truth and easier mobile clients later. Until then, WEB may derive client-side from overview JSON using this spec.

---

## 6. Open items

- Exact **`blocked`** triggers once normalization/comparison blocking is fully exposed on overview.
- Whether **`attention`** includes “low quote fill” near deadline (needs product threshold).
- Copy for user-visible labels: e.g. “All clear” / “Needs attention” / “Blocked”.
