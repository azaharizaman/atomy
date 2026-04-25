# Atomy-Q AI Launch Readiness And Operational Hardening Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the alpha program with production-grade operator handoff, observability, failure drills, release verification, and documentation that proves Atomy-Q is AI-first without becoming operationally brittle.

**Architecture:** This plan does not introduce a new business feature. It hardens the cross-plan runtime: health monitoring, alerts, cost/quota tracking, audit evidence, operator responsibilities, staging drills, release checklists, and documentation. The result should make the AI posture supportable by engineering and operations, not just technically implemented.

**Tech Stack:** PHP 8.3, Laravel console/scheduler, Nexus packages, Notifier, Outbox, Audit, WEB status surfaces, PHPUnit, Vitest, Playwright, operational docs.

---

## Scope

- Endpoint-group observability and alerting
- Quota, timeout, and degraded-state logging
- Operator responsibility checklist for keys, tokens, endpoints, quotas, networking, and model ownership
- Provider contract verification for every endpoint group
- Staging validation and failure drills
- Alpha release checklist and rollback posture
- Final documentation alignment across spec, plans, env docs, and implementation summaries

## Runbook Output

Plan 6 is the operator handoff slice. The written handoff must be concrete enough that a release coordinator can execute it without guessing:

### Operator Checklist

| Domain | Required Evidence |
|---|---|
| Provider ownership | Named provider account or organization, billing owner, and route/endpoint owner |
| Active provider | The selected global provider for the environment and the exact `AI_MODE` value |
| Endpoint topology | One live endpoint URL or route per capability group, with the owning capability group named |
| Credentials | Auth token or service credential for each provider route plus the rotation owner |
| Model binding | Model id and revision per endpoint group, even when the runtime reads them indirectly through config |
| Capacity policy | Scaling policy, timeout budget, retry budget, and circuit-breaker expectations |
| Budget ownership | Quota limit, cost alert threshold, and the human owner for each threshold |
| Network policy | Allowlisted egress, private networking, and firewall/proxy constraints |
| Staging readiness | Non-production endpoints, smoke credentials, and the owner of the staging environment |
| Incident response | Pager / contact list for provider degradation, quota exhaustion, and token failure |
| Data posture | Approved data-handling, retention, and compliance posture for tenant data |
| Secret rotation | Rotation schedule, emergency revoke path, and backup contact |

### Rollback Posture

- Roll back by switching `AI_MODE=off` at the environment level. Do not rely on ad hoc per-endpoint toggles as a substitute for a real rollback.
- When rollback is active, the main RFQ chain must remain manually operable and AI-only surfaces must show truthful unavailable states.
- No AI-only endpoint may return synthetic success payloads during rollback. The response must say unavailable, not pretend the provider is still healthy.
- Manual continuity paths remain authoritative during rollback: upload, manual source-line work, normalization overrides, deterministic comparison, vendor selection, award signoff, and approvals.
- Restore `AI_MODE=provider` only after the selected provider endpoints, health checks, and contract tests are green again.

### Failure Drill Matrix

| Drill | Trigger | Expected API Outcome | Expected WEB Outcome | Success Signal |
|---|---|---|---|---|
| AI-off continuity | `AI_MODE=off` | Manual RFQ chain continues; AI-only endpoints return `ai_disabled` / unavailable | AI controls hide or show unavailable copy; manual paths stay visible | RFQ work can finish without synthetic AI output |
| Single-group degradation | One capability-group endpoint degrades or times out | Only the affected capability becomes unavailable or degraded | Only the affected AI panel calls out the issue | Other capability groups remain usable |
| Provider auth failure | Token or route credential is invalid | Provider calls fail loudly and do not produce fake success | The relevant AI surface shows unavailable, not empty success | Failure is visible in logs and status |
| Quota exhaustion | Provider quota budget is exceeded | Status becomes degraded or unavailable per policy and alerting fires | AI assist surfaces collapse to manual continuity | Operator sees the budget breach immediately |
| Timeout storm | Provider latency exceeds retry budget | Retry budget is honored and the capability falls back truthfully | No page-wide failure, only scoped unavailable AI affordances | Manual workflow remains usable under load |

### Verification Matrix

| Verification Target | Command | Pass Criteria |
|---|---|---|
| Insight/governance plan verification | `composer verify:atomy-q-ai-insights-governance-reporting` | InsightOperations tests and the API feature-flag context both pass in their own execution contexts |
| WEB AI primitives | `cd apps/atomy-q/WEB && npm run test:unit -- src/hooks/use-ai-status.test.ts src/components/ai/ai-unavailable-callout.test.tsx src/components/ai/ai-narrative-panel.test.tsx` | Shared AI status, unavailable callout, and narrative rendering stay stable |
| RFQ browser continuity | `cd apps/atomy-q/WEB && npm run test:e2e -- tests/rfq-alpha-journeys.spec.ts tests/rfq-lifecycle-e2e.spec.ts tests/screen-smoke.spec.ts` | AI-assisted and AI-off browser journeys remain usable |
| Staging drill evidence | Deployment-time `AI_MODE=off` and degraded-endpoint runs captured in the release log | The operator log records expected API and WEB outcomes for each drill |

## Layer Ownership

