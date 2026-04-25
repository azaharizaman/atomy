# Atomy-Q Alpha Release Handoff

**Date:** 2026-04-25  
**Status:** Draft for execution  
**Audience:** Release coordinator, engineering lead

## Purpose

This document is the execution handoff for the Atomy-Q alpha release after completion of the six AI implementation plans. It converts the architecture and plan requirements into an operator-usable release checklist with explicit evidence, rollback posture, and go/no-go gates.

This document is not a replacement for the implementation plans. The plans remain the source of truth for design intent and implementation scope. This handoff is the source of truth for alpha release execution.

## Scope

This handoff covers:

- provider-backed AI readiness in staging,
- alpha release gates by capability group,
- manual continuity verification for the main RFQ chain,
- failure drills and required evidence,
- verification command matrix,
- rollback through `AI_MODE=off`,
- go/no-go decision recording,
- blocker classification for unresolved issues.

This handoff does not introduce new product scope.

## Release Assumptions

- All six AI implementation plans listed in [2026-04-23-atomy-q-ai-plan-index.md](/home/azaharizaman/dev/atomy/docs/superpowers/plans/2026-04-23-atomy-q-ai-plan-index.md) are implemented on the release candidate branch.
- Alpha default runtime mode is `AI_MODE=provider`.
- Alpha uses one globally selected provider for all capability groups.
- Main RFQ chain must remain manually operable when AI is disabled, degraded, or unavailable.
- AI-only surfaces must return truthful unavailable states and must not fabricate successful AI output.
- Provider-backed actions must preserve audit and provenance records required by the design and implementation plans.

## Capability Groups And Release Gates

| Capability Group | Alpha Gate | Required Outcome | Blocking Failure |
|---|---|---|---|
| `document_intelligence` | Staging provider contract test passes and quote upload/reparse works | Source extraction works with real provider; manual source-line continuity also works | Provider path broken or manual continuity broken |
| `normalization_intelligence` | Staging provider contract test passes and normalize workspace stays usable | AI suggestions work with real provider; manual mapping and overrides still work | Suggestions fail without truthful state, or manual normalize path blocked |
| `sourcing_recommendation_intelligence` | Staging provider contract test passes and recommendation surfaces show provenance | AI recommendation works or truthfully reports unavailable; manual vendor selection remains usable | Synthetic recommendation success or blocked vendor workflow |
| `comparison_intelligence` | Staging provider contract test passes and comparison overlays are bounded to AI surfaces | Deterministic comparison remains authoritative; AI overlays are truthful and auditable | Frozen facts mutated by AI or unavailable state lies |
| `award_intelligence` | Staging provider contract test passes and award guidance/debrief provenance persists | Award guidance is reviewable and manual award still works | No provenance, broken manual award, or silent AI failure |
| `insight_intelligence` | Staging provider contract test passes and insights are clearly non-authoritative | Insight surfaces work or truthfully degrade without blocking RFQ chain | Fake summaries presented as healthy output |
| `governance_intelligence` | Staging provider contract test passes and governance narrative is clearly separated from authoritative records | Governance AI assists review but does not become source of truth | Narrative mutates authoritative governance state or hides failure |

## Blocker Rubric

### Release blockers

- Tenant isolation leak or cross-tenant existence leak.
- AI-only endpoint returns synthetic success or fabricated AI payload.
- Main RFQ manual continuity path fails under `AI_MODE=off`.
- Provider contract test fails for a capability group required in alpha.
- Audit or provenance required for AI-assisted decisions is missing or unreadable.
- Rollback through `AI_MODE=off` is not documented or not verified.

### Must-fix before release unless explicitly waived by engineering lead

- Degraded or unavailable AI state is not visible in API or WEB where required.
- Capability-group alerting is missing, noisy, or routed to wrong owner.
- Staging endpoint/model/token ownership is unclear.
- Verification commands are incomplete, not reproducible, or not recorded.

### Post-alpha candidates

- Non-critical UX polish on AI narratives or layout.
- Additional dashboards or status placement not required for release safety.
- Non-blocking prompt quality improvements after truthful behavior is proven.

## Ownership Checklist

Release coordinator and engineering lead must fill all rows before go/no-go.

