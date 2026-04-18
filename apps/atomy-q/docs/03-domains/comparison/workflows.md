# Comparison Workflows

This file covers the operational business flow for the Comparison domain. It does not describe the comparison run state model itself; that belongs in [lifecycle.md](./lifecycle.md).

## Workflow Access

- The comparison list is available at `/comparison-runs`.
- The preview and final flows are created through API actions and surfaced in the RFQ workspace.
- The matrix and readiness views are read surfaces for a persisted comparison run.

## Workflow 001 - Create a Preview Comparison Run

### Trigger

A buyer opens the comparison workspace before freezing the RFQ.

### Steps

1. The client sends a preview request for a tenant-scoped RFQ.
2. The API verifies the RFQ exists and has quote submissions with source documents.
3. The comparison coordinator generates a preview result.
4. The controller persists a preview `ComparisonRun` row.
5. The WEB app renders the preview matrix and readiness data.

### Outputs

- Preview comparison run created
- Preview matrix and readiness payload stored
- Workspace can render preview evidence

### Failure Handling

- If the RFQ does not exist, the API returns `404`.
- If no source-document-backed quote submissions exist, the API returns `422`.
- If the comparison engine reports not-ready or other workflow errors, the API returns `422` with a live error payload.

### Domains Involved

- Comparison
- RFQ
- Quote Intake

## Workflow 002 - Freeze the Final Comparison

### Trigger

A buyer freezes the comparison after all quotes are ready.

### Steps

1. The API resolves the RFQ and loads all quote submissions for the tenant.
2. The controller verifies that enough submissions are ready.
3. The controller checks every quote submission for blocking normalization issues.
4. The snapshot service freezes the comparison payload.
5. The comparison coordinator computes the final matrix and scoring payload.
6. The controller persists a final `ComparisonRun` row with the frozen snapshot.
7. The decision-trail writer records the snapshot-freeze event.

### Outputs

- Final comparison run created
- Frozen comparison snapshot stored
- Decision trail updated
- Comparison ready for award and approval handoff

### Failure Handling

- If the RFQ is missing, the API returns `404`.
- If not enough ready submissions exist, the API returns `422`.
- If any submission still has blocking issues, the API returns `422`.
- If any ready submission lacks a source document, the API returns `422`.

### Domains Involved

- Comparison
- RFQ
- Quote Intake
- Awards
- Approvals

## Workflow 003 - Inspect Matrix or Readiness

### Trigger

A buyer opens the comparison run detail, matrix, or readiness view.

### Steps

1. The UI loads the comparison run list or detail page.
2. The API returns the run summary, matrix, or readiness payload for the tenant.
3. The WEB comparison hook normalizes the payload and validates the run belongs to the current RFQ when an RFQ is supplied.
4. The UI renders the matrix, blockers, warnings, and comparison status.

### Outputs

- Persisted comparison run read in the UI
- Matrix and readiness views rendered from live data

### Failure Handling

- If the run does not belong to the current tenant, the API returns `404`.
- If the live payload is malformed, the hook fails loudly instead of fabricating a matrix.

### Domains Involved

- Comparison
- RFQ
- Quote Intake

## Workflow 004 - Deferred Control Surfaces

### Trigger

A buyer tries to change a control that is intentionally not part of alpha.

### Steps

1. The user requests scoring-model, lock, or unlock behavior.
2. The controller checks that the run exists for the tenant.
3. The controller returns a deferred `422` response that makes it clear the control is not enabled in alpha.

### Outputs

- Clear deferred-control response
- No fake state mutation

### Failure Handling

- Missing comparison runs still return `404`.
- Deferred controls never mutate the run in alpha.

### Domains Involved

- Comparison
