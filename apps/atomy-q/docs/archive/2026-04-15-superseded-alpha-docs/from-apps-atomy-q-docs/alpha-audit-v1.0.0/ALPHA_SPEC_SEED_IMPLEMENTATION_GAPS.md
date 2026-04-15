# Atomy-Q Alpha Spec Seed: Implementation Gaps

**Source audit:** `ALPHA_RELEASE_AUDIT.md`  
**Purpose:** Convert audit findings into implementation-ready initiatives that can be expanded into formal design/spec documents.  
**Audience:** Product, architect, backend, frontend, QA.

---

## Foundational rules for all future brainstorm/spec work

These rules override convenience or speed when evaluating implementation options for Atomy-Q.

### Rule 1: Nexus package-first implementation

Atomy-Q is not only a SaaS product. It is also a proving ground for the Nexus first-party package ecosystem. For every required business capability, the design process must first ask:

- Can this be implemented by using an existing Nexus Layer 1 package?
- If not, should the missing capability be added to an existing Nexus package instead of being implemented only inside Atomy-Q?
- If multiple packages are involved, should the composition live in a Nexus Layer 2 orchestrator instead of app-local service code?
- If framework/infrastructure glue is needed, should that be implemented as a Nexus Layer 3 adapter instead of app-local glue?

App-local implementation inside `apps/atomy-q/` should be the last resort, not the default.

### Rule 2: Real-world package-gap reflection

When a gap is discovered during SaaS delivery, the design must not jump straight to “fix it in Atomy-Q”. It must reflect on whether the gap is a real reusable need that would benefit future SaaS products built on Nexus.

Every spec/design pass should classify gaps into one of these buckets:

- `Extend existing Nexus package`
- `Create new Nexus package capability`
- `Create Nexus orchestrator`
- `Create Nexus adapter`
- `Atomy-Q-specific implementation only`

The default bias should be toward reusable Nexus improvements where the capability is not uniquely tied to Atomy-Q.

### Rule 3: Orchestrator and adapter discovery is mandatory

Each brainstorm/spec pass must explicitly identify:

- Candidate Layer 2 orchestrators for cross-package workflows
- Candidate Layer 3 adapters for Laravel, queue, storage, AI provider, auth, or persistence integration
- Whether Atomy-Q is currently carrying orchestration/integration logic that should be promoted into Nexus

### Required analysis section for every future spec

Every future brainstorm/spec document in this project must include a section named:

`Nexus package-first reflection`

That section must answer:

1. Which existing Nexus packages were considered first?
2. What capability is missing from those packages?
3. Should the missing capability be added in Nexus instead of Atomy-Q?
4. Is a new orchestrator warranted?
5. Is a new adapter warranted?
6. What remains genuinely app-specific after that analysis?

### Required ownership decision matrix for every future spec

Every future brainstorm/spec document in this project must also include a section named:

`Ownership decision matrix`

That section must classify each meaningful capability or change request using the matrix below before implementation starts.

| Capability / gap | Reusable across future SaaS? | Best layer | Proposed owner | Why not another layer? | App-specific residue |
|------------------|------------------------------|------------|----------------|------------------------|----------------------|
| Example: quote parse state machine | Yes | Layer 1 package | Nexus package enhancement | Not Layer 3 because rules are domain logic; not app-only because every quote-ingestion SaaS will need it | Laravel controller + API response mapping |

Use these rules when filling the matrix:

- If the logic is domain logic and could reasonably serve another SaaS, default to **Nexus Layer 1**.
- If the logic coordinates multiple packages or workflows, default to **Nexus Layer 2 orchestrator**.
- If the logic is framework, persistence, queue, storage, provider, or transport integration, default to **Nexus Layer 3 adapter**.
- Only choose **Atomy-Q app-local** when the capability is genuinely product-specific and unlikely to benefit another SaaS.
- Every `Atomy-Q app-local` decision must include a short justification for why reuse is not worthwhile.

---

## How to use this document

Each section below is a spec seed for one implementation initiative. A later brainstorm or design pass should expand each initiative into:

- Detailed user journeys
- Sequence diagrams
- API request/response schemas
- Rollout and fallback plans
- Test plan and release criteria

