# Atomy-Q API Endpoints Reference

This document maps every required API endpoint to the UI screens, elements, and interactions they serve. Use this as the contract between the Atomy-Q frontend and its backend API.

**Base URL:** `/api/v1`
**Authentication:** Bearer token (JWT) in `Authorization` header. All endpoints except `/auth/*` require authentication.
**Tenant Context:** Tenant ID derived from the authenticated user's token. All data is tenant-scoped.

**Navigation Note:** Normalization is **not** in the main sidebar; it is reached contextually from Quote Intake ("Accept & Normalize") or from RFQ Detail. Quote Normalization endpoints (Section 8) serve the Normalization Workspace when accessed via those flows.

---

## 1. Authentication & Session

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| POST | `/auth/login` | SignIn | Sign In button | Authenticate with email/password, returns JWT + refresh token |
| POST | `/auth/sso` | SignIn | SSO Sign In button | Initiate SSO flow (SAML/OIDC redirect) |
| POST | `/auth/mfa/verify` | SignIn | Verify MFA modal | Validate MFA OTP code |
| POST | `/auth/forgot-password` | SignIn | Forgot Password modal | Send password reset email |
| POST | `/auth/refresh` | Global | Session management | Refresh expired JWT using refresh token |
| POST | `/auth/logout` | Layout | User menu > Sign Out | Invalidate session and tokens |
| POST | `/auth/device-trust` | SignIn | Remember this device checkbox | Register trusted device to skip MFA |

## 2. Dashboard

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/dashboard/kpis` | Dashboard | KPI cards | Active RFQs, pending approvals, total savings, cycle time |
| GET | `/dashboard/spend-trend` | Dashboard | Spend Trend chart | Monthly spend aggregation for chart |
| GET | `/dashboard/vendor-scores` | Dashboard | Vendor Scores panel | Top vendors with aggregate scores |
| GET | `/dashboard/recent-activity` | Dashboard | Recent Activity list | Latest RFQ activity feed |
| GET | `/dashboard/risk-alerts` | Dashboard | Attention Required panel | Risk item counts by severity |

## 3. RFQ Management

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/rfqs` | RFQ List | Compact data table with expandable rows | List RFQs with filters (status, owner, category, search, pagination). Compact table: RFQ title with owner avatar and name as subtitle in one column; expandable rows show category, deadline, vendors, quotes, est. value, savings. No hover tooltip. |
| POST | `/rfqs` | Create RFQ | Submit/Publish button | Create a new RFQ |
| GET | `/rfqs/:id` | RFQ Detail | Page load | Get single RFQ with full details |
| PUT | `/rfqs/:id` | RFQ Detail | Edit action | Update RFQ metadata |
| PATCH | `/rfqs/:id/status` | RFQ Detail | Status actions | Transition RFQ status through the shared lifecycle policy (`draft`, `published`, `closed`, `awarded`, `cancelled`) |
| POST | `/rfqs/:id/duplicate` | RFQ List, RFQ Detail | Duplicate action | Clone RFQ as a real persisted draft, copying RFQ core fields and line items |
| PUT | `/rfqs/:id/draft` | Create RFQ | Save Draft button | Persist allowed draft-editable RFQ fields for draft RFQs |
| POST | `/rfqs/bulk-action` | RFQ List | Bulk action toolbar | Bulk close or cancel selected RFQs with real affected counts |
| GET | `/rfqs/:id/line-items` | RFQ Detail, Create RFQ | Line items tab/section | List line items for an RFQ |
| POST | `/rfqs/:id/line-items` | Create RFQ | Line item editor | Add line item to RFQ |
| PUT | `/rfqs/:id/line-items/:itemId` | Create RFQ | Line item editor | Update a line item |
| DELETE | `/rfqs/:id/line-items/:itemId` | Create RFQ | Line item delete | Remove a line item |

