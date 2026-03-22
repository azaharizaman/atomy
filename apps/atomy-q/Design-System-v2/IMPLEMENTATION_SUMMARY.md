# Design-System-v2 vs Blueprint v2 Gap Analysis

Date: 2026-03-10
Scope: `apps/atomy-q/Design-System-v2`
Reference: `apps/atomy-q/QUOTE_COMPARISON_FRONTEND_BLUEPRINT_v2.md`

## Summary

The current Design-System-v2 includes strong base primitives and many domain UI elements, but it is not yet complete against Blueprint v2 component contracts and screen-level requirements.

Primary risk is not visual styling; it is contract coverage and behavior orchestration (navigation accordion control, slideover stacking, record header contract, workspace breadcrumb contract, and child-workspace specialized components).

## Existing Coverage (Implemented)

- Foundations/tokens, buttons, badges (including generic **`Tag`**, **`CornerLabel`**, and enhanced **`FieldHint`**), avatars, inputs, tabs, cards.
- Data table with sorting, selection, row expansion, and bulk actions.
- Slide-over with width variants (`sm|md|lg|xl`).
- Layout primitives (Default + Workspace), top bar, active record menu, breadcrumb, timeline.

## Missing Components Required for Blueprint v2 Completion

### P0 - Contract Blockers

1. `MainNav` (composite accordion controller)
   - Must enforce one-open-group-at-a-time across expandable sidebar groups.
2. `UserMenuDropdown`
   - Top bar avatar menu actions: User Settings, Notifications, Logout.
3. `NotificationCenter`
   - Bell-triggered panel/slide-over contract for notifications.
4. `WorkspaceBreadcrumbs` (contract-safe wrapper)
   - Segments with `{ label, path }` and enforced RFQs root exit behavior.
5. `RecordHeader`
   - Child-record detail header contract (`title`, `status`, `actions`, `metadata`).
6. `SlideOverStackManager`
   - Max stack depth 2 support for nested slide-overs.
7. `ActiveRecordMenuV2` API
   - Prop-driven `primaryTabs` and `childRecordLinks`; snippet expand/collapse behavior.

### P1 - Screen-Critical Building Blocks

8. Auth set
   - `SignInCard`, `InlineAuthError`, `SessionExpiryBanner`, `MfaPromptPanel`.
9. Create RFQ set
   - `Stepper`, `StickyActionBar`, `LineItemEditor`, `UploadDropzoneWithProgress`.
10. Quote Intake Detail set
   - `QuoteDetailActionBar`, `ValidationCallout`, `OverrideChip`, `RevertControl`.
11. Normalization set
   - `MappingGrid`, `ConversionBadge`, `ConflictIndicator`, `NormalizationLockBar`.
12. Comparison Matrix set
   - `ComparisonMatrixGrid`, `VendorSummaryHeader`, `DeltaBadge`, `RecommendationCard`, `ApprovalGateBanner`, `ReadinessBanner`.
13. Approvals set
   - `PriorityMarker`, `AssignmentControl`, `SnoozeControl`, `EvidenceTabsPanel`, `DecisionPanel`.
14. Award set
   - `SplitAllocationEditor`, `SignOffChecklist`, `DebriefStatusList`, `HandoffStatusTimeline`, `PayloadPreviewPanel`, `ProtestTimerBadge`.

### P2 - Extended Workspace Retained in Blueprint v2

15. Extended modules
   - `NegotiationWorkspace` blocks.
   - `RiskCompliancePanel` blocks.
   - `DecisionTrailLedger` blocks.
   - `DocumentsEvidenceVault` blocks.
   - `VendorProfileSlideOver`.
   - `ScoringModelBuilderShell`.
   - Notification center dedicated view.

## Recommended Implementation Order

1. Deliver all P0 contracts first (navigation, slideover stack, record header, workspace breadcrumbs).
2. Deliver reusable P1 blocks in this sequence:
   - Create RFQ + intake + normalization
   - Comparison matrix + recommendation and approval banners
   - Approval and award blocks
3. Add P2 extended modules.
4. Add route-level screen shells after component completion to validate all flows in blueprint route hierarchy.

## Acceptance Criteria for "Design-System-v2 Complete"

- All `Component Contracts (UI-level)` in blueprint are present as reusable DS components.
- Every top-13 screen in blueprint can be assembled without introducing new base components.
- Slide-over stack depth, navigation accordion behavior, and workspace breadcrumb semantics match blueprint rules.
- Showcase page is extended with representative examples for each newly introduced contract component.

## Update - P0 Delivery (Implemented)

Implemented in this iteration:

- `MainNav` composite component with single-group accordion behavior.
- `UserMenuDropdown` with required user actions.
- `NotificationCenter` right-edge slide-over.
- `WorkspaceBreadcrumbs` contract wrapper.
- `RecordHeader` contract component.
- `SlideOverStackManager` with max depth support and top-panel interaction isolation.
- `ActiveRecordMenu` API expansion for prop-driven tabs/child links and snippet collapse support.

Integration updates:

- `Layout.tsx` now uses `MainNav` in Default and Workspace shells.
- `TopBar.tsx` now wires `UserMenuDropdown` and `NotificationCenter`.
- `ShowcasePage.tsx` includes P0 contract demos and interactive verification surfaces.

Remaining work:

- P1 screen-critical blocks (auth/create/intake/normalization/matrix/approval/award).
- P2 retained modules (negotiation, risk/compliance, decision trail, vault, vendor profile, scoring builder).

## Update - P1 Delivery (Implemented)

Implemented reusable P1 component modules:

- `AuthComponents.tsx`: `SignInCard`, `InlineAuthError`, `SessionExpiryBanner`, `MfaPromptPanel`.
- `CreateRFQComponents.tsx`: `Stepper`, `StickyActionBar`, `LineItemEditor`, `UploadDropzoneWithProgress`.
- `QuoteIntakeComponents.tsx`: `QuoteDetailActionBar`, `ValidationCallout`, `OverrideChip`, `RevertControl`.
- `NormalizationComponents.tsx`: `MappingGrid`, `ConversionBadge`, `ConflictIndicator`, `NormalizationLockBar`.
- `ComparisonComponents.tsx`: `ComparisonMatrixGrid`, `VendorSummaryHeader`, `DeltaBadge`, `RecommendationCard`, `ApprovalGateBanner`, `ReadinessBanner`.
- `ApprovalComponents.tsx`: `PriorityMarker`, `AssignmentControl`, `SnoozeControl`, `EvidenceTabsPanel`, `DecisionPanel`.
- `AwardComponents.tsx`: `AwardDecisionSummary`, `SplitAllocationEditor`, `SignOffChecklist`, `DebriefStatusList`, `HandoffStatusTimeline`, `PayloadPreviewPanel`, `ProtestTimerBadge`.

Showcase integration:

- Added `P1 Workflow Blocks` section to `ShowcasePage.tsx` with interactive samples across all P1 modules.

## Cloud Environment Configuration Update

To preinstall this app's npm dependencies for cloud/devcontainer startup:

- Added `.devcontainer/post-create.sh` to install `apps/atomy-q/Design-System-v2` dependencies.
- Updated `.devcontainer/devcontainer.json` with:
  - `"postCreateCommand": "bash .devcontainer/post-create.sh"`

## Update - Table/Grid & Line-Item UX Refinements

Implemented additional UI/UX refinements requested after P1:

- Expanded row indentation alignment in `DataTable` via `expandedIndentColumns` so expanded content follows parent text hierarchy.
- Bulk actions in `DataTable` toolbar now use a safer dropdown (`Bulk Actions`) instead of exposed action buttons.
- Added `DataTable` variants support:
  - row grouping (`groupBy`, `renderGroupHeader`)
  - group summaries (`renderGroupSummary`, `showGroupSummary`)
  - table summary row (`renderTableSummary`, `showTableSummary`)
- Enhanced Data Display showcase:
  - title-aligned search and filter controls (dropdown/toggle/checkbox/date+numeric ranges)
  - table/grid view mode switch
  - configuration popup (cog) for columns visibility, records per page, group/table summaries
  - card-based Grid View rendering for table records
- Fixed checklist icon/text vertical alignment in `Alert` checklist items.
- Upgraded Create RFQ line items editor:
  - labeled table-like headers
  - locale-aware currency input group (prefix/suffix)
  - non-prominent remove control (ghost without hover emphasis)
  - sortable row handle with drag/drop reordering
  - heading/subheading grouping rows for line item sections

## Update - Horizontal process track & Stepper (2026-03-21)

- Added `horizontalProcessTrackLogic.ts` with pure helpers: `segmentToneAfterStep`, `computeTodayCursor`, `collectDateAnchors`.
- Added `HorizontalProcessTrack.tsx`: horizontal milestone rail (compact/detailed), `completeAppearance` (`success` | `accent`), semantic **issue** (amber) / **blocked** (red) nodes and segments, optional **Today** cursor when steps include `date` (ISO) and `showTodayCursor` is set; `pinTodayCursorToEnds` optional.
- `Stepper` in `CreateRFQComponents.tsx` now wraps `HorizontalProcessTrack` and keeps `steps` + `activeStepId`; extended optional `StepperStep` fields (`description`, `status`, `date`); optional `showProgressBar` (default true), `showTodayCursor`, `today`, `variant`.
- Showcase **Create RFQ** subsection: two extra cards demo detailed+issue and schedule+today cursor.
- Spec: `docs/superpowers/specs/2026-03-21-atomy-q-horizontal-process-track-design.md`.
- Parity: same files updated under `apps/atomy-q/Screen-Blueprint`.
- **Refinement:** Horizontal track nodes reuse exported `TIMELINE_DOT_PALETTE` / `timelineNodeSizeClass` / `timelineIconTextClass` from `Timeline.tsx`; connectors are `h-px` (1px, matching vertical `w-px` spine) and meet node centers via per-step left/right flex segments. Showcase demos live under **Timeline → Horizontal process track** after the vertical timeline.