Before writing solution details, each initiative must also be reviewed through the Nexus package-first rules above.

---

## 1. Tenant and company foundation

### Problem

Alpha scope says users can create a company, but the system currently relies on `tenant_id` as an isolation key without a first-class tenant lifecycle. A `Tenant` model exists, but there is no tenants migration and no company creation flow.

### Goal

Make tenant/company a first-class platform concept so onboarding, auth, seeding, and data ownership all resolve through a real persisted tenant record.

### Non-goals

- Full enterprise org hierarchy
- Multi-tenant admin console beyond Alpha needs
- Complex billing/subscription linkage

### Proposed architecture

- Treat product term `company` as API/platform term `tenant`.
- Add tenant persistence in Layer 3 and keep domain rules explicit in contracts/services.
- Introduce a minimal tenant creation workflow that can be used by onboarding or admin provisioning.
- Ensure all user, RFQ, quote, comparison, and award flows require a valid tenant record.

### Nexus package-first reflection

- Check whether tenant identity, lifecycle, and feature gating belong in an existing Nexus tenancy-related package or require a new reusable package capability.
- If tenant onboarding spans identity, subscription, and feature setup, evaluate a Nexus Layer 2 orchestrator instead of app-local coordination.
- Keep Laravel persistence and HTTP concerns in adapters; avoid embedding reusable tenancy rules only inside Atomy-Q.

### API / contracts

- `POST /tenants` or a registration flow that creates tenant plus first user
- `GET /tenants/{id}` for authenticated tenant context
- Contract for tenant creation service and tenant lookup service

### Data model

- `tenants` table with ULID primary key
- Core fields: `id`, `name`, `slug` or code, `status`, timestamps
- Optional Alpha-safe fields: `created_by`, `settings_json`
- Ensure `users.tenant_id` references a real tenant

### Migration plan

1. Create `tenants` migration.
2. Backfill tenant rows for existing seeded/demo users.
3. Update seeders so they create tenants first.
4. Add tenant existence validation in onboarding/auth flows.

### Risks / open questions

- Whether company creation is self-serve or admin-only
- Whether existing seeded rows contain tenant IDs that do not map cleanly to new tenant records
- Whether tenant slug/code is externally visible in URLs

### Acceptance criteria

- Every active user references an existing tenant.
- Seeder/demo data creates tenant rows before tenant-scoped data.
- Company creation path exists and returns a real tenant identifier.
- No core Alpha flow depends on orphaned `tenant_id` values.

---

## 2. Quote ingestion and AI normalization pipeline

### Problem

Alpha promises real AI-driven quote normalization, but quote upload currently stops at storage and metadata persistence. Reparse is stubbed and no extraction/normalization pipeline is wired in the API.

### Goal

Ship one real end-to-end quote ingestion pipeline: upload file, enqueue processing, extract vendor quote content, normalize it into persisted source lines, and expose status/errors back to users.

### Non-goals

- Multi-provider provider selection UI
- Complex human-in-the-loop review tooling beyond Alpha essentials
- Full OCR platform abstraction if basic supported formats are enough

### Proposed architecture

- Use one provider path only for Alpha.
- Reuse existing monorepo ML/quotation packages where possible.
- Add async processing with explicit job states.
- Persist extraction outputs into normalization tables already used by downstream comparison logic.
- Keep provider-specific logic in Layer 3; keep workflow coordination in app/orchestrator layer.

### Nexus package-first reflection

- `quotation-intelligence` and `machine-learning` must be the first implementation targets for extraction and normalization capabilities.
- If quote ingestion state transitions, parse result normalization, or confidence handling are missing, prefer extending Nexus packages rather than app-local service logic.
- Evaluate a Nexus Layer 2 orchestrator for document intake -> extraction -> normalization write flow.
- Evaluate Layer 3 adapters for OpenAI/Anthropic provider wiring, queue execution, storage retrieval, and Laravel job integration.

### API / contracts

- `POST /quote-submissions` creates submission and processing job
- `POST /quote-submissions/{id}/reparse` re-enqueues real parsing work
- `GET /quote-submissions/{id}` returns processing state, errors, and summary
- Internal contract for extraction service, normalization writer, and parse status transitions

### Data model

