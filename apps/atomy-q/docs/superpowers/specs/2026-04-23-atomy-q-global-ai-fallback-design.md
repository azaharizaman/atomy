# Atomy-Q Global AI Fallback Design

**Date:** 2026-04-23

## Purpose

Atomy-Q is an AI-backed SaaS, but alpha must remain operational when AI is disabled globally or when the AI engine is unavailable.

This spec defines the operating model for all AI-capable Atomy-Q areas in alpha:

- AI is controlled globally through environment configuration.
- Manual workflows continue to work when AI is off.
- AI-only features are unavailable when AI is off.
- The UI distinguishes between:
  - intentionally disabled AI
  - AI engine unavailable or degraded at runtime

## Goals

1. Preserve all core RFQ workflows without AI.
2. Keep AI as the primary product differentiator when enabled.
3. Hide AI-only controls when a manual fallback exists.
4. Show explicit unavailability messaging when no fallback exists.
5. Provide a single, centralized capability model so AI behavior does not drift across screens.
6. Distinguish configuration-disabled AI from runtime AI failure in logs and user-facing messaging.

## Non-Goals

- No per-tenant AI toggle in alpha.
- No manual vendor ranking implementation in alpha.
- No attempt to fabricate AI outputs when the AI engine is off.
- No automatic fallback from AI-only decisions into synthetic deterministic results.

## Operating Model

### Global AI Mode

AI availability is a global application concern, controlled by environment variables.

Recommended alpha configuration:

- `AI_MODE=off`
- `AI_MODE=deterministic`
- `AI_MODE=llm`

For alpha:

- `off` means AI is intentionally disabled.
- `deterministic` means AI-capable flows use deterministic adapters.
- `llm` remains dormant unless a real provider is wired in.

`AI_MODE` is the app-wide switch for alpha. Feature-specific toggles may exist internally, but they must resolve through this single global mode.

The system must distinguish:

- `disabled` - AI is intentionally off by config
- `unavailable` - AI was expected but could not be reached
- `degraded` - partial capability exists, but some AI-backed actions are not available

### Capability Registry

All AI-capable screens and actions must be declared in a capability registry rather than checking environment flags inline.

Each capability declares:

- `feature_key`
- `requires_ai`
- `has_manual_fallback`
- `fallback_ui_mode`
- `degradation_message_key`

Suggested `fallback_ui_mode` values:

- `hide_ai_controls`
- `show_unavailable_message`

This registry is the authoritative source for:

- whether a control is rendered
- whether a request can execute
- whether to show an unavailable banner
- what message to show when AI is off or degraded

## Capability Classes

### AI-Assisted

AI can improve the workflow, but the workflow still completes without AI.

When AI is off:

- hide AI-only controls
- keep the manual workflow visible
- do not interrupt the page with error messaging unless the AI-specific panel itself is the primary surface

### AI-Only

The capability does not have a manual fallback in alpha.

When AI is off:

- keep the surface visible if it is the main user entry point
- show an unavailable message
- prevent any action that would imply a real AI decision

### AI-Backed but Degradable

Some workflows have a core deterministic/manual path and optional AI-driven assistance.

When AI is off:

- execute the core deterministic/manual path
- suppress AI-generated embellishments, suggestions, ranking, or explanations

## Alpha AI-Capable Areas

### 1. Quote Ingestion

The quote ingestion workflow includes upload, extraction, parsing, and readiness updates.

Alpha behavior:

- File upload remains available.
- Parsing and persistence remain available.
- Deterministic ingestion adapters remain the default behavior when AI is enabled in deterministic mode.
- AI-specific extraction assistance is hidden when AI is off.

Fallback policy:

- Manual upload and ingestion continue.
- No user-visible AI decision is required to complete the workflow.

### 2. Quote Normalization

Normalization includes source-line mapping, manual override, conflict resolution, and readiness evaluation.

Alpha behavior:

- Manual mapping remains available.
- Manual override remains available.
- Conflict resolution remains available.
- AI suggestions and intelligence metadata are hidden when AI is off.

Fallback policy:

- Hide AI-specific mapping assistance if the user can map manually.
- Keep the normalization workspace usable.

### 3. Comparison

Comparison covers preview, freeze, matrix rendering, readiness, and the transition into award.

Alpha behavior:

- Comparison preview and final freeze remain available if they can be completed from stored quote data.
- Deterministic comparison outputs remain available in deterministic mode.
- AI-derived recommendation overlays are hidden when AI is off.

Fallback policy:

- Keep the matrix, readiness, and freeze workflow visible when they can be produced from non-AI data.
- Do not surface AI-only explanations or recommendation hints.

### 4. Award

Award covers award creation, signoff, debrief, protest, and display of frozen comparison evidence.

Alpha behavior:

- Award creation remains available through the frozen comparison evidence and manual vendor selection path.
- AI-derived ranking support is not required for award creation when a manual path exists.
- If an award subpanel depends entirely on AI guidance, it must show an unavailable state when AI is off.

Fallback policy:

- Hide AI-only award support when manual award selection exists.
- Show unavailable messaging only for award subflows that cannot function without AI.

### 5. Vendor Recommendation

Vendor recommendation is an AI-backed capability in alpha and is currently the clearest AI value proposition in the RFQ workflow.

