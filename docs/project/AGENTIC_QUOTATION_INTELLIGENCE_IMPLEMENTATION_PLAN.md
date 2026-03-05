# Agentic Implementation Plan: Quotation Comparison SaaS

**Date:** March 4, 2026  
**Scope:** Move `QuotationIntelligence` from scaffolded orchestrator to enterprise-ready, agentic quotation comparison platform.

## 1. Objectives

1. Deliver robust, auditable apples-to-apples quote comparison for medium/large organizations.
2. Use existing Nexus packages first; avoid duplicate capabilities.
3. Run implementation using multi-agent coding with strict ownership, architecture checks, and quality gates.

## 2. Architecture Guardrails (Non-Negotiable)

1. Follow three-layer architecture in [docs/project/ARCHITECTURE.md](/home/conrad/dev/atomy/docs/project/ARCHITECTURE.md).
2. Reuse packages from [docs/project/NEXUS_PACKAGES_REFERENCE.md](/home/conrad/dev/atomy/docs/project/NEXUS_PACKAGES_REFERENCE.md) before creating anything new.
3. Layer 2 orchestrators must remain framework-agnostic and depend on interfaces.
4. Every workflow path must enforce tenant isolation (`tenantId` filtering).
5. Failures must throw domain exceptions; no synthetic fallback return values.

## 3. Agent Topology

Use four parallel agents per sprint (matching AGENTS mandates):

1. `Architect Agent`
- Owns contracts in `orchestrators/QuotationIntelligence/src/Contracts`.
- Owns orchestration boundaries and package dependency correctness.
- Produces/updates architecture notes and acceptance criteria.

2. `Developer Agent`
- Owns service/coordinator implementation in `src/Services`, `src/Coordinators`, `src/DTOs`, `src/ValueObjects`.
- Implements only against interfaces.
- Adds/updates unit tests for every behavior change.

3. `QA Agent`
- Owns feature tests, regression suites, and edge-case coverage.
- Reproduces defects with failing test first.
- Validates tenant isolation, zero-checks, and error behavior.

4. `Maintenance Agent`
- Owns `IMPLEMENTATION_SUMMARY.md` updates and documentation sync.
- Maintains composer/test wiring and CI scripts.
- Tracks package usage compliance and technical debt register.

## 4. Agentic Workflow Protocol

For each ticket, enforce this sequence:

1. Discovery
- Map current code and package capabilities before editing.
- Confirm no existing package already solves the requirement.

2. Interface-first design
- Define/update contract(s) in `src/Contracts` before service logic.

3. Test-first validation
- Add failing tests for bug/feature acceptance criteria.

4. Implementation
- Implement in smallest safe increments.
- Keep orchestrator stateless and dependency-injected.

5. Verification
- Run targeted tests first, then orchestrator suite.
- Validate tenant scoping and audit traceability output.

6. Documentation
- Update [IMPLEMENTATION_SUMMARY.md](/home/conrad/dev/atomy/orchestrators/QuotationIntelligence/IMPLEMENTATION_SUMMARY.md) each merged change.

## 5. Backlog by Workstream

## WS-A: Foundation Hardening (Sprint 1)

1. Fix composer/test bootstrapping so `QuotationIntelligence` tests load classes correctly.
2. Normalize contract/implementation mismatches across coordinator and tests.
3. Replace placeholder values (`unknown`, `PENDING`) with explicit domain exceptions or typed pending states.
4. Add mandatory tenant-ownership checks in ingest/process/query path.
5. Add deterministic audit payload for each pipeline stage.

**Exit Criteria**
1. Orchestrator unit/feature tests execute in CI.
2. No silent default fallbacks in critical path.
3. Tenant-isolation tests pass.

## WS-B: Line Alignment and Normalization Engine (Sprint 2)

1. Implement cross-vendor alignment by RFQ line with semantic + taxonomy + UoM normalization.
2. Add normalization lock context (base unit/base currency/rate lock date).
3. Add confidence scoring and low-confidence escalation flags.
4. Expand evidence snippets to support field-level provenance.