- Extend `quote_submissions` with processing status, error code/message, parsed-at timestamps if missing
- Persist extracted lines into `normalization_source_lines`
- Optional processing attempts/audit table if retries need traceability

### Migration plan

1. Add status/error fields needed for async processing.
2. Implement queued job and service contracts.
3. Wire provider credentials/config.
4. Persist extracted lines and update submission status.
5. Replace stub reparse flow.

### Risks / open questions

- Which formats are mandatory for Alpha
- Whether synchronous fallback is needed if queues are unavailable
- What confidence threshold or failure policy is acceptable for launch

### Acceptance criteria

- Upload triggers a real processing path.
- Reparse does real work and updates persisted state.
- Successful parsing creates retrievable normalization source lines.
- Failures are visible through API and UI, not silent or fake-success responses.

---

## 3. Normalization read APIs backed by persisted data

### Problem

Normalization mutations and readiness logic rely on persisted rows, but `source-lines` and `normalized-items` list endpoints currently return empty arrays. The UI therefore cannot use the API as the source of truth.

### Goal

Make normalization list APIs return complete, tenant-scoped, persisted data for source lines, mapped items, unresolved items, conflicts, and readiness context.

### Non-goals

- Advanced AI-assisted mapping correction UX
- Historical version browser unless required for Alpha

### Proposed architecture

- Query normalization data directly from existing persisted models.
- Align readiness rules and list endpoint payloads to the same underlying state.
- Expose conflict/missing-price/missing-map states explicitly rather than relying on hidden service behavior.

### Nexus package-first reflection

- If normalization state, readiness, conflict classification, or mapping semantics are currently app-local, assess whether they belong in a reusable Nexus quotation/normalization package.
- If multiple packages are needed to derive readiness, evaluate a dedicated orchestrator rather than duplicating policy in controllers.
- Keep Eloquent/API serialization details in Atomy-Q or adapters, but move reusable normalization logic into Nexus.

### API / contracts

- `GET /normalization/source-lines`
- `GET /normalization/normalized-items`
- Optional `GET /normalization/readiness`
- Response contracts must include identifiers, RFQ line linkage, quote line linkage, match/conflict status, pricing completeness, and timestamps

### Data model

- Existing normalization tables remain source of truth
- Add indexes if needed for tenant + RFQ + submission lookups
- Add status fields only if current schema cannot represent unresolved/conflict states clearly

### Migration plan

1. Define response schema from existing data model.
2. Implement tenant-scoped queries.
3. Add feature tests for empty, partial, and complete normalization states.
4. Remove UI assumptions that empty arrays are acceptable placeholders.

### Risks / open questions

- Whether normalized items are mutable in place or versioned
- How conflict resolution actions should be represented
- Whether pagination is needed for large RFQs in Alpha

### Acceptance criteria

- Source lines endpoint returns persisted lines after quote parsing.
- Normalized items endpoint returns mapped and unresolved records accurately.
- Readiness state can be derived from returned data and matches backend decisions.
- No endpoint returns placeholder empty arrays when real data exists.

---

## 4. Comparison workflow completion

### Problem

Final comparison persists a snapshot, but preview, matrix, readiness, scoring updates, and lock/unlock are incomplete or stubbed. The comparison workflow is therefore inconsistent and hard to reason about.

### Goal

Define and implement a coherent comparison lifecycle from preview through finalization, with one consistent data contract across preview, matrix, readiness, and locking behaviors.

### Non-goals

- Advanced scenario modeling
- Multi-round negotiations
- Experimental scoring model variants beyond Alpha core

### Proposed architecture

- Define a comparison aggregate rooted in persisted normalized data.
- Decide whether preview is ephemeral or persisted as a draft comparison.
- Compute matrix/readiness from the same normalized state used for finalization.
- Make lock/finalization semantics explicit and enforceable.

### Nexus package-first reflection

- Check whether comparison scoring, matrix generation, readiness, and finalization semantics belong in a reusable Nexus procurement/comparison package.
- If comparison spans normalization, policy evaluation, and award readiness, identify a Layer 2 orchestrator candidate.
- Keep snapshot persistence and Laravel controller concerns outside reusable package logic.

### API / contracts

