# Awards Workflows

This file covers the operational business flow for the Awards domain. It does not describe award state semantics; that belongs in [lifecycle.md](./lifecycle.md).

## Workflow Access

- Award actions are tenant-scoped.
- The award create flow depends on finalized comparison evidence.

## Workflow 001 - Create An Award

### Trigger

A user selects a vendor from a finalized comparison and submits the award form.

### Steps

1. The API validates the RFQ, comparison run, vendor, amount, currency, and optional split details.
2. The controller confirms the RFQ exists in the tenant.
3. The controller confirms the vendor has a corresponding quote submission for that RFQ.
4. The controller confirms the comparison run belongs to the RFQ and is finalized.
5. The award row is created inside a transaction.
6. The decision trail records the award creation event.

### Outputs

- Award record in `pending` state
- Decision-trail event

### Failure Handling

- Missing RFQ, vendor, or comparison run returns `404`.
- A comparison run that is not finalized returns `422`.

## Workflow 002 - Adjust Split Details

### Trigger

A user updates the award split details.

### Steps

1. The API validates the split-details array.
2. The controller finds the award in the current tenant.
3. The award’s `split_details` payload is replaced and persisted.

### Outputs

- Updated award payload

### Failure Handling

- Missing awards return `404`.
- The split update does not change the award’s lifecycle state.

## Workflow 003 - Debrief A Vendor

### Trigger

A user sends a debrief message to a vendor after an award decision.

### Steps

1. The API validates the optional debrief message.
2. The controller finds the award and the vendor submission for the same RFQ.
3. The controller creates the debrief record in a transaction.
4. The decision trail records the debrief event when it is first created.
5. The response includes the vendor name, message, and debrief timestamp.

### Outputs

- Debrief record
- Decision-trail event

### Failure Handling

- Missing award or vendor returns `404`.
- Repeated debrief requests are idempotent through `firstOrCreate`.

## Workflow 004 - Protest And Resolve A Protest

### Trigger

A user opens a protest on an award or resolves an existing protest.

### Steps

1. Protest marks the award as protested and attaches a protest ID.
2. Resolve checks that the protest ID matches the open protest marker.
3. Resolving a protest clears the protest marker and restores the award state based on whether signoff already happened.

### Outputs

- Protested award payload
- Resolved award payload

### Failure Handling

- Missing awards return `404`.
- Resolving the wrong protest ID returns `404`.

## Workflow 005 - Sign Off The Award

### Trigger

A user signs off the award.

### Steps

1. The controller locks the award row in a transaction.
2. The award is marked `signed_off`.
3. The signoff timestamp and signer are stored.
4. The decision trail records the signoff event.

### Outputs

- Signed-off award payload
- Decision-trail event

### Failure Handling

- Missing awards return `404`.
- Already signed-off awards are returned as-is.

## Domains Involved

- Awards
- RFQ
- Comparison
- Vendors
- Quote Intake
