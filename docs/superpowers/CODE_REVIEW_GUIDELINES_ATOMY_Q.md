# Atomy-Q code review guidelines (superpowers: code-reviewer)

**Purpose:** Mandatory systematic pass for reviews touching **`apps/atomy-q/`** (API, WEB, tests, docs). The code-reviewer subagent (and human reviewers) should walk this list on every invocation and report results under **`### Atomy-Q guideline pass`** in the review output.

**Source of architectural truth:** [`AGENTS.md`](../../AGENTS.md), [`docs/project/ARCHITECTURE.md`](../project/ARCHITECTURE.md).

---

## 1. Controller and service exception contracts

- [ ] Endpoints that call services using **`DB::transaction`**, locks, or I/O catch **`InvalidArgumentException`** (or domain equivalents) and return **appropriate 4xx** with stable, non-leaking messages.
- [ ] **Other throwables** (e.g. **`QueryException`**, **`Throwable`**) are **`report()`’d** and surfaced as **generic JSON** (no stack traces, no internal details). Prefer **500** for unexpected failures unless a **422**-style generic message is intentionally consistent with the endpoint contract.
- [ ] **`forgot-password`**-style endpoints: mail/transport failures **must not** change the public success shape (log only), where anti-enumeration applies.

---

## 2. Dependency injection (Laravel API)

- [ ] **`AuthController`**-style controllers depend on **interfaces** for swappable services (**`PasswordResetServiceInterface`**, not the concrete class unless no interface exists yet).
- [ ] **`AppServiceProvider`** (or module provider) **binds** the interface to the implementation.

---

## 3. Multi-tenancy and eager loading

- [ ] Parent query is **`where('tenant_id', $tenantId)`**; **`with([...])`** on relations that could exist in another tenant uses **closure constraints** with the **same `$tenantId`**, not unconstrained eager loads.
- [ ] Cross-tenant access returns **404** where the project standard says to avoid existence leaks (not **403** for “wrong tenant” on id-based lookups).

---

## 4. Activity / overview and request limits

- [ ] **`buildOverviewActivity`** (and similar): **no fixed per-source limits** (e.g. 10/10/5/5) that ignore the caller’s **`$limit`**. Derive **per-source `limit()`** from the clamped request cap (e.g. distribute **`floor(limit/n)`** + remainder).
- [ ] Final merged list is sliced to **≤ requested limit**; **`/activity`** and **`/overview`** stay consistent with the same helper where applicable.

---

## 5. WEB: API contract parsing (TanStack Query / Axios)

- [ ] **Pagination `meta`**: **finite integers only**; **invalid meta fails the query** (throw) — do not fabricate **`per_page` / `total_pages`** from bad payloads.
- [ ] **Counts / KPI numbers**: **`null` / blank → undefined** and **fallback with `??`only then**; **throw** on decimals, booleans, arrays, or non-integer strings so contract drift surfaces in dev.
- [ ] **`catch (unknown)`**: use **`axios.isAxiosError`** or **`parseApiError`** before reading **`response` / `data`**.

---

## 6. UI and duplication

- [ ] **Duplicate components** (same JSX/logic in multiple pages under **`src/app`**) → extract to **`components/ds`** (or existing design-system folder) when non-trivial.

---

## 7. Playwright and CORS stubs

- [ ] Shared **`buildCorsHeaders`** allows **methods and headers** needed by tests (**PUT**, **PATCH**, **DELETE**, **OPTIONS**, custom headers). Defaults should be broad; **override** per spec when a test adds verbs/headers.

---

## 8. Documentation and plans

- [ ] **Markdown tables**: no broken **bold/backtick** nesting in cells; package names use clean **`code`** or **bold**.
- [ ] Plan “Create” vs **Modify/Verify** matches **repo reality** for existing files.
- [ ] **E2E / staging** instructions include an **explicit `PLAYWRIGHT_BASE_URL=...` example**, not only prose.

---

## 9. Password reset and security-sensitive flows

- [ ] Reset token row: **tenant-scoped** composite key where required; consumption in **transaction** with **`lockForUpdate()`** on the token row; **TTL** enforced; missing/unparseable **`created_at`** treated as invalid.
- [ ] Client **token length** validation aligned with **server `min`**.

---

## 10. Verification discipline

- [ ] **Tests** exist or are updated for: strict parsers, tenant isolation, limit behavior, and **controller error mapping** when behavior changes.
- [ ] Review output states **which checklist items were checked** and any **N/A** with reason (e.g. “diff touches only `packages/foo`”).

---

## 11. Sourcing lifecycle review pass

- [ ] Layer 2 sourcing coordinators depend on **orchestrator-local contracts** for policies and transaction management, not directly on Layer 1 concrete contracts or `DB` facades.
- [ ] Multi-write lifecycle flows such as RFQ duplication wrap all dependent persistence steps in **one adapter-owned transaction boundary** so partial duplicates cannot leak on failure.
- [ ] Laravel transaction adapters that accept **`callable`** inputs convert them to **`Closure`** before passing them to **`DB::transaction()`** so invokable objects and array/string callables do not fail at runtime.
- [ ] Reminder / notification workflows update persisted delivery metadata **only after** the notifier/mailer dispatch succeeds.
- [ ] Reminder notification template data treats **blank strings as missing** for fallbackable fields like RFQ title, vendor name, and channel; do not rely on bare **`??`** when empty strings are possible.
- [ ] Reminder template payloads avoid unnecessary **PII** such as raw vendor email unless a downstream renderer truly requires it.
- [ ] Notification recipients use a **stable unique identifier** (for example invitation id plus label) and derive locale/timezone from runtime or stored preferences with **`en` / `UTC`** only as fallbacks.
- [ ] Request-to-DTO patch flows do not use bare **`??`** merges for nullable fields; they track **field presence** so explicit null clears survive into persistence.
- [ ] DTOs and adapter mappings stay in sync with actual usage (for example, if logs or notifiers need invitation `channel`, the DTO and query/persist mapping expose it).
- [ ] Reviewers check request validation against persistence schema for nullable fields so API contracts do not advertise clears that the database cannot store.
- [ ] `IMPLEMENTATION_SUMMARY.md` follow-ups are re-verified against live bindings and adapters before release notes or plans say work is still pending.

---

## Review output template (append to code-reviewer response)

```markdown
### Atomy-Q guideline pass
- §1 Exception contracts: [Pass / Fail / N/A — …]
- §2 DI interfaces: …
- §3 Tenant + eager loads: …
- §4 Activity limits: …
- §5 WEB parsing + catch: …
- §6 UI duplication: …
- §7 Playwright CORS: …
- §8 Docs/plans: …
- §9 Password reset: …
- §10 Verification: …
- §11 Sourcing lifecycle: …
```

When **no** paths under **`apps/atomy-q/`** appear in the diff, still state: **`### Atomy-Q guideline pass: N/A`** (no Atomy-Q files in scope).
