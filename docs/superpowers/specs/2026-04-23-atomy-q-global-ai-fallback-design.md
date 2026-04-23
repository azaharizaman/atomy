# Atomy-Q AI-First Operating Architecture

**Date:** 2026-04-23  
**Status:** Draft for review  
**Scope:** Target-state architecture with explicit alpha release posture

## Purpose

Atomy-Q is an AI-backed procurement SaaS. AI is not an optional marketing overlay. AI is part of the product promise across the RFQ chain: quote ingestion, normalization, vendor recommendation, comparison insighting, award guidance, and decision support.

At the same time, Atomy-Q cannot become operationally brittle. The main RFQ processing chain must remain usable when AI is disabled, degraded, unreachable, or intentionally taken offline for diagnostics or risk control.

This spec defines the target-state architecture for that operating model and makes the alpha decisions explicit.

## Decision Summary

- Atomy-Q is **AI-first**, not AI-optional.
- Alpha must ship with **real provider-backed AI**, not only deterministic or rule-based approximations.
- Alpha will use **one globally selected provider** for all AI capability groups.
- **OpenRouter** is the alpha default and primary provider.
- **Hugging Face** is also supported as an alternative single-provider deployment option.
- Alpha will use **separate managed endpoints per capability group**, not one shared endpoint for the whole application.
- The **main RFQ chain** must remain manually operable when provider-backed AI is down.
- Some AI surfaces remain **AI-only** in alpha and must show a truthful unavailable state when AI is down.
- AI may **extract, classify, summarize, recommend, and draft**, but it may not silently perform authoritative business actions such as final award, approval, or vendor status mutation without deterministic validation and human confirmation.

## Goals

1. Make AI a live, provider-backed part of the alpha product.
2. Preserve RFQ business continuity when AI is unavailable.
3. Centralize AI capability and health decisions so behavior does not drift across API and WEB.
4. Assign ownership correctly across Nexus Layer 1, Layer 2, and Layer 3.
5. Ensure provider-backed AI outputs are auditable, tenant-safe, and operationally supportable.
6. Define exactly what the operator must provide to power AI in alpha.

## Non-Goals

- No multi-provider failover in alpha.
- No per-tenant AI on/off control in alpha.
- No fully autonomous approval or award execution.
- No synthetic AI outputs when provider-backed AI is unavailable.
- No hiding provider failure behind fake success payloads.

## Core Product Position

The correct product stance is:

- **AI-primary** for the default experience.
- **Manual continuity** for operational safety.
- **Human-authoritative** for legally or commercially binding decisions.

This means:

- AI should be used by default when available.
- Users should still be able to continue the RFQ chain when AI is unavailable.
- Final business authority remains with explicit application logic and user action, not opaque model output.

## Architecture Principles

### 1. AI-first, not AI-autonomous

AI can recommend, rank, classify, summarize, extract, and draft. AI cannot directly finalize awards, approve workflows, or mutate tenant-critical records without an explicit deterministic application step or user confirmation.

### 2. Capability-based control, not scattered env checks

AI behavior must be controlled through a centralized capability registry and health model. Controllers, jobs, hooks, and pages must not independently guess whether AI is available.

### 3. Manual continuity for the RFQ chain

If AI fails, the user must still be able to:

- upload quote documents,
- manually create or edit source lines when extraction fails,
- manually map source lines to RFQ lines,
- run deterministic comparison on stored normalized data,
- manually choose a vendor for award,
- continue approvals and decision-trail review.

### 4. Truthful failure semantics

If a feature is AI-only, the system must say it is unavailable. It must not return fabricated recommendation arrays, fake summaries, or synthetic ranking output.

### 5. Provenance for every AI artifact

Every stored AI artifact must retain provenance metadata sufficient for audit, debugging, and operator diagnosis.

## Target-State Capability Topology

Atomy-Q AI is split into capability groups. Each group may depend on a different endpoint or route within the selected global provider.