## 4. RFQ Templates

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/rfq-templates` | RFQ Templates, Create RFQ Template Picker | Template list/picker | List templates with search/filter |
| POST | `/rfq-templates` | RFQ Templates | Create Template | Create new template |
| GET | `/rfq-templates/:id` | RFQ Templates | Template detail | Get template details |
| PUT | `/rfq-templates/:id` | RFQ Templates | Edit Template | Update template |
| PATCH | `/rfq-templates/:id/status` | RFQ Templates | Publish/Archive toggle | Change template status |
| POST | `/rfq-templates/:id/duplicate` | RFQ Templates | Duplicate Template | Clone template |
| POST | `/rfq-templates/:id/apply` | Create RFQ | Apply Template button | Pre-fill RFQ form from template |

## 5. Vendor Management

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/vendors` | Multiple screens | Vendor selectors/lists | List vendors with search/filter |
| GET | `/vendors/:id` | Vendor Profile | Page load | Get vendor profile with performance data |
| GET | `/vendors/:id/performance` | Vendor Profile | Performance tab | Vendor performance metrics over time |
| GET | `/vendors/:id/compliance` | Vendor Profile | Compliance tab | Due diligence and sanctions data |
| GET | `/vendors/:id/history` | Vendor Profile | History tab | Prior RFQ participation and award history |

## 6. Vendor Invitations

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/rfqs/:id/invitations` | RFQ Detail | Vendors tab | List vendor invitations for an RFQ |
| POST | `/rfqs/:id/invitations` | RFQ Detail | Send Invitations slide-over | Send invitations to vendors |
| POST | `/rfqs/:id/invitations/:invId/remind` | RFQ Detail | Send Reminder button | Persist reminder metadata and trigger the RFQ invitation reminder boundary |

## 7. Quote Intake

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/quote-submissions` | Quote Intake Inbox | Data table list | List submissions as data table with filters (status, RFQ, vendor). Columns: File name, Vendor, RFQ, Status (processing/parsed/accepted/rejected/error/pending_assignment), Parse confidence, Uploaded at. |
| POST | `/quote-submissions/upload` | Quote Intake Inbox | Upload & Parse slide-over | Upload and parse quote file(s) |
| GET | `/quote-submissions/:id` | Quote Intake Detail (separate screen at `/quote-intake/:id`) | Two-tab detail view | Get full submission detail. Tab 1 (Overview): document/preview reference, vendor details, parse confidence, validation result (quote-level errors/warnings). Tab 2 (Parsed line items): array of lines with id, description, quantity, uom, unitPrice, currency, confidence, rfqLineId, overridden, mappedToRfqLineId, validationWarning — supports expandable rows with manual override/revert. Line-level override/revert may call PUT/DELETE `/normalization/source-lines/:id/override` (Section 8) when the submission is accepted and in normalization state. Clicking a row in the Quote Intake list navigates to this detail screen. |
| PATCH | `/quote-submissions/:id/status` | Quote Intake Inbox | Accept/Reject buttons | Accept or reject a submission |
| POST | `/quote-submissions/:id/replace` | Quote Intake Inbox | Replace & Re-Parse slide-over | Replace document and re-parse |
| POST | `/quote-submissions/:id/reparse` | Quote Intake Inbox | Re-Parse button | Re-run extraction on existing file |
| POST | `/quote-submissions/:id/assign` | Quote Intake Inbox | Assign Document slide-over | Link unassigned document to RFQ/vendor |

