# Atomy-Q Alpha Launch Readiness Superseding Spec

**Date:** 2026-04-30  
**Status:** Superseding design spec for final alpha readiness  
**Audience:** Product owner, engineering lead, release coordinator, operator, QA lead  
**Scope:** Atomy-Q API, Atomy-Q WEB, current release docs, AI operating posture, manual-continuity launch posture, and final alpha go/no-go rules.

## 1. Authority

This document is the controlling alpha launch readiness specification for Atomy-Q.

It supersedes prior alpha launch design specs, implementation plans, archived task specs, AI launch-readiness handoff docs, and release checklist interpretations where they conflict with this document. Older documents remain valid as historical evidence and implementation detail only when they do not conflict with this spec.

This spec does not delete or invalidate completed implementation work. It defines the final contract that determines whether the current application can be released to design-partner alpha.

## 2. Launch Decision Summary

Atomy-Q is not launch-ready until one of the valid release postures below is explicitly selected, verified, disclosed, and signed off.

### 2.1 Valid Release Postures

| Posture | Meaning | Launch Decision |
|---|---|---|
| AI-enabled alpha | Provider-backed AI capability gates pass and manual continuity also passes. | Go when all gates are green. |
| Manual-continuity alpha | AI gates are red, incomplete, or deliberately disabled, but the statutory human-operated workflow passes end-to-end and customers are clearly informed that AI-assisted paid features are unavailable. | Conditional go with explicit disclosure and owner sign-off. |
| Internal alpha only | Local gates, staging smoke, manual continuity, disclosure, or sign-off are incomplete. | No external design-partner launch. |

### 2.2 Non-Negotiable Rule

Manual continuity is a permanent product and compliance requirement. It is not an alpha fallback and it must not be removed post-alpha.

Human review, manual evidence handling, manual override, deterministic comparison, vendor selection, approval, award signoff, and audit review are statutory requirements for every Atomy-Q workflow where AI is used as decision support or decision-making assistance.

## 3. Governing Doctrines

### 3.1 Human Authority And Manual Continuity Doctrine

Atomy-Q AI is decision-support unless a future approved legal basis explicitly permits a different posture.

The SaaS must always preserve:

- human-operable RFQ creation and editing,
- manual line-item setup,
- manual vendor selection,
- manual quote intake continuity,
- manual source-line creation and correction,
- manual normalization mapping and override,
- deterministic comparison over persisted normalized data,
- human award creation and signoff,
- human approval decisions,
- human review of AI-assisted decision artifacts,
- auditability of both AI-generated and human-entered evidence.

No future beta or general-availability work may remove these paths without a separately approved statutory, legal, and product design change.

### 3.2 AI Availability And Truthful Degradation Doctrine

AI capabilities are a paid product obligation. Atomy-Q must pursue high-availability AI capability after alpha, and beta readiness should focus heavily on provider availability, failure isolation, observability, quota resilience, response validation, and support operations.

Atomy-Q must not claim absolute AI availability. Provider endpoints, credentials, networks, quotas, budgets, and model behavior can fail.

When AI is unavailable, degraded, disabled, or not provisioned:

- the product must disclose the state to users clearly,
- AI surfaces must not fabricate success,
- API responses must return truthful unavailable/degraded states,
- WEB surfaces must show scoped unavailable/error UX instead of page-wide crashes,
- manual continuity must remain usable,
- support and release evidence must record the outage or unavailable posture,
- customer-facing release notes and in-app messaging must not imply that unavailable AI is working.

### 3.3 AI Decision-Support Boundary

AI may:

- extract,
- normalize,
- summarize,
- draft,
- recommend,
- classify,
- explain,
- identify risks,
- prefill evidence,
- produce narrative decision-support artifacts.

AI must not independently:

- approve vendors,
- reject vendors,
- clear sanctions,
- complete due diligence,
- approve awards,
- sign off awards,
- mutate frozen comparison facts,
- create authoritative governance findings without human review,
- hide statutory review requirements,
- convert an unavailable AI result into fake success.

### 3.4 Evidence Over Intention Doctrine

Launch readiness is earned only through current evidence.

Chat history, old checklist entries, or prior passing commands are not sufficient when the code, environment, generated client, route surface, migrations, provider config, or release branch has changed.

Every launch claim must be tied to:

- exact command,
- date,
- executor,
- commit SHA,
- environment posture,
- result,
- artifact or log location when applicable.

## 4. Supported Alpha Journey

The supported alpha journey is narrow and explicit.

