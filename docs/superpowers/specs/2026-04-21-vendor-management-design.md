# Vendor Management Design

- Date: 2026-04-21
- Area: `apps/atomy-q/WEB`, vendor-facing procurement domain, supporting Nexus packages/orchestrators
- Goal: define a complete buyer-side vendor-management path for Atomy-Q alpha, covering vendor master management, approved-vendor requisition selection, AI-assisted shortlist generation, and ESG/compliance/risk recording.

## Problem Statement

Atomy-Q currently exposes RFQ-level vendor roster and invitation views, but it does not yet provide a complete vendor-management capability. The current product gap is visible in three ways:

1. There is no top-level vendor workspace where tenant users can create, review, classify, approve, or monitor vendors.
2. RFQ vendor invitation UX exists as a read-side roster, but invitation initiation is not yet a complete workflow.
3. Requisition and RFQ flows do not yet enforce a vendor-master-first operating model with approved-vendor-only selection.

The intended future state is not a simple address book. The vendor capability needs to support procurement operations as a governed master-data system with the following traits:

- Vendors must exist in the vendor master before they can be used in requisitions or RFQs.
- Only manually approved vendors may be selected for new requisitions.
- Compliance, risk, and ESG records must be captured throughout the vendor lifecycle.
- AI should assist by generating a draft shortlist of candidate vendors for a requisition, but human users remain the final decision-makers.
- External vendor self-service is explicitly deferred to post-alpha.

This design specifies the alpha-state vendor-management system that satisfies those rules.

## Product Positioning

For alpha, vendor management is a **buyer-side internal control surface**.

It is not yet:

- a vendor self-service portal,
- a contract lifecycle management system,
- a payment/banking master,
- a catalog-pricing management suite,
- or a fully autonomous sourcing engine.

Instead, it is an operational procurement master-data and decision-support capability that provides:

- vendor master creation and maintenance,
- manual approval state recording,
- vendor classification and searchability,
- approved-vendor gating for requisitions,
- AI-assisted vendor recommendation,
- and structured ESG/compliance/risk evidence capture.

## Goals

1. Establish a tenant-scoped vendor master as the single source of truth for vendor eligibility.
2. Prevent requisitions from selecting vendors outside the vendor master.
3. Prevent new requisitions from selecting vendors unless they are in approved status.
4. Provide procurement users with a top-level vendor workspace for search, review, update, and monitoring.
5. Support structured vendor classification for category, capability, geography, and operational fit.
6. Support hybrid AI-assisted vendor recommendation using taxonomy, line-item structure, and free-text requisition context.
7. Record ESG, compliance, and risk evidence in a way that is useful for review and scoring without making it an automatic alpha approval gate.
8. Keep the design explainable, tenant-safe, auditable, and compatible with the Nexus three-layer architecture.

## Non-Goals

- No vendor self-service portal in alpha.
- No supplier onboarding through external invitation links in alpha.
- No auto-approval or auto-rejection based on compliance/risk/ESG checks.
- No bank account, payment rail, tax settlement, or remittance management in this feature slice.
- No negotiated pricing catalog or contract clause management in alpha.
- No fully autonomous vendor selection that bypasses user review.
- No use of LLM outputs to override hard eligibility rules.

## Business Rules

The following rules are mandatory for alpha:

1. A vendor must exist in the vendor master before it can be referenced by a requisition or RFQ.
2. Only vendors with status `Approved` may be selected for new requisitions.
3. Vendors in `Draft`, `Under Review`, `Restricted`, `Suspended`, or `Archived` status are not selectable for new requisitions.
4. Approval is a manually recorded off-system business decision. The system stores approval state and metadata, but does not automate that decision in alpha.
5. ESG, compliance, and risk records are continuously maintained and scored, but do not automatically block vendor eligibility in alpha.
6. AI-generated recommendations may only operate on the eligible vendor pool and may not surface ineligible vendors as selectable candidates.
7. Users must confirm or edit the AI-prefilled shortlist before vendors are invited into an RFQ flow.
8. All vendor records and all vendor-related queries must be tenant-scoped.
9. Historical requisitions, RFQs, and awards retain historical vendor references even if the vendor later becomes restricted, suspended, or archived.