## 8. Quote Normalization

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/normalization/:rfqId/source-lines` | Normalization Workspace | Source lines list | Get source lines for an RFQ's quotes |
| GET | `/normalization/:rfqId/normalized-items` | Normalization Workspace | Mapping grid | Get normalized items |
| PUT | `/normalization/source-lines/:id/mapping` | Normalization Workspace | Mapping dropdown | Map a source line to an RFQ line item |
| POST | `/normalization/:rfqId/bulk-mapping` | Normalization Workspace | Bulk Mapping slide-over | Apply mapping to multiple lines |
| PUT | `/normalization/source-lines/:id/override` | Normalization Workspace | Override slide-over | Manual normalization override |
| DELETE | `/normalization/source-lines/:id/override` | Normalization Workspace | Revert Override button | Remove manual override |
| GET | `/normalization/:rfqId/conflicts` | Normalization Workspace | Conflict queue | List unresolved conflicts |
| PUT | `/normalization/conflicts/:id/resolve` | Normalization Workspace | Resolve Conflict slide-over | Resolve a mapping conflict |
| POST | `/normalization/:rfqId/lock` | Normalization Workspace | Lock for Comparison button | Lock normalization for comparison run |
| POST | `/normalization/:rfqId/unlock` | Normalization Workspace | Unlock button | Unlock normalization |

## 9. Comparison Matrix

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/comparison-runs` | Comparison Matrix | Page load | List comparison runs |
| GET | `/comparison-runs/:id` | Comparison Matrix | Run detail | Get full matrix data for a run |
| POST | `/comparison-runs/preview` | Comparison Matrix | Preview Run button | Execute preview comparison (relaxed validation) |
| POST | `/comparison-runs/final` | Comparison Matrix | Final Run button | Execute final comparison (full validation) |
| GET | `/comparison-runs/:id/matrix` | Comparison Matrix | Matrix table | Get category-grouped line comparison data |
| GET | `/comparison-runs/:id/readiness` | Comparison Matrix | Readiness banner | Check comparison readiness |
| PATCH | `/comparison-runs/:id/scoring-model` | Comparison Matrix | Select Scoring Model | Change scoring model for a run |
| POST | `/comparison-runs/:id/lock` | Comparison Matrix | Lock Matrix button | Lock comparison run |
| POST | `/comparison-runs/:id/unlock` | Comparison Matrix | Unlock Matrix button | Unlock comparison run |

## 10. Scoring Models

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/scoring-models` | Scoring Model Builder | Model list | List scoring models |
| POST | `/scoring-models` | Scoring Model Builder | Create Model | Create new scoring model |
| GET | `/scoring-models/:id` | Scoring Model Builder | Model detail | Get model with criteria and weights |
| PUT | `/scoring-models/:id` | Scoring Model Builder | Save changes | Update model criteria/weights |
| POST | `/scoring-models/:id/publish` | Scoring Model Builder | Publish button | Publish model version |
| GET | `/scoring-models/:id/versions` | Scoring Model Builder | Version History slide-over | Get version history |
| PUT | `/scoring-models/:id/assignments` | Scoring Model Builder | Model Assignment slide-over | Assign model to categories/RFQs |
| POST | `/scoring-models/:id/preview` | Scoring Model Builder | Live preview | Preview vendor scoring with current weights |

## 11. Scoring Policies

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/scoring-policies` | Scoring Policies | Policy list | List scoring policies |
| POST | `/scoring-policies` | Scoring Policies | Create Policy | Create new policy |
| GET | `/scoring-policies/:id` | Scoring Policies | Policy detail | Get policy with dimensions and rules |
| PUT | `/scoring-policies/:id` | Scoring Policies | Edit Policy | Update policy |
| POST | `/scoring-policies/:id/publish` | Scoring Policies | Publish button | Publish policy version |
| PATCH | `/scoring-policies/:id/status` | Scoring Policies | Archive button | Archive policy |
| PUT | `/scoring-policies/:id/assignments` | Scoring Policies | Policy Assignment slide-over | Assign policy to categories |
| GET | `/scoring-policies/:id/versions` | Scoring Policies | Version History slide-over | Get version history with diffs |

