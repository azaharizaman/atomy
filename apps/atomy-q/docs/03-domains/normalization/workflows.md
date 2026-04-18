# Normalization Workflows

This file covers the operational business flow for the Normalization domain. It does not describe state semantics; that belongs in [lifecycle.md](./lifecycle.md).

## Workflow Access

- All routes are tenant-scoped.
- Every RFQ-specific read first confirms that the RFQ exists in the current tenant.

## Workflow 001 - Inspect Source Lines

### Trigger

A user opens the normalization workspace for an RFQ.

### Steps

1. The API confirms the RFQ belongs to the current tenant.
2. The controller loads source lines for the RFQ’s quote submissions.
3. The response includes source description, quantities, prices, mapped RFQ line IDs, conflicts, and readiness metrics.

### Outputs

- Source-line list
- Normalized-item list
- Readiness metadata

### Failure Handling

- Missing RFQs return `404`.
- Tenant leakage is prevented by the RFQ-scoped lookup.

## Workflow 002 - Map Or Remap A Source Line

### Trigger

A user links a source line to an RFQ line item.

### Steps

1. The API validates the target RFQ line ID.
2. The controller confirms the source line belongs to the tenant.
3. The controller confirms the RFQ line belongs to the same RFQ.
4. The source line is updated with the new RFQ line ID.
5. The readiness service is re-evaluated for the related submission.

### Outputs

- Mapping response
- Updated readiness metadata

### Failure Handling

- Unknown source lines return `404`.
- Unknown RFQ lines return `404`.
- Cross-RFQ mapping attempts return `422`.

## Workflow 003 - Apply Or Revert A Manual Override

### Trigger

A user overrides a source line or removes an override.

### Steps

1. The API validates the override payload.
2. The controller updates the source-line `raw_data` override payload.
3. If the override includes a numeric unit price, the source-line unit price is updated too.
4. The readiness service is re-evaluated.
5. Reverting the override removes the override payload and returns `204`.

### Outputs

- Override acknowledgment
- Reverted override acknowledgment
- Updated readiness metadata

### Failure Handling

- Missing source lines return `404`.

## Workflow 004 - Resolve Conflicts

### Trigger

A user resolves a normalization conflict.

### Steps

1. The API validates the resolution payload.
2. The controller finds the conflict in the current tenant.
3. The conflict is marked resolved with timestamp and resolver metadata.
4. The related submission is re-evaluated for readiness.

### Outputs

- Conflict resolution payload
- Updated readiness metadata

### Failure Handling

- Missing conflicts return `404`.

## Workflow 005 - Lock Or Unlock Normalization

### Trigger

A user opens or closes a normalization work session for an RFQ.

### Steps

1. The API confirms the RFQ belongs to the current tenant.
2. The lock endpoint returns a `locked = true` acknowledgment.
3. The unlock endpoint returns a `locked = false` acknowledgment.

### Outputs

- Lock/unlock acknowledgment

### Failure Handling

- Missing RFQs return `404`.
- The lock state is not persisted as a durable aggregate yet.

## Domains Involved

- Normalization
- Quote Intake
- RFQ
- Comparison