## User Roles

This design assumes these internal user personas for alpha:

### 1. Procurement Administrator

Responsible for:

- creating vendor master records,
- updating operational metadata,
- recording manual approval state,
- restricting or suspending vendors,
- and reviewing vendor activity.

### 2. Category Manager / Buyer

Responsible for:

- creating requisitions,
- reviewing AI-suggested vendor shortlists,
- selecting approved vendors for sourcing,
- and using vendor history/performance/compliance context during selection.

### 3. Compliance / Risk Reviewer

Responsible for:

- recording sanctions checks, due diligence findings, certification expiries, and risk notes,
- reviewing ESG/compliance scores,
- and escalating issues to procurement users.

### 4. Executive / Procurement Leadership

Responsible for:

- monitoring vendor portfolio health,
- reviewing exposure, warning patterns, and operational usage,
- and using summary dashboards rather than editing master data directly.

## Operating Model

The vendor capability should be organized into five connected product surfaces:

1. **Vendor Master**
2. **Vendor Review and Approval Registry**
3. **Vendor Intelligence and Classification**
4. **Requisition Vendor Selection**
5. **Vendor Lifecycle Monitoring**

This separation keeps concerns clean:

- Vendor Master is the authoritative record.
- Review and Approval governs eligibility state.
- Intelligence and Classification make vendors searchable and rankable.
- Requisition Selection consumes only approved vendors.
- Lifecycle Monitoring tracks changes after approval.

## Vendor Lifecycle

Alpha vendor lifecycle should use the following statuses:

### 1. Draft

Purpose:

- initial vendor record exists,
- core information capture is in progress,
- not yet reviewed.

Behavior:

- not selectable in requisitions,
- visible in vendor workspace,
- may accumulate documents and notes.

### 2. Under Review

Purpose:

- vendor record is materially complete enough for internal review,
- classification, evidence, and assessments are being added.

Behavior:

- not selectable in requisitions,
- may receive ESG/compliance/risk records,
- manual approval decision still pending.

### 3. Approved

Purpose:

- vendor is manually approved by procurement outside the system,
- approval decision is recorded in Atomy-Q.

Behavior:

- selectable in requisitions,
- eligible for AI recommendation candidate pool,
- visible as active sourcing candidate.

Required metadata:

- `approved_by`
- `approved_at`
- `approval_note`

### 4. Restricted

Purpose:

- vendor remains known and historically valid,
- but should not be considered for new sourcing activity unless manually restored.

Behavior:

- excluded from requisition selection,
- excluded from AI recommendation,
- historical records remain visible.

### 5. Suspended

Purpose:

- vendor is operationally paused due to material concern.

Behavior:

- excluded from requisition selection,
- excluded from AI recommendation,
- should display high-visibility warnings in vendor workspace.

### 6. Archived

Purpose:

- vendor is no longer active in the operating portfolio,
- but is retained for history, reporting, and auditability.

Behavior:

- excluded from requisition selection,
- read-only except for limited metadata updates if needed.

## Vendor Master Data Model

The vendor master should be an operational master, not merely a contact record.

### Core Identity

Required fields:

- `vendor_id`
- `tenant_id`
- `legal_name`
- `display_name`
- `registration_number` or equivalent business identifier
- `country_of_registration`
- `status`
- `created_at`
- `updated_at`

Optional or recommended fields:

- tax identifier(s)
- website
- parent company / group indicator
- alternate names / aliases

### Contact and Coverage

Required fields:

- primary contact name
- primary contact email
- primary contact phone or at least one contact channel
- operating regions / countries served

Recommended fields:

- multiple contacts by function:
  - sales
  - commercial
  - compliance
  - operational support
- preferred communication channel
- timezone / locale

### Operational Classification

Required fields:

- primary category
- secondary categories
- capability tags
- commodity/service tags
- geographic coverage tags

Recommended fields:

- strategic tier
- business size / maturity tier
- internal preferred-vendor flags
- diversity/ownership classification if relevant to tenant policy
- supported delivery modes (onsite, remote, regional, global)