| Capability Group | Purpose | Representative Atomy-Q Surfaces | Alpha Provider-Backed | Manual Continuity |
|---|---|---|---|---|
| `document_intelligence` | Extract and structure quote documents into usable source lines and commercial terms | quote upload, quote reparse, quote detail | Yes | Yes |
| `normalization_intelligence` | Suggest line-item mapping, taxonomy, unit normalization, and conflict hints | normalize workspace, normalization review payloads | Yes | Yes |
| `sourcing_recommendation_intelligence` | Rank vendors and explain shortlist recommendations | RFQ vendors page, vendor recommendation endpoint | Yes | Partial |
| `comparison_intelligence` | Produce comparison recommendation overlays, anomaly explanations, and confidence signals | comparison preview/final, comparison run detail, recommendation endpoints | Yes | Yes |
| `award_intelligence` | Draft award guidance, debrief summaries, and recommendation rationale for the award workflow | award page, debrief drafting, recommendation reuse | Yes | Yes |
| `insight_intelligence` | Generate RFQ and dashboard insight summaries | dashboard, RFQ insights sidebar, overview insight surfaces | Yes | Partial |
| `governance_intelligence` | Summarize or enrich vendor governance, sanctions, and due diligence outputs | vendor governance pages, risk views, due-diligence explanations | Yes | Yes |

`Manual continuity` means the business workflow remains usable. It does not mean every AI-derived embellishment has a manual replacement.

## Layer Ownership

The architecture must respect Nexus layering.

### Layer 1: First-Party Packages (`packages/`)

Layer 1 owns domain truth and framework-agnostic contracts.

| Package | Responsibility In This Design |
|---|---|
| `Nexus\Common` | IDs, clocks, common result envelopes, provider trace primitives where truly generic |
| `Nexus\Tenant` | Tenant context, tenant-safe scoping for all AI requests and persisted artifacts |
| `Nexus\Setting` | Runtime AI settings that are configuration-like rather than deployment-secret-like; future scoped settings beyond alpha |
| `Nexus\FeatureFlags` | Future rollout gates and kill switches beyond the global alpha env switch |
| `Nexus\Storage` | Storage abstraction for documents, generated artifacts, and provider request/response references where persisted |
| `Nexus\Document` | Document domain contracts for source files and versioned document handling |
| `Nexus\AuditLogger` and `Nexus\Audit` | Human-readable audit entries and tamper-evident audit evidence for AI-assisted decisions |
| `Nexus\Notifier` | Operational alerts and notifications when AI endpoint groups degrade |
| `Nexus\Idempotency` | Safe retry behavior for AI-triggering mutating endpoints |
| `Nexus\Outbox` | Post-commit publication of AI events, alerts, and downstream work |
| `Nexus\MachineLearning` | Provider abstraction, model/runtime vocabulary, endpoint capability contracts, runtime health primitives, provider request/response contracts |
| `Nexus\ProcurementML` | Procurement-specific ranking, sourcing recommendation, and procurement AI contracts where they belong in Layer 1 |

### Layer 2: Orchestrators (`orchestrators/`)

Layer 2 coordinates multi-package workflows and owns cross-package orchestration logic.

| Orchestrator | Responsibility In This Design |
|---|---|
| `QuotationIntelligence` | Coordinate document intelligence, normalization intelligence, commercial terms extraction, risk hints, and comparison-oriented intelligence |
| `QuoteIngestion` | Coordinate quote submission processing, source line persistence, reparse behavior, and ingestion decision trails |
| `ProcurementOperations` | Coordinate sourcing recommendation, procurement decision support, and recommendation explanation flows |
| `IntelligenceOperations` | Coordinate AI health, endpoint-group health aggregation, capability availability, provider-state normalization, and application-facing AI status snapshots |
| `SettingsManagement` | Future runtime settings coordination if AI settings move from env-only to managed settings |

### Layer 3: Application Adapters (`apps/atomy-q/API` and `apps/atomy-q/WEB`)

Layer 3 owns framework-specific behavior.

| Layer 3 Surface | Responsibility In This Design |
|---|---|
| `apps/atomy-q/API` | Env/config parsing, secret injection, provider HTTP adapters, controller behavior, queue/job wiring, persistence models, migrations, route exposure, status endpoints |
| `apps/atomy-q/WEB` | Status consumption, UX gating, hide/disable/unavailable rendering, manual continuity UX, pre-auth and post-auth AI messaging |

## Provider Architecture

### Alpha Provider Model

Alpha uses **one globally selected provider** for all capability groups.

The default alpha provider is **OpenRouter**. **Hugging Face** is supported as an alternative single-provider deployment option. Future providers may be added later through `Nexus\MachineLearning` contracts, but alpha still runs with exactly one active provider in a given environment.

This is a deployment decision, not a Layer 1 business-modeling decision. Different capability groups may still use different models, routes, or endpoint URLs, but they must all belong to the same selected provider in that environment.