1. Register a tenant/company or log in.
2. Create an RFQ.
3. Add and edit RFQ line items.
4. Select approved vendors from the vendor master.
5. Invite vendors.
6. Upload quote files.
7. Review and correct source lines.
8. Normalize quoted lines against RFQ lines.
9. Resolve blocking normalization conflicts.
10. Freeze a final comparison run.
11. Create an award from the final comparison.
12. Sign off the award.
13. Review decision-trail evidence.
14. Invite one additional tenant user and verify pending activation.

Every alpha launch posture must prove this journey through live API-backed WEB behavior with `NEXT_PUBLIC_USE_MOCKS=false`.

## 5. Scope Boundaries

### 5.1 In Scope For Alpha Launch

- Tenant registration and login.
- RFQ lifecycle for the supported journey.
- RFQ line-item setup.
- Approved vendor selection and invitation.
- Quote upload, source-line review, and normalization.
- Comparison readiness and final comparison freeze.
- Award creation and signoff.
- Decision trail review.
- Minimal users and roles invite flow.
- AI status visibility.
- AI-assisted features only when they pass provider, truthful degradation, provenance, and manual-continuity gates.
- Explicit unavailable UX and customer disclosure for unavailable AI capability.

### 5.2 Out Of Scope Unless Explicitly Enabled

These surfaces may exist in code, but they are not launch-supported unless they satisfy this spec:

- negotiation workflows,
- advanced scoring-model authoring,
- scoring policy administration,
- RFQ template lifecycle,
- scenario simulation,
- integrations console,
- document vault workflows beyond quote evidence needed for alpha,
- advanced reporting schedules and exports,
- delegated authority management,
- device trust,
- full MFA enrollment productization,
- production email delivery,
- post-award handoff beyond alpha evidence,
- autonomous governance decisions,
- autonomous vendor approval or rejection.

Out-of-scope exposed routes must be hidden, explicitly deferred, or documented with operator-facing behavior and owner.

## 6. Current Readiness Findings To Close

This section records the readiness gaps observed during the 2026-04-30 analysis pass. It is not a permanent bug tracker, but these findings must be closed, deferred under this spec, or superseded by newer evidence before launch.

### 6.1 WEB Gate Failures

The WEB app is no-go while lint or production build fails.

Known failures from the 2026-04-30 pass:

- `npm run lint` fails with `no-explicit-any` errors and React compiler memoization preservation failure.
- `npm run build` fails in the vendor ESG compliance page because governance generation is invoked without required mutation arguments.

Required outcome:

- `cd apps/atomy-q/WEB && npm run lint` exits 0.
- `cd apps/atomy-q/WEB && npm run build` exits 0.
- Any remaining warnings are either fixed or recorded as non-blocking with owner and rationale.

### 6.2 API Golden-Path Failures

The API is no-go while supported alpha journey tests fail.

Known failures from the 2026-04-30 pass:

- RFQ duplication persists line-item `specifications` with extra JSON quoting.
- Quote ingestion returns `failed` where supported inline deterministic processing expects `ready`.
- Quote reparse can leave `INTELLIGENCE_FAILED`.
- Comparison preview and final comparison can hit `absolutePath must resolve to an existing filesystem path`.
- Comparison snapshot and decision-trail write paths fail behind the same missing-file condition.

Required outcome:

- Quote upload and reparse have deterministic, explainable status transitions.
- Missing quote files are handled through domain-safe unavailable/failure states, not raw 500s.
- Final comparison can be created from valid persisted normalized data.
- Decision-trail evidence is written for final comparison and award signoff.

### 6.3 AI Truthfulness And Artifact Failures

AI provider availability does not close launch readiness unless API and WEB behavior remain truthful under failure.

Known failures from the 2026-04-30 pass:

- Dashboard AI summary generation returns a 500 when the provider fails instead of returning an unavailable artifact while preserving facts.
- Vendor recommendation tests expect `vendor_ai_ranking` artifact persistence, but no artifact is found.

Required outcome:

- Provider failure returns structured unavailable/degraded AI artifacts where the feature contract requires continuity.
- AI-only features return unavailable, not fake success.
- AI-assisted features preserve deterministic facts and manual workflow state when AI fails.
- Required AI artifacts are persisted with feature key, capability group, provenance, source facts, and reason codes.

### 6.4 Environment Evidence Gaps

The local default API test run attempted PostgreSQL on `127.0.0.1:5433` and failed because the service was unavailable.

Required outcome:

- Final release evidence must state whether tests were run on PostgreSQL or SQLite.
- Staging release evidence must use the deployed database posture, not only SQLite.
- If SQLite is used for contract/export convenience, the evidence must say so explicitly and cannot replace staging smoke.

### 6.5 Operational Evidence Gaps

Current release docs still require:

- staging WEB URL,
- staging API URL,
- storage disk verification,
- true deployed mocks-off smoke,
- release owners,
- engineering sign-off,
- product sign-off,
- operator/staging sign-off.

No design-partner alpha decision is valid until these are filled.

## 7. Alpha Release Gates

### 7.1 Local Engineering Gates

All must pass before staging release verification:

| Gate | Required Command |
|---|---|
| WEB lint | `cd apps/atomy-q/WEB && npm run lint` |
| WEB build | `cd apps/atomy-q/WEB && npm run build` |
| WEB unit | `cd apps/atomy-q/WEB && npm run test:unit` |
| API clean DB | `cd apps/atomy-q/API && php artisan migrate:fresh --seed` |
| API alpha matrix | Current alpha matrix maintained in the release checklist |
| API full suite | `cd apps/atomy-q/API && php artisan test` unless explicitly split with rationale |
| OpenAPI validity | `cd apps/atomy-q/API && php artisan scramble:export --path=../openapi/openapi.json` plus JSON validation |
| Generated client | `cd apps/atomy-q/WEB && npm run generate:api` and rerun affected WEB build/tests |

### 7.2 Staging Gates

All must pass before any external design-partner alpha:

- Deployed WEB origin recorded.
- Deployed API origin recorded.
- `NEXT_PUBLIC_USE_MOCKS=false` confirmed in the deployed WEB build.
- API migrations applied.
- Storage disk write/read/delete verified.
- Queue posture recorded.
- Staging smoke runs from tenant registration through award signoff.
- Users and roles pending activation row is verified.
- Logs and screenshots are captured.

### 7.3 Manual Continuity Gates

These gates apply to both AI-enabled alpha and manual-continuity alpha:

- Quote upload remains possible.
- Manual source-line work remains possible.
- Manual normalization mapping and override remain possible.
- Deterministic comparison remains possible.
- Manual vendor selection remains possible.
- Manual award creation and signoff remain possible.
- Approval progression remains possible.
- Decision-trail review remains possible.

### 7.4 AI-Enabled Alpha Gates

These gates apply only when launching as AI-enabled alpha:

- `AI_MODE=provider` is active in staging.
- Public AI status endpoint reports intended provider/capability posture.
- Provider contract test passes for every alpha-enabled capability group.
- Healthcheck-only success is not accepted as provider proof.
- AI artifacts persist required provenance.
- AI unavailable/degraded states render truthfully in API and WEB.
- `AI_MODE=off` rollback is verified after provider-mode proof.

### 7.5 Manual-Continuity Alpha Gates

These gates apply when launching with AI disabled, unavailable, or deferred:

- `AI_MODE=off` or equivalent manual-continuity posture is recorded.
- Every AI-enabled or paid AI-assist surface clearly informs users that the AI feature is unavailable.
- Commercial/customer messaging states that AI-assisted capability is temporarily unavailable in this launch posture.
- Support and operator runbooks explain what users can still do manually.
- No in-app route or API endpoint claims AI success while AI is disabled.
- Manual workflow can complete the supported alpha journey end-to-end.

## 8. Customer Disclosure Requirements

User disclosure is mandatory whenever the product posture differs from AI-enabled alpha.

Minimum disclosure surfaces:

- in-app AI status banner or scoped AI unavailable callout,
- release notes or onboarding note for design partners,
- support runbook entry,
- customer-facing statement for paid AI-assisted features,
- operator evidence in the release checklist.

The disclosure must state:

- which AI-assisted features are unavailable,
- whether the unavailability is temporary, degraded, disabled, or not yet launched,
- what manual workflow remains available,
- whether any user action is required,
- where support can be contacted.

The disclosure must not:

- imply AI is working when it is disabled,
- hide paid-feature unavailability behind generic copy,
- present deterministic/manual output as provider-backed AI,
- promise absolute AI availability.

## 9. AI High-Availability Path To Beta

Post-alpha, high-availability AI capability is the main beta focus.

Beta-readiness work should prioritize:

- provider contract automation for each capability group,
- capability-specific availability dashboards,
- alert routing by capability group,
- quota and cost threshold alerts,
- timeout and retry budgets,
- circuit-breaker behavior,
- provider authentication failure drills,
- degraded single-capability drills,
- provider response validation,
- artifact replay and provenance review,
- customer-visible AI status history,
- support workflow for paid AI unavailability,
- operational ownership for provider accounts, credentials, models, quota, and incident escalation.

Beta high-availability work must preserve the Human Authority And Manual Continuity Doctrine.

## 10. Route And Surface Control

Every exposed API route and WEB page must be classified before release.

