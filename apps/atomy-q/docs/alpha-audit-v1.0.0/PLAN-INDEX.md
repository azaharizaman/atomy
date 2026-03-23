# Atomy-Q Alpha — 30-Day Execution Plan (Index)

**Product:** Atomy-Q — Intelligent Quotation Comparison System  
**Objective:** Ship a **working Alpha** where the core flow runs on **real API + real DB**, with a **clear position on AI** (shipped minimal path or documented deferral), **no critical stubs** on the golden path, and **production-capable** deployment.  
**Reference audit:** [`ALPHA_RELEASE_AUDIT.md`](./ALPHA_RELEASE_AUDIT.md)  
**Scope definition:** [`../ALPHA_SCOPE.md`](../ALPHA_SCOPE.md)  

---

## Weekly plan files

| Week | Calendar days | File | Theme |
|------|----------------|------|--------|
| 1 | Days 1–7 | [`PLAN-WEEK-1.md`](./PLAN-WEEK-1.md) | Foundation: triage, build green, tenants/companies, vendors |
| 2 | Days 8–14 | [`PLAN-WEEK-2.md`](./PLAN-WEEK-2.md) | Golden-path API: normalization reads, RFQ ops, reparse + AI wiring start |
| 3 | Days 15–21 | [`PLAN-WEEK-3.md`](./PLAN-WEEK-3.md) | AI completion, comparison UX/API alignment, **awards**, mock removal |
| 4 | Days 22–28 | [`PLAN-WEEK-4.md`](./PLAN-WEEK-4.md) | Staging, infra, security, performance — **release candidate** |
| 5 | Days 29–30 | [`PLAN-WEEK-5.md`](./PLAN-WEEK-5.md) | Freeze, Alpha launch, comms (marketing, IR, customers) |

---

## Audience guide

Each week document is structured for:

- **Engineering / human developers** — tasks, files, PRs, tests  
- **AI coding agents** — bounded tasks, acceptance criteria, verification commands  
- **Third-party integration** — API stability, env contracts, webhook/queue assumptions (if any)  
- **Deployment** — releases, migrations, smoke checks, rollback  
- **Infrastructure** — compute, DB, Redis, S3, secrets, observability  
- **Marketing** — messaging guardrails, what to promise vs not, collateral timing  
- **Investor relations** — factual milestones, risks, “Alpha” definition  

---

## Cross-week dependencies (critical path)

1. **Week 1:** Production build must pass; **tenants** table + creation story exists.  
2. **Week 2:** Normalization **GET** endpoints return real data; idempotent routes stop lying.  
3. **Week 3:** **Awards** implemented end-to-end; mocks off for demo path.  
4. **Week 4:** Staging mirrors prod; security + perf sign-off.  
5. **Week 5:** Code freeze → tagged Alpha → monitored launch.

---

*Version: 1.0.0 — aligned with ALPHA_RELEASE_AUDIT.md*
