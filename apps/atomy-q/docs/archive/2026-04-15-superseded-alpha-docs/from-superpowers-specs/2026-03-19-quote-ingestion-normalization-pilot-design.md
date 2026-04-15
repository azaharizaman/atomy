# Quote Ingestion And Normalization Pilot Design

**Date:** 2026-03-19  
**Product:** Atomy-Q  
**Context:** Internal pilot with 1-3 friendly customers  
**Primary Goal:** Prove core procurement workflow value by making supplier quote upload, extraction, normalization, review, and comparison trustworthy enough for real pilot use.

## 1. Problem Statement

Atomy-Q already has meaningful product surface in authentication, administration, RFQ workflows, approvals, and tenant governance. For an internal pilot, the highest-value next development area is not platform breadth, but the integrity of the quote ingestion path.

The current product risk is that quote comparison may work in a demo but fail under real customer inputs because supplier files are inconsistent, extracted data is uncertain, and comparison results can become hard to trust if review and audit paths are weak.

The next epic should therefore focus on a pilot-grade quote ingestion and normalization workflow that:

- accepts supplier quote documents per RFQ
- extracts candidate structured data from uploaded files
- normalizes extracted lines against RFQ lines
- surfaces ambiguity and missing data explicitly
- requires targeted human review for unresolved issues
- freezes a stable comparison snapshot before approval

## 2. Scope

### In Scope

- Supplier quote document upload against an RFQ and supplier
- Extraction of candidate quote header fields and line items
- Deterministic and heuristic normalization of supplier lines to RFQ lines
- Field-level and line-level issue reporting
- Exception review workflow for unresolved mappings and values
- Frozen comparison snapshot generation before approval
- Audit trail for ingestion, review, and decision events
- Tenant-scoped API and persistence rules across the full workflow
- Manual-assist fallback path when automation is insufficient

### Out Of Scope

- Full enterprise-grade straight-through automation
- Advanced AI-driven supplier recommendation or award optimization
- Broad new modules unrelated to RFQ-to-award workflow
- Cross-tenant analytics or benchmarking
- Final production-scale ingestion tuning for high volume

## 3. Recommended Approach

Three product approaches were considered:

### Approach A: Human-Assisted Ingestion

Users upload files, the system extracts partial data, and humans complete most normalization manually.

**Pros**
- Lowest correctness risk
- Fastest to support operationally
- Simplest pilot fallback

**Cons**
- Weak product differentiation
- Too much review effort per submission
- Lower signal on actual automation value

### Approach B: Semi-Automated Normalization

The system auto-parses common supplier quote formats, runs deterministic matching first, uses lower-confidence heuristics second, and sends only unresolved items to review.

**Pros**
- Strong pilot value without overclaiming automation
- Makes review effort focused instead of full-manual
- Builds a realistic foundation for future intelligence features

**Cons**
- Requires careful confidence, exception, and review design
- More cross-package coordination work than Approach A

### Approach C: High-Automation Comparison

The system tries to fully ingest and compare supplier submissions with minimal human review.

**Pros**
- Strongest demo story
- Highest long-term leverage if accurate

**Cons**
- Highest pilot trust risk
- Wrong decisions become harder to detect
- More likely to create support and audit problems

### Recommendation

Use **Approach B: Semi-Automated Normalization**.

This fits the internal pilot target. It proves real product value while preserving explicit human control over uncertainty. It also avoids the dangerous pattern of silently turning low-confidence extraction into procurement truth.

## 4. Product Boundary

The next development epic should be **Pilot-Ready Quote Ingestion And Normalization**.

For each RFQ:

1. A buyer opens an RFQ run with expected line items.
2. Supplier quote files are uploaded against that RFQ and supplier.
3. The system extracts candidate structured data.
4. The system attempts to normalize supplier lines to RFQ lines.
5. Unresolved data is routed into targeted review.
6. Once blocking issues are resolved, a comparison snapshot is frozen.
7. Approval and award actions occur only against that frozen snapshot.

The core rule is:

> Raw extraction is never trusted automatically when uncertainty remains. Candidate data must remain reviewable until it is resolved into normalized comparison truth.

## 5. Workflow Design

### End-To-End Workflow

1. Buyer creates or opens an RFQ run and defines expected line items.
2. Buyer uploads one or more supplier quote documents.
3. Submission enters `uploaded`.
4. Extraction pipeline processes the files and produces candidate data.
5. Submission enters `extracted` and then `normalizing`.
6. Normalization attempts line mapping and value reconciliation.
7. If unresolved issues remain, submission enters `needs_review`.
8. A buyer or authorized reviewer resolves exceptions through a review flow.
9. When all blocking issues are resolved, submission enters `ready`.
10. Comparison engine generates a frozen snapshot.
11. Snapshot becomes eligible for approval.
12. Approval decision and vendor award are recorded against the frozen snapshot.

### Key Workflow Constraint

Comparison results must not mutate underneath approvers. Reprocessing may create a newer candidate normalization result, but any approved or reviewed comparison must remain tied to the specific frozen snapshot used at decision time.

## 6. Architecture Alignment

This design should follow the project three-layer architecture.

### Layer 1: Atomic Packages

Use existing packages wherever possible:

- `Document`: store uploaded quote files and derived artifacts
- `DataProcessor`: extraction, parsing, OCR, and transformation pipeline
- `Procurement`: RFQ rules, supplier quote domain rules, comparison prerequisites
- `Currency`: normalize monetary values and conversion metadata
- `AuditLogger`: immutable event recording for review and decisions