| Classification | Rule |
|---|---|
| Alpha-supported | Must pass tests, docs, staging smoke, and user-facing behavior checks. |
| Alpha-supporting internal | May be used by supported flows or operators; must be documented and safe. |
| Deferred | Must return explicit deferred behavior or be hidden. |
| Hidden | Must not appear in alpha navigation and must not fetch hidden live data from WEB. |
| Experimental | Not allowed in external design-partner alpha unless explicitly approved. |

Stub IDs, fake success payloads, and "not implemented yet" responses are not acceptable on alpha-supported paths.

They may exist only on deferred or hidden paths when:

- the route is unreachable from the supported journey,
- the behavior is documented,
- the user-facing UX is not misleading,
- an owner and post-alpha disposition are recorded.

## 11. Data, Audit, And Provenance Requirements

Every AI-assisted decision-support artifact must include:

- tenant scope,
- feature key,
- capability group,
- provider name when provider-backed,
- model id or configured model reference when available,
- source fact hash,
- reason codes,
- availability status,
- actor or system initiator,
- timestamp,
- link to durable business object when applicable.

Every human override or review action must include:

- actor,
- timestamp,
- reason,
- before/after values where applicable,
- source record link,
- tenant scope,
- decision-trail or audit-log entry when the action affects procurement decisions.

## 12. Security And Tenant Isolation Requirements

All alpha-supported routes must preserve:

- tenant-scoped query roots,
- 404 for missing and wrong-tenant access,
- no cross-tenant existence leaks,
- authorization on protected routes,
- no secrets in logs,
- no provider credentials in API responses,
- no raw sensitive evidence sent to AI providers unless approved by data-handling policy,
- safe handling of upload paths and storage references.

The missing-file comparison failures observed during readiness analysis are launch blockers because file/path safety is part of this requirement.

## 13. Release Evidence Ledger

The release checklist remains the evidence ledger, but it must defer to this spec for gate interpretation.

Every evidence row must include:

- command or manual step,
- executor,
- date,
- environment,
- commit SHA,
- result,
- artifact or screenshot reference when applicable,
- owner,
- whether the evidence supports AI-enabled alpha or manual-continuity alpha.

Evidence expires when:

- code changes in affected API/WEB/package/orchestrator files,
- OpenAPI or generated client changes,
- migrations change,
- provider config changes,
- staging env changes,
- route surface changes,
- release posture changes between AI-enabled and manual-continuity alpha.

## 14. Go, Conditional Go, And No-Go Rules

### 14.1 Go: AI-Enabled Alpha

Allowed only when:

- local engineering gates pass,
- staging gates pass,
- manual continuity gates pass,
- AI-enabled gates pass,
- customer disclosure accurately states AI-enabled posture,
- sign-offs are complete.

### 14.2 Conditional Go: Manual-Continuity Alpha

Allowed only when:

- local engineering gates pass for manual-supported flow,
- staging gates pass,
- manual continuity gates pass,
- AI-disabled/unavailable disclosure is complete,
- AI surfaces do not fabricate success,
- paid-feature unavailability is clearly disclosed,
- support and operator runbooks match the posture,
- sign-offs explicitly approve manual-continuity alpha.

This posture is not a failure if it is deliberate, disclosed, and evidence-backed.

### 14.3 No-Go

No external alpha launch is allowed when any of the following is true:

- WEB lint or build is red.
- Supported API alpha tests are red.
- Staging mocks-off smoke is missing.
- Manual continuity is broken.
- AI is unavailable but undisclosed.
- AI surfaces fabricate success.
- Quote upload, normalization, comparison freeze, award signoff, or decision-trail review cannot complete.
- Tenant isolation defects are present.
- Required sign-offs are missing.
- Release posture is ambiguous.

## 15. Required Spec Backlinks

This spec must be linked from:

- `apps/atomy-q/docs/CURRENT_STATE.md`,
- `apps/atomy-q/docs/INDEX.md`,
- `apps/atomy-q/docs/02-release-management/current-release/release-overview.md`,
- `apps/atomy-q/docs/02-release-management/current-release/release-plan.md`,
- `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md`,
- `apps/atomy-q/docs/02-release-management/current-release/blockers.md`.

## 16. Implementation Planning Boundary

This spec is a design and launch-readiness contract. It is not an implementation plan.

The next implementation plan should decompose work into at least these slices:

1. WEB release gate repair.
2. API golden-path repair.
3. AI truthfulness and artifact repair.
4. Route/surface classification and deferred behavior audit.
5. Staging evidence and disclosure package.
6. Beta high-availability AI backlog seed.

No implementation slice may weaken the Human Authority And Manual Continuity Doctrine.