### Approval Metadata

Required for approved vendors:

- `approved_by_user_id`
- `approved_at`
- `approval_note`

Recommended for all status changes:

- `status_changed_by`
- `status_changed_at`
- `status_change_reason`

### Lifecycle Signals

Recommended derived fields or projections:

- invitation count
- participation count
- quote submission count
- award count
- last invited at
- last quoted at
- last awarded at
- last active at
- vendor health summary

## Supporting Records

The vendor aggregate should be supported by separate child records rather than one overloaded table.

Recommended supporting record groups:

1. `VendorContact`
2. `VendorCategoryAssignment`
3. `VendorCapabilityAssignment`
4. `VendorApprovalRecord`
5. `VendorComplianceRecord`
6. `VendorRiskFinding`
7. `VendorEsgRecord`
8. `VendorEvidenceDocument`
9. `VendorActivityEvent`
10. `VendorPerformanceSnapshot`

This design keeps vendor core identity stable while allowing specialized lifecycles for monitoring data.

## Approval Model

Approval is not an automated workflow in alpha.

The system should treat approval as a manually recorded internal decision with the following characteristics:

- a user explicitly changes vendor status to `Approved`,
- the system stores who performed the change,
- the system stores when it happened,
- and the user may provide a reason or note.

The design intentionally separates:

- **eligibility state** from
- **compliance/risk/ESG observations**.

This is important because alpha policy is:

- operational approval is manual and off-system,
- the system records monitoring signals,
- but those signals do not automatically revoke eligibility.

Any later change from `Approved` to `Restricted` or `Suspended` is also manual and audited.

## Requisition Vendor Selection Model

Vendor selection belongs in the requisition lifecycle, but its candidate pool is controlled by vendor master state.

### Selection Preconditions

A vendor is eligible for a requisition if and only if:

- it belongs to the same tenant,
- it is in `Approved` status,
- it is not archived,
- and it is not excluded by hard selection constraints such as geography or obvious category mismatch if those are configured as deterministic filters.

### Requisition Flow

1. User creates or edits a requisition.
2. Requisition captures structured sourcing context:
   - title
   - description
   - line items
   - category or taxonomy
   - site / geography
   - spend band
   - additional requirements
3. User opens vendor selection.
4. System loads approved-vendor candidate pool.
5. System runs recommendation engine.
6. System returns a draft shortlist with explanation and warnings.
7. User edits shortlist if needed.
8. User confirms shortlisted vendors.
9. RFQ invitation flow operates on those selected vendors only.

### Hard UI Constraints

The requisition UI must not allow:

- inline creation of a vendor from inside requisition flow,
- selection of a vendor not in the master,
- or selection of a vendor with non-approved status.

If no suitable approved vendor exists, the UX should direct the user back to vendor master creation/review rather than quietly allowing a bypass.

## RFQ Invitation Handoff

The RFQ invitation lifecycle should be downstream of requisition vendor selection.

Recommended separation:

- **Vendor Master** controls vendor existence and approval.
- **Requisition** controls shortlist definition.
- **RFQ** controls invitation, reminder, and response tracking.

The RFQ invitation roster should therefore be a projection of selected requisition vendors plus RFQ invitation state.

Invitation operations should support:

- initial invite creation,
- reminder sending,
- invitation status display,
- response tracking,
- and audit trail.

But invitation initiation should not become a loophole for bypassing the vendor master.

## AI-Assisted Vendor Recommendation

### Role of AI in Alpha

AI is assistive, not authoritative.

The AI system should:

- generate a draft shortlist,
- rank candidates,
- infer fit from unstructured requisition context,
- and explain recommendations.

The AI system must not:

- introduce vendors outside the approved candidate pool,
- override hard eligibility rules,
- automatically send invitations,
- or silently lock in final vendor choices.

### Hybrid Recommendation Inputs

Recommendation should be based on three source families:

1. **Structured requisition signals**
   - category / taxonomy
   - line item codes
   - quantities / units
   - spend band
   - geography

2. **Vendor master signals**
   - categories
   - capabilities
   - region coverage
   - historical participation
   - historical awards
   - operational activity recency
   - performance indicators