- `POST /comparison-runs/preview`
- `GET /comparison-runs/{id}/matrix`
- `GET /comparison-runs/{id}/readiness`
- `POST /comparison-runs/final`
- `POST /comparison-runs/{id}/lock` and `/unlock` only if workflow requires them

### Data model

- Reuse `comparison_runs` and snapshot payload
- Add draft/preview status if preview becomes persistent
- Add lock/finalized metadata if not already present

### Migration plan

1. Decide preview persistence model.
2. Implement matrix/readiness off normalized persisted state.
3. Replace fake ids and stub responses.
4. Add tests for transition rules and tenant scoping.

### Risks / open questions

- Whether preview should survive page reloads
- Whether scoring models can change after preview or after finalization
- Whether locking is needed before awards in Alpha

### Acceptance criteria

- Preview, matrix, and readiness use the same source data.
- Final comparison produces a stable persisted snapshot.
- Stub comparison endpoints are removed or fully implemented.
- Tenant isolation is enforced across all comparison queries and mutations.

---

## 5. Award and winner selection

### Problem

Alpha requires selecting a winner and saving the result, but all award endpoints currently return `501 Not Implemented`.

### Goal

Implement award creation, retrieval, and UI wiring so a user can select a winner from a comparison result and persist that decision.

### Non-goals

- Full procurement contracting workflow
- Multi-stage approval chains unless required by Alpha
- Post-award vendor management

### Proposed architecture

- Define award creation as a tenant-scoped action that references a valid RFQ and finalized comparison result.
- Persist decision metadata, selected vendor, rationale, and timestamps.
- Expose awards through simple read/write endpoints for Alpha.

### Nexus package-first reflection

- Assess whether award decision rules, winner selection validation, and audit semantics belong in a reusable Nexus package rather than app-only code.
- If award creation coordinates comparison, approval policy, and persistence, evaluate a Nexus orchestrator.
- If notifications/audit writes are needed, consider adapters rather than controller-level branching.

### API / contracts

- `POST /awards`
- `GET /awards`
- `GET /awards/{id}`
- Optional `POST /awards/{id}/cancel` only if revocation is part of Alpha

### Data model

- Reuse existing awards table if schema is sufficient
- Required fields likely include `tenant_id`, `rfq_id`, `comparison_run_id`, `vendor_id`, status, decision metadata, timestamps
- Add audit fields if absent

### Migration plan

1. Validate current awards table against required workflow.
2. Add missing columns if needed.
3. Implement award service/controller logic.
4. Wire web app winner-selection flow to live API.

### Risks / open questions

- Single winner vs split award
- Whether award requires prior approval workflow
- What audit trail is mandatory for Alpha

### Acceptance criteria

- User can select and persist a winner through the API.
- Award can be read back and shown in UI.
- Award references valid tenant-scoped comparison/RFQ records.
- No award flow depends on mock or seed-only state.

---

## 6. Vendor master and invitation alignment

### Problem

Vendor invitations are stored, but the vendor directory API is stubbed and quote submissions use string vendor references. Alpha lacks a stable vendor master model.

### Goal

Provide a real tenant-scoped vendor directory that supports invitations, quote submissions, lookup, and reminder actions using stable vendor identities.

### Non-goals

- Supplier portal
- Vendor compliance/risk scoring
- Rich vendor CRM features

### Proposed architecture

- Decide whether Alpha uses a dedicated `vendors` master table or a derived vendor aggregate.
- Normalize how invitations and quote submissions refer to vendors.
- Make reminder and listing endpoints operate on persisted vendor data.

### Nexus package-first reflection

- Assess whether vendor identity, invitation lifecycle, and vendor-contact normalization are reusable procurement capabilities that should live in Nexus.
- If vendor directory, invitations, and submissions need coordination, identify a possible Layer 2 procurement/vendor orchestrator.
- Keep Laravel CRUD scaffolding in adapters, but avoid trapping reusable vendor domain rules in Atomy-Q.

### API / contracts

- `GET /vendors`
- `POST /vendors`
- `PATCH /vendors/{id}`
- `POST /vendor-invitations/{id}/remind`

### Data model

- `vendors` table if not already present
- Stable `vendor_id` used by invitations and submissions
- Tenant-scoped uniqueness on vendor email/name as appropriate

### Migration plan

