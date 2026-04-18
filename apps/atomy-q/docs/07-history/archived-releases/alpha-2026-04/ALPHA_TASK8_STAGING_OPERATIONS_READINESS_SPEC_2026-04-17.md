# Alpha Task 8 Staging Operations Readiness Spec

## Document Control

- **Task:** Section 9, Task 8 - Staging Operations Readiness
- **Date:** 2026-04-17
- **Status:** Draft for review
- **Primary plan reference:** [ALPHA_RELEASE_PLAN_2026-04-15.md](./ALPHA_RELEASE_PLAN_2026-04-15.md)

## 1. Purpose

Task 8 closes blocker `A7` by turning the current alpha from "implemented in a developer workspace" into "deployable and operable by a staging owner."

The API and WEB now have much stronger local evidence across quote ingestion, live-mode fail-loud behavior, award workflow, Users & Roles, hidden alpha surfaces, and contract regeneration. What is still weak is the operator boundary. A new person should not need tribal knowledge to decide which env variables matter, whether quote ingestion runs synchronously or via a worker, which storage wiring is required, which origins are allowed through CORS, or how to run the mocks-off golden path on a staging URL.

This spec defines the minimum operational contract, documentation set, and verification evidence required to call the Atomy-Q alpha staging-ready for a design-partner environment.

## 2. Product And Operational Decision

Task 8 adopts **Option B: documentation-first staging hardening with a thin verification layer**.

This means Task 8 will:

- keep operator docs as the primary source of truth
- add copyable env examples rather than prose-only configuration guidance
- add a dedicated staging runbook
- add a small number of repo-native verification commands or scripted checks where ambiguity is expensive
- require evidence capture in the release checklist

This task will **not**:

- build a full deployment automation framework
- introduce a large platform-management subsystem
- replace the runbook with undocumented one-off shell usage

## 3. Current State

The repository already contains part of the staging contract, but it is fragmented and still developer-oriented.

Observed current state:

- `apps/atomy-q/API/README.md` documents many env keys and a local example `.env`, but it is still oriented around local setup rather than staging operations.
- `apps/atomy-q/API/.env.example` exists, which reduces the gap called out in the release plan note, but it still needs a staging-quality contract review.
- `apps/atomy-q/WEB/README.md` documents local setup, E2E usage, and some live-mode notes, but not a complete staging runtime contract.
- `apps/atomy-q/WEB/.env.example` exists but is minimal and does not define the intended alpha staging posture clearly.
- `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md` already acts as the evidence ledger and should be extended rather than bypassed.
- `apps/atomy-q/docs/STAGING_ALPHA_RUNBOOK.md` does not exist yet.

The main problem is therefore not "missing all staging docs." The main problem is "the staging contract is incomplete, split across files, and not yet proven with reproducible operator checks."

## 4. Scope

### In scope

- Define the required staging environment contract for API and WEB.
- Choose and document the quote-ingestion runtime mode for staging.
- Define staging prerequisites: DB migration, seed policy, queue expectations, storage wiring, URL and CORS assumptions, and feature-flag posture.
- Create a dedicated runbook for design-partner staging bring-up and smoke verification.
- Add a small verification layer for storage and queue/runtime health where the runbook would otherwise be ambiguous.
- Record staging smoke evidence and environment assumptions in `ALPHA_RELEASE_CHECKLIST.md`.

### Out of scope

- Full infrastructure-as-code or container orchestration design.
- General production hardening for every future Atomy deployment environment.
- A broad observability or alerting program.
- Re-architecting quote ingestion purely for operational elegance.
- Replacing the release checklist with a separate release-management system.

## 5. Required Deliverables

Task 8 must produce or update the following operator-facing artifacts:

- `apps/atomy-q/API/README.md`
- `apps/atomy-q/WEB/README.md`
- `apps/atomy-q/API/.env.example`
- `apps/atomy-q/WEB/.env.example`
- `apps/atomy-q/docs/STAGING_ALPHA_RUNBOOK.md`
- `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`

Task 8 may also add minimal helper commands or scripted checks if they materially reduce ambiguity in the runbook.

## 6. Staging Runtime Decision

Task 8 adopts **synchronous deterministic quote ingestion for the alpha staging path** unless implementation reality proves that ingestion is already unavoidably asynchronous at the current boundary.

### Rationale