## 12. Scenarios

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/scenarios?rfqId=:id` | Scenario Simulator | Scenario list | List scenarios for an RFQ |
| POST | `/scenarios` | Scenario Simulator | Save Scenario slide-over | Save a new scenario |
| PUT | `/scenarios/:id` | Scenario Simulator | Edit assumptions | Update scenario assumptions |
| DELETE | `/scenarios/:id` | Scenario Simulator | Delete scenario | Remove a scenario |
| POST | `/scenarios/compare` | Scenario Simulator | Scenario Diff slide-over | Compare two scenarios |

## 13. Recommendations

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/recommendations/:runId` | Recommendation | Page load | Get recommendation for a comparison run |
| GET | `/recommendations/:runId/mcda` | Recommendation | Why This slide-over | Get full MCDA breakdown |
| POST | `/recommendations/:runId/override` | Recommendation | Override slide-over | Override recommendation with justification |
| POST | `/recommendations/:runId/rerun` | Recommendation | Request Re-Run slide-over | Trigger new comparison with adjusted weights |

## 14. Risk & Compliance

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/risk-items?rfqId=:id` | Risk & Compliance | Risk panels | List risk items for an RFQ |
| POST | `/risk-items/:id/escalate` | Risk & Compliance | Escalation slide-over | Escalate a risk item |
| POST | `/risk-items/:id/exception` | Risk & Compliance | Policy Exception slide-over | Request policy exception |
| POST | `/vendors/:id/sanctions-screening` | Risk & Compliance | Screen Vendor button | Run sanctions/AML screening |
| GET | `/vendors/:id/sanctions-history` | Risk & Compliance | Screening History slide-over | Past screening results |
| GET | `/vendors/:id/due-diligence` | Risk & Compliance | Due Diligence checklist | Get due diligence checklist items |
| PATCH | `/vendors/:id/due-diligence/:itemId` | Risk & Compliance | Checklist item toggle | Update checklist item status |

## 15. Approvals

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/approvals` | Approval Queue | Queue table | List approvals with filters (type, status, priority) |
| GET | `/approvals/:id` | Approval Detail | Page load | Get approval with full context |
| POST | `/approvals/:id/approve` | Approval Detail | Approve button | Approve with mandatory reason |
| POST | `/approvals/:id/reject` | Approval Detail | Reject button | Reject with mandatory reason |
| POST | `/approvals/:id/return` | Approval Detail | Return for Revision button | Return with reason and instructions |
| POST | `/approvals/:id/reassign` | Approval Queue/Detail | Reassign slide-over | Reassign to eligible user |
| POST | `/approvals/:id/snooze` | Approval Queue | Snooze button | Snooze for specified duration |
| POST | `/approvals/:id/request-evidence` | Approval Queue/Detail | Request Evidence slide-over | Request additional evidence |
| POST | `/approvals/bulk-approve` | Approval Queue | Bulk Approve button | Approve multiple items with shared reason |
| POST | `/approvals/bulk-reject` | Approval Queue | Bulk Reject button | Reject multiple items with shared reason |
| POST | `/approvals/bulk-reassign` | Approval Queue | Bulk Reassign button | Reassign multiple items |
| GET | `/approvals/:id/history` | Approval Detail | Prior history section | Get decision history for an approval |

## 16. Negotiations

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/negotiations?rfqId=:id` | Negotiation Workspace | Round timeline | List negotiation rounds for an RFQ |
| POST | `/negotiations/rounds` | Negotiation Workspace | Launch Round slide-over | Start a new negotiation round |
| POST | `/negotiations/rounds/:id/counter-offer` | Negotiation Workspace | Counter Offer slide-over | Submit counter-offer |
| POST | `/negotiations/:rfqId/bafo` | Negotiation Workspace | Request BAFO slide-over | Send BAFO request to vendors |
| POST | `/negotiations/:rfqId/close` | Negotiation Workspace | Close Negotiation button | Close negotiations |

## 17. Award Decision

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/awards?rfqId=:id` | Award Decision | Page load | Get award decision for an RFQ |
| POST | `/awards` | Award Decision | Finalize Award button | Create award decision |
| PUT | `/awards/:id/split` | Award Decision | Split Award slide-over | Configure split award allocations |
| POST | `/awards/:id/debrief/:vendorId` | Award Decision | Send Debrief button | Send debrief to non-winning vendor |
| POST | `/awards/:id/protest` | Award Decision | Record Protest slide-over | Record vendor protest |
| PATCH | `/awards/:id/protest/:protestId/resolve` | Award Decision | Protest resolution | Resolve protest (uphold/re-evaluate/cancel) |
| POST | `/awards/:id/signoff` | Award Decision | Sign-off button | Add sign-off to award |

