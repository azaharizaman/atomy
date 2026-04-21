# RFQ Line Items Inline Drawer Design

**Date:** 2026-04-19  
**Status:** Draft  
**Scope:** Atomy-Q frontend, RFQ line-items screen

## 1. Change Summary

The RFQ line-items screen currently shows line-item data, but it does not provide an obvious way to add new line items from the screen itself. This design adds a visible creation entry point and an inline drawer so users can create line items without leaving the RFQ context.

The change stays within the existing RFQ alpha flow:
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/page.tsx`
- `POST /api/v1/rfqs/{rfqId}/line-items`

## 2. Change Classification

**Category:** Corrective  
**Change class:** C2

Reasoning:
- This is a behavior/UI fix inside an already-supported alpha flow.
- It does not expand the product into a new workflow.
- It improves discoverability and completeness of the existing “add RFQ line items” step.

## 3. Goals

1. Make the add action visible on the RFQ line-items screen.
2. Keep the user on the same RFQ page while creating a line item.
3. Reuse the existing line-item create API.
4. Keep the interaction accessible and predictable.
5. Cover the new flow with focused tests.

## 4. Non-Goals

1. No separate line-item creation page.
2. No inline editing of existing line items in this change.
3. No line-item delete or reorder UI.
4. No section-heading creation flow.
5. No backend contract changes unless implementation discovers a missing field in the existing create endpoint.

## 5. Current State

The current line-items page:
- shows a table/grid of line items,
- shows an empty state when there are no rows,
- does not offer a create CTA,
- already has live create support in the API via `rfqStoreLineItem`.

The relevant current frontend pattern already exists elsewhere in the app:
- page-level action buttons via `PageHeader.actions`,
- empty-state action buttons via `EmptyState.action`,
- drawer-style overlays with backdrop close behavior in the tasks screen.

## 6. Proposed UX

### 6.1 Entry Points

Add a primary `Add line item` button in the line-items page header.

Mirror the same action in the empty state so first-time users do not need to hunt for the CTA.

The trigger should be available only when the RFQ is editable. For this screen, that means:
- show the CTA when `rfq.status === 'draft'`,
- hide or suppress the CTA when the RFQ is not draft, because the page already presents itself as read-only in that state.

### 6.2 Drawer Behavior

Clicking the CTA opens a right-side inline drawer anchored to the current screen.

Drawer behavior:
- width similar to the tasks drawer pattern,
- backdrop overlay closes the drawer on click,
- `Escape` closes the drawer,
- close button in the drawer header,
- focus returns to the trigger after close,
- form resets each time the drawer opens.

### 6.3 Form Fields

The drawer should collect the fields already supported by the live create endpoint:
- Description
- Quantity
- UOM
- Unit price
- Currency
- Specifications

The drawer should not introduce section-heading creation, because the live API does not currently accept a section row payload.

### 6.4 Submit Flow

Submitting the form should:
1. validate fields client-side,
2. call the existing create endpoint through a frontend mutation hook,
3. close the drawer on success,
4. refetch the line-items list so the new row appears immediately,
5. show an error in the drawer if the request fails.

### 6.5 Mock-Mode Behavior

The app already uses mock mode for seed-backed reads. This change should not pretend that mock data can be persisted.

Recommended behavior:
- the CTA remains visible so the screen still advertises the create affordance,
- the drawer can open in mock mode,
- the save action is blocked in mock mode with a clear inline message or disabled save state,
- the user is told to use live mode for persistence.

This matches the existing app pattern where write actions are live-API-backed and mock mode is explicitly called out.

## 7. Data Flow

### 7.1 Frontend State

The line-items page should own:
- the drawer open/close state,
- the currently selected RFQ id,
- the create form state,
- the submit pending/error state.

The drawer should be a focused component rather than extra inline logic in the page body.

### 7.2 API Mutation

The create mutation should use the generated API client symbol:
- `rfqStoreLineItem`

The mutation payload should be limited to the create endpoint contract. The server should continue to assign sort order.

### 7.3 Refresh Strategy

After a successful create:
- invalidate the line-items query for the current RFQ,
- or refetch it directly if the implementation uses local query handles,
- then close the drawer.

The page should not require a full navigation refresh.

## 8. File-Level Plan

### New files
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/line-item-drawer.tsx`
- `apps/atomy-q/WEB/src/hooks/use-create-rfq-line-item.ts`

### Existing files to update
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/page.tsx`
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/page.test.tsx`

### Optional documentation follow-up
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`

## 9. Error Handling

1. If the RFQ context fails to load, do not offer the create drawer.
2. If line items fail to load, keep the page’s existing error state intact.
3. If the create request fails, keep the drawer open and surface the error message inside the drawer.
4. If validation fails, show field-level feedback before the request is sent.
5. If mock mode blocks persistence, explain that the user needs live mode rather than silently discarding the action.

## 10. Accessibility

The drawer must remain keyboard usable:
- button trigger is focusable,
- backdrop close is keyboard reachable,
- `Escape` closes the drawer,
- save and cancel controls are reachable in tab order,
- the drawer title should be announced as a dialog-like surface if the implementation uses ARIA roles.

## 11. Testing Strategy

### Page tests
Add tests that verify:
- the `Add line item` CTA renders in the header for draft RFQs,
- the same CTA appears in the empty state,
- clicking the CTA opens the drawer,
- the drawer can be closed,
- a successful submit path calls the mutation and closes the drawer,
- a failed submit path keeps the drawer open and shows the error,
- non-draft RFQs do not show the create CTA.

### Hook tests
If a new mutation hook is created, add a focused unit test for:
- payload shaping,
- mock-mode blocking behavior,
- success and error handling.

### Regression guard
Preserve the current read-only presentation for non-draft RFQs.

## 12. Risks and Mitigations

### Risk: CTA implies editing where the RFQ is read-only
Mitigation: only show the CTA in draft mode.

### Risk: Mock mode appears writable when it is not
Mitigation: block persistence in mock mode and explain the limitation in the drawer.

### Risk: Drawer adds complexity to a simple creation flow
Mitigation: keep the drawer local to the line-items screen and avoid a new route.

### Risk: Section-heading rows may be assumed to be part of creation
Mitigation: keep the first version focused on plain line-item rows only.

## 13. Acceptance Criteria

1. The line-items screen shows a visible `Add line item` action in the normal page header.
2. The empty state also exposes the same add action.
3. Clicking the action opens an inline drawer on the current RFQ line-items screen.
4. The drawer submits to the existing line-item create API.
5. Successful saves close the drawer and refresh the displayed rows.
6. Validation and API errors are shown in the drawer.
7. Non-draft RFQs do not expose the create CTA.
8. Tests cover the CTA, drawer open/close behavior, and create submission path.

## 14. Spec Self-Review

Checked for:
- placeholder text: none,
- conflicting requirements: none,
- missing scope boundaries: none,
- ambiguous interaction target: resolved in favor of an inline drawer,
- backend scope drift: none.