1. Decide canonical vendor identity model.
2. Add or finalize `vendors` table.
3. Update invitation/submission relations.
4. Implement tenant-scoped vendor queries and remind behavior.

### Risks / open questions

- Whether vendor identity is email-based, external-ID-based, or both
- Whether existing string vendor IDs are already meaningful
- Deduplication rules for vendor records

### Acceptance criteria

- `/vendors` returns real persisted tenant-scoped records.
- Invitations and quote submissions resolve to stable vendor identities.
- Reminder action targets real records and is idempotent.

---

## 7. RFQ mutation hardening and stub removal

### Problem

RFQ CRUD is mostly functional, but duplicate, save-draft, and bulk actions remain stubbed or weakly implemented. Some endpoints return synthetic identifiers, and TODOs indicate tenant/idempotency risks.

### Goal

Complete the remaining RFQ mutations so all Alpha-critical RFQ actions are real, tenant-safe, and concurrency-safe.

### Non-goals

- Template marketplace
- Complex bulk workflow automation
- Legacy backward compatibility with fake/stub response shapes

### Proposed architecture

- Implement duplicate as a real copy operation for RFQ plus permitted related data.
- Define draft/published semantics clearly.
- Restrict bulk actions to explicit allowed commands with tenant-scoped queries.
- Apply idempotency to duplicate and bulk write paths.

### Nexus package-first reflection

- Reuse Nexus idempotency capabilities before adding app-local duplicate/bulk protections.
- If RFQ lifecycle, duplication rules, or bulk command handling are reusable procurement patterns, evaluate placing them in Nexus packages.
- If multiple reusable services must be composed for safe mutation flows, identify a Layer 2 orchestrator instead of controller-driven coordination.

### API / contracts

- `POST /rfqs/{id}/duplicate`
- `POST /rfqs/{id}/save-draft`
- `POST /rfqs/bulk-action`

### Data model

- Reuse RFQ tables
- Add status metadata if draft/published lifecycle is underspecified
- Ensure child record copy rules are documented

### Migration plan

1. Define RFQ state transitions.
2. Implement duplicate copy rules.
3. Implement tenant-scoped bulk actions.
4. Add idempotency coverage and concurrency tests.

### Risks / open questions

- Which relations are copied on duplicate: vendors, attachments, comparison settings, deadlines
- Whether draft and published are separate statuses or separate workflows
- Which bulk actions are actually in Alpha scope

### Acceptance criteria

- Duplicate returns a real RFQ identifier and copied persisted data.
- Bulk actions only affect tenant-owned RFQs.
- Save draft persists real state transitions.
- No RFQ mutation endpoint returns synthetic placeholder responses.

---

## 8. Identity, session, role, and permission hardening

### Problem

The application still binds several auth-adjacent interfaces to stub or no-op implementations in `AppServiceProvider`, including session, permissions, roles, MFA, and audit logging.

### Goal

Either replace production-critical stubs with real implementations or explicitly remove those capabilities from Alpha scope so no launch path depends on fake security behavior.

### Non-goals

- Full SSO suite
- Enterprise IAM administration
- MFA rollout if explicitly out of Alpha scope

### Proposed architecture

- Define the minimum Alpha auth/security model.
- Replace only the bindings required by that model.
- Feature-flag or remove routes/UI that would imply unsupported RBAC/session features.
- Add audit logging for high-risk mutations if award/comparison approvals require it.

### Nexus package-first reflection

- Inventory whether identity, sessions, RBAC, MFA, and audit logging already exist or should exist as reusable Nexus capabilities.
- Where Atomy-Q currently binds stubs, prefer replacing them with Nexus-backed implementations instead of app-only replacements.
- If auth flows require cross-package coordination, evaluate a dedicated orchestrator; if Laravel-specific persistence or middleware is needed, prefer adapters.

### API / contracts

- Clarify which permission/session contracts are required for Alpha runtime
- Remove or disable APIs that imply unsupported auth features

### Data model

- Session/audit tables only if needed by chosen Alpha scope
- Role/permission persistence only if true RBAC is in scope

### Migration plan

1. Inventory stubbed bindings by runtime usage.
2. Decide keep/replace/remove for each.
3. Implement required bindings and permission checks.
4. Hide unsupported features in UI and API.