## 18. PO/Contract Handoff

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/handoffs` | PO/Contract Handoff | Handoff list | List handoff records |
| GET | `/handoffs/:id` | PO/Contract Handoff | Handoff detail | Get handoff with payload and status |
| POST | `/handoffs/:id/validate` | PO/Contract Handoff | Validate Payload button | Run schema validation on payload |
| POST | `/handoffs/:id/send` | PO/Contract Handoff | Send button | Initiate handoff to destination system |
| POST | `/handoffs/:id/retry` | PO/Contract Handoff | Retry button | Retry failed handoff |
| GET | `/handoffs/destinations` | PO/Contract Handoff | Destination selector | List available destination systems |

## 19. Decision Trail / Audit Ledger

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/decision-trail` | Decision Trail | Event ledger | List entries with filters (scope, type, date range) |
| GET | `/decision-trail/:id` | Decision Trail | Expanded event card | Get full event detail |
| POST | `/decision-trail/verify` | Decision Trail | Verify Integrity button | Run hash-chain verification |
| POST | `/decision-trail/export` | Decision Trail | Export slide-over | Generate trail export (PDF/CSV/JSON) |

## 20. Documents & Evidence Vault

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/documents` | Evidence Vault | Document list | List documents with filters (type, RFQ, vendor, tags) |
| GET | `/documents/:id` | Evidence Vault | Document metadata panel | Get document details |
| GET | `/documents/:id/download` | Evidence Vault | Download button | Download document file |
| GET | `/documents/:id/preview` | Evidence Vault | Preview action | Get document preview URL |
| GET | `/evidence-bundles` | Evidence Vault | Bundle list | List evidence bundles |
| POST | `/evidence-bundles` | Evidence Vault | Create Bundle slide-over | Create new evidence bundle |
| GET | `/evidence-bundles/:id` | Evidence Vault | View Bundle slide-over | Get bundle contents |
| POST | `/evidence-bundles/:id/add-document` | Evidence Vault | Add to Bundle dropdown | Add document to bundle |
| POST | `/evidence-bundles/:id/finalize` | Evidence Vault | Finalize button | Lock bundle with manifest |
| GET | `/evidence-bundles/:id/export` | Evidence Vault | Export Bundle button | Download bundle as ZIP |

## 21. Reports & Analytics

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/reports/kpis` | Reports & Analytics | KPI tiles | Get reporting KPIs |
| GET | `/reports/spend-trend` | Reports & Analytics | Spend trend chart | Time-series spend data |
| GET | `/reports/spend-by-category` | Reports & Analytics | Category breakdown | Spend by procurement category |
| POST | `/reports/export` | Reports & Analytics | Export Options slide-over | Generate report export |
| GET | `/reports/schedules` | Reports & Analytics | Schedule list | List report schedules |
| POST | `/reports/schedules` | Reports & Analytics | Schedule Report slide-over | Create report schedule |
| PUT | `/reports/schedules/:id` | Reports & Analytics | Edit Schedule | Update schedule |
| DELETE | `/reports/schedules/:id` | Reports & Analytics | Delete Schedule button | Remove schedule |
| POST | `/reports/schedules/:id/run-now` | Reports & Analytics | Run Now button | Trigger immediate report run |
| GET | `/reports/runs` | Reports & Analytics | Saved Runs tab | List generated report runs |
| GET | `/reports/runs/:id/download` | Reports & Analytics | Download link | Download generated report |

