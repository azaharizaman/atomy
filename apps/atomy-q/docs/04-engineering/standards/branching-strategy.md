# Atomy-Q Branching Strategy

## Changelog

| Date | Version | Change |
|---|---|---|
| 2026-04-18 | 1.0 | Initial Atomy-Q branching strategy for alpha execution, remediation, release candidates, and hotfix control. |

## Purpose

This document defines the branching model for the Atomy-Q SaaS application. It exists to keep work isolated, reviewable, releasable, and traceable to the alpha change-control framework.

The strategy applies to:

- `apps/atomy-q/API`
- `apps/atomy-q/WEB`
- docs and release-management changes that affect alpha execution
- shared Nexus dependency work only when it directly supports Atomy-Q delivery

## Goals

The branching strategy must:

- keep each change small enough to review and verify
- prevent scope creep during alpha remediation
- preserve a clean path from branch to checklist evidence to merge
- make it obvious which changes belong to the current alpha release gate
- minimize the chance of regressions being mixed into unrelated work

## Non-Goals

This document does not:

- define the internal GitHub PR review process in detail
- replace the alpha change-control policy
- define product scope or release scope
- prescribe tooling beyond what is needed to support branch discipline

## Branching Principles

### 1. One branch should represent one reviewable change

Each branch should normally map to one clear purpose:

- a feature
- a remediation
- a release gate task
- a hotfix
- a documentation-only update

If a branch grows beyond one purpose, split it.

### 2. Branches must align with change-control

For alpha remediation and stabilization work:

- every branch must have a corresponding entry in the alpha change-control ledger
- the branch name should reflect the change intent
- the PR body should reference the ledger row and the affected blocker/task

### 3. Branches must preserve release hygiene

- do not mix unrelated refactors into a remediation branch
- do not carry hidden scope expansion inside a small fix branch
- do not merge release-candidate changes without the required verification evidence

### 4. Branches must be disposable

Branches are working artifacts, not long-term storage. Once merged, close them and let the canonical docs, implementation summaries, and history carry the durable record.

## Branch Types

### Default Branch

The repository default branch is the integration baseline for Atomy-Q.

Use it for:

- starting new work
- cutting release-candidate branches
- merging approved work after review and verification

Rules:

- keep it releasable
- do not leave alpha blocker work half-merged on the default branch
- do not use it as a scratchpad

### Feature Branches

Feature branches are for bounded product work that is not tied to a release gate.

Recommended pattern:

- `feature/<area>-<short-description>`

Examples:

- `feature/rfq-line-items`
- `feature/users-roles-ui`
- `feature/quote-normalization-panel`

Rules:

- the branch should stay within one product area
- the work must still obey the alpha standards if it touches alpha-supported flows
- if the work becomes a blocker fix, reclassify it under remediation

### Alpha Remediation Branches

Alpha remediation branches are the primary branch type for pre-release stabilization work.

Recommended pattern:

- `alpha/remediation/<area>`
- `alpha/remediation/<blocker-id>-<area>`

Examples:

- `alpha/remediation/rfq-line-items`
- `alpha/remediation/a3-live-mode-fallback`
- `alpha/remediation/task9-release-gate`

Rules:

- the branch must map to one alpha change-control ledger row
- the change scope must remain narrow
- the branch should not introduce unrelated product scope
- if the branch starts affecting multiple blockers, split it into separate branches

### Release-Candidate Branches

Release-candidate branches are used only when Atomy-Q is preparing a specific release decision.

Recommended pattern:

- `release/alpha-<date>`
- `release/<version>`

Examples:

- `release/alpha-2026-04`
- `release/alpha-task9`

Rules:

- create from the default branch after the release scope is frozen
- allow only stabilization, evidence, and blocker closure work
- no new product scope without explicit release-owner approval
- if the branch drifts into feature work, cut that work out immediately

### Hotfix Branches

Hotfix branches are for urgent fixes that must be isolated from normal feature flow.

Recommended pattern:

- `hotfix/<area>-<short-description>`

Examples:

- `hotfix/auth-tenant-404`
- `hotfix/staging-runbook-link`

Rules:

- hotfixes should be minimal and targeted
- hotfixes must still be verified before merge
- if the issue is release-blocking for alpha, the hotfix must be tracked in the alpha change-control ledger

### Documentation Branches

Use documentation branches for docs-only changes when they do not overlap with a code change.

Recommended pattern:

- `docs/<area>-<short-description>`

Examples:

- `docs/alpha-scope-update`
- `docs/branching-strategy`

Rules:

- docs-only branches should not include unrelated code changes
- if docs are updated because behavior changed, the behavior change branch should usually own the docs update

### Chore Branches

Chore branches are for tooling, dependency hygiene, and non-product maintenance.

Recommended pattern:

