# RFQ Flow Seed Script – Design

**Date:** 2026-03-13

## Goal

A terminal script in the API app that (1) seeds real-world-like RFQ data and (2) exercises the full API flow by replaying a realistic sequence of HTTP calls (create RFQ → add line items → publish → invite vendors → submit quotes → intake → normalization → comparison → approvals → award), with script-driven “user” decisions where the API expects intervention.

## Architecture

- **Artisan command** `atomy:seed-rfq-flow` in the API app.
- **HTTP client**: The command calls the same app’s API over HTTP (e.g. `APP_URL`) so that (1) persistence goes through the real API and (2) the flow doubles as an API smoke test.
- **Auth**: Command logs in via `POST /api/v1/auth/login` (email, password from env or prompts; tenant resolved server-side from the user row), then sends `Authorization: Bearer <token>` on every request.
- **Flow driver**: Sequential steps; each step is one or more API calls. For steps that require “user” input (e.g. resolve conflict, approve), the script calls the corresponding API with a default/mock choice.

## Flow Steps (by target status)

1. **draft**  
   - POST /rfqs → POST /rfqs/{id}/line-items (random 5–15 items) → optional PUT /rfqs/{id}/draft.

2. **published**  
   - Do draft steps → PATCH /rfqs/{id}/status { status: published } → POST /rfqs/{id}/invitations (random 2–6 vendors) → optionally POST invitations/{invId}/remind for some.

3. **closed**  
   - Do published steps → for a random subset of invitations, POST /quote-submissions/upload (rfq_id, vendor_id, vendor_name) → PATCH /quote-submissions/{id}/status { status: accepted } for each → GET/PUT normalization endpoints → POST /comparison-runs/preview and /final → GET/POST approvals as needed. Then PATCH /rfqs/{id}/status { status: closed }.

4. **awarded**  
   - Do closed steps → POST /approvals/{id}/approve (or bulk) → POST /awards → POST /awards/{id}/signoff → PATCH /rfqs/{id}/status { status: awarded }.

## Implementation Notes

- **RFQ count**: Prompt “How many RFQs?” (default 1). “Target status?” (draft | published | closed | awarded).
- **Vendors**: Use a fixed list of fake vendor names/emails (e.g. from DatabaseSeeder); generate vendor_id as new ULID per invitation.
- **Line items**: Random count in [5, 15]; description, quantity, uom, unit_price from a small set of realistic values.
- **Persistence**: Key write endpoints (RFQ store, line items, updateStatus, invitations, quote upload, quote status) are implemented so the script creates data only via the API.
- **Errors**: Command logs each request/response (or summary); on 4xx/5xx it reports and can stop or continue depending on option.

## Files

- **Command**: `apps/atomy-q/API/app/Console/Commands/SeedRfqFlowCommand.php`
- **Config**: Use existing `APP_URL`; optional `ATOMY_SEED_TENANT_ID`, `ATOMY_SEED_EMAIL`, `ATOMY_SEED_PASSWORD` for non-interactive runs.
- **API changes**: RfqController (store, storeLineItem, updateStatus, lineItems, updateLineItem, destroyLineItem), VendorInvitationController (store), QuoteSubmissionController (upload, updateStatus) persist to DB.