### Separate Endpoint Groups

Alpha must not rely on one shared managed endpoint for every AI function. Separate endpoint groups are required because latency, cost, model shape, prompt structure, and failure domains differ materially across the RFQ chain.

| Endpoint Group | Primary Consumers | Why It Must Be Separate |
|---|---|---|
| `AI_DOCUMENT_ENDPOINT` | upload, reparse, commercial terms extraction | document parsing has different payload size, latency, and model behavior |
| `AI_NORMALIZATION_ENDPOINT` | line mapping, taxonomy hints, UOM normalization | normalization needs structured extraction and classification behavior |
| `AI_SOURCING_RECOMMENDATION_ENDPOINT` | vendor ranking and shortlist explanation | recommendation requires vendor/context ranking rather than document parsing |
| `AI_COMPARISON_AWARD_ENDPOINT` | comparison explanations, recommendation overlays, award guidance, debrief drafting | comparison and award guidance depend on frozen sourcing data and explanation workflows |
| `AI_INSIGHT_ENDPOINT` | dashboard and RFQ insight summaries | summary generation should not share failure or scaling limits with transactional RFQ operations |
| `AI_GOVERNANCE_ENDPOINT` | sanctions/due diligence narrative enrichment, governance explanations | governance workloads are adjacent but operationally different from RFQ chain traffic |

### Future Provider Strategy

The target-state architecture must support future provider expansion through `Nexus\MachineLearning` contracts. Alpha does not require active multi-provider failover, mixed-provider routing, or per-capability provider selection, but it must not hard-code OpenRouter-, Hugging Face-, or any other provider-specific assumptions into Layer 1 business contracts.

## Runtime Modes And Health Model

### Configuration Modes

The top-level AI mode should be modeled as:

- `off`
- `provider`
- `deterministic`

For implementation compatibility during transition, a legacy `llm` value may be accepted as an alias of `provider`, but the target-state naming should be `provider` because Atomy-Q AI is broader than LLM-only use.

### Meaning Of Modes

| Mode | Meaning |
|---|---|
| `off` | AI intentionally disabled globally |
| `provider` | Real provider-backed AI from the selected single global provider is the default path |
| `deterministic` | Controlled fallback mode for diagnostics, emergency continuity, local development, or staged rollout; not the commercial alpha default |

### Runtime Health States

| Health State | Meaning |
|---|---|
| `disabled` | AI is intentionally off by configuration |
| `healthy` | required provider endpoint groups are reachable and within policy |
| `degraded` | some endpoint groups are failing or breaching thresholds, but continuity paths still exist |
| `unavailable` | provider-backed AI is expected but currently not usable for the affected capability group |

### Availability Model

Availability is computed from:

- config mode,
- provider selection,
- endpoint-group health,
- capability registry dependencies,
- manual fallback policy.

This means the system can be globally `degraded` while only some capability groups are unavailable.

## Capability Registry Contract

Every AI-capable surface must map to a capability definition containing at least:

- `feature_key`
- `capability_group`
- `requires_ai`
- `has_manual_fallback`
- `fallback_ui_mode`
- `degradation_message_key`
- `operator_critical`

Recommended `fallback_ui_mode` values:

- `hide_ai_controls`
- `show_unavailable_message`
- `show_manual_continuity_banner`

The registry is authoritative for:

- whether an action is callable,
- whether a page hides AI affordances,
- whether the API returns unavailable,
- what message the UI shows,
- whether operator alerts should fire for that capability.

## Surface Operating Model

### Dashboard

**AI default path**

- show provider-backed summaries, insight cards, and AI status indicators

**Manual continuity**

- KPI cards, activity, counts, and operational navigation still render from deterministic/live data

**If AI unavailable**

- AI insight widgets show explicit unavailable state
- “AI insights ready” language must disappear

### RFQ Overview And Insights Sidebar

**AI default path**

- RFQ summary, recommendation-oriented copy, and AI insight blocks are live

**Manual continuity**

- RFQ metadata, counts, schedule, and next-step guidance remain visible

**If AI unavailable**

- hide or replace AI-only insight blocks
- keep non-AI comparison, counts, and schedule context visible

### Quote Ingestion

**AI default path**

- uploaded quote documents go through provider-backed extraction and structuring

**Manual continuity**

- upload must still succeed even when provider-backed AI is unavailable
- if extraction fails, the user must be able to manually create or edit source lines

**Required alpha design implication**

