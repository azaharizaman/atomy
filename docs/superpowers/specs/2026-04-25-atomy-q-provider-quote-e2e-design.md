# Atomy-Q Provider Quote E2E Design

**Date:** 2026-04-25
**Status:** Approved design
**Scope:** Non-deterministic provider-backed quotation processing for alpha release, plus hybrid test strategy.

## Decision

Atomy-Q alpha must support provider-backed quote ingestion and normalization as the core path.

`AI_MODE=provider` is the production path. `AI_MODE=deterministic` remains useful for local diagnostics, emergency continuity, and narrow unit tests, but release evidence must not treat deterministic output as provider-backed success.

## Provider Path

Quote upload and reparse must invoke the selected provider when feature policy and endpoint health allow `quote_document_extraction`.

For OpenRouter document extraction, scanned quotation PDFs require:

- endpoint: `AI_DOCUMENT_ENDPOINT`
- token: `AI_DOCUMENT_AUTH_TOKEN` or `AI_DEFAULT_AUTH_TOKEN`
- model: `AI_DOCUMENT_MODEL_ID`
- request shape: chat-completions JSON
- PDF input: base64 `file_data`
- parser plugin: `file-parser`
- OCR engine: `mistral-ocr`

The API must map provider output into persisted normalization source lines and commercial-term fields without leaking provider-specific payload shape above the app adapter boundary.

## Manual Continuity

If provider extraction is unavailable or fails:

- quote upload still succeeds
- quote status becomes `needs_review`
- source-line workspace remains usable
- user can manually add, edit, delete, map, and override source lines
- user-facing errors stay capability-scoped and do not expose provider internals
- logs and decision trail keep provider failure/provenance detail for operators

## Hybrid Testing

Default development and CI should not hit real provider endpoints.

Test layers:

1. Unit and feature tests use fake provider transport responses shaped like OpenRouter output.
2. Local API e2e may use a fake provider endpoint for repeatable development.
3. Alpha release e2e is opt-in and must use real OpenRouter against `sample/**`.

Release e2e must prove:

- sample requisition metadata seeds an RFQ with matching RFQ lines
- vendor PDFs upload through quote intake
- provider extraction persists source lines
- normalization review shows extracted source lines
- comparison readiness can proceed once lines are mapped and blockers are resolved

## Sample Fixture Contract

Each requisition folder under `sample/` should contain one `metadata.json` and one or more quotation PDFs.

Example:

```text
sample/
  vehicle-service-rfq/
    metadata.json
    kuching-utama.pdf
    alternate-vendor.pdf
```

`metadata.json` describes the RFQ seed data and expected extraction anchors. The e2e suite should use it to create RFQ records, upload every `quotes[].file`, and assert minimum expected extracted values.

Required top-level fields:

- `requisition_id`
- `title`
- `buyer`
- `currency`
- `rfq_line_items`
- `quotes`

`rfq_line_items[]` defines target RFQ lines. `quotes[]` defines vendor quote PDFs and expected extraction anchors.

## Out Of Scope

- Always-on live provider tests in normal CI
- Multi-provider failover
- Per-tenant provider selection
- Manual vendor ranking as replacement for AI ranking

## Implementation Notes

Provider-specific OpenRouter payload construction belongs in the API adapter layer. Shared Layer 1 and Layer 2 contracts must stay provider-neutral.

The fake provider endpoint should return stable OpenRouter-shaped JSON, not app-internal source-line DTOs, so the app exercises the same mapper used by real provider responses.

## Acceptance Gates

- API provider-backed extraction test passes with mocked OpenRouter-shaped response.
- Upload/reparse manual-continuity tests still pass.
- Fake-provider e2e passes without network.
- Opt-in real-provider e2e passes against at least one `sample/*/metadata.json` requisition folder.
- Release checklist records real-provider evidence separately from deterministic evidence.
