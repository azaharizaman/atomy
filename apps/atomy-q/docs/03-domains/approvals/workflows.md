# Approvals Workflows

This file covers the operational business flow for the Approvals domain. It does not describe the approval status model itself; that belongs in [lifecycle.md](./lifecycle.md).

## Workflow Access

- The global approval queue is available from the authenticated dashboard at `/approvals`.
- RFQ-scoped approval access is available from `/rfqs/[rfqId]/approvals`.
- The detail page for a specific approval can be reached from either entry point.
- The route surface is tenant-scoped; approvals outside the current tenant are treated as not found.

## Workflow 001 - Review a Pending Approval From the Global Queue

### Trigger

A buyer opens the global approval queue to process pending work.

### Steps

1. The WEB app requests the paginated approval list through `useApprovalsList`.
2. The API filters approvals by tenant and any requested filters such as status, type, RFQ, or priority.
3. The queue page renders the approval rows, summary text, assignee, SLA badge, and RFQ link.
4. The user opens an approval row to view the detail screen.
5. The detail page loads the approval, the linked RFQ, and the linked comparison run when present.
6. If the approval is still pending, the user may enter an optional reason and approve or reject the item.
7. The API persists the decision, sets the approver metadata, and returns the updated status.
8. The WEB app refreshes the view and routes the user back to the queue.

### Outputs

- Queue list rendered for the current tenant
- Approval detail view loaded with RFQ context
- Approved or rejected approval row persisted in storage
- Updated queue state after the mutation

### Failure Handling

- If the queue request fails or returns malformed data, the WEB hook surfaces an error instead of fabricating approval rows.
- If the approval id does not exist for the current tenant, the detail page shows a not-found state.
- If the approval is not linked to a finalized comparison run, the approve action is rejected with `422`.
- If the linked comparison snapshot is missing or the quote submissions are not ready, the approve action is rejected with `422`.
- If the API rejects the mutation, the WEB page shows a toast error and keeps the user on the detail screen.

### Domains Involved

- Approvals
- RFQ
- Comparison
- Auth
- Dashboard shell

## Workflow 002 - Review Approvals From an RFQ Workspace

### Trigger

A buyer opens the RFQ workspace and selects the approvals section for that requisition.

### Steps

1. The RFQ overview calculates pending, approved, and rejected counts from tenant-scoped approval rows.
2. The Active Record Menu surfaces the pending-count badge when the value is present.
3. The RFQ-scoped approvals page requests the same approval list with `rfq_id` and pending-only filtering.
4. The page narrows the list to the active RFQ and renders a compact queue.
5. Clicking a row redirects to the global approval detail route for the selected approval.

### Outputs

- RFQ-scoped approval queue
- Approval badge on the RFQ workspace when pending approvals exist
- Shortcut into the global approval detail screen

### Failure Handling

- If the RFQ has no approval rows, the section renders an empty state.
- If approval counts are unavailable, the RFQ overview falls back to the rest of the workspace data without inventing a count.
- If the approval row belongs to another RFQ or tenant, the downstream detail lookup resolves to not found.

### Domains Involved

- Approvals
- RFQ
- Comparison
- Auth

## Workflow 003 - Upstream Approval Creation

### Trigger

An upstream RFQ, comparison, or risk flow creates a new approval requirement.

### Steps

1. The upstream workflow persists an approval row with tenant, RFQ, requestor, and optional comparison-run linkage.
2. The approval starts in the pending state.
3. The RFQ overview and approval queue become the read surfaces for that row.
4. The buyer processes the item through Workflow 001 or Workflow 002.
5. The approval history table records the actions taken against the approval.

### Outputs

- Pending approval row available in the queue
- RFQ approval count visible in the workspace
- Audit record available for approval actions

### Failure Handling

- If the upstream flow does not persist the approval row, the queue never shows a synthetic placeholder.
- If audit history is not written, the queue can still render the approval, but the approval audit trail is incomplete.

### Domains Involved

- Approvals
- RFQ
- Comparison
- Risk and Compliance
