# Atomy-Q Alpha — Week 5 Plan (Days 29–30)

**Dates:** Alpha countdown **Days 29–30** (short final week)  
**Theme:** **Freeze, ship, communicate** — code freeze, tagged Alpha release, production deployment, monitoring, and coordinated **internal/external** messaging for customers, partners, marketing, and investors.  
**Exit criteria:** **Alpha** tag in repo; production deployment successful; **smoke tests** green; on-call rotation defined; **marketing/IR** materials aligned with **verified** capabilities only; **integration partners** notified of go-live window and support channel.

---

## 1. Week overview

| Area | Goal |
|------|------|
| **Product** | Formal **Alpha** acceptance: golden path signed in prod or **designated Alpha prod** environment. |
| **Engineering** | **No** merges except hotfixes via release branch; tag + changelog. |
| **Integration** | Partners receive **go-live notice**, API version, support SLAs (best-effort for Alpha). |
| **Deployment** | Production deploy executed; rollback drill **not** during launch window unless failure. |
| **Infrastructure** | 24–48h heightened monitoring; alerts routed; logs retained for incident review. |
| **Marketing** | Controlled announcement: **who**, **what**, **limitations**; no hype beyond audit-approved claims. |
| **Investor relations** | Factual milestone: date, environment, what was delivered; forward-looking statements only per counsel. |

---

## 2. Day-by-day schedule

### Day 29 — Release freeze, docs & runbook finalization

| Track | Actions |
|--------|---------|
| **Engineering** | **Code freeze** on `release/alpha` (or equivalent); merge only **Sev-1** hotfixes. Run **full** test suite; update `CHANGELOG.md` / release notes; version tag `alpha-1.0.0` (semver as per org policy). |
| **AI agent** | Changelog entries: **breaking** API changes vs Week 1 baseline; migration list. |
| **Integration** | Send **release notes** + OpenAPI export + Postman collection version; **support** email/Slack channel + **office hours** (optional). |
| **Deployment** | Final **runbook** walkthrough: DB backup → migrate → `queue:restart` → smoke → monitor. **Rollback** steps rehearsed. |
| **Infra** | Verify alerts (5xx rate, queue lag, disk, DB connections); paging to on-call. |
| **Marketing** | **Internal review** of public post / email: must match **ALPHA_SCOPE** + audit reality (AI stance, awards, etc.). Legal/compliance approval if required. |
| **IR** | Prepare **board/investor** blurb: *Atomy-Q Alpha deployed [date]; core quotation comparison workflow available to pilot users; roadmap: Beta criteria [TBD].* Avoid revenue unless true. |

**Deliverables:** Tagged release; frozen artifacts; signed go/no-go checklist (PM + Eng + Infra).

**Verification:** CI green on tag; staging **parity** with tag.

---

### Day 30 — Alpha deployed & launch communications

| Track | Actions |
|--------|---------|
| **Engineering** | Deploy tag to **production**; run **production smoke**: login → create tenant (if in scope) → RFQ → upload quote → normalize → compare → award. Record **transaction IDs** / screenshots for audit. Hotfix process ready. |
| **AI agent** | Document any post-deploy fixes in hotfix branch procedure only. |
| **Integration** | **Go-live email** with: base URL, auth method, rate limits, known limitations, deprecation policy (none or “best effort” for Alpha). |
| **Deployment** | Post-deploy verification; **30-min** stability check; communicate “all clear” to stakeholders. |
| **Infra** | Dashboards pinned; **48h** watch; incident bridge if Sev-1. |
| **Marketing** | **Cohort launch**: emails to pilot customers; social/website update per approved copy; **press** only if pre-approved (often **no** press for Alpha). |
| **IR** | Distribute **investor update** per compliance: milestone achieved, risks (early product), next milestones (Beta, enterprise, etc.). Schedule **customer reference** only after willing pilot confirms. |

**Verification:** Smoke checklist 100% pass; error budget not exceeded in first 4 hours (define threshold, e.g. &lt;1% 5xx).

---

## 3. Roles & responsibilities (RACI snapshot)

| Activity | Responsible | Accountable | Consulted | Informed |
|----------|-------------|-------------|-----------|----------|
| Go/no-go | Eng lead | Product/CTO | Security, Infra | IR, Marketing |
| Prod deploy | DevOps/Deploy | Eng lead | — | All teams |
| Partner comms | Integration PM | CTO/BD | Legal | Partners |
| Public/cohort comms | Marketing | CEO | Legal | IR |
| Investor note | IR | CEO/CFO | Legal | Board |

---

## 4. Communication guardrails (all teams)

**Approved themes**

- Intelligent **quotation comparison** workflow for pilot teams.  
- **Alpha** = early access; feedback welcomed; **SLA not production-grade**.  
- Security practices in progress (no false certifications).

**Avoid**

- Guaranteed accuracy of AI extraction.  
- Revenue, pipeline, or customer counts **unless verified**.  
- Comparisons to named competitors without evidence.  
- “SOC 2 compliant” / “enterprise-ready” unless true.

---

## 5. Post-Alpha (Days 31+) — handoff note

Not part of the 30-day plan but **expected outputs**:

| Team | Next actions |
|------|----------------|
| **Engineering** | Triage Alpha feedback; Beta backlog; tech debt from stubs deferred |
| **Integration** | Biweekly API office hours through Beta |
| **Deployment** | Automate deploy further; staging refresh from anonymized prod |
| **Infra** | Cost review; right-sizing |
| **Marketing** | Case study pipeline (with customer permission) |
| **IR** | Update model only with validated metrics |

---

## 6. Artifacts (Week 5 deliverables checklist)

- [ ] Git tag + **GitHub/GitLab release** with notes  
- [ ] **Production smoke** results attached to release  
- [ ] **Runbook** v1.0 signed  
- [ ] **Partner** go-live pack (email sent)  
- [ ] **Marketing** assets live (cohort email, FAQ)  
- [ ] **IR** communication sent  
- [ ] **Incident** response: primary/secondary on-call named  

---

## 7. Success definition (Alpha)

Per [`ALPHA_SCOPE.md`](../ALPHA_SCOPE.md) and [`ALPHA_RELEASE_AUDIT.md`](./ALPHA_RELEASE_AUDIT.md), **Alpha success** = a user can complete the **core flow** on **real backend and database**, with **documented** AI behavior (live or explicitly scoped). **Week 5** does not add features — it **proves** the prior weeks in **production** and **communicates honestly**.

---

*Previous:* [`PLAN-WEEK-4.md`](./PLAN-WEEK-4.md) · *Index:* [`PLAN-INDEX.md`](./PLAN-INDEX.md)
