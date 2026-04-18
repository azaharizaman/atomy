# Alpha Change Control

This is the canonical change management policy for Atomy-Q alpha. It establishes a **binary scope gate** — features are either in alpha scope or they are not. No intermediate or partially-scoped changes allowed.

## Section 1: Purpose

The alpha release has a locked feature scope defined by Tasks 1-6 (see [Alpha Scope](./01-product/scope/alpha-scope.md)). Any change that expands beyond this scope must be formally proposed, reviewed, and approved before work begins. This document:

- Defines what is in alpha scope
- Establishes the binary gate process
- Categorizes change types with separate workflows
- Provides templates for proposal and corrective tracking
- Assigns roles and enforcement responsibilities

---

## Section 2: Alpha Scope Definition

### What's In Scope

**Supported Alpha Flow (Tasks 1-6):**
1. Register a tenant company or log in as a tenant user.
2. Create, list, and open RFQs.
3. Add RFQ line items and schedule fields.
4. Invite vendors.
5. Upload or record vendor quote submissions.
6. Normalize quote source lines through the supported deterministic alpha intelligence path.
7. Resolve normalization blockers.
8. Freeze a final comparison run.
9. Review comparison matrix and evidence.
10. Create and sign off an award.
11. Read the decision trail for comparison and award activity.

**Applications:**
- `apps/atomy-q/API` — Laravel API
- `apps/atomy-q/WEB` — Next.js frontend

**Nexus Packages:** See [Alpha Scope](./01-product/scope/alpha-scope.md) Section 3.

### What's Out of Scope

Any feature, module, or capability not listed in the supported alpha flow above. This includes:
- New user-facing modules outside the 11-step workflow
- Enabling deferred surfaces (projects, tasks, approvals as generic features, etc.)
- Broad refactors not required for verified alpha defects

---

## Section 3: Change Categories

| Category | Description | Process |
|----------|------------|---------|
| **Additional** | New feature, functionality, or enhancement not in alpha scope | Full review: proposal → Tech Lead approval → scope expansion or defer |
| **Corrective** | Bug fixes, security patches, regression fixes | Lighter process: direct fix with PR review |

**Corrective also includes:**
- Error remediation in existing alpha-supported flows
- UI and UX refinements in existing alpha-supported screens
- Removal of remaining mock fallback behavior in live mode
- Test hardening and documentation tied to those fixes

---

## Section 4: Binary Gate Flow

```
[Any Change Request]
        │
        ▼
[Is it in alpha scope?] ──YES──▶ [Normal implementation flow]
        │                         (PR → review → merge)
        │
       NO
        │
        ▼
[Submit Additional Change Proposal]
        │
        ▼
[Tech Lead Review & Decision]
        │
        ├─ APPROVED ──▶ [Add to alpha scope] ──▶ [Implementation]
        │
        ├─ DEFERRED ──▶ [Record in deferred log] ──▶ [No work proceeds]
        │
        └─ REJECTED ──▶ [Work does not proceed]
```

---

## Section 5: Additional Change Process

### When Required

A proposal is required when the change:
- Adds a new feature not in the 11-step alpha workflow
- Enables a deferred surface (projects, tasks, approvals as generic, etc.)
- Introduces a new module or user-facing capability
- Requires a broad refactor not tied to a verified defect

### Proposal Format

Use the template in **Section 10**. Required fields:
1. **Summary** — One-line description
2. **Justification** — Why needed, what problem solved
3. **Scope Impact** — Packages/layers affected
4. **Risk Assessment** — Regression risk (Low/Medium/High), test coverage status

### Decision Outcomes

| Decision | Meaning | Next Steps |
|----------|---------|-----------|
| **Approved** | Added to alpha scope | Proceed to implementation |
| **Deferred** | Recorded for post-alpha | No work in alpha branch |
| **Rejected** | Not added | Do not implement |

### Enforcement

- **PRs without approval** for out-of-scope changes are **rejected at review**
- Maintainers block scope bleeding at the review gate
- No partial implementation: changes are either in scope or they are not

---

## Section 6: Corrective Change Process

