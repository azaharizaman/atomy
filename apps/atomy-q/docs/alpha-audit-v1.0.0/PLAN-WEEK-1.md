# Atomy-Q Alpha — Week 1 Plan (Days 1–7)

**Dates:** Alpha countdown **Days 1–7**  
**Theme:** **Foundation** — unblock builds, define tenant/company model, seed data integrity, real vendor surface for API consumers.  
**Exit criteria (end of Week 1):** `npm run build` and `npm run lint` pass in WEB; API migrates cleanly including **new tenants table**; seeded demo tenant references real `tenants` rows; **minimal tenant-creation API** exists (or documented admin-only alternative); **VendorController** backed by persistence (not empty/stub responses); owners assigned for every P0 audit item.

---

## 1. Week overview

| Area | Goal |
|------|------|
| **Product** | “Company/workspace” is representable in DB and API; vendor list is trustworthy for integrations. |
| **Engineering** | Fix blocking TS build; migrations + seeder alignment; close obvious stub APIs on critical read paths preparation. |
| **Integration partners** | Receive **OpenAPI version** (or frozen export) by end of week; **base URL + auth** contract stable for sandbox. |
| **Deployment / Infra** | Dev/staging DB can run **full migration set**; document ports (API vs WEB). |
| **Marketing** | **Do not** announce public launch; internal “Week 1 dev milestone” only. Prepare **one-pager facts** (what Alpha is / is not). |
| **Investor relations** | **Internal** milestone note: build unblocked + tenant model landed — no revenue claims. |

---

## 2. Day-by-day schedule

### Day 1 — P0 triage & ownership

| Track | Actions |
|--------|---------|
| **Engineering** | Import P0/P1 list from [`ALPHA_RELEASE_AUDIT.md`](./ALPHA_RELEASE_AUDIT.md); create tickets per item; assign DRI per area (API, WEB, AI, Infra). |
| **AI agent** | Input: audit §13. Output: ticket titles + acceptance criteria + file hints per P0. |
| **Integration** | Schedule 30-min **API contract sync**: auth header, `tenant_id` in JWT claims, error shape. |
| **Deployment** | Confirm **branch strategy** (`main` vs `release/alpha`), tag convention (`alpha-0.x`). |
| **Infra** | Inventory: PostgreSQL version, Redis need, S3/MinIO for local vs staging. |
| **Marketing** | Draft **non-public** Alpha definition (3–5 bullets): core flow only; AI stance TBD Week 3. |
| **IR** | **No** external communication. Internal: risk register link to audit gaps (award API, AI). |

**Deliverables:** Backlog in tracker; RACI; integration sync notes.  
**Tests:** N/A.

---

### Day 2 — Fix Next.js production build

| Track | Actions |
|--------|---------|
| **Engineering** | Fix TS error in `WEB/src/components/ds/HorizontalProcessTrack.tsx` (`aria-label`: `null` → `undefined` or conditional render). |
| **AI agent** | Single PR: build + lint clean; no unrelated refactors. |
| **Integration** | No API change expected; notify if WEB bundle hash process changes CI. |
| **Deployment** | Add/verify CI step: `cd apps/atomy-q/WEB && npm ci && npm run build`. |
| **Infra** | Ensure Node version pinned in `.nvmrc` / `package.json` engines if missing. |
| **Marketing** | N/A |
| **IR** | N/A |

**Verification:** `npm run build`, `npm run lint` (WEB).  
**Acceptance:** Build exits 0 on CI.

---

### Day 3 — Tenants table migration

| Track | Actions |
|--------|---------|
| **Engineering** | Add migration `create_tenants_table` (e.g. `id` ULID PK, `name`, timestamps). Decide **optional** `users.tenant_id` FK in follow-up migration if safe. |
| **AI agent** | Follow existing migration style in `apps/atomy-q/API/database/migrations`. |
| **Integration** | Document **tenant** as top-level object for future `GET /tenants/:id` (even if read-only in Alpha). |
| **Deployment** | Runbook note: **backup before migrate** on staging. |
| **Infra** | Migration downtime estimate (usually seconds for new table). |
| **Marketing** | N/A |
| **IR** | N/A |

**Verification:** `php artisan migrate` on clean DB; PHPUnit smoke if exists.

---

### Day 4 — Align seeder with tenants