## 22. Integrations & API Monitor

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/integrations` | Integrations Config | Integration cards | List configured integrations |
| POST | `/integrations` | Integrations Config | Configure slide-over | Create new integration |
| GET | `/integrations/:id` | Integrations Config | Integration detail | Get integration config |
| PUT | `/integrations/:id` | Integrations Config | Edit button | Update integration config |
| PATCH | `/integrations/:id/status` | Integrations Config | Enable/Disable toggle | Enable or disable integration |
| DELETE | `/integrations/:id` | Integrations Config | Delete button | Remove integration |
| POST | `/integrations/:id/test` | Integrations Config | Test Connection button | Test connectivity and auth |
| GET | `/integrations/catalog` | Integrations Config | Connector catalog | List available connector types |
| GET | `/integrations/health` | Integration Monitor | Health cards | Get health status of all integrations |
| GET | `/integrations/jobs` | Integration Monitor | Job log table | List integration jobs with status |
| POST | `/integrations/jobs/:id/retry` | Integration Monitor | Retry button | Retry a failed job |

## 23. Users & Access Management

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/users` | User & Access | User directory table | List users with filters |
| POST | `/users/invite` | User & Access | Invite User slide-over | Send user invitation |
| GET | `/users/:id` | User & Access | User detail drawer | Get user details |
| PUT | `/users/:id` | User & Access | Edit user | Update user role/permissions |
| POST | `/users/:id/suspend` | User & Access | Suspend button | Suspend user account |
| POST | `/users/:id/reactivate` | User & Access | Reactivate button | Reactivate suspended user |
| GET | `/roles` | User & Access | Role selector | List available roles with permissions |
| GET | `/users/:id/delegation-rules` | User & Access | Delegation Rules slide-over | Get delegation config |
| PUT | `/users/:id/delegation-rules` | User & Access | Delegation Rules slide-over | Update delegation rules |
| PUT | `/users/:id/authority-limits` | User & Access | Authority Limits slide-over | Update approval authority limits |

## 24. Admin Settings

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/settings` | Admin Settings | Settings forms | Get all tenant settings |
| PUT | `/settings/general` | Admin Settings | General section Save | Update general settings |
| PUT | `/settings/workflow` | Admin Settings | Workflow section Save | Update workflow settings |
| PUT | `/settings/compliance` | Admin Settings | Compliance section Save | Update compliance settings |
| GET | `/feature-flags` | Admin Settings | Feature Flags section | List feature flags |
| PUT | `/feature-flags/:id` | Admin Settings | Toggle switch | Enable/disable feature flag |

## 25. Notifications

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/notifications` | Notification Center, Header bell | Notification feed | List notifications with filters |
| GET | `/notifications/unread-count` | Layout Header | Bell badge | Get unread notification count |
| PATCH | `/notifications/:id/read` | Notification Center | Click notification | Mark as read |
| POST | `/notifications/mark-all-read` | Notification Center | Mark all read button | Mark all as read |
| DELETE | `/notifications/clear-read` | Notification Center | Clear all read button | Remove read notifications |

