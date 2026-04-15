# Atomy-Q Alpha — Active Mitigation Index

**Product:** Atomy-Q — Intelligent Quotation Comparison System  
**Objective:** Ship a **working Alpha** where the core flow runs on **real API + real DB**, with a **clear position on AI** (shipped minimal path or documented deferral), **no critical stubs** on the golden path, and **production-capable** deployment.  
**Reference audit:** [`ALPHA_RELEASE_AUDIT.md`](./ALPHA_RELEASE_AUDIT.md)  
**Scope definition:** [`../ALPHA_SCOPE.md`](../ALPHA_SCOPE.md)  

---

## Active execution documents

| Type | File | Purpose |
|------|------|---------|
| Audit baseline | [`ALPHA_RELEASE_AUDIT.md`](./ALPHA_RELEASE_AUDIT.md) | Source-of-truth gap audit |
| Scope baseline | [`../ALPHA_SCOPE.md`](../ALPHA_SCOPE.md) | Alpha boundaries and what is explicitly out-of-scope |
| Progress analysis | [`../ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`](../ALPHA_PROGRESS_ANALYSIS_2026-03-31.md) | Consolidated status readout across the top 10 gaps |
| Remaining-gaps spec | [`../ALPHA_REMAINING_GAPS_IMPLEMENTATION_SPEC_2026-04-09.md`](../ALPHA_REMAINING_GAPS_IMPLEMENTATION_SPEC_2026-04-09.md) | Combined closure spec for all still-open alpha gaps |
| Active mitigation | [`ALPHA_GAP_8_9_10_MITIGATION_PLAN_2026-04-09.md`](./ALPHA_GAP_8_9_10_MITIGATION_PLAN_2026-04-09.md) | Current execution plan for production readiness, mock-removal, and docs consolidation |

---

## Audience guide

Active alpha documents are structured for:

- **Engineering / human developers** — tasks, files, PRs, tests  
- **AI coding agents** — bounded tasks, acceptance criteria, verification commands  
- **Third-party integration** — API stability, env contracts, webhook/queue assumptions (if any)  
- **Deployment** — releases, migrations, smoke checks, rollback  
- **Infrastructure** — compute, DB, Redis, S3, secrets, observability  
- **Marketing** — messaging guardrails, what to promise vs not, collateral timing  
- **Investor relations** — factual milestones, risks, “Alpha” definition  

---

## Current critical path to alpha

1. Build, lint, and API/WEB test gates are reproducible in CI and staging.
2. Live mode has no silent seed/mock fallback on golden-path pages (RFQ list path already fail-loud; remaining pages pending).
3. Vendor API baseline is now tenant-scoped and live; OpenAPI/client parity + award journey closure remain.
4. Queue/storage/env contracts are documented and verified in runbooks.
5. Gap ownership and closure evidence are maintained in one active mitigation ledger.

---

## Retired artifacts

The weekly execution documents and generated GitHub-project bootstrap artifacts were removed on **2026-04-09** because they were stale and no longer represented active execution state:

- `PLAN-WEEK-1.md` … `PLAN-WEEK-5.md`
- `github-project/README.md`
- `github-project/alpha-plan-items.json`
- `github-project/bootstrap-alpha-project.sh`

---

*Version: 1.2.0 — vendor API baseline live; critical-path checkpoints refreshed*