| Domain | Required Evidence | Owner | Status | Notes |
|---|---|---|---|---|
| Release branch | Exact branch, commit SHA, and release candidate tag if used |  |  |  |
| Active provider | Selected provider and `AI_MODE` value for staging and alpha |  |  |  |
| Provider account | Billing owner and account/organization owner |  |  |  |
| Endpoint topology | One endpoint or route per capability group |  |  |  |
| Model binding | Model id and revision per endpoint group |  |  |  |
| Credentials | Token or service credential owner and storage location |  |  |  |
| Quota policy | Spend threshold, quota ceiling, alert threshold, escalation owner |  |  |  |
| Network posture | Egress allowlists, proxies, firewall constraints |  |  |  |
| Incident response | Primary and backup contact for provider outage/token failure/quota exhaustion |  |  |  |
| Data posture | Approved retention, redaction, and compliance posture for tenant data |  |  |  |
| Secret rotation | Rotation owner, schedule, emergency revoke path |  |  |  |
| Staging environment | Staging endpoint owner, smoke credentials, refresh policy |  |  |  |

## Preflight Checklist

All items must be checked before staging verification starts.

- [ ] Release candidate branch identified and frozen for alpha verification.
- [ ] Required migrations applied in staging.
- [ ] `.env` and secret state for staging match intended alpha topology.
- [ ] `AI_MODE=provider` confirmed in staging.
- [ ] One global provider selected for staging and alpha.
- [ ] Endpoint route exists for each capability group.
- [ ] Provider credentials validated without exposing secrets in logs.
- [ ] Model ids and revisions documented for each endpoint group.
- [ ] Logging fields required by Plan 6 are present on AI call paths.
- [ ] Alert routing for degraded/unavailable capability groups is configured.
- [ ] Audit/provenance persistence enabled for AI-assisted actions in scope.

## Staging Provider Readiness

All capability groups required for alpha must have evidence recorded here.

| Capability Group | Endpoint/Route | Model | Contract Test Command | Result | Evidence Link/Note |
|---|---|---|---|---|---|
| `document_intelligence` |  |  |  |  |  |
| `normalization_intelligence` |  |  |  |  |  |
| `sourcing_recommendation_intelligence` |  |  |  |  |  |
| `comparison_intelligence` |  |  |  |  |  |
| `award_intelligence` |  |  |  |  |  |
| `insight_intelligence` |  |  |  |  |  |
| `governance_intelligence` |  |  |  |  |  |

Rule: healthcheck-only green is insufficient. Each capability group needs a request/response contract proof that the staging provider accepts Atomy-Q payload shape and returns output the capability mapper validates.

## Failure Drill Matrix

Every drill must be run against staging and recorded before alpha release.

| Drill | Trigger | Expected API Outcome | Expected WEB Outcome | Required Evidence | Pass/Fail | Notes |
|---|---|---|---|---|---|---|
| AI-off continuity | `AI_MODE=off` | Manual RFQ chain works; AI-only endpoints return unavailable/`ai_disabled` | AI affordances hide or show unavailable copy; manual paths stay visible | Command log, screenshots, API response samples |  |  |
| Single-group degradation | Disable or degrade one endpoint group only | Only affected capability group degrades | Only affected AI panel shows issue | Health snapshot, logs, screenshots |  |  |
| Provider auth failure | Invalid token or credential | Provider calls fail loudly, no fake success | Relevant surfaces show unavailable | Error log sample, status output, screenshots |  |  |
| Quota exhaustion | Budget or quota threshold exceeded | Capability becomes degraded/unavailable and alert fires | AI assist surfaces collapse truthfully to continuity path | Alert evidence, logs, screenshots |  |  |
| Timeout storm | Latency exceeds retry budget | Retry budget honored, capability degrades truthfully | No page-wide crash; scoped unavailable AI state only | Logs with timeout/retry behavior, screenshots |  |  |

Rule: no drill passes if any AI-only endpoint returns fabricated output or if the main RFQ chain becomes unusable.

## Manual Continuity Verification

Under `AI_MODE=off`, release coordinator and engineering lead must verify the main RFQ chain remains usable end-to-end:

- [ ] quote upload remains possible,
- [ ] manual source-line work remains possible,
- [ ] manual normalization mapping and overrides remain possible,
- [ ] deterministic comparison remains usable on persisted normalized data,
- [ ] manual vendor selection remains possible,
- [ ] manual award flow remains possible,
- [ ] approval workflow progression remains possible.

Record supporting evidence:

| Continuity Step | Evidence | Verified By | Notes |
|---|---|---|---|
| Quote upload/manual ingestion |  |  |  |
| Manual source-line editing |  |  |  |
| Manual normalization mapping |  |  |  |
| Deterministic comparison |  |  |  |
| Manual vendor selection |  |  |  |
| Manual award submission |  |  |  |
| Approval workflow progression |  |  |  |

## Verification Matrix

