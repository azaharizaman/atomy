# Backend API gaps / suggestions (from WEB implementation)

This file records **missing or ambiguous backend support** discovered while implementing the WEB UI screens so the backend team can action them.

## RFQ List (`GET /api/v1/rfqs`)

### Query parameters (needed for Screen Blueprint parity)
- **Search**
  - Suggested: `q` (string)
- **Filtering**
  - Suggested: `status` (enum: `active|closed|awarded|archived|draft|pending`)
  - Suggested: `ownerId` (string) or `owner` (string)
  - Suggested: `category` (string)
- **Pagination**
  - Suggested: `page` (number) and `perPage` (number)
- **Sorting**
  - Suggested: `sort` (string) + `direction` (`asc|desc`) or a single `sortBy` value

### Response fields (needed for table + expanded row)
Each RFQ list item should include, at minimum:
- **Identity**: `id` (string, e.g. `RFQ-2401`)
- **Core**: `title` (string), `status` (string)
- **Owner**: `owner: { id, name }` (or `ownerName` if flat)
- **Dates**: `deadline` (ISO date or formatted date)
- **Metrics**:
  - `estValue` (string or numeric + currency)
  - `savings` (percentage string or numeric)
  - `vendorsCount` (number)
  - `quotesCount` (number)
- **Expanded row metadata**:
  - `category` (string)

If the canonical backend field names differ, please confirm the mapping so the generated OpenAPI client can type it.

## RFQ Workspace Overview (`GET /api/v1/rfqs/:id`)

### Response fields (needed for Active Record Menu snippet + KPI scorecards)
- `id`, `title`, `status`
- `vendorsCount`, `quotesCount`, `estValue`, `savings`
- Suggested additionally (Blueprint KPI wants these):
  - `expectedQuotes` (number)
  - `normalizationProgress` (0–100)
  - `latestComparisonRun` summary: `{ id, mode, status }`
  - `approvalStatus` summary

### Activity timeline
Screen Blueprint shows a 6-entry activity feed on Overview.
- **Suggestion A**: include `activity: ActivityEvent[]` inside `GET /rfqs/:id`
- **Suggestion B (preferred for payload size)**: add `GET /rfqs/:id/activity?limit=20`

Proposed `ActivityEvent` shape:
- `id` (string)
- `timestamp` (ISO string) + optional `relativeLabel`
- `actor: { id, name }` (or `actorName`)
- `type` (string)
- `detail` (string)

