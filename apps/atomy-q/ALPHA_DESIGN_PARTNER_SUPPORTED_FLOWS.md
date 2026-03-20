# Atomy-Q — Design partner alpha: supported flows

**Audience:** Buying-org design partners (1–3 tenants) and internal onboarding.  
**Last updated:** 2026-03-20

This document states what the **alpha** build is intended to support versus what remains **stubbed**, **deferred**, or **out of scope**. It aligns with the product scope in [`docs/superpowers/specs/2026-03-20-atomy-q-alpha-release-brainstorm-design.md`](../../docs/superpowers/specs/2026-03-20-atomy-q-alpha-release-brainstorm-design.md) and the implementation plan [`docs/superpowers/plans/2026-03-20-atomy-q-design-partner-alpha-implementation-plan.md`](../../docs/superpowers/plans/2026-03-20-atomy-q-design-partner-alpha-implementation-plan.md).

---

## Identity & access

| Flow | Alpha status | Notes |
|------|----------------|-------|
| Email + password login (JWT) | **Supported** | Tenant resolved from the user record; optional tenant hint in UI for dev. |
| Session refresh | **Supported** | Refresh token flow as implemented in API + WEB. |
| Forgot password | **Supported** | `POST /api/v1/auth/forgot-password` returns a **generic** success message (no user enumeration). Email delivery uses configured mail driver (e.g. `log` on staging). |
| Reset password | **Supported** | `POST /api/v1/auth/reset-password` with email, token, password + confirmation. WEB: `/forgot-password`, `/reset-password` (token via query or paste). |
| SSO / OIDC | **Not an alpha requirement** | SSO may appear in the UI for future enterprise use; **design partners are not required to use SSO** for alpha. Use email/password + reset only. |

---

## Tenant & security expectations

- **Buyer-only:** Partner users are **buying-organization** accounts. There is **no** vendor self-service portal or vendor-authenticated submission in alpha.
- **Isolation:** Cross-tenant access to resources returns **404** (no existence leak), consistent with [`AGENTS.md`](../../AGENTS.md).
- **Review:** Run [`apps/atomy-q/WEB/docs/SECURITY_REVIEW_AUTH_AND_TENANTS.md`](WEB/docs/SECURITY_REVIEW_AUTH_AND_TENANTS.md) before inviting external tenants.

---

## Sourcing & RFQ (vertical slice)

| Flow | Alpha status | Notes |
|------|----------------|-------|
| RFQ list / create / detail / overview | **Supported** | Pagination and filters as implemented; overview includes KPI fields and activity where wired. |
| RFQ workspace sections (line items, vendors, quote intake, comparison, award, etc.) | **Mixed** | Many sections exist as **scaffolded** workspace navigation; depth varies. Use [`WEB/IMPLEMENTATION_STATUS.md`](WEB/IMPLEMENTATION_STATUS.md) and [`WEB/BACKEND_API_GAPS.md`](WEB/BACKEND_API_GAPS.md) for field-level detail. |
| Buyer-side quote capture | **Supported (API + pilot path)** | Authenticated tenant users can submit quotes (upload / intake paths covered by API workflow tests). Vendor portal is out of scope. |
| Comparison runs & final snapshot | **Supported** | Final comparison run is required for the documented approval path (see API `ComparisonSnapshotWorkflowTest` / README “Quote intake & comparison”). |
| Global approvals queue | **Supported (one variant)** | **`type`: `comparison_approval`** — pending approvals tied to a **final** comparison run snapshot. List: `GET /api/v1/approvals`; approve/reject via existing endpoints. WEB: `/approvals`, `/approvals/[id]`. |

---

## Stubbed or deferred UI / nav

The sidebar and dashboard may still surface links to areas that are **placeholders** or **partial** (e.g. some settings, reporting, vendor management as a first-class screen). Treat anything not listed above as **not guaranteed** for partner demos unless explicitly confirmed for your tenant.

---

## API contract

- **OpenAPI:** Exported spec and Scramble docs — see [`apps/atomy-q/API/README.md`](API/README.md).
- **WEB client generation:** `npm run generate:api` — see [`apps/atomy-q/WEB/README.md`](WEB/README.md).

---

## Verification before partner invite

From repo root (or app directories as noted in each README):

1. `cd apps/atomy-q/API && php artisan test`
2. `cd apps/atomy-q/WEB && npm run test:unit`
3. `cd apps/atomy-q/WEB && npm run test:e2e` (optionally with `PLAYWRIGHT_BASE_URL` pointing at staging)