- **Layer 1**
  - `packages/Notifier`: alert contracts for degraded/unavailable AI capability groups.
  - `packages/Outbox`: publish post-commit AI events and alerts.
  - `packages/Audit` and `packages/AuditLogger`: persisted operator-visible evidence of AI-assisted actions and failures.
  - `packages/Idempotency`: verify retry safety for AI-triggering endpoints.
- **Layer 2**
  - `orchestrators/IntelligenceOperations`: runtime health snapshots, drill helpers, alert threshold coordination.
- **Layer 3**
  - `apps/atomy-q/API`: scheduled health checks, console commands, notifier wiring, structured logs, metrics, readiness endpoints, deploy docs.
  - `apps/atomy-q/WEB`: operator-visible but non-intrusive AI status indicators where appropriate.

## File Structure

- Modify: `orchestrators/IntelligenceOperations/src/...` for alert thresholds and drill-friendly snapshots
- Modify: `apps/atomy-q/API/app/Console/Commands/...` or create new AI health/diagnostic commands
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Modify: `apps/atomy-q/API/app/Exceptions/...` if status/error mapping needs standardization
- Modify: `apps/atomy-q/API/config/logging.php`
- Modify: `apps/atomy-q/API/config/atomy.php`
- Modify: `apps/atomy-q/API/routes/api.php` only if readiness/diagnostic endpoints are needed
- Add: API tests for health checks, degraded alerts, and runbook-critical paths
- Modify: `apps/atomy-q/WEB/src/components/layout/...` only if a separate approval adds a minimal operator-facing status placement later
- Modify: `apps/atomy-q/API/.env.example`
- Modify: `apps/atomy-q/WEB/.env.example`
- Modify documentation:
  - `docs/superpowers/specs/2026-04-23-atomy-q-global-ai-fallback-design.md`
  - all AI implementation plans if any file names or responsibilities drift
  - affected `IMPLEMENTATION_SUMMARY.md` files
  - any operator/runbook docs created during implementation

## Task 1: Observability And Alerting

- [ ] Add structured logging fields for:
  - `ai_mode`
  - `capability_group`
  - `feature_key`
  - `provider`
  - `endpoint_group`
  - `tenant_id`
  - `rfq_id`
  - `outcome`
  - `reason_code`
- [ ] Confirm Plans 2-5 added these fields on their provider-backed call paths, then close gaps here rather than introducing observability only at the end.
- [ ] Add degraded/unavailable alert flows through `Notifier` and `Outbox`.
- [ ] Ensure alerts are capability-group aware so document extraction failures do not page the wrong operational owner.

## Task 2: Operator Responsibility Handoff

- [ ] Produce a concrete operator checklist covering:
  - selected provider account or organization ownership
  - active provider choice for the environment (`openrouter`, `huggingface`, future supported value)
  - endpoint URLs per capability group
  - auth tokens or service credentials
  - model ids and revisions
  - scaling policy and autoscaling thresholds
  - timeout and retry budgets
  - quota and cost budget ownership
  - network policy and allowlists
  - secret rotation procedure
  - staging endpoint provisioning
  - incident contact ownership
  - data-handling approval and retention policy
- [ ] Link this checklist from the implementation summaries and release docs.

## Task 3: Failure Drills And Release Verification

- [ ] Define and automate at least these drills:
  - `AI_MODE=off` main RFQ chain continuity drill
  - single endpoint-group degraded drill
  - provider auth/token failure drill
  - quota exceeded drill
  - timeout/retry storm drill
- [ ] Capture expected API and WEB outcomes for each drill.
- [ ] Verify no AI-only endpoint returns synthetic success payloads during drills.
- [ ] Add provider contract tests for each endpoint group that verify the staging provider accepts the app request shape and returns a response that the capability mapper can validate:
  - document extraction
  - normalization suggestions
  - sourcing recommendation
  - comparison/award overlay
  - insight summary
  - governance narrative
- [ ] Treat healthcheck-only success as insufficient for alpha release. Health proves reachability; provider contract tests prove integration compatibility.

## Task 4: Release Gating And Rollback

- [ ] Define alpha release gates for every capability group and endpoint group.
- [ ] Document rollback posture:
  - how to switch to `AI_MODE=off`,
  - what user-visible changes occur,
  - what manual continuity steps support must expect,
  - what data remains safe and reviewable.
- [ ] Confirm deterministic/manual continuity coverage matches the spec for the main RFQ chain.

## Task 5: Final Verification Matrix

- [ ] Run a combined verification matrix covering:
  - Layer 1 package tests,
  - orchestrator tests,
  - API feature tests,
  - WEB unit tests,
  - RFQ E2E tests,
  - explicit AI-off and degraded drills.
- [ ] Record exact commands and outcomes in the final implementation summaries or release handoff doc.
- [ ] Make sure the handoff doc names the `AI_MODE=off` rollback, the failure-drill matrix, and the checklist owners so release execution does not require hunting across multiple docs.

## Exit Criteria

- Operations has a complete checklist of what must be provisioned and owned to run alpha AI.
- Engineering has repeatable drills for disabled, degraded, and unavailable AI states.
- The release team can prove Atomy-Q is AI-first in alpha without compromising RFQ continuity.
- Documentation, config, logs, alerts, and verification artifacts all match the shipped behavior.