- `chore/<area>-<short-description>`

Examples:

- `chore/test-harness-update`
- `chore/docs-navigation-cleanup`

Rules:

- chores should be isolated from product behavior changes
- if a chore affects alpha release evidence or behavior, it must be treated as a remediation branch instead

## Branch Naming Rules

- use lowercase
- use hyphen-separated tokens
- prefer semantic branch names over ticket-number-only names
- keep the branch name short enough to scan in PR lists
- include the area first, then the intent
- avoid vague names like `fix-thing` or `new-branch`

## Workflow By Branch Type

### New Feature Workflow

1. Create the branch from the default branch.
2. Keep the implementation aligned to one feature area.
3. Update the relevant docs if the change creates a durable decision.
4. Run the required tests and checks for the changed surfaces.
5. Open a PR with a clear scope summary and verification notes.

### Alpha Remediation Workflow

1. Open or update the corresponding alpha change-control ledger entry.
2. Create the branch from the default branch or the active release-candidate branch, depending on freeze state.
3. Keep the change bounded to the stated blocker or task.
4. Run the required gates for the change class.
5. Record evidence in the current release checklist.
6. Merge only after the sign-off requirements are satisfied.

### Release-Candidate Workflow

1. Cut the branch when the alpha release scope is frozen.
2. Only allow stabilization, evidence, and blocker closure work.
3. Keep every change tied to the current release checklist.
4. Re-run impacted release gates after each meaningful fix.
5. Do not add unrelated enhancements.

### Hotfix Workflow

1. Branch from the closest safe integration point.
2. Keep the fix narrow and urgent.
3. Verify the fix immediately.
4. Backport or forward-port the fix if needed so the default branch and release branch do not diverge semantically.

## Merge Rules

- Branches should be merged only after verification evidence is complete.
- Every alpha remediation merge must map to the change-control ledger.
- Merge requests must not bundle unrelated changes just because they are nearby.
- If a branch is no longer needed, close it after merge rather than letting it linger.
- Never merge a branch that still has unresolved scope expansion or missing evidence for its branch class.

## Rebase And Sync Rules

- Keep branches current with the integration baseline.
- Rebase or merge-from-default regularly enough to avoid large drift.
- For release-candidate branches, keep sync operations conservative and explicit.
- Resolve conflicts immediately when they affect alpha scope, tenant behavior, contracts, or staging posture.

## Freeze Rules

When the alpha release enters final release-candidate mode:

- only stabilization and blocker closure work should land on the release branch by default
- no net-new alpha scope may be added
- changes that do not support release readiness should remain on feature or docs branches
- any exception must be explicit, documented, and approved by the release owner

## Branching And Change Control

The alpha change-control ledger is the authoritative record for remediation work.

Branch requirements:

- one ledger row per remediation branch
- branch name must be traceable to the ledger row
- PR description must name the blocker or task
- verification evidence must be recorded in the current release checklist

If a branch affects multiple blockers, split it unless the change is truly inseparable.

## Atomy-Q Specific Guidance

### API Branches

For `apps/atomy-q/API` work:

- keep auth, tenant, RFQ, quote intake, normalization, comparison, awards, and minimal users/roles changes isolated
- do not mix API contract regeneration with unrelated domain refactors unless they are part of one bounded release fix
- use release-candidate branches only for final stabilization or release gating work

### WEB Branches

For `apps/atomy-q/WEB` work:

- keep live-mode hook changes separated from UI polish unless both are required for one remediation
- keep mock-mode seed updates explicit and local-only
- avoid combining route hiding, live data changes, and unrelated UI refactors unless they are tightly coupled

### Docs Branches

For `apps/atomy-q/docs` work:

- docs-only branches are acceptable for canonical documentation updates
- if docs describe a durable decision, they should point back to the active product, release, or domain doc
- release evidence and branch discipline docs must stay in sync with the current alpha posture

## Examples

Good:

- `alpha/remediation/rfq-line-items`
- `alpha/remediation/task9-release-gate`
- `release/alpha-2026-04`
- `feature/quote-intake-sidebar`
- `docs/branching-strategy`

Bad:

- `fix-stuff`
- `temp-branch`
- `alpha/remediation/everything`
- `feature/rfq-fixes-and-users-and-staging`

## Review Checklist

Before a branch is opened for merge, confirm:

- the branch name matches the work type
- the scope fits one reviewable change
- the alpha change-control ledger is updated when required
- the relevant docs are updated when a durable decision changed
- required tests and release gates were run
- the branch is not carrying unrelated scope

## Enforcement

This branching strategy is mandatory for Atomy-Q work.

If a branch violates this document:

- split the work
- rename the branch if needed
- update the change-control ledger
- rerun the required gates
- do not merge until the branch reflects the correct scope
