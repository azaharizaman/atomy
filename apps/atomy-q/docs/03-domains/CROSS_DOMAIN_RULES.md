# Cross-Domain Rules

These rules describe how one domain changes the behavior of another domain in the implemented Atomy-Q frontend.

For the route inventory, see [DOMAIN_MAP.md](./DOMAIN_MAP.md). For navigation structure details, see [navigation structure analysis](../../WEB/docs/NAV_STRUCTURE_ANALYSIS.md). For auth and tenant security context, see [auth and tenant security review](../../WEB/docs/SECURITY_REVIEW_AUTH_AND_TENANTS.md) and [security-baseline.md](../04-engineering/standards/security-baseline.md).

## Rule 001 - Session Loss Forces Reauth

If the auth store has no valid authenticated session:
- The dashboard shell stops rendering protected content
- `AuthProvider` tries refresh-token recovery first
- If recovery fails, the user is redirected to `/login`

## Rule 002 - Alpha Mode Hides Product Surface

If `NEXT_PUBLIC_ALPHA_MODE=true`:
- The dashboard nav only keeps the alpha-visible top-level entries
- `Documents`, `Reporting`, `Settings`, and `Approval Queue` disappear from the main nav
- The RFQ workspace hides alpha-deferred record links such as `Documents`, `Negotiations`, and `Risk & Compliance`
- `Documents`, `Reporting`, and `Settings` pages render the shared deferred screen instead of their normal content

## Rule 003 - Projects Feature Toggles Cross-Domain Links

If the backend says `projects=false`:
- The dashboard shell hides `Projects`
- The Projects pages stop querying and render nothing or an error state instead of pretending the feature exists
- The RFQ list removes the project filter and clears an active `projectId`
- The RFQ workspace stops linking the project name to `/projects/[projectId]`

## Rule 004 - Tasks Feature Toggles Cross-Domain Nav

If the backend says `tasks=false`:
- The dashboard shell hides `Task Inbox`
- The Tasks page stops querying and renders nothing instead of a fake inbox

## Rule 005 - RFQ Project Context Flows Into Projects

If an RFQ has a `projectId` and Projects are enabled:
- The RFQ workspace shows the project name as a link to the Project detail page
- The RFQ list can filter by project
- Project detail pages can show linked RFQs and tasks for that project

If `projectId` is missing or Projects are disabled:
- The workspace shows plain text or `Unassigned`
- No cross-domain project link is emitted

## Rule 006 - RFQ Approval Data Drives Workspace Badges

If the RFQ approval hook returns a pending count:
- The RFQ workspace shows that count on the `Approvals` item in the Active Record Menu
- The count is hidden when the value is missing or zero

If a user opens the global Approval Queue and selects a row:
- The app routes into the matching RFQ workspace record rather than staying in the queue

## Rule 007 - Approval Queue Owns Queue Filtering

If the user is working in `/approvals`:
- Queue filters stay in the Approval domain
- RFQ screens do not duplicate queue filters
- The RFQ workspace only receives the selected RFQ context

## Rule 008 - RFQ Workspace Owns Record Context

If the user is inside `/rfqs/[rfqId]/*`:
- The dashboard shell does not wrap the page again
- The Active Record Menu owns RFQ navigation
- Child records such as quote intake, comparison runs, approvals, and decision trail stay attached to the active RFQ instead of becoming top-level routes

## Rule 009 - Scaffolded RFQ Sections Stay Out of the Real Flow

If the app resolves `/rfqs/[rfqId]/[section]`:
- The stub route only provides navigation parity
- A dedicated workspace page should own the real behavior when one exists
- Shared section titles should stay aligned with the actual RFQ links in `active-record-menu.tsx`

## Rule 010 - Mock Data Never Bleeds Into Live Domains

If `NEXT_PUBLIC_USE_MOCKS=true`:
- Seed data can back list and detail hooks
- Mock-specific fallbacks are allowed only in local/demo mode

If `NEXT_PUBLIC_USE_MOCKS=false`:
- Live hooks must fail loudly on malformed or missing payloads
- Seed data must not replace live API failures for a production path

## Rule 011 - Settings Own Administration, Not RFQ Work

If the user is working in Settings:
- User and role management stays in the Settings domain
- Project ACL editing stays in the Projects domain
- RFQ pages should only link to settings when they need shared configuration, not duplicate admin flows

## Rule 012 - Feature Flags Guard Shared Data Fetches

If a feature-flagged domain is disabled:
- The shell hides the entry point first
- The route page should avoid fetching data it cannot render
- Any cross-link into that domain should be removed or downgraded to plain text