| Track | Actions |
|--------|---------|
| **Engineering** | Update `PetrochemicalTenantSeeder` (or equivalent) to **insert `tenants` row** before users/RFQs; use same `ATOMY_SEED_TENANT_ID` / default ULID. |
| **AI agent** | Idempotent seed: skip if tenant already exists; preserve current “skip if RFQs exist” behavior where appropriate. |
| **Integration** | Provide **seed tenant id** for sandbox Postman collections. |
| **Deployment** | `php artisan db:seed` on staging after migrate. |
| **Infra** | N/A |
| **Marketing** | N/A |
| **IR** | N/A |

**Verification:** Fresh DB: migrate + seed completes; `tenants` has one row; users reference it.

---

### Day 5 — Create tenant API (minimal)

| Track | Actions |
|--------|---------|
| **Engineering** | Implement **POST /api/v1/tenants** (or admin-only route) creating `Tenant` + first admin user **or** only `Tenant` if users created separately. Align with product (self-serve vs invite-only Alpha). |
| **AI agent** | Feature test: 201 response, tenant id returned, JWT optional follow-up. |
| **Integration** | Publish request/response schema; idempotency if duplicate company name. |
| **Deployment** | Feature behind flag if risky: `FEATURE_TENANT_SELF_SIGNUP`. |
| **Infra** | Rate-limit tenant creation endpoint at edge if public. |
| **Marketing** | If Alpha is **invite-only**, **do not** advertise self-signup. |
| **IR** | Clarify **GTM**: direct invites vs open signup — one sentence for Q&A prep. |

**Verification:** PHPUnit/API feature test; manual Postman.

---

### Day 6 — Register flow (optional)

| Track | Actions |
|--------|---------|
| **Engineering** | If product chooses **open registration**: extend `AuthController` with register validating email uniqueness, tenant creation, password policy. **If invite-only:** skip and document. |
| **AI agent** | Mirror validation rules from login; never return “user exists” vs “wrong password” distinction for enumeration (align with security policy). |
| **Integration** | Same auth response shape as login (`access_token`, `refresh_token`, `user`). |
| **Deployment** | Email verification optional for Alpha — document. |
| **Infra** | If email sent: SMTP/Postmark env on staging. |
| **Marketing** | Messaging: “Alpha access by invitation” vs “request access” landing copy. |
| **IR** | N/A |

**Verification:** Feature tests; optional E2E login after register.

---

### Day 7 — VendorController real DB

| Track | Actions |
|--------|---------|
| **Engineering** | Replace stub `VendorController`: either **`vendors` table** + CRUD or aggregate distinct vendors from `vendor_invitations` + `quote_submissions` per tenant. Scope **every** query by `tenant_id`. |
| **AI agent** | Remove “Stub Vendor” strings; 404 for wrong tenant (not 403 per project convention where applicable). |
| **Integration** | Stable `GET /vendors`, `GET /vendors/{id}` JSON for CRM connectors. |
| **Deployment** | New migration if new table; data backfill script if aggregating only. |
| **Infra** | N/A |
| **Marketing** | Vendor terminology in UI copy: “Suppliers” vs “Vendors” — align with sales. |
| **IR** | N/A |

**Verification:** PHPUnit + manual API calls with two tenants (isolation).

---

## 3. Cross-functional checkpoints (end of Week 1)

| Checkpoint | Owner | Evidence |
|------------|-------|----------|
| Build green | WEB lead | CI artifact / screenshot |
| Migrations applied | API lead | Staging DB schema dump or `\dt` |
| Tenant story | Product + API | Decision doc: self-serve vs admin-created |
| Integration pack | Partnerships | Postman + env template |
| Comms freeze | Marketing | Internal only; no press |

---

## 4. Risks & mitigations

| Risk | Mitigation |
|------|------------|
| Scope creep on registration | Time-box Day 6; default invite-only |
| FK migration breaks seed | Test fresh DB daily |
| Vendor model ambiguity | Product picks: master table vs aggregate for Alpha |

---

## 5. Artifacts to produce this week

- [ ] Updated **runbook**: migrate + seed + smoke URLs  
- [ ] **Integration** README: auth, tenant id, sample curl  
- [ ] **Internal** Alpha one-pager (Marketing + IR aligned)  
- [ ] CI: WEB build + API PHPUnit subset on PR  

---

*Next week:* [`PLAN-WEEK-2.md`](./PLAN-WEEK-2.md)