### Risks / open questions

- Whether JWT-only auth is sufficient for Alpha
- Whether approvals/awards require audit logs
- Whether role-based UI already assumes real permission data

### Acceptance criteria

- No production-critical path depends on stubbed security bindings.
- Unsupported auth features are hidden or disabled, not silently no-op.
- Permission checks for sensitive Alpha actions are explicit and testable.

---

## 9. Frontend live-API readiness

### Problem

The web app still contains mock fallbacks, seed-backed flows, and test defaults that favor mocks. Production build is also currently broken due to a TypeScript error.

### Goal

Make the web app operate correctly against the live API with mocks disabled, and ensure CI catches live integration regressions.

### Non-goals

- Removing all test doubles from all test suites
- Rewriting generated API client unless necessary

### Proposed architecture

- Fix build blockers first.
- Define strict live-API behavior for Alpha core flows.
- Remove silent fallback-to-seed behavior for those flows.
- Add at least one live API E2E path in CI/staging validation.

### Nexus package-first reflection

- Frontend integration itself is app-specific, but generated clients, contract handling, and error envelope patterns may belong in shared adapters/tooling.
- If repeated SaaS apps will need the same API client patterns or live-vs-mock contract safeguards, capture that as reusable adapter/tooling work in Nexus.

### API / contracts

- Confirm canonical API base URL contract including `/api/v1`
- Ensure frontend hooks expect real API error/loading states instead of mock fallback values

### Data model

- No primary data model changes expected

### Migration plan

1. Fix `npm run build`.
2. Remove seed fallbacks in Alpha-critical hooks/pages.
3. Separate demo/mock mode from Alpha runtime mode.
4. Run E2E against live API in CI or staging gate.

### Risks / open questions

- Whether a demo mode must remain for sales/internal use
- Which pages may degrade gracefully versus hard-fail
- Whether current Playwright setup can support live API affordably

### Acceptance criteria

- `npm run build` passes.
- Core Alpha routes function with `NEXT_PUBLIC_USE_MOCKS=false`.
- Core hooks surface real API errors instead of silently reading seed data.
- CI or release gating includes at least one live API smoke run.

---

## 10. Alpha scope reduction and feature flags

### Problem

Several modules are scaffolded, stubbed, or non-core, but remain reachable in the product surface. This creates false completeness and expands launch risk.

### Goal

Reduce Alpha to a clearly bounded core workflow and hide or disable unfinished modules through explicit flags and navigation rules.

### Non-goals

- Permanent deletion of future roadmap modules
- Deep feature flag platform work beyond Alpha needs

### Proposed architecture

- Define Alpha core as auth, tenant/company, RFQ, vendors, quote ingestion, normalization, comparison, and award.
- Add a top-level Alpha feature mode flag, plus module-specific flags only where necessary.
- Remove or hide scaffold pages and stub APIs from the Alpha runtime surface.

### Nexus package-first reflection

- Feature-flagging policy may remain app-specific, but reusable package-backed modules should expose clear capability boundaries that allow SaaS apps to enable/disable them predictably.
- If multiple future apps will need the same module-capability gating patterns, evaluate shared adapter/tooling support.

### API / contracts

- Feature-flag middleware or route registration guard for incomplete modules
- Frontend nav and route guards tied to the same feature decisions

### Data model

- No primary schema changes required

### Migration plan

1. Inventory routes/pages outside Alpha core.
2. Mark each as hide, disable, or fully implement.
3. Add flags and navigation guards.
4. Validate Alpha user journey cannot enter incomplete modules.

### Risks / open questions

- Whether hidden modules should return `404`, `403`, or "coming soon"
- Whether internal users need access to non-Alpha modules in staging
- Whether a single `FEATURE_ALPHA_CORE_ONLY` flag is sufficient

### Acceptance criteria

- Alpha users cannot navigate into unfinished modules.
- Stub APIs are removed, disabled, or hidden behind non-production flags.
- Product surface matches documented Alpha scope.

---

## 11. Environment, deployment, and runtime contract

### Problem

Even with code fixes, Alpha deployment remains underdefined: AI provider config is undocumented, queue/runtime expectations are unclear, CORS is localhost-only, storage assumptions are incomplete, and API/web env examples risk drift.