**Exit Criteria**
1. End-to-end comparison result is deterministic and reproducible.
2. Every normalized field includes source evidence.

## WS-C: Risk and Commercial Terms Intelligence (Sprint 3)

1. Add peer-based price anomaly detection by RFQ line cluster.
2. Add extraction and normalization for terms:
- Incoterms
- Payment terms
- Lead time
- Warranty
3. Add rule packs for high-risk deviations and conflict flags.
4. Emit structured risk events for downstream workflows.

**Exit Criteria**
1. Risk findings include reason, severity, and source evidence.
2. False positives are bounded with threshold configuration.

## WS-D: MCDA + LCC Recommendation Engine (Sprint 4)

1. Add weighted multi-criteria scoring:
- Price
- Risk
- Delivery
- Sustainability/ESG
2. Add life-cycle cost mode (acquisition + operating + disposal factors).
3. Add explainability payload:
- Criteria weights
- Per-vendor score breakdown
- Rank rationale
4. Add scenario simulation API for weight sensitivity.

**Exit Criteria**
1. Recommendation is explainable and auditable.
2. Score reruns with same inputs produce same outputs.

## WS-E: Agentic Runtime + Governance (Sprint 5)

1. Introduce explicit sub-agent orchestration model:
- Extraction agent
- Normalization agent
- Risk agent
- Recommendation agent
- Reviewer agent
2. Add human-approval gates for high-risk outcomes.
3. Add immutable event logging and override capture.
4. Add feature-flag rollout by tenant and industry segment.

**Exit Criteria**
1. Human-in-the-loop overrides are fully traceable.
2. Rollout can be safely controlled per tenant.

## 6. Ticket Template (Agent-Ready)

Use this template for every implementation ticket:

1. `Title`
2. `Business Outcome`
3. `Owned By` (`Architect`, `Developer`, `QA`, `Maintenance`)
4. `Layer Impact` (`L1`, `L2`, `L3`)
5. `Interfaces Added/Changed`
6. `Packages Reused`
7. `Acceptance Criteria` (numbered, testable)
8. `Test Cases` (happy path + edge cases + tenant isolation)
9. `Risk Controls` (audit evidence, zero-check, exception handling)
10. `Docs to Update`

## 7. Sprint Cadence and Parallelization

1. Weekly cadence with daily integration window.
2. Parallel streams:
- Stream 1: Contracts + core coordinator
- Stream 2: ML/risk logic
- Stream 3: tests + fixtures + QA hardening
3. Merge policy:
- Contract PR merges first.
- Implementation PR merges second.
- QA/doc PR merges last.

## 8. Quality Gates

A ticket is done only when all pass:

1. Static architecture compliance (no layer violations).
2. Unit tests for changed classes.
3. Feature/regression coverage for behavior.
4. Tenant isolation and exception-path tests.
5. `IMPLEMENTATION_SUMMARY.md` updated.

## 9. KPI Targets for This Program

1. Quote ingestion to comparison completion under agreed SLA for 100x100 (line x quote) workloads.
2. 100% evidence coverage on normalized recommendation fields.
3. Zero known cross-tenant leakage defects.
4. High-confidence risk precision with configurable thresholds.
5. Repeatable deterministic scoring under lock-date normalization.

## 10. Immediate Next 7-Day Execution Plan

1. Day 1
- Architect defines contract correction list and dependency map.
- QA reproduces current failures as baseline.

2. Day 2-3
- Developer fixes bootstrapping and contract mismatches.
- QA validates tests now execute and fail for real logic, not wiring.

3. Day 4-5
- Developer removes placeholder fallback behavior and adds domain exceptions.
- Architect reviews boundary compliance and tenant-scoping.

4. Day 6
- QA runs isolation and regression pack.
- Maintenance updates implementation summary and technical debt log.

5. Day 7
- Demo: stable ingest -> normalize -> risk flow with traceable output.
- Approve Sprint 2 scope.