3. **Narrative requisition signals**
   - title
   - description
   - free-text scope notes
   - implied technical or service intent inferred from text

### Recommended Recommendation Architecture

Use a two-stage model.

#### Stage 1: Deterministic Eligibility and Baseline Scoring

This stage enforces hard rules and computes baseline fit.

Suggested factors:

- approved status
- category overlap
- capability overlap
- geography match
- spend-range suitability
- historical participation relevance
- recent activity signal
- optional preferred-vendor weighting if tenant policy supports it

This stage should yield:

- eligible candidate set,
- baseline fit score,
- explicit exclusion reasons for filtered-out vendors.

#### Stage 2: LLM-Assisted Enrichment and Explanation

This stage interprets unstructured context and enriches ranking.

Suggested LLM tasks:

- infer latent category intent from requisition description,
- infer missing capability clues from line-item narrative,
- resolve semantic equivalence across similar service descriptions,
- produce a human-readable rationale for why a vendor appears relevant,
- identify ambiguity or missing data in the requisition.

The LLM output should be bounded:

- it may adjust or enrich fit within a capped range,
- it may generate explanation text,
- it may not bypass deterministic exclusion rules.

### Recommendation Output Contract

The shortlist output should include for each vendor:

- `vendor_id`
- `vendor_name`
- `fit_score`
- `confidence_band`
- `recommended_reason_summary`
- `deterministic_reasons[]`
- `llm_insights[]`
- `warning_flags[]`

Example warning flags:

- ESG evidence outdated
- compliance review overdue
- no recent participation in this category
- weak geography match
- sparse historical signal

### Explainability Requirement

Users must be able to inspect why a vendor was shortlisted.

Recommended explanation sections:

- category/capability match,
- geography/coverage match,
- historical signal,
- narrative similarity insight,
- and warnings.

This is required to keep the recommendation system auditable and trusted.

## Vendor Classification Model

Vendor classification should combine authoritative human-maintained data with AI-assisted derived intelligence.

### Human-Maintained Classification

Authoritative fields:

- primary category
- secondary categories
- capability tags
- geography coverage
- strategic tier
- risk tier
- ESG maturity band

These are used by deterministic logic and should be directly editable by users with appropriate access.

### AI-Derived Classification

Advisory fields:

- inferred category suggestions
- inferred capability suggestions
- semantic vendor cluster membership
- requisition-fit embeddings or semantic signatures
- confidence score for inferred mappings

These should be stored as recommendations or machine suggestions, not silently merged into authoritative master data.

### Promotion Rule

If AI suggests a new category/capability classification, it should remain advisory until a user reviews and promotes it into the maintained classification set.

This avoids uncontrolled drift in vendor identity.

## ESG, Compliance, and Risk Recording

The alpha model is **evidence + scoring**, not enforcement automation.

### ESG / Compliance / Risk Objectives

The system should:

- centralize vendor evidence,
- make review status visible,
- compute useful health signals,
- and surface warnings in vendor and requisition contexts.

The system should not automatically change approval state based on these scores in alpha.

### Evidence Registry

The evidence registry should support:

- certifications
- policy documents
- questionnaires
- audit results
- sanctions screening records
- due diligence documents
- sustainability disclosures
- manual review notes
- issue remediation attachments

Evidence records should support:

- type
- title
- source
- observed date
- expiry date if applicable
- review status
- reviewer
- notes
- linked file/document reference

### Findings and Issues

Findings should be separate from documents.

A finding should support:

- issue type
- severity
- domain (`ESG`, `Compliance`, `Risk`)
- opened at
- opened by
- current status
- remediation owner
- remediation due date
- resolution summary

This allows issues to live beyond a single document.

### Scoring Model

The design should compute at least these high-level scores:

- `esg_score`
- `compliance_health_score`
- `risk_watch_score`
- `evidence_freshness_score`

These scores should be explainable summary metrics, not opaque black boxes.

### Warning Surfaces

Warnings should appear in:

- top-level vendor list,
- vendor detail overview,
- requisition vendor selection panel,
- and potentially procurement dashboards.