## 26. Search

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/search?q=:query` | Layout Header | Global search input | Full-text search across RFQs, vendors, quotes, approvals |

## 27. User Settings (Account)

Current-user self-service only. All mutation endpoints operate on the authenticated user; no `userId` in path for profile, preferences, or notifications. Subscription and payment may be tenant-scoped depending on product model. Base URL and auth: `/api/v1`, Bearer JWT.

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/me` or `/account/profile` | User Settings | Profile tab load | Get current user profile (name, email, tenantId, emailVerified, metadata, timezone, locale) |
| PUT | `/me` or `/account/profile` | User Settings | Profile Save | Update current user profile (name, timezone, locale, avatar ref); email change may require verification flow |
| POST | `/account/change-password` | User Settings | Change password slide-over | Change password for current user (current password + new password) |
| GET | `/account/preferences` | User Settings | Preferences load | Get user-scoped preferences (e.g. locale, timezone, UI preferences) |
| PUT | `/account/preferences` | User Settings | Preferences Save | Update user-scoped preferences |
| GET | `/account/notifications` | User Settings | Notification settings tab | Get notification preferences (channels, categories, opt-in/out) |
| PUT | `/account/notifications` | User Settings | Notification settings Save | Update notification preferences |
| GET | `/account/subscription` | User Settings | Subscriptions tab | Get current subscription (plan, status, renewal date, usage if any) |
| GET | `/account/subscription/plans` | User Settings | Change plan dropdown | List available plans for upgrade/downgrade |
| POST | `/account/subscription/change` | User Settings | Change plan action | Request plan change (may redirect to billing portal) |
| GET | `/account/payment-methods` | User Settings | Payment tab | List saved payment methods (masked) for current user/tenant |
| POST | `/account/payment-methods` | User Settings | Add payment method | Add payment method (or redirect to billing portal) |
| DELETE | `/account/payment-methods/:id` | User Settings | Remove payment method | Remove a saved payment method |
| PATCH | `/account/payment-methods/:id/default` | User Settings | Set default method | Set default payment method |

---

## Endpoint Summary

| Category | Endpoint Count |
|----------|---------------|
| Authentication | 7 |
| Dashboard | 5 |
| RFQ Management | 12 |
| RFQ Templates | 7 |
| Vendor Management | 5 |
| Vendor Invitations | 3 |
| Quote Intake | 7 |
| Quote Normalization | 10 |
| Comparison Matrix | 9 |
| Scoring Models | 8 |
| Scoring Policies | 8 |
| Scenarios | 5 |
| Recommendations | 4 |
| Risk & Compliance | 7 |
| Approvals | 12 |
| Negotiations | 5 |
| Award Decision | 7 |
| PO/Contract Handoff | 6 |
| Decision Trail | 4 |
| Evidence Vault | 10 |
| Reports & Analytics | 11 |
| Integrations | 11 |
| Users & Access | 10 |
| Admin Settings | 6 |
| Notifications | 5 |
| Search | 1 |
| User Settings (Account) | 14 |
| Projects (planned, Phase 1) | 11 |
| Tasks (planned, Phase 1) | 9 |
| **Total** | **223** |

## 28. Projects (planned)