The design-partner alpha goal is operator confidence and reproducible buyer workflow validation, not architectural completeness. Synchronous deterministic ingestion reduces the number of moving parts that can block the core flow:

- fewer race conditions in quote upload and readiness transitions
- less worker timing ambiguity during partner demos and smoke runs
- easier diagnosis when quote ingestion fails
- lower dependency on queue health for the main alpha path

### Boundary rule

Task 8 must document the chosen runtime honestly:

- If staging uses synchronous deterministic ingestion, the docs must say so explicitly.
- If staging still uses queued ingestion, the runbook and checklist must make worker startup, job execution, and failed-job visibility hard release requirements.

Task 8 must not imply "real AI staging" if the shipped alpha path is deterministic. The env contract may still include dormant LLM fields, but the runbook must identify them as inactive unless intentionally enabled.

## 7. Environment Contract

Task 8 must distinguish **required**, **conditionally required**, and **dormant/deferred** environment keys.

### 7.1 API required env

The API staging contract must explicitly cover at least:

- app identity and base URL
  - `APP_ENV`
  - `APP_KEY`
  - `APP_URL`
- database connectivity
  - `DB_CONNECTION`
  - `DB_HOST`
  - `DB_PORT`
  - `DB_DATABASE`
  - `DB_USERNAME`
  - `DB_PASSWORD`
- JWT/auth
  - `JWT_SECRET`
  - `JWT_TTL`
  - `JWT_REFRESH_TTL`
  - `JWT_ALGO`
  - `JWT_ISSUER`
- queue/cache/runtime
  - Redis connection variables actually used by the app/runtime
  - queue connection variables if queueing remains enabled
- storage
  - `FILESYSTEM_DISK`
  - S3/MinIO variables for the configured disk
- CORS
  - allowed WEB origins for the staging hostname(s)
  - credential support expectations if the WEB sends cookies or auth-adjacent credentials
- mail/notifications
  - the mail driver and minimum fields needed for user/admin invite semantics or reminder flows
- quote intelligence
  - `QUOTE_INTELLIGENCE_MODE`
  - any deterministic or LLM-provider fields relevant to the chosen runtime
- feature flags
  - `FEATURE_PROJECTS_ENABLED`
  - `FEATURE_TASKS_ENABLED`

### 7.2 API env classification rules

`API/.env.example` must clearly communicate:

- which values are mandatory for any staging boot
- which values are required only for specific optional capabilities
- which values are documented for future/provider completeness but are not active in the alpha staging path

The file must be copyable by an operator without reconstructing key names from prose in the README.

### 7.3 WEB required env

The WEB staging contract must explicitly cover at least:

- `NEXT_PUBLIC_API_URL`
- `NEXT_PUBLIC_USE_MOCKS=false`
- any alpha-mode flag expected by staging navigation behavior
- Playwright/staging smoke variables where they are part of the documented verification workflow
  - `PLAYWRIGHT_BASE_URL`
  - any existing-server expectation if the smoke is run against a deployed host

### 7.4 URL and origin rules

Task 8 must document one canonical staging shape:

- exact API base URL form consumed by the WEB
- exact WEB origin(s) that must be allowed by API CORS
- whether direct browser access to uploaded quote assets uses app-mediated reads, storage URLs, or both

This must be documented with explicit examples, not only variable names.

## 8. Staging Prerequisites

The runbook must document the minimum environment prerequisites before smoke execution:

- database migration command
- seed policy
- storage bucket or MinIO provisioning and credential wiring
- queue worker command if any alpha path depends on it
- failed-job visibility mechanism if queueing is active
- allowed CORS origins
- whether `FEATURE_PROJECTS_ENABLED` and `FEATURE_TASKS_ENABLED` should be enabled for design-partner staging

### Seed policy requirement

Task 8 must state clearly whether staging:

- starts from migrations only
- uses baseline seed data
- uses a targeted flow seed command such as RFQ-flow seeding

The runbook must explain which option is used for design-partner validation and why.

## 9. Thin Verification Layer

Task 8 should add only the smallest verification surface that materially reduces ambiguity for operators.

### 9.1 Required verification targets

The verification layer must cover:

- storage write/read on the configured disk
- queue or runtime execution health if ingestion remains async
- reproducible mocks-off staging smoke procedure

### 9.2 Storage verification

Task 8 should provide a repo-native way to verify that the configured staging disk can:

- accept a test write
- persist the object to the expected location
- be read back by the same application path or storage abstraction used by quote handling

