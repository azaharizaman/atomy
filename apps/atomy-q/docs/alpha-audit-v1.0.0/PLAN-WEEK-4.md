# Atomy-Q Alpha — Week 4 Plan (Days 22–28)

**Dates:** Alpha countdown **Days 22–28**  
**Theme:** **Release candidate** — production configuration, staging parity, security hardening, performance sanity, identity posture documented.  
**Exit criteria:** Production **CORS** + env templates verified; identity stubs **replaced or explicitly accepted** with security sign-off; **queue workers** and **S3** production-ready; **staging** full regression passes; **tenant isolation** re-audited on all new endpoints; no **P0** open bugs for golden path.

---

## 1. Week overview

| Area | Goal |
|------|------|
| **Product** | Freeze scope except Sev-1 fixes; “known issues” list signed by PM. |
| **Engineering** | Security + perf; no new features unless blocking Alpha. |
| **Integration** | **Release candidate API** tag; partner smoke tests complete. |
| **Deployment** | One-click or documented **runbook** deploy to prod; rollback tested. |
| **Infrastructure** | HA basics: DB backups, Redis persistence policy, S3 versioning optional, TLS end-to-end. |
| **Marketing** | **Beta waitlist** or **private Alpha cohort** logistics (landing page, email). |
| **Investor relations** | **Data room** update slide: Alpha date, metrics to track (activation, not revenue). |

---

## 2. Day-by-day schedule

### Day 22 — CORS & environment matrix

| Track | Actions |
|--------|---------|
| **Engineering** | `config/cors.php`: production WEB origins; env-driven `ALLOWED_ORIGINS` if useful; no `*` with credentials. |
| **AI agent** | Config test or snapshot; document `APP_URL` vs `NEXT_PUBLIC_APP_URL`. |
| **Integration** | Partners using server-side calls may not need CORS — document. |
| **Deployment** | Same-origin vs cross-origin checklist for staging/prod. |
| **Infra** | Load balancer forwards `Origin` correctly. |
| **Marketing** | Public marketing site domain vs app domain — cookie consent if EU. |
| **IR** | N/A |

**Verification:** Browser smoke from prod WEB to prod API; preflight OPTIONS.

---

### Day 23 — Identity stubs: replace or document

| Track | Actions |
|--------|---------|
| **Engineering** | Evaluate `AtomyPermissionQueryStub` / `RoleQueryStub` / session stubs: **minimal real** read from DB **or** formal “Alpha uses JWT only; RBAC deferred” ADR. |
| **AI agent** | No behavior change without tests; document in `IMPLEMENTATION_SUMMARY` or ADR. |
| **Integration** | If roles exposed in JWT — document claim names. |
| **Deployment** | N/A |
| **Infra** | N/A |
| **Marketing** | Don’t advertise role granularity beyond admin/user if that’s all that’s real. |
| **IR** | Governance story: security roadmap post-Alpha. |

**Verification:** Security review meeting notes; sign-off.

---

### Day 24 — Queue workers (production)

| Track | Actions |
|--------|---------|
| **Engineering** | Supervisor/systemd unit files; `queue:work` args; restart on deploy; **graceful** timeout aligned with job. |
| **AI agent** | `failed_jobs` monitoring; `retry` command documented. |
| **Integration** | SLA: async processing time expectation (P95) communicated. |
| **Deployment** | Blue/green or rolling: drain workers before deploy. |
| **Infra** | Autoscale queue workers if load-based (optional). |
| **Marketing** | N/A |
| **IR** | Ops maturity narrative — “observable async pipeline”. |

**Verification:** Kill worker mid-job — recovery behavior acceptable.

---

### Day 25 — S3 (or object storage) production