The product must support manual source-line entry or correction. Without that, upload continuity is not real continuity.

### Quote Reparse

**AI default path**

- reparse re-invokes provider-backed extraction

**Manual continuity**

- manual edits must remain authoritative and preserved
- reparse must not overwrite confirmed manual corrections without explicit user action

### Normalization

**AI default path**

- provider-backed line mapping suggestions, taxonomy hints, unit normalization, and conflict hints are available

**Manual continuity**

- users must be able to manually map source lines to RFQ lines, resolve conflicts, and override AI-suggested values

**If AI unavailable**

- normalization workspace remains usable
- AI confidence, taxonomy hints, and AI suggestion panes are hidden or marked unavailable

### Vendor Recommendation

**AI default path**

- provider-backed ranking, shortlist explanation, and recommendation reasons are shown

**Manual continuity**

- user can still manually select vendors for the RFQ

**If AI unavailable**

- no ranked recommendation output is returned
- manual vendor selection remains available
- recommendation-specific panels show unavailable state

Vendor ranking remains AI-only in alpha. Manual ranking is not required for continuity because vendor selection itself remains manual.

### Comparison

**AI default path**

- comparison uses stored normalized lines plus provider-backed recommendation overlays, anomaly explanations, and confidence signals

**Manual continuity**

- deterministic matrix generation and final freeze must remain possible from stored normalized data

**If AI unavailable**

- matrix, readiness, and freeze remain available
- AI recommendation overlays, recommendation endpoints, and explanation-only fields are hidden or marked unavailable

### Recommendation Endpoints

This includes API surfaces under `/recommendations/*` and any future UI wrappers around them.

**Rule**

- if the endpoint is AI-only, it must return a structured unavailable response when AI is unavailable
- it must not return synthetic empty arrays or fake status markers

### Award

**AI default path**

- provider-backed guidance may explain recommended award rationale, draft debrief messaging, and summarize comparison evidence

**Manual continuity**

- award creation remains a user-authoritative action based on frozen comparison evidence
- debrief messaging must remain manually editable

**If AI unavailable**

- award creation and signoff remain usable
- debrief drafting assistance and AI rationale become unavailable

### Approvals

**AI default path**

- AI may summarize supporting evidence, risks, and comparison context

**Manual continuity**

- approval decisions remain human-authoritative and continue using frozen comparison data and explicit policy checks

**If AI unavailable**

- approvals continue
- AI summary panels become unavailable

### Decision Trail

Decision trail is always required.

If AI is used, the trail must capture AI provenance. If AI is unavailable and the user continues manually, the trail must capture that the workflow proceeded under manual continuity or degraded AI conditions.

### Vendor Governance, Risk, And Due Diligence

**AI default path**

- provider-backed summarization or narrative enrichment can support sanctions, due diligence, and governance interpretation

**Manual continuity**

- governance evidence, status changes, and manual review remain available without AI

**If AI unavailable**

- narrative enrichment disappears
- governance records and manual decisions remain usable

### Reporting And Future Analytics Surfaces

Provider-backed narrative summaries may exist. Deterministic report data must remain usable without AI. AI-generated summaries must show unavailable rather than fabricated output.

## API Contract

### New Public Status Endpoint

The API must expose a public application AI status contract, for example:

- `GET /api/v1/ai/status`

The response must contain:

- current AI mode,
- aggregate health,
- capability-group health,
- capability registry projection safe for WEB consumption,
- reason codes suitable for UI messaging and operations.

### AI-Only Endpoint Behavior

AI-only endpoints must return a structured unavailable response when AI is unavailable.

Recommended shape:

```json
{
  "error": {
    "code": "ai_unavailable",
    "message": "Vendor recommendation is temporarily unavailable because the AI provider is unreachable.",
    "feature_key": "vendor_recommendation",
    "capability_group": "sourcing_recommendation_intelligence",
    "manual_continuity": "available"
  }
}
```

`code` values should include at least:

- `ai_disabled`
- `ai_unavailable`
- `ai_degraded`

### AI-Assisted Endpoint Behavior

Endpoints that support manual continuity must continue returning business data when AI is unavailable, but AI-derived fields must become null, omitted, or explicitly unavailable.

Examples:

- normalization source lines still return source data, but AI hints become unavailable
- comparison matrix still returns deterministic matrix data, but AI recommendation overlays do not
- award data still returns frozen comparison evidence, but AI rationale or draft text is unavailable

### No Synthetic Outputs