Potential future extension:

- `QuotationIntelligence`: confidence enrichment, supplier suggestions, ranking intelligence

This future capability should not be a prerequisite for the pilot.

### Layer 2: Orchestrator

Add or extend an orchestrator workflow to coordinate:

- upload registration
- extraction dispatch
- normalization
- exception review transitions
- snapshot freezing
- approval eligibility gating

The orchestrator should own cross-package coordination and state transitions. Controllers should remain thin.

### Layer 3: Laravel Adapters

Laravel adapters should handle:

- file upload endpoints
- persistence
- queue jobs
- tenant-scoped Eloquent queries
- API resources
- request validation
- authenticated review and approval endpoints

Tenant isolation must be enforced on RFQs, suppliers, quote submissions, extracted artifacts, review actions, and snapshot access. Cross-tenant existence leakage must still resolve to tenant-scoped `404` behavior.

## 7. Data Model And States

### Quote Submission States

- `uploaded`
- `extracting`
- `extracted`
- `normalizing`
- `needs_review`
- `ready`
- `frozen`
- `failed`

### Line Normalization States

- `matched`
- `ambiguous`
- `missing`
- `invalid`
- `reviewed`

### Comparison Snapshot States

- `draft`
- `ready_for_approval`
- `approved`
- `rejected`
- `awarded`

### Review Resolution Types

- `accept_extracted_value`
- `remap_to_rfq_line`
- `split_line`
- `merge_lines`
- `mark_not_quoted`
- `override_price`
- `override_uom`

### Frozen Snapshot Contents

Each frozen snapshot should capture enough data for reproducibility:

- RFQ version
- included supplier submissions
- normalized line set used for comparison
- applied exception resolutions
- currency and conversion metadata
- calculation timestamp
- actor and review metadata

This supports later questions such as why a vendor was selected at a specific time with a specific data set.

## 8. Safeguards

### Blocking Readiness Rules

A submission must not become `ready` when any blocking condition remains:

- required RFQ lines are unmapped
- prices are missing
- currency is unresolved
- critical UOM normalization is unresolved
- confidence falls below the review threshold for required values

### Explicit Uncertainty

The API should return issue codes and confidence metadata at field and line level so the UI can show exactly what requires review. Avoid generic parse-failed responses when the real state is partial success with targeted exceptions.

### Idempotent Reprocessing

Re-running extraction or normalization must create a new candidate result, not mutate the historical snapshot already used for review or approval.

### Manual Assist Fallback

Admins or authorized internal operators should be able to mark a submission as manual-entry-assisted when automation fails, while still using the same review, normalization, and audit path.

## 9. API And UX Requirements

### API Requirements

Add or complete tenant-scoped endpoints for:

- quote submission upload
- submission status retrieval
- extraction and normalization issue listing
- review resolution actions
- comparison snapshot generation
- snapshot detail retrieval
- approval eligibility checks

Each endpoint should expose machine-readable issue states rather than burying exceptions in free-text messages.

### UX Requirements

The review experience should optimize for exception handling, not full-document editing.

The UI should:

- show unresolved fields and lines first
- group issues by supplier submission and RFQ line
- expose confidence and issue reason
- let reviewers apply explicit resolution actions
- show whether the submission is blocked from readiness
- clearly show when a snapshot is frozen and approval-safe

## 10. Testing Strategy

The first implementation should include tests for:

- tenant isolation across all submission and review queries
- duplicate uploads
- malformed or unsupported files
- missing currencies
- unresolved or conflicting UOMs
- ambiguous line mapping
- partial extraction success
- manual resolution flows
- reprocessing behavior
- snapshot freezing and immutability
- audit trail generation
- approval gating on unresolved issues

## 11. Delivery Sequence

Recommended implementation sequence:

1. Define orchestrator contracts for quote ingestion, normalization review, and snapshot freezing.
2. Implement quote submission persistence and upload registration.
3. Implement extraction result storage and processing state transitions.
4. Implement normalization result model and issue taxonomy.
5. Implement review resolution workflow and readiness gating.
6. Implement frozen comparison snapshot generation.
7. Add audit logging and approval eligibility integration.
8. Add focused UI/API exception review flow.
9. Add manual-assist fallback and support tooling.

## 12. Success Criteria For Pilot

This epic is successful for the internal pilot when:

- buyers can upload real supplier quotes for pilot RFQs
- the system extracts and normalizes enough data to reduce manual comparison effort
- uncertainty is explicit and reviewable
- no approval occurs on unresolved blocking issues
- comparison outputs are reproducible after approval
- support staff can recover from failed automation without bypassing the audit path

## 13. Risks And Mitigations

### Risk: Low extraction accuracy across inconsistent supplier formats

**Mitigation:** Treat extraction as candidate data only and require structured exception review for unresolved values.

### Risk: Snapshot drift after approval

**Mitigation:** Freeze immutable comparison snapshots and bind approval actions to snapshot IDs.

### Risk: Cross-tenant data leakage in support workflows

**Mitigation:** Apply tenant-scoped existence checks everywhere and preserve `404` behavior for inaccessible resources.

### Risk: Review flow becomes too slow for pilot use

**Mitigation:** Design the UX around exception-only review and prioritize deterministic mapping before heuristic matching.

## 14. Recommended Next Planning Step

The next execution step after approval of this spec is to create an implementation plan for the pilot-ready quote ingestion and normalization workflow, beginning with orchestrator contracts and state model design.
