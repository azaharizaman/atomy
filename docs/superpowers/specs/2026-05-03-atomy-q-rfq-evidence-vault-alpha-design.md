# Atomy-Q RFQ Evidence Vault Alpha Design

**Date:** 2026-05-03
**Status:** Approved design proposal for alpha scope inclusion
**Scope:** Atomy-Q RFQ workspace, quote evidence, comparison evidence, approval trail, award evidence, and procurement decision audit packs.

## Purpose

This design adds an RFQ-local **Evidence Vault** as an alpha feature for Atomy-Q.

The feature exists to prove the procurement decision. It is not a generic document management system. Atomy-Q is explicitly centered on quotations, buyer quote uploads, future vendor self-service quote/supporting uploads, comparison, approval, award, and audit trails. A top-level document library would dilute that product boundary and should not be carried as a redundant module.

The Evidence Vault must answer one question:

> Can a buyer, approver, or auditor reconstruct why this award was made?

## Product Decision

Atomy-Q alpha should include an RFQ workspace Evidence Vault focused on **Award Justification Packs** with minimum necessary quote traceability.

The feature is RFQ-scoped only:

- Enable `/rfqs/{rfqId}/documents` as the RFQ **Evidence Vault** screen.
- Keep global `/documents` out of the product surface.
- Remove generic `documents` API routes instead of preserving redundant endpoints.
- Replace generic document-bundle semantics with procurement-specific RFQ evidence bundles.

This is a scope expansion from the current launch-readiness route classification, where RFQ documents and top-level documents were deferred. The expansion is acceptable only if implemented as an RFQ-local evidence feature with the constraints in this design.

## Non-Goals

The alpha feature must not become a generic DMS.

Out of scope:

- top-level `/documents` page,
- generic document folders,
- generic document search,
- arbitrary tags and taxonomy management,
- document preview productization,
- external sharing,
- vendor self-service uploads,
- retention policy automation,
- e-signature,
- legal contract repository,
- compliance evidence pack as a separate workflow,
- full ZIP export if manifest export is enough for alpha.

Future vendor self-service upload should enter through quotation or supporting-evidence workflows, not through a global document module.

## User Surface

The RFQ workspace tab should be labeled **Evidence Vault**. The underlying route may remain `/rfqs/{rfqId}/documents` for continuity, but user-facing copy should not say generic "Documents" where audit intent is clearer.

The page should include:

- readiness banner,
- award justification pack card,
- evidence timeline,
- evidence sections,
- supporting evidence upload drawer,
- finalize action,
- export action.

Evidence sections:

- Quote Sources,
- Normalization Review,
- Final Comparison,
- Approval Trail,
- Award and Signoff,
- Supporting Evidence.

The timeline should present the procurement evidence sequence:

1. quote uploaded,
2. quote normalized or manually reviewed,
3. comparison finalized,
4. approval decided,
5. award created,
6. debrief/signoff completed,
7. evidence pack finalized.

## Readiness States

The Award Justification Pack should expose these states:

| State | Meaning |
|---|---|
| `not_ready` | Required evidence is missing or blocked. |
| `draft_ready` | Required evidence exists and can be finalized. |
| `finalized` | Manifest is immutable and exportable. |
| `superseded` | A newer finalized pack replaced this evidence version. |

Finalization blockers:

- no finalized comparison run,
- compared quote lacks a source file,
- unresolved normalization conflict exists,
- approval decision is missing,
- award is missing,
- award signoff is missing,
- required decision-trail sequence is incomplete,
- storage write or checksum generation fails.

Missing quote source file may be waived only with explicit buyer reason and actor attribution. A waiver is evidence, not silent success.

AI/provider unavailable must not block manual evidence assembly when deterministic/manual evidence exists. The Vault should disclose unavailable AI provenance truthfully and continue using stored workflow evidence.

## Evidence Sources

The Vault should index existing procurement artifacts instead of duplicating them into a generic file library.

Required alpha sources:

- `QuoteSubmission` source files and metadata,
- normalization source lines and conflict state,
- final `ComparisonRun` snapshot and summary,
- `Approval` and `ApprovalHistory`,
- `Award`, award debrief, and signoff state,
- `DecisionTrailEntry` records that prove workflow sequence,
- manually uploaded supporting evidence.

Optional referenced sources:

- vendor governance evidence,
- risk findings,
- AI artifact summaries,
- provider provenance payloads.

Optional sources may appear as references in the manifest, but they should not become separate alpha workflows.

## Data Model

The core model is an RFQ evidence bundle, not a document folder.

### EvidenceBundle

Required semantics:

- tenant-scoped,
- RFQ-scoped,
- optional comparison, approval, and award references,
- `type=award_justification`,
- `status=draft|finalized|superseded`,
- immutable `manifest` after finalization,
- `checksum`,
- `version`,
- `finalized_at`,
- `created_by`.

Existing `evidence_bundles` table/model/controller drift should be replaced with the current model. Atomy-Q is pre-release; do not add backward-compatibility workarounds for stale generic document semantics.

### EvidenceBundleItem

Each item records one included artifact:

- `tenant_id`,
- `evidence_bundle_id`,
- `source_type`,
- `source_id`,
- `artifact_kind`,
- `label`,
- `storage_path` when file-backed,
- `checksum` when file-backed or manifest-backed,
- `metadata`,
- `included_at`.

### SupportingEvidence

Manual supporting evidence is buyer-uploaded RFQ evidence that is not already produced by the workflow:

- `tenant_id`,
- `rfq_id`,
- optional `vendor_id`,
- optional `quote_submission_id`,
- optional `award_id`,
- `reason`,
- `original_filename`,
- `file_type`,
- `storage_path`,
- `checksum`,
- `uploaded_by`,
- `uploaded_at`.

Supporting evidence must be tenant-scoped and RFQ-scoped. It must not create a global document library.

## Manifest

Finalized packs store a manifest snapshot.

Manifest contents:

- RFQ identity and line-item summary,
- included quote submissions and source filenames,
- quote readiness and processing state,
- normalization review summary,
- manual override summary,
- unresolved conflict count at finalization,
- final comparison run ID and frozen snapshot reference,
- finalist ranking and score summary,
- approval ID, approver, decision timestamp, and history summary,
- award ID, selected vendor, award rationale, debrief status, and signoff,
- decision-trail entry IDs,
- supporting evidence item IDs,
- waiver reasons and actor attribution,
- generated checksum,
- generated timestamp,
- generated actor.

Finalized manifests are immutable. If evidence changes after finalization, create a new bundle version and mark the old pack `superseded`.

## API Design

Generic document endpoints should be removed:

- remove `GET /documents`,
- remove `GET /documents/{id}`,
- remove `GET /documents/{id}/download`,
- remove `GET /documents/{id}/preview`.

RFQ-scoped endpoints should replace them:

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/rfqs/{rfqId}/evidence-vault` | Return readiness, sections, timeline, blockers, bundle status, and actions. |
| `POST` | `/rfqs/{rfqId}/evidence-vault/supporting-evidence` | Upload one supporting evidence artifact. |
| `POST` | `/rfqs/{rfqId}/evidence-vault/award-pack/finalize` | Validate readiness and freeze the award justification manifest. |
| `GET` | `/rfqs/{rfqId}/evidence-vault/award-pack/export` | Export finalized manifest or bundle artifact. |

The previous `/evidence-bundles/*` route group should either be removed or converted to RFQ-scoped routes. The public product contract should not expose generic evidence bundles detached from an RFQ.

API rules:

- wrong tenant returns `404`,
- missing RFQ returns `404`,
- not-ready finalization returns `422` with blocker codes,
- storage failure returns a domain-safe error and does not persist synthetic success,
- finalization writes bundle, items, manifest, checksum, and decision-trail event in one transaction,
- finalized manifest mutation is rejected.

## Architecture

Implementation should stay Laravel-way inside `apps/atomy-q/API` unless a reusable Nexus seam becomes obvious during implementation.

Recommended API boundaries:

- `EvidenceVaultController` for RFQ-scoped HTTP behavior,
- `EvidenceVaultSummaryService` for assembling readiness and sections,
- `AwardEvidencePackFinalizer` for validation and manifest finalization,
- `SupportingEvidenceStorageService` for upload persistence and checksum handling,
- request classes for upload and finalization payloads,
- API resources for summary, bundle, blockers, sections, and timeline rows.

Read models should query from the current tenant root. Multi-write finalization must use one transaction.

Storage remains on the configured Laravel filesystem disk. Local/S3 behavior must be verified through the existing storage smoke posture.

## WEB Design

WEB should keep the route local to the RFQ workspace:

- remove the top-level Documents nav item from alpha/product navigation,
- remove or retire `apps/atomy-q/WEB/src/app/(dashboard)/documents/page.tsx`,
- update RFQ workspace nav label from Documents to Evidence Vault,
- implement `/rfqs/{rfqId}/documents` as the Evidence Vault page.

The page should fail loudly in live mode:

- malformed summary payload throws through the live-hook pattern,
- API unavailable shows scoped unavailable copy,
- finalization blockers are rendered as actionable checklist rows,
- export is disabled until pack is finalized,
- manual attach drawer requires reason and file.

## Testing

API feature coverage:

- RFQ evidence vault summary returns complete award evidence,
- blocker reporting covers missing comparison, missing quote source, unresolved normalization conflict, missing approval, missing award, and missing signoff,
- tenant isolation returns `404`,
- supporting evidence upload succeeds and records checksum,
- supporting evidence storage failure does not persist success,
- award pack finalization creates immutable manifest and decision-trail event,
- finalized pack cannot be mutated,
- new finalization after evidence change creates a new version or supersedes the old pack.

WEB coverage:

- RFQ Evidence Vault renders readiness states,
- blocker checklist renders API blocker codes,
- attach drawer validates reason and file,
- finalize action is disabled until ready,
- export action is disabled until finalized,
- live API failure shows scoped unavailable state,
- App Router page uses promised params and passes production build.

Verification gates:

- `cd apps/atomy-q/API && php artisan test --filter EvidenceVault`
- `cd apps/atomy-q/API && php artisan test --filter "QuoteSubmissionWorkflowTest|NormalizationReviewWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest"`
- `cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/documents/page.test.tsx`
- `cd apps/atomy-q/WEB && npm run build`

## Release Impact

This design changes alpha scope. It does not close Task 9 by itself.

Before design-partner alpha can claim Evidence Vault support:

- the global document route and generic document API endpoints must be removed or made unreachable,
- RFQ Evidence Vault must pass API and WEB gates,
- release docs must classify RFQ Evidence Vault as alpha-supported,
- customer/operator disclosure must state that this is an RFQ award evidence feature, not a general document repository,
- staging smoke must include evidence pack readiness or finalization if this feature is part of the launch claim.

If implementation is not completed before external alpha, this feature must remain deferred and must not be marketed as supported.