The API must never return fabricated recommendation payloads or fake completed statuses simply to keep the UI quiet.

### Persistence And Provenance

Every persisted AI artifact should capture provenance fields such as:

- tenant id
- provider
- endpoint group
- model id or model revision
- prompt template version
- provider request id or trace id
- input snapshot hash
- output hash
- confidence or score where applicable
- latency
- inference timestamp
- accepted or rejected by user where applicable

Raw provider secrets must never be persisted.

## WEB Contract

### Status Consumption

The WEB app must:

- start from `NEXT_PUBLIC_AI_MODE` as a public bootstrap hint
- call the public AI status endpoint
- use the live status payload as the authoritative runtime view

### Render Rules

If manual fallback exists:

- hide AI-only controls
- keep the manual path visible
- show only local, relevant messaging

If manual fallback does not exist:

- keep the surface visible if it is an intentional entry point
- show a truthful unavailable message

The WEB app must distinguish:

- `disabled by configuration`
- `temporarily unavailable`
- `degraded`

### Messaging Rules

The UI must not use vague generic errors where an AI capability-specific unavailable state is appropriate.

Good examples:

- `AI recommendation is unavailable. You can still manually select vendors.`
- `AI extraction is unavailable. Upload succeeded. Continue by entering source lines manually.`
- `AI insight summaries are unavailable. Core RFQ data is still available.`

### Pre-Auth And Post-Auth

Pre-auth and post-auth screens must not require a tenant-authenticated status request merely to determine whether AI surfaces should be shown. The public status contract exists so AI posture can be rendered consistently before and after login.

## Security, Tenant Isolation, And Audit

### Tenant Safety

- No AI request may mix data across tenants.
- Provider-bound payloads must contain only the tenant-scoped data required for the capability.
- Cross-tenant existence must still collapse to tenant-safe semantics in HTTP responses.

### Secret Handling

- API keys and provider tokens live only in secure API-side secret storage or environment injection.
- WEB never receives provider secrets.
- Public WEB env vars may mirror AI mode and status path only.

### Data Minimization

- Send the minimum required RFQ, quote, vendor, and governance data to the provider.
- Avoid unnecessary PII in prompts or payloads.
- If a field is not required for the model task, it should not be sent.

### Audit

- Human-readable audit entries must record whether an action used live AI, deterministic fallback, or manual continuity.
- High-risk AI-assisted decisions should retain tamper-evident audit references where required.

## Operator Responsibilities

This section is the operator-owned input contract. Without these inputs, provider-backed AI alpha is not launchable.

### Required Operator Inputs

| Item | What You Must Provide | Why It Is Needed | Consumed By |
|---|---|---|---|
| Provider account / organization | active provider account with billing and route or endpoint ownership | required to run provider-backed AI in the selected environment | API operations and platform setup |
| Managed endpoint URLs or provider routes | one route/URL per endpoint group within the selected provider | application must route each capability group to the correct provider path | API config and provider adapters |
| Access token or service credential | credential with invoke permission for the selected provider routes/endpoints | required to authenticate inference calls | API only |
| Approved model per endpoint | model id, revision, and intended task per endpoint group | required to bind each capability group to the correct model behavior | API config and operations documentation |
| Endpoint scaling policy | min replicas, max replicas, autoscaling thresholds, cold-start policy | required for latency and capacity planning | provider operations |
| Network policy | allowlisted outbound access, private networking decisions, firewall rules if applicable | required so the API can reach the selected provider securely | infrastructure and API runtime |
| Timeout and retry budgets | per-endpoint timeout, retry count, circuit-breaker policy | required to compute degraded and unavailable states safely | API and IntelligenceOperations |
| Quota and cost budget | spend limits, alert thresholds, concurrency expectations | required so AI failure is not discovered only after quota exhaustion | operations and finance |
| Healthcheck policy | how endpoint health is checked and what thresholds define degraded or unavailable | required for truthful status reporting | IntelligenceOperations and API |
| Secret rotation policy | rotation interval, owner, emergency revoke path | required for secure provider operations | infrastructure and API runtime |
| Staging endpoints | non-production managed endpoints with representative models | required for launch verification and regression testing | staging API and QA |
| Incident contacts | who receives alerts for provider degradation or quota exhaustion | required for operational response | Notifier and ops |
| Data handling approval | confirmation that provider usage, retention, and contractual terms are acceptable for tenant data | required for compliant production operation | management, security, compliance |

### Required Deployment Variables