### Goal

Define a complete runtime contract for local, staging, and production environments so Alpha can be deployed and operated predictably.

### Non-goals

- Full platform engineering redesign
- Multi-cloud deployment abstraction

### Proposed architecture

- Publish one canonical env and deployment contract for API and web.
- Document queue worker, object storage, JWT, DB, CORS, and AI provider requirements.
- Align web and API examples on a single source of truth for base URLs and ports.

### Nexus package-first reflection

- Runtime configuration is partly app-specific, but provider adapters, queue conventions, storage contracts, and integration wiring should be checked for promotion into reusable Nexus adapters.
- If repeated SaaS deployments will need the same operational glue, document those as adapter candidates instead of embedding one-off setup rules in Atomy-Q.

### API / contracts

- API env contract for DB, JWT, storage, CORS, AI provider, queue
- Web env contract for API base URL and mock/live mode
- Operational runbook for workers and storage

### Data model

- No product schema changes expected
- Operational metadata only if background jobs need additional tracking

### Migration plan

1. Update `.env.example` files.
2. Document worker command/process manager requirements.
3. Add staging checklist for uploads, parsing, CORS, and auth.
4. Verify production smoke path end to end.

### Risks / open questions

- Whether queue-backed processing is mandatory for Alpha
- Whether MinIO and S3 must both be supported
- Whether existing DB port/url examples reflect actual deployment topology

### Acceptance criteria

- API and web env examples fully describe Alpha prerequisites.
- CORS is valid for intended staging/production origins.
- Queue and storage requirements are documented and testable.
- Deployment runbook can support an end-to-end Alpha smoke test.

---

## Ownership decision matrix seed

Use this as the starting point during brainstorm/spec work. The final spec should refine these entries, not ignore them.

| Capability / gap | Reusable across future SaaS? | Best layer | Proposed owner | Why not another layer? | App-specific residue |
|------------------|------------------------------|------------|----------------|------------------------|----------------------|
| Tenant/company lifecycle | Yes | Layer 1 + Layer 3 | Nexus tenancy capability + Laravel adapter | Not app-only because tenant identity is foundational for future SaaS; not just Layer 3 because lifecycle rules are domain logic | Atomy-Q onboarding screens and copy |
| Tenant onboarding flow | Yes | Layer 2 | Nexus onboarding orchestrator | Coordinates identity, tenant creation, feature defaults, maybe billing; too cross-cutting for controller-only logic | Atomy-Q-specific onboarding UX |
| Quote ingestion state machine | Yes | Layer 1 | Nexus quotation/ingestion enhancement | Domain workflow, not framework glue; likely reusable across procurement/document SaaS | Endpoint shaping and UI status rendering |
| AI extraction provider wiring | Yes | Layer 3 | Nexus AI/provider adapter | Provider transport/config belongs in adapters, not app controllers | App-specific environment defaults |
| Quote intake -> extraction -> normalization flow | Yes | Layer 2 | Nexus quote-intake orchestrator | Coordinates multiple packages and async transitions; too broad for Layer 1 only | Atomy-Q API endpoint and polling UX |
| Normalization readiness/conflict logic | Yes | Layer 1 | Nexus normalization capability | Reusable domain logic; should not live only in Atomy-Q services | Eloquent serialization and page presentation |
| Normalization list query shaping | Partly | Layer 3 + app-local | Laravel adapter plus Atomy-Q response shaping | Query/persistence mapping is adapter work; final JSON format may remain app-specific | Page-specific payload trimming/grouping |
| Comparison scoring and matrix rules | Yes | Layer 1 | Nexus comparison capability | Domain logic reusable across sourcing/procurement apps | Atomy-Q table layout and presentation |
| Comparison workflow coordination | Yes | Layer 2 | Nexus comparison orchestrator | Coordinates normalization readiness, scoring, snapshotting, and finalization | Atomy-Q route/controller composition |
| Award winner validation rules | Yes | Layer 1 | Nexus award/procurement capability | Validation rules are reusable; not just controller checks | Atomy-Q award UX copy and status labels |
| Award creation flow | Yes | Layer 2 | Nexus award orchestrator | Likely coordinates comparison result, policy checks, audit, notifications | Atomy-Q API payload/UI flow |
| Vendor identity and invitation rules | Yes | Layer 1 | Nexus vendor/procurement capability | Vendor domain is not unique to Atomy-Q | Atomy-Q vendor forms and listing filters |
| Vendor reminder delivery integration | Yes | Layer 3 | Notification/mail adapter | Delivery transport is adapter territory | Reminder button placement and UX |
| RFQ duplicate/draft/bulk mutation rules | Likely yes | Layer 1 + Layer 2 | Nexus RFQ capability plus mutation orchestrator | Underlying rules are reusable; coordination across child entities may need orchestrator | Atomy-Q endpoint routing and toast messages |
| Idempotency enforcement for mutations | Yes | Layer 1 + Layer 3 | Existing Nexus idempotency package plus Laravel adapter integration | Already a reusable cross-SaaS concern; should not be reinvented in controllers | Endpoint-specific idempotency key conventions |
| Roles/permissions/session production bindings | Yes | Layer 1/3 depending scope | Nexus auth capability and Laravel adapters | Security primitives should be shared when possible; framework glue belongs in adapters | Atomy-Q role labels and page gating decisions |
| Audit logging for sensitive procurement actions | Yes | Layer 1 + Layer 3 | Nexus audit capability plus persistence adapter | Cross-cutting and reusable; not specific to one SaaS | Which Atomy-Q actions are marked critical |
| Frontend live API contract guards | Partly | Layer 3/tooling + app-local | Shared client/tooling adapter with Atomy-Q integration | Client tooling can be reusable, but route/page behavior remains app-specific | React hooks and screen states |
| Feature flagging for Alpha-only surface | Partly | App-local unless generalized later | Atomy-Q | Product rollout surface is currently product-specific; only promote if repeated pattern appears across apps | Entire implementation remains in Atomy-Q for now |
| Deployment/env alignment | Partly | Layer 3/tooling + app-local | Shared adapter/tooling where reusable, app-local runbook otherwise | Some operational glue is reusable, but deployment topology remains product-specific | Atomy-Q env docs and release checklist |