| Track | Actions |
|--------|---------|
| **Engineering** | Bucket policy: private objects, presigned URLs if downloads; CORS for direct upload only if used. |
| **AI agent** | E2E upload test against staging bucket. |
| **Integration** | Virus scanning policy if required by enterprise customers — document future. |
| **Deployment** | Lifecycle rules for temp prefixes. |
| **Infra** | Encryption at rest; IAM least privilege. |
| **Marketing** | N/A |
| **IR** | Data handling: quotes may contain sensitive pricing — encryption + access control mentioned. |

**Verification:** Upload + download path; no public ACL mistakes.

---

### Day 26 — Staging deploy & full regression

| Track | Actions |
|--------|---------|
| **Engineering** | Full test suite: `phpunit`, `phpstan` if used, WEB `npm run test:unit`, Playwright **against staging**. |
| **AI agent** | Fix any failures; no partial skips without ticket. |
| **Integration** | Partner runs smoke tests; issues triaged P0/P1. |
| **Deployment** | Tag `rc-1`; deploy staging from tag; DB migrate + seed smoke tenant. |
| **Infra** | Monitoring dashboards green overnight. |
| **Marketing** | Schedule **Alpha cohort** invites after Day 28 sign-off. |
| **IR** | Prepare **milestone date** communication (internal + board if applicable). |

**Verification:** Regression report artifact (pass/fail table).

---

### Day 27 — Performance (hot paths)

| Track | Actions |
|--------|---------|
| **Engineering** | N+1 audit on RFQ overview, quote list, comparison show; add `with()` eager loads; pagination enforced. |
| **AI agent** | Laravel debugbar off in prod; query count budget per request documented. |
| **Integration** | Rate limits documented for list endpoints. |
| **Deployment** | Opcache PHP; WEB CDN for static assets. |
| **Infra** | DB connection pooling if needed; read replicas out of scope for Alpha unless required. |
| **Marketing** | “Fast enough for pilot” — no benchmark claims without data. |
| **IR** | Scalability: qualitative architecture only unless benchmarks reviewed. |

**Verification:** Staging load smoke (light); slow query log review.

---

### Day 28 — Security: tenant isolation audit

| Track | Actions |
|--------|---------|
| **Engineering** | Manual + automated tests: every new endpoint uses `tenantId($request)` or equivalent; **404** for cross-tenant id probe per project policy. |
| **AI agent** | Add regression tests for audit findings from Week 1–3. |
| **Integration** | Partners must not send `tenant_id` in body to impersonate — document. |
| **Deployment** | Security headers (HSTS, etc.) on API gateway. |
| **Infra** | WAF optional; IP allowlist for admin routes optional. |
| **Marketing** | Security page stub for website (“enterprise-grade practices” — legal review). |
| **IR** | SOC2 path: “not certified in Alpha” if true. |

**Verification:** Security checklist signed; no open Sev-1/2.

---

## 3. Cross-functional checkpoints (end of Week 4)

| Checkpoint | Owner | Evidence |
|------------|-------|----------|
| RC tagged | Eng manager | Git tag |
| Staging green | QA | Test report |
| Partner smoke | Integration | Email ack |
| Prod readiness | Infra + Deploy | Runbook + checklist |
| Comms ready | Marketing | Cohort invite copy approved |
| IR materials | IR | Slide + milestone wording |

---

## 4. Risks & mitigations

| Risk | Mitigation |
|------|------------|
| Last-minute scope | Change control board; only Sev-1 |
| Partner finds blocker | Daily triage slot Days 26–28 |
| Prod secrets leak | Pre-deploy secret scan |

---

## 5. Artifacts this week

- [ ] **Production runbook** (deploy, rollback, migrate, workers)  
- [ ] **Security review** summary  
- [ ] **Regression report**  
- [ ] **Marketing**: cohort email + landing FAQ  
- [ ] **IR**: board slide + data room PDF update  

---

*Previous:* [`PLAN-WEEK-3.md`](./PLAN-WEEK-3.md) · *Next:* [`PLAN-WEEK-5.md`](./PLAN-WEEK-5.md)