The exact names may evolve, but alpha requires the API to be supplied with configuration equivalent to:

- `AI_MODE`
- `AI_PROVIDER=openrouter`
- `AI_PROVIDER_NAME=openrouter`
- `AI_DOCUMENT_ENDPOINT_URL`
- `AI_DOCUMENT_ENDPOINT_TOKEN`
- `AI_DOCUMENT_MODEL_ID`
- `AI_NORMALIZATION_ENDPOINT_URL`
- `AI_NORMALIZATION_ENDPOINT_TOKEN`
- `AI_NORMALIZATION_MODEL_ID`
- `AI_SOURCING_RECOMMENDATION_ENDPOINT_URL`
- `AI_SOURCING_RECOMMENDATION_ENDPOINT_TOKEN`
- `AI_SOURCING_RECOMMENDATION_MODEL_ID`
- `AI_COMPARISON_AWARD_ENDPOINT_URL`
- `AI_COMPARISON_AWARD_ENDPOINT_TOKEN`
- `AI_COMPARISON_AWARD_MODEL_ID`
- `AI_INSIGHT_ENDPOINT_URL`
- `AI_INSIGHT_ENDPOINT_TOKEN`
- `AI_INSIGHT_MODEL_ID`
- `AI_GOVERNANCE_ENDPOINT_URL`
- `AI_GOVERNANCE_ENDPOINT_TOKEN`
- `AI_GOVERNANCE_MODEL_ID`
- `AI_REQUEST_TIMEOUT_SECONDS`
- `AI_MAX_RETRIES`
- `AI_HEALTH_FAILURE_THRESHOLD`
- `AI_HEALTH_RECOVERY_THRESHOLD`

The WEB app requires only public mirrors such as:

- `NEXT_PUBLIC_AI_MODE`
- `NEXT_PUBLIC_AI_STATUS_PATH`

### Explicit Operator Ownership

You are responsible for:

- providing valid provider credentials and endpoint URLs,
- ensuring those endpoints are provisioned and scaled,
- approving the models used for each capability group,
- funding the provider usage,
- approving the provider data-handling posture,
- maintaining rotation and emergency disable procedures.

The application team is responsible for:

- implementing the provider integration,
- enforcing tenant-safe prompts and payloads,
- exposing truthful status and failure behavior,
- preserving manual continuity,
- logging and auditing AI usage correctly.

## Alpha Release Posture

Alpha is not AI-excluded. Alpha includes real provider-backed AI across the full chain.

### Alpha Must Ship With Live AI For

- quote ingestion
- quote reparse
- normalization intelligence
- vendor recommendation
- comparison insighting and recommendation overlays
- award guidance and debrief drafting
- RFQ and dashboard insights

### Alpha Must Also Preserve Manual Continuity For

- upload continuity even when AI extraction fails
- manual source-line entry or correction
- manual normalization and override
- deterministic comparison and freeze
- manual vendor selection
- manual award selection and signoff
- approvals and decision trail

### Alpha-Specific Constraints

- one globally selected provider for all capability groups
- alpha default provider: OpenRouter
- Hugging Face supported as an alternative single-provider deployment
- separate endpoint groups required
- no multi-provider failover yet
- no mixed-provider topology yet
- no per-capability provider routing yet
- no per-tenant AI toggle yet
- global AI kill switch remains required
- deterministic mode remains available for diagnostics and emergency continuity, but it is not the primary alpha sales posture

### Explicit Alpha Deferrals

- active secondary provider failover
- mixed-provider routing across capability groups
- per-tenant AI enablement policy
- autonomous approvals or awards
- manual vendor ranking as a first-class replacement for AI recommendation

## Launch Gates

The design is not complete until the following are true:

1. Provider-backed AI is live in staging for every alpha capability group.
2. A global AI-off drill confirms the RFQ chain remains manually operable.
3. A degraded-endpoint drill confirms AI-only surfaces show truthful unavailable states.
4. Audit and provenance for AI-assisted flows are persisted and reviewable.
5. No AI-only endpoint returns synthetic success payloads when the provider is unavailable.

## Final Position

Atomy-Q alpha should launch as an AI-first procurement SaaS with real provider-backed AI across the full RFQ chain, using OpenRouter by default while allowing a single-provider Hugging Face deployment option, and while remaining operationally resilient enough to continue the core RFQ workflow when provider-backed AI is unavailable.

That is the correct balance between product differentiation and enterprise reliability.