These endpoints support portfolio-style projects that group RFQs, track budget/health via `Nexus\Project` and `Nexus\ProjectManagementOperations`, and enforce project-scoped governance. **Phase 1:** Implemented behind feature flag `FEATURE_PROJECTS_ENABLED`; ACL and budget are stubbed until Phase 2/3.

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/projects` | Projects List | Projects table | **Planned.** List projects with filters (status, owner/PM, client, date range, search, pagination). Used by the Projects root page and project selector controls elsewhere. |
| POST | `/projects` | Create/Edit Project | Create Project modal | **Planned.** Create a new project with name, client, start/end dates, budget metadata, and project manager assignment (backed by `Nexus\Project`). |
| GET | `/projects/:id` | Project Detail | Page load | **Planned.** Get full project details including summary fields, key metrics, and links to RFQs, tasks, and health. |
| PUT | `/projects/:id` | Project Detail | Edit Project button | **Planned.** Update project metadata (name, description, dates, budget fields, PM) while enforcing `Nexus\Project` lifecycle rules. |
| PATCH | `/projects/:id/status` | Project Detail | Status dropdown | **Planned.** Transition project status (draft/active/on_hold/completed/cancelled) with completion rule enforced via incomplete tasks. |
| GET | `/projects/:id/health` | Project Detail | Health cards & charts | **Planned.** Get aggregated project health from `ProjectManagementOperationsCoordinator` (labor, expense, timeline, overall score). Powers health cards and charts on the Project Detail screen. |
| GET | `/projects/:id/rfqs` | Project Detail | RFQs tab | **Planned.** List RFQs associated to the project. Used by the Project Detail "RFQs" tab and project context picker. |
| GET | `/projects/:id/tasks` | Project Detail | Tasks tab | **Planned.** List tasks associated to the project (via `Nexus\Task`), including status, assignee, and due dates. |
| GET | `/projects/:id/budget` | Project Detail | Budget panel | **Planned.** Get budget vs. actuals summary for the project (labor and expense) using the ProjectManagementOperations Laravel adapter and app-specific budget implementations. |
| GET | `/projects/:id/acl` | Project Settings | Project Access tab load | **Planned.** Get project-level ACL, mapping users/groups to project roles (Owner, Manager, Contributor, Viewer, ClientStakeholder). Drives project-scoped governance UI. |
| PUT | `/projects/:id/acl` | Project Settings | Project Access Save | **Planned.** Update project ACL (add/remove members, change roles). When a project is present, this ACL can supersede general RFQ/task permissions. |

## 29. Tasks (planned)

These endpoints introduce user tasks backed by `Nexus\Task`, covering both project-centric work and RFQ-centric workflow items (approvals, follow-ups, etc.). **Phase 1:** Implemented behind feature flag `FEATURE_TASKS_ENABLED`; schedule preview is stubbed until Phase 3.

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| GET | `/tasks` | Task Inbox | Task list | **Planned.** List tasks filtered by assignee (default current user), status, priority, due date, project, and RFQ. Powers the global Task Inbox and small task widgets on dashboard. |
| POST | `/tasks` | Multiple (RFQ Detail, Project Detail, Task Inbox) | Create Task modal | **Planned.** Create a new task with title, description, assignee(s), priority, due date, optional `projectId`, optional `rfqId`, and metadata. |
| GET | `/tasks/:id` | Task Detail | Task drawer/modal | **Planned.** Get full task details including status history, assignees, links to parent project/RFQ, and dependency information. |
| PUT | `/tasks/:id` | Task Detail | Edit Task button | **Planned.** Update task metadata (title, description, assignee, due date, priority, parent associations) while preserving dependency rules from `Nexus\Task`. |
| PATCH | `/tasks/:id/status` | Task Detail, Task Inbox | Status toggle / quick actions | **Planned.** Update task status (e.g. todo/in_progress/blocked/done/cancelled) and emit any required decision trail/audit events. |
| GET | `/tasks/:id/dependencies` | Task Detail | Dependencies panel | **Planned.** Get dependency graph for a task (predecessors/successors) from `Nexus\Task` to render dependency chains. |
| PUT | `/tasks/:id/dependencies` | Task Detail | Edit Dependencies action | **Planned.** Update a task's predecessor/dependency list, enforcing acyclicity checks via `DependencyGraphInterface`. |
| POST | `/tasks/schedule/preview` | Project Detail, Gantt View | "Preview Schedule" button | **Planned.** Run `ScheduleCalculatorInterface` for a set of tasks (typically within a project) to compute early/late dates and slack for Gantt-style schedule visualization. |

## 30. Operational approvals (Nexus ApprovalOperations)

Generic operational approval API (`Nexus\ApprovalOperations`), **not** RFQ quote approval (see Section 15). Routes and resource names are distinct from `/approvals` RFQ flows.

| Method | Endpoint | Screen | Element/Interaction | Description |
|--------|----------|--------|---------------------|-------------|
| POST | `/operational-approvals/instances` | (optional future) | Start operational approval | Create instance from template + subject; policy + workflow correlation via orchestrator. |
| GET | `/operational-approvals/instances/:instanceId` | (optional future) | Instance detail | SLA snapshot stub; 404 if missing or wrong tenant. |
| POST | `/operational-approvals/instances/:instanceId/decisions` | (optional future) | Approve / Reject | Record decision; tenant-scoped. |