Examples:

- `ESG review stale`
- `Sanctions check due`
- `Compliance document expired`
- `Open severe risk finding`

### Integration Direction

The codebase already has adjacent ESG and procurement compliance patterns. This design should reuse those boundaries rather than inventing a disconnected ad hoc model.

## Performance and Historical Signals

Operational selection should not depend only on static metadata. Vendor performance should influence ranking and review.

Recommended vendor performance dimensions:

- participation rate
- quote response rate
- award conversion rate
- cycle-time performance
- delivery reliability or project completion quality where available
- incident count
- dispute count if modeled later

These signals should be stored as snapshots or derived read models instead of mutating vendor master identity records.

## User Experience Surfaces

### 1. Top-Level Vendors Page

Purpose:

- vendor portfolio entry point,
- searchable/filterable vendor list,
- create vendor,
- review status and warning surfaces.

Capabilities:

- search by name, registration, category, capability
- filter by status, region, score bands, approval state, warning state
- sort by recent activity, health, or strategic relevance
- quick create action
- quick status actions if permitted

### 2. Vendor Detail Workspace

Recommended sections:

- Overview
- Contacts
- Categories and Capabilities
- Approval History
- ESG / Compliance / Risk
- Performance and Activity
- Documents / Evidence
- Audit Trail

This is the core operating surface for procurement and compliance users.

### 3. Vendor Review Queue

Purpose:

- operational queue for non-ready or attention-needed vendors.

Suggested views:

- Draft vendors
- Under Review vendors
- Missing required master data
- Expiring evidence
- Open severe findings
- Recently suspended/restricted vendors

### 4. Requisition Vendor Selection Panel

Purpose:

- show AI-prefilled shortlist,
- allow user confirmation/editing,
- surface vendor health and fit explanations.

Capabilities:

- recommended vendors tab
- all approved vendors search tab
- rationale drawer / side panel
- warning chips
- confidence display
- manual add/remove within approved pool

### 5. RFQ Invitation Surface

Purpose:

- invite selected vendors,
- track invitation lifecycle,
- send reminders,
- monitor responses.

The invitation surface should inherit selected vendors rather than independently sourcing arbitrary vendor records.

## Navigation and Information Architecture

The app should add a top-level main navigation item:

- `Vendors` → `/vendors`

Recommended IA:

- `/vendors`
- `/vendors/[vendorId]`
- `/vendors/[vendorId]/overview`
- `/vendors/[vendorId]/esg-compliance`
- `/vendors/[vendorId]/performance`
- `/vendors/[vendorId]/documents`
- `/vendors/review`

For RFQ/requisition flows:

- requisition vendor selection should be part of requisition creation/editing,
- RFQ vendors page should remain RFQ-scoped roster/invitation management,
- but should no longer function as the first place vendor identity is introduced.

## Data Flow

### Vendor Creation and Approval

1. User creates vendor in vendor master.
2. Vendor starts in `Draft`.
3. Procurement/compliance users enrich classification and records.
4. Manual off-system approval occurs.
5. User records approval in system and changes status to `Approved`.
6. Vendor becomes visible to requisition selection and AI recommendation.

### Requisition Vendor Recommendation

1. User creates/edits requisition.
2. Structured and narrative requisition data is captured.
3. System loads approved vendor pool.
4. Deterministic filters and baseline scoring run.
5. LLM enrichment interprets narrative fit and explanation.
6. System returns draft shortlist.
7. User confirms or edits shortlisted vendors.
8. Confirmed shortlist is persisted on requisition.

### RFQ Invitation and Participation

1. RFQ is created from requisition with selected vendors.
2. Invitation records are created for selected vendors.
3. Reminder and response lifecycle proceeds.
4. Quote intake and comparison run processes consume those vendors.
5. Historical activity contributes back into vendor performance projections.

### Monitoring

1. ESG/compliance/risk evidence and findings are added over time.
2. Derived scores and warnings update vendor read models.
3. Procurement users see warnings in vendor workspace and selection workflows.
4. Users may manually restrict or suspend vendor if needed.

## Architecture Guidance