---

## Suggested sequencing

Recommended order for spec/design expansion:

1. Tenant and company foundation
2. Quote ingestion and AI normalization pipeline
3. Normalization read APIs backed by persisted data
4. Comparison workflow completion
5. Award and winner selection
6. Vendor master and invitation alignment
7. RFQ mutation hardening and stub removal
8. Frontend live-API readiness
9. Identity, session, role, and permission hardening
10. Alpha scope reduction and feature flags
11. Environment, deployment, and runtime contract

## Cross-cutting Nexus opportunities to evaluate

These should be explicitly reviewed during brainstorm/spec work, not treated as afterthoughts.

### Candidate Layer 1 package enhancements

- Tenant/company lifecycle and tenant feature policy support
- Quote ingestion domain model and parse state machine
- Normalization readiness/conflict classification logic
- Comparison scoring, matrix, and finalization rules
- Award decision and validation rules
- Vendor identity and invitation domain rules

### Candidate Layer 2 orchestrators

- Tenant onboarding orchestrator
- Quote intake and normalization orchestrator
- Comparison and award decision orchestrator
- Vendor invitation and reminder orchestrator
- RFQ mutation orchestration for duplicate, draft, and bulk actions

### Candidate Layer 3 adapters

- Laravel adapters for tenant persistence and onboarding endpoints
- Queue/job adapter for quote parsing workflows
- AI provider adapters for extraction/normalization
- Storage adapter for quote source document retrieval
- Audit/session/permission adapters for production auth flows
- Shared API client/tooling adapters for future SaaS frontends

## Suggested spec template for the next step

Use this section template when turning one initiative into a full design document:

1. Context and problem statement
2. Goals
3. Non-goals
4. Nexus package-first reflection
5. Ownership decision matrix
6. User journeys
7. Domain model and terminology
8. Architecture and layering plan
9. Orchestrator candidates
10. Adapter candidates
11. API contracts
12. Data model and migrations
13. Security and tenant isolation
14. Idempotency and concurrency behavior
15. Error handling and observability
16. Testing strategy
17. Rollout plan
18. Open questions
19. Acceptance criteria