Record exact command, date, executor, and result. Replace placeholders with repo-accurate commands where plan-specific verify scripts exist.

| Verification Target | Command | Executor | Date | Result | Notes |
|---|---|---|---|---|---|
| Foundation/runtime governance tests | `composer verify:atomy-q-ai-foundation-runtime-governance` |  |  |  |  |
| Quote intake/normalization tests | `composer verify:atomy-q-ai-quote-intake-normalization` |  |  |  |  |
| Recommendation/decision-support tests | `composer verify:atomy-q-ai-sourcing-recommendation` |  |  |  |  |
| Comparison/award/approval tests | `composer verify:atomy-q-ai-comparison-award-approval` |  |  |  |  |
| Insights/governance/reporting tests | `composer verify:atomy-q-ai-insights-governance-reporting` |  |  |  |  |
| WEB AI primitives | `cd apps/atomy-q/WEB && npm run test:unit -- src/hooks/use-ai-status.test.ts src/components/ai/ai-unavailable-callout.test.tsx src/components/ai/ai-narrative-panel.test.tsx` |  |  |  |  |
| RFQ alpha journeys E2E | `cd apps/atomy-q/WEB && npm run test:e2e -- tests/rfq-alpha-journeys.spec.ts tests/rfq-lifecycle-e2e.spec.ts tests/screen-smoke.spec.ts` |  |  |  |  |
| AI-off continuity drill evidence | Release-time staging command/log entry |  |  |  |  |
| Degraded drill evidence | Release-time staging command/log entry |  |  |  |  |

If any command differs in final branch reality, update this document before release. Release evidence must not rely on memory or chat history.

## Rollback Procedure

Rollback path for alpha is environment-level `AI_MODE=off`.

### Trigger rollback when any of these occur

- required capability group unavailable in production-like environment,
- synthetic or misleading AI success detected,
- provider auth, quota, or timeout failure cannot be restored inside release window,
- manual continuity does not behave as specified under degraded AI state,
- audit or provenance gap found on an in-scope AI-assisted action.

### Rollback steps

1. Switch environment configuration to `AI_MODE=off`.
2. Redeploy or reload configuration according to environment process.
3. Verify AI status endpoint reports disabled state.
4. Verify AI-only surfaces show truthful unavailable state.
5. Verify main RFQ manual continuity path works.
6. Notify release stakeholders that alpha is running in manual continuity mode.
7. Do not restore `AI_MODE=provider` until provider health and contract tests are green again.

### Expected user-visible effect after rollback

- AI-only surfaces become unavailable or hide AI-specific affordances.
- Main RFQ chain continues through manual and deterministic paths.
- No authoritative business record is lost or silently mutated by rollback.
- Existing audit and provenance records remain reviewable.

## Go/No-Go Decision Record

Complete this section in final alpha release review.

| Decision Area | Question | Answer | Owner | Notes |
|---|---|---|---|---|
| Provider readiness | Do all required capability groups pass staging contract verification? |  |  |  |
| Manual continuity | Does main RFQ chain complete under `AI_MODE=off`? |  |  |  |
| Truthful degradation | Do AI-only surfaces fail truthfully without synthetic success? |  |  |  |
| Audit/provenance | Are required provenance records persisted and reviewable? |  |  |  |
| Alerts/ownership | Are alerts, quota thresholds, and incident owners assigned? |  |  |  |
| Rollback readiness | Is `AI_MODE=off` rollback verified and documented? |  |  |  |

Final decision:

- [ ] Go
- [ ] No-go

Approvers:

| Role | Name | Decision Date | Signature/Confirmation |
|---|---|---|---|
| Release coordinator |  |  |  |
| Engineering lead |  |  |  |

## Open Risks

Track non-blocking risks separately from blockers so release review stays clear.

| Risk | Impact | Mitigation | Owner | Release Blocking |
|---|---|---|---|---|
|  |  |  |  | Yes / No |

## Source References

- [2026-04-23-atomy-q-ai-plan-index.md](/home/azaharizaman/dev/atomy/docs/superpowers/plans/2026-04-23-atomy-q-ai-plan-index.md)
- [2026-04-23-atomy-q-global-ai-fallback-design.md](/home/azaharizaman/dev/atomy/docs/superpowers/specs/2026-04-23-atomy-q-global-ai-fallback-design.md)
- [2026-04-23-atomy-q-ai-launch-readiness-and-operational-hardening.md](/home/azaharizaman/dev/atomy/docs/superpowers/plans/2026-04-23-atomy-q-ai-launch-readiness-and-operational-hardening.md)
