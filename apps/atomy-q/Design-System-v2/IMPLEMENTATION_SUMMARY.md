# Design-System-v2 vs Blueprint v2 Gap Analysis

Date: 2026-03-10
Scope: `apps/atomy-q/Design-System-v2`
Reference: `apps/atomy-q/QUOTE_COMPARISON_FRONTEND_BLUEPRINT_v2.md`

## Summary

The current Design-System-v2 includes strong base primitives and many domain UI elements, but it is not yet complete against Blueprint v2 component contracts and screen-level requirements.

Primary risk is not visual styling; it is contract coverage and behavior orchestration (navigation accordion control, slideover stacking, record header contract, workspace breadcrumb contract, and child-workspace specialized components).

## Existing Coverage (Implemented)

- Foundations/tokens, buttons, badges, avatars, inputs, tabs, cards.
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