The check may be implemented as:

- an Artisan command
- a focused test or smoke script suitable for staging
- another small repo-native command with clear pass/fail output

The check must avoid destructive behavior and must identify:

- disk name
- path used
- success or failure reason

### 9.3 Queue verification

If quote ingestion or any alpha-critical path still depends on the queue in staging, Task 8 must provide a repo-native way to verify:

- worker startup command
- that a lightweight job is processed successfully
- that failures become visible through the documented failed-job path

If the chosen staging path is synchronous and no alpha-critical flow depends on queue execution, the runbook must say that explicitly rather than pretending queue verification is mandatory for the main smoke.

### 9.4 Why the verification layer stays small

Task 8 is not a platform rewrite. The verification layer exists to make the runbook concrete at the highest-risk points only:

- storage
- queue/runtime execution
- live golden-path proof

## 10. Staging Smoke Definition

Task 8 requires one documented, reproducible, mocks-off golden-path smoke from the deployed WEB.

### Required smoke journey

The documented smoke must cover:

1. tenant registration
2. login
3. RFQ creation
4. vendor invite
5. quote upload
6. normalization readiness
7. comparison final
8. award create
9. award signoff
10. Users & Roles invite

### Smoke posture rules

- The smoke must run with `NEXT_PUBLIC_USE_MOCKS=false`.
- The smoke must use the deployed staging URLs, not localhost assumptions, unless the runbook explicitly documents a pre-staging local rehearsal separately.
- The smoke may be partly manual if full browser automation is not yet practical, but the steps and expected outcomes must be explicit.
- If a step depends on the chosen ingestion mode, the runbook must name the wait condition or success signal.

## 11. Documentation Architecture

Task 8 should separate concerns cleanly.

### 11.1 README role

The API and WEB READMEs should:

- describe the environment contract at a high level
- link to the staging runbook for the full operational sequence
- document where alpha staging differs from local development

They should not become giant release-day scratchpads.

### 11.2 Runbook role

`STAGING_ALPHA_RUNBOOK.md` should be the operator-facing source of truth for:

- environment setup sequence
- prerequisite checks
- runtime mode decision
- storage and queue verification
- staging smoke execution
- evidence capture instructions

### 11.3 Checklist role

`ALPHA_RELEASE_CHECKLIST.md` should remain the evidence ledger and release-governance record.

Task 8 must extend it with:

- exact staging URLs
- operator name
- smoke date
- env assumptions that materially affect the result
- storage verification result
- queue verification result if applicable
- final smoke outcome and any accepted constraints

## 12. Acceptance Criteria

Task 8 is complete only when all of the following are true:

- `API/.env.example` and `WEB/.env.example` can be copied by an operator without reconstructing the environment contract from prose.
- `API/README.md` and `WEB/README.md` document the staging contract honestly and link to the staging runbook.
- `STAGING_ALPHA_RUNBOOK.md` exists and contains the full bring-up and smoke flow.
- The chosen quote-ingestion runtime mode for staging is explicit and consistent across docs.
- Storage verification has a documented and reproducible pass/fail procedure.
- Queue verification has a documented and reproducible pass/fail procedure if queueing remains part of the alpha-critical path.
- A mocks-off staging smoke procedure is documented for the full alpha journey.
- `ALPHA_RELEASE_CHECKLIST.md` has fields or sections ready to record staging URLs, operator, date, env assumptions, and smoke evidence.

## 13. Verification Expectations

Minimum verification evidence for Task 8 should include:

- documentation review of updated READMEs and `.env.example` files
- execution of the storage verification path against the configured staging disk or an operator-equivalent pre-staging environment
- execution of queue verification if staging remains async for alpha-critical ingestion
- at least one recorded mocks-off staging smoke or an explicit blocker note stating why design-partner readiness is not yet earned

If the team cannot complete a true staging smoke in time, Task 8 must not be marked complete implicitly. The release checklist must state that the result is **internal alpha only** until staging smoke evidence exists.

## 14. Deferred Work And Non-Goals

Task 8 does not require:

- full deployment automation
- production-grade observability dashboards
- generalized infrastructure modules for every future environment
- replacing deterministic alpha ingestion with a live LLM provider
- a perfectly automated end-to-end browser smoke for every operator scenario

Task 8 does require honesty. If any staging dependency remains manual or fragile, the runbook and checklist must say so directly.
