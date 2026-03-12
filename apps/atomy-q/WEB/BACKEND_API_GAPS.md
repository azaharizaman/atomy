# Backend API gaps / suggestions (from WEB implementation)

This file records **missing or ambiguous backend support** discovered while implementing the WEB UI screens so the backend team can action them.

## RFQ List (`GET /api/v1/rfqs`)

### Query parameters (needed for Screen Blueprint parity)

- [x] **Search**
  - Implemented: `q` (string)
- [x] **Filtering**
  - Implemented: `status` (enum: `active|closed|awarded|archived|draft|pending`)
  - Implemented: `ownerId` (string) or `owner` (string, matches owner name/email)
  - Implemented: `category` (string)
- [x] **Pagination**
  - Implemented: `page` (number) and `perPage` (number); `per_page` also supported
- [x] **Sorting**
  - Implemented: `sort`/`sortBy` + `direction` (`asc|desc`) with whitelist

### Response fields (needed for table + expanded row)

Each RFQ list item should include, at minimum:

- [x] **Identity**: `id` (string, e.g. `RFQ-2401`)
- [x] **Core**: `title` (string), `status` (string)
- [x] **Owner**: `owner: { id, name }` (email also included)
- [x] **Dates**: `deadline` (ISO 8601 string). Note: validation on write endpoints is still pending.
- [x] **Metrics**:
  - `estValue` (string or numeric + currency)
  - `savings` (percentage string or numeric)
  - `vendorsCount` (number)
  - `quotesCount` (number)
- [x] **Expanded row metadata**:
  - `category` (string)

If the canonical backend field names differ, please confirm the mapping so the generated OpenAPI client can type it.

### Pagination metadata (list response)

The WEB list page currently uses `useRfqs`, which returns a plain array of `RfqListItem[]`. To support correct pagination (total count, total pages), the list response should either:

- Return a **wrapped shape** such as `{ data: RfqListItem[]; meta: { total: number; totalPages: number; page?: number; perPage?: number } }`, or
- Expose a **header or envelope** with total count so the client can compute total pages.

Status: **Implemented**. Response now includes `{ data, meta: { total, total_pages, current_page, per_page } }`.

### Sidebar status counts (optional)

Status: **Not implemented**. The Requisition sidebar could show per-status counts (e.g. Active: 12, Closed: 5) when a source is available — e.g. `GET /api/v1/rfqs/counts` or counts in the list meta. Until then, badges are omitted.

## RFQ Workspace Overview (`GET /api/v1/rfqs/:id`)

### Response fields (needed for Active Record Menu snippet + KPI scorecards)

- [x] `id`, `title`, `status`
- [x] `vendorsCount`, `quotesCount`, `estValue`, `savings`
- [ ] Suggested additionally (Blueprint KPI wants these):
  - `expectedQuotes` (number)
  - `normalizationProgress` (0–100)
  - `latestComparisonRun` summary: `{ id, mode, status }`
  - `approvalStatus` summary

### Activity timeline

Screen Blueprint shows a 6-entry activity feed on Overview.

- [ ] **Suggestion A**: include `activity: ActivityEvent[]` inside `GET /api/v1/rfqs/:id`
- [ ] **Suggestion B (preferred for payload size)**: add `GET /api/v1/rfqs/:id/activity?limit=20`

Proposed `ActivityEvent` shape:

- `id` (string)
- `timestamp` (ISO string) + optional `relativeLabel`
- `actor: { id, name }` (or `actorName`)
- `type` (string)
- `detail` (string)