The solution should align with Nexus three-layer boundaries.

### Layer 1 (`packages/`)

Should hold:

- vendor domain contracts and value objects,
- vendor classification contracts,
- vendor evidence / finding domain models where reusable,
- recommendation input/output value objects where framework-agnostic.

Layer 1 should not depend on Laravel or UI concerns.

### Layer 2 (`orchestrators/`)

Should hold coordination logic such as:

- vendor recommendation coordination,
- requisition-to-vendor shortlist orchestration,
- ESG/compliance score aggregation if it spans packages,
- activity-to-performance projection coordination.

This layer should define its own contracts where orchestration boundaries are needed.

### Layer 3 (`adapters/`, Laravel app)

Should hold:

- persistence implementations,
- HTTP controllers/endpoints,
- database migrations,
- API resource mapping,
- authorization and policies,
- and frontend-specific DTO shaping.

### WEB App (`apps/atomy-q/WEB`)

Should hold:

- top-level Vendors UI,
- vendor detail workspace,
- requisition vendor recommendation/selection panel,
- RFQ invitation and roster UX,
- hooks that consume generated API clients,
- and explainable shortlist presentation.

## API Surface Expectations

The generated API already suggests vendor and invitation read/write capabilities. The finalized backend surface should support at least these families.

### Vendor Master

- `GET /vendors`
- `POST /vendors`
- `GET /vendors/{id}`
- `PATCH /vendors/{id}`
- `PATCH /vendors/{id}/status`

### Vendor Classification and Metadata

- `GET /vendors/{id}/capabilities`
- `PUT /vendors/{id}/capabilities`
- `GET /vendors/{id}/categories`
- `PUT /vendors/{id}/categories`

### Approval and Audit

- `POST /vendors/{id}/approve`
- `POST /vendors/{id}/restrict`
- `POST /vendors/{id}/suspend`
- `GET /vendors/{id}/history`

### ESG / Compliance / Risk

- `GET /vendors/{id}/compliance`
- `GET /vendors/{id}/due-diligence`
- `PATCH /vendors/{id}/due-diligence/{itemId}`
- `POST /vendors/{id}/sanctions-screening`
- `GET /vendors/{id}/sanctions-history`
- `GET /vendors/{id}/esg` or equivalent read model if added

### Recommendation and Requisition Selection

Recommended new or clarified endpoints:

- `POST /requisitions/{id}/vendor-recommendations`
- `PUT /requisitions/{id}/selected-vendors`
- `GET /requisitions/{id}/selected-vendors`

### RFQ Invitation Lifecycle

- `GET /rfqs/{rfqId}/invitations`
- `POST /rfqs/{rfqId}/invitations`
- `POST /rfqs/{rfqId}/invitations/{invId}/remind`

## Validation Rules

The system should validate the following strictly:

- vendor status transitions must be explicit and auditable,
- requisition selected vendors must all belong to tenant,
- requisition selected vendors must all be `Approved`,
- invitation creation must reject vendors outside the RFQ/requisition-selected set if that constraint is enforced at API layer,
- all required identity fields must be present before moving to `Under Review` or `Approved` if such readiness checks are introduced,
- score computations must tolerate missing data without inventing synthetic values.

## Error Handling Expectations

Error behavior should be explicit and non-leaky.

Recommended semantics:

- tenant-scoped not-found behavior for inaccessible vendors,
- validation errors for illegal status transitions,
- validation errors for selecting non-approved vendors,
- clear user-facing explanation when no approved vendors match requisition criteria,
- fail-loud recommendation errors in live mode rather than silent fallback to fabricated shortlist data.

## Auditability Requirements

The following actions should emit auditable events or activity entries:

- vendor creation,
- vendor status change,
- approval recorded,
- suspension or restriction recorded,
- compliance/risk finding opened or resolved,
- ESG/compliance evidence attached,
- requisition vendor shortlist confirmed,
- RFQ invitations sent and reminded.

Audit entries should include actor, time, target record, change type, and concise reason where provided.

## Security and Multi-Tenancy

This domain is tenant-sensitive and must preserve isolation.

Mandatory controls:

- every vendor query and mutation is tenant-scoped,
- every requisition/vendor association is tenant-scoped,
- status changes must respect authorization,
- vendor existence across tenants must not leak through differing error responses,
- recommendation inputs and outputs must only use tenant-local vendor data,
- uploaded evidence must respect document access control.

## Reporting and Read Models

Recommended read models for alpha:

1. `VendorListRow`
2. `VendorOverviewReadModel`
3. `VendorHealthSummary`
4. `VendorRecommendationCandidate`
5. `VendorSelectionExplanation`
6. `VendorPerformanceSummary`
7. `VendorComplianceSummary`

These should be optimized for UI consumption rather than leaking raw persistence entities directly into the frontend.

## Testing Strategy

### Unit Tests

Cover:

- vendor status transition rules,
- classification normalization,
- deterministic recommendation scoring,
- bounded LLM influence logic,
- score aggregation,
- and validation of approved-only selection.

### Integration / Adapter Tests

Cover:

- tenant-scoped vendor persistence,
- vendor list filtering,
- requisition selected-vendor persistence,
- invitation creation constraints,
- ESG/compliance read/write mapping,
- and audit event emission.

### Frontend Tests

Cover:

- Vendors list/filter states,
- vendor detail tabs and warning states,
- requisition recommendation panel behavior,
- approved-only selection guardrails,
- RFQ invitation actions,
- and explicit error states for unavailable live data.

### End-to-End Tests

Cover:

- create vendor → approve vendor → create requisition → AI shortlist → confirm selected vendors → create RFQ → send invitations,
- plus warning surfaces for vendors with stale or weak monitoring records.

## Phased Delivery

### Phase A: Vendor Master Foundation

Deliver:

- top-level Vendors navigation,
- vendor list/detail/create/edit,
- status model,
- approval metadata recording,
- approved-only filtering support.

### Phase B: Requisition Vendor Selection

Deliver:

- requisition vendor selection panel,
- approved-vendor search/filter,
- persistence of selected vendors,
- RFQ handoff from selected vendors.

### Phase C: AI Recommendation

Deliver:

- deterministic candidate scoring,
- LLM enrichment for narrative fit and explanation,
- draft shortlist prefill,
- recommendation explanation UI.

### Phase D: ESG / Compliance / Risk Monitoring

Deliver:

- evidence registry,
- findings workflow,
- health scores,
- warning chips and detail surfaces,
- reporting-ready health summaries.

### Post-Alpha

Explicitly deferred:

- vendor self-service portal,
- supplier onboarding journeys,
- external document submission,
- supplier-side remediation interaction,
- vendor-owned profile maintenance.

## Open Design Choices Resolved by This Spec

The following choices are intentionally fixed to remove ambiguity:

1. Vendor self-service is deferred to post-alpha.
2. Vendors must exist in the master before requisition selection.
3. Only approved vendors are selectable for new requisitions.
4. Approval is manual and off-system, but recorded in-system.
5. Compliance/risk/ESG are recorded and scored, but do not automatically govern approval in alpha.
6. AI preselects a draft shortlist, but users retain final selection control.
7. Recommendation logic is hybrid: structured taxonomy + line-item context + free-text narrative.
8. Vendor master is operationally rich, but does not include contract/payment master scope in alpha.

## Success Criteria

This design is satisfied when the product can support the following end-to-end buyer-side workflow:

1. Procurement user creates vendor in vendor master.
2. Procurement/compliance users enrich vendor classification and evidence.
3. Procurement user records manual approval.
4. Buyer creates requisition.
5. System recommends approved vendors using hybrid scoring and LLM enrichment.
6. Buyer confirms/edit shortlist.
7. RFQ invitations are created from the confirmed shortlist.
8. Vendor activity and monitoring signals feed historical visibility and future selection quality.

## Out of Scope Reminder

This spec intentionally does not define:

- supplier portal authentication,
- supplier external profile maintenance,
- payment onboarding,
- contractual pricing catalogs,
- autonomous sourcing decisions,
- or hard automated eligibility revocation from monitoring scores.

Those should be addressed in follow-up post-alpha design work.
