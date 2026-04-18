# RFQ Workflows

This file covers the operational business flow for the RFQ domain. It does not describe the RFQ state model itself; that belongs in [lifecycle.md](./lifecycle.md).

## Workflow Access

- The RFQ list and create screen live at `/rfqs` and `/rfqs/new`.
- The RFQ workspace uses the `/rfqs/[rfqId]/*` route tree.
- The RFQ overview page is the central summary surface for the record.
- The domain is tenant-scoped and respects project ACL visibility when a project is attached.

## Workflow 001 - Create a New RFQ Draft

### Trigger

A buyer starts a new requisition from the RFQ list.

### Steps

1. The WEB create form validates the RFQ title, deadline, and optional scheduling or pricing fields.
2. The client sends the RFQ create payload to the API.
3. The API validates the request, resolves the authenticated tenant and user, and creates a draft RFQ row.
4. The controller assigns the RFQ number and persists the record.
5. The WEB app routes the user into the new RFQ workspace.

### Outputs

- Draft RFQ record created
- RFQ number assigned
- Workspace opens on the new record

### Failure Handling

- If validation fails, the request stays on the client and the form displays field errors.
- If the API rejects the payload, the WEB app shows a request-level error.
- If the create flow cannot persist the RFQ, the API path fails loudly rather than inventing a placeholder record.

### Domains Involved

- RFQ
- Auth

## Workflow 002 - Edit, Save, and Transition an RFQ

### Trigger

A buyer updates the requisition or changes its lifecycle state.

### Steps

1. The workspace loads the RFQ detail or overview payload.
2. The user edits draft fields or scheduling data through the draft/save form.
3. The API validates the payload and persists the editable fields.
4. When the user transitions status, the controller hands the request to the sourcing lifecycle coordinator.
5. The coordinator applies the real transition policy and returns the updated RFQ record.

### Outputs

- Updated RFQ record
- Updated RFQ status
- Refreshed summary and activity data

### Failure Handling

- Save only succeeds on a tenant-scoped RFQ.
- Invalid transition requests return `422`.
- Lifecycle precondition failures return `404` when the RFQ cannot be resolved or `422` when the transition is not allowed.
- Project-linked RFQs fail closed if project ACL visibility is missing.

### Domains Involved

- RFQ
- Projects
- Auth

## Workflow 003 - Duplicate an RFQ

### Trigger

A buyer needs a new RFQ based on an existing requisition.

### Steps

1. The user requests a duplicate from a tenant-scoped source RFQ.
2. The API validates the source record and executes the lifecycle coordinator.
3. The coordinator creates a new draft RFQ with copied root fields and a new RFQ number.
4. The coordinator-owned copy flow lets the child line-item copy happen within the larger orchestration boundary.
5. The new RFQ is returned to the client and the workspace can continue from the copied draft.

### Outputs

- New draft RFQ created
- Copied root fields preserved
- Child line items copied by the orchestration flow

### Failure Handling

- If the source RFQ does not exist in the current tenant, the API returns `404`.
- Duplicate-number collisions are retried and ultimately mapped to a domain exception.
- If the copy flow fails after the source RFQ is resolved, the idempotency flow fails loudly rather than creating a synthetic clone.

### Domains Involved

- RFQ
- Auth
- Idempotency

## Workflow 004 - Read RFQ Workspace Summary

### Trigger

A buyer opens the RFQ overview or workspace summary cards.

### Steps

1. The overview endpoint loads the RFQ, the latest comparison run, and tenant-scoped approval counts.
2. The controller counts quote-submission states to compute normalization progress.
3. The controller counts approval states to compute the approval summary.
4. The WEB overview hook normalizes the payload and optionally overlays the activity feed endpoint.
5. The workspace renders the summary cards, comparison snapshot, approval status, and recent activity.

### Outputs

- Summary cards for quote, comparison, and approval progress
- Activity feed rows
- Workspace links into child RFQ sections

### Failure Handling

- If activity or overview data is missing, the hook stops short of fabricating counts.
- If the RFQ cannot be resolved for the tenant, the API returns `404`.
- If the linked project is not visible, the summary does not expose the project relationship.

### Domains Involved

- RFQ
- Quote Intake
- Comparison
- Approvals
- Projects