### When Allowed

Corrective changes may proceed without proposal when they:
- Fix a verified bug in an alpha-supported flow
- Patch a security vulnerability
- Address a regression in existing behavior

### Workflow

1. Branch from current alpha HEAD
2. Implement the fix
3. Run appropriate gates based on change class (see Section 7)
4. Open PR with **Corrective Change Record** template (see Section 11)
5. PR description must include the filled template

### Change Classes

| Class | Definition | Minimum Gates |
|-------|-----------|--------------|
| `C1` | Cosmetic UI/copy/layout only | Targeted tests + checklist entry |
| `C2` | Behavior change inside an existing alpha flow | Targeted tests, relevant live-mode tests, `npm run build`, checklist entry |
| `C3` | Auth, tenant scoping, API contract, RFQ/quote/comparison/award behavior, staging/runtime, or cross-cutting adapter changes | WEB lint/build/unit, API alpha suite, targeted E2E smoke, checklist entry, explicit reviewer sign-off |

If classification is unclear, treat the change as `C3`.

---

## Section 7: Mandatory Path

1. Open a change entry in the current release checklist.
2. Map the change to affected blockers and release tasks.
3. Confirm no scope expansion, or document the requested expansion.
4. Run the required gates for the class.
5. Record non-regression evidence.
6. Merge only after required sign-offs are present.

---

## Section 8: Hard Stops

**Do not merge if:**
- Live mode silently falls back to seed/mock behavior on alpha pages
- A previously closed blocker regresses without an accepted waiver
- Tenant-scoped 404 semantics are weakened
- API/WEB contract drift is introduced without regeneration and verification
- A deferred surface becomes reachable in alpha mode without a decision
- An out-of-scope change proceeds without Tech Lead approval

---

## Section 9: Roles

| Role | Responsibility |
|------|----------------|
| **Tech Lead** | Final decision on scope expansion, owns scope integrity. Reviews Additional Change Proposals. |
| **Reviewer** | Enforces change class gates, blocks scope bleeding at PR review. |
| **Developer** | Can propose changes. Must not implement out-of-scope work without approval. |

---

## Section 10: Template — Additional Change Proposal

```markdown
# Additional Change Proposal

## Summary
[One-line description of the proposed change]

## Justification
Why is this needed? What problem does it solve?

## Scope Impact
- Packages/layers affected: [list]
- New dependencies: [list/none]
- API contract changes: [yes/no]

## Risk Assessment
- Regression risk: [Low/Medium/High]
- Test coverage: [Existing/Partial/None]
- Alpha integrity impact: [describe]

## Recommended Decision
- [ ] Approve (add to alpha scope)
- [ ] Defer (post-alpha)
- [ ] Reject

---

Submitted by: [Name]
Date: [YYYY-MM-DD]

## Tech Lead Decision

Decision: [Approved/Deferred/Rejected]
Reason: [brief justification]
Decision Date: [YYYY-MM-DD]
```

---

## Section 11: Template — Corrective Change Record

```markdown
# Corrective Change Record

## Issue Description
[Brief description of the bug/security issue]

## Root Cause
[What caused the issue?]

## Fix Summary
[How was it fixed?]

## Impact Assessment
- Affected packages: [list]
- Affected alpha flows: [list]
- Regression risk: [Low/Medium/High]

## Change Class
[C1 / C2 / C3]

## Verification
- Tests added/updated: [yes/no]
- Gates run: [list]
- Evidence: [links/results]

---

Reported by: [Name]
Fixed by: [Name]
Date: [YYYY-MM-DD]
PR #: [number]
```

---

## Section 12: Document Maintenance

- Reviewed at alpha release close
- Updated when scope changes are approved by Tech Lead
- Deferred changes logged separately for post-alpha planning

---

## References

- [Alpha Scope](./01-product/scope/alpha-scope.md)
- [Out of Scope](./01-product/scope/out-of-scope.md)
- [Current Release Checklist](./02-release-management/current-release/release-checklist.md)
- [Release Overview](./02-release-management/current-release/release-overview.md)