Alpha behavior:

- When AI is enabled, the recommendation surface can rank candidates and surface explanation data.
- When AI is off, vendor ranking does not run.
- Manual vendor ranking is not implemented in alpha and is not a fallback.

Fallback policy:

- Keep the vendor recommendation entry point visible if the user is expected to use it for shortlist guidance.
- Disable or hide AI ranking actions.
- Show a message that the feature returns when AI is online.

### 6. RFQ Insights / AI Insights Sidebar

The RFQ insights sidebar contains AI-backed summarization and recommendation-oriented content.

Alpha behavior:

- When AI is enabled, the sidebar can show generated insights.
- When AI is off, AI insight panels must not imply that analysis is available.

Fallback policy:

- If the sidebar has manual data that can still be shown, keep it and hide AI-only insight blocks.
- If the entire panel is AI-dependent, render a clear unavailable state.

### 7. Recommendation Endpoints

The recommendation endpoints are UI/API surfaces for AI-generated recommendation outputs.

Alpha behavior:

- These endpoints must not synthesize AI recommendation content when AI is off.
- If they have no deterministic/manual equivalent in alpha, they must return a structured unavailable response.

### 8. Dashboard and Reporting Insight Surfaces

Any dashboard, reporting, or summary widget that consumes AI-generated scoring, ranking, or explanations follows the same policy as the source capability.

Alpha behavior:

- If the widget has a manual or deterministic equivalent, hide AI-only embellishments when AI is off.
- If the widget is entirely AI-driven, show a clear unavailable state.

This includes existing or future surfaces that summarize vendor recommendations, comparison insights, or AI-derived scoring outputs.

## UI Rules

### When Manual Fallback Exists

Render behavior:

- hide AI-only controls
- keep manual workflow controls visible
- avoid noisy global warnings on the page if the user can still complete the task

Examples:

- manual line-item mapping
- manual quote review
- manual award selection

### When No Manual Fallback Exists

Render behavior:

- keep the screen or component visible
- show an explicit unavailable message
- explain whether the cause is disabled config or runtime degradation

Suggested message templates:

- `This feature is unavailable while AI is disabled.`
- `This feature is temporarily unavailable because the AI engine is degraded.`
- `This feature will return once AI is back online.`

### UX Requirement

Do not show the same fallback treatment for both cases.

The UI must clearly differentiate:

- intentionally disabled by configuration
- temporarily unavailable due to runtime failure

The default UX for alpha is localized messaging on the affected AI surface. A persistent global AI-off banner is not required unless a specific admin or diagnostics screen is introduced later.

## Backend Rules

### Capability Gate

All AI-backed controller actions and coordinator calls must consult a centralized capability gate before execution.

The gate must answer:

- is this feature AI-required?
- is AI currently enabled?
- is the AI engine healthy?
- does this feature have a manual fallback?

### Response Semantics

AI-only endpoints must return structured unavailability responses instead of synthetic success payloads.

Recommended shape:

```json
{
  "status": "unavailable",
  "reason": "ai_disabled",
  "message": "Vendor ranking is unavailable while AI is disabled."
}
```

If the engine is down rather than disabled:

```json
{
  "status": "unavailable",
  "reason": "ai_unavailable",
  "message": "Vendor ranking is temporarily unavailable because the AI engine is degraded."
}
```

The backend should expose a dedicated AI status payload for the WEB app, either through a new endpoint such as `GET /api/v1/ai/status` or through an equivalent shared application contract. The payload must include the current mode, health, and a human-readable reason.

### No Fake AI Outputs

When AI is off:

- do not fabricate ranking results
- do not fabricate recommendations
- do not fabricate AI insight summaries
- do not replace failures with placeholder AI values in live mode

## Logging and Observability

Every AI-capable request should log:

- `feature_key`
- `ai_mode`
- `ai_state`
- `tenant_id`
- `rfq_id` when applicable
- `outcome`

Recommended `outcome` values:

- `executed`
- `hidden`
- `unavailable`
- `degraded`

The logs must also preserve the distinction between:

- config-disabled AI
- runtime AI failure

## Data Flow

1. The UI requests a screen or action.
2. The UI consults the capability registry for that feature.
3. The backend capability gate checks the global AI mode and AI health.
4. If the feature has a manual fallback, AI-only controls are hidden and the manual path remains active.
5. If the feature has no fallback, the user sees an unavailable message.
6. If the feature is AI-enabled, the normal AI-backed workflow runs.

## Testing Strategy

### Required Coverage

1. Global AI off behavior.
2. AI available behavior.
3. Runtime degraded behavior.
4. Manual fallback screens hide AI-only controls.
5. AI-only screens show unavailable messaging.
6. No synthetic AI outputs are returned when AI is off.

### Suggested Test Matrix

- Quote ingestion upload with AI off
- Quote normalization manual mapping with AI off
- Comparison preview/freeze with AI off
- Award creation with AI off
- Vendor recommendations with AI off
- RFQ insights sidebar with AI off
- Recommendation endpoint behavior with AI off

## Open Questions

Resolved for alpha:

- global AI mode
- localized messaging on affected surfaces
- vendor recommendation and generated insights are AI-only
- quote ingestion, normalization, comparison, and award remain usable without AI through their existing manual paths
