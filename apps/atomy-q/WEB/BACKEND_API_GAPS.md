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

## RFQ Workspace Overview (`GET /api/v1/rfqs/:rfqId/overview`)

Workspace KPIs and nested `rfq` mirror list/detail fields (`vendors_count` / `quotes_count`, `estValue`, `savings`, etc.).

### Response fields (needed for Active Record Menu snippet + KPI scorecards)

- [x] `id`, `title`, `status` (under `data.rfq`)
- [x] `vendorsCount`, `quotesCount`, `estValue`, `savings` (under `data.rfq` as snake_case + `estValue` alias)
- [x] Blueprint aliases on **`data`** (alongside nested `normalization` / `approvals`):
  - `expectedQuotes` (number) — same as `expected_quotes`
  - `normalizationProgress` (0–100) — same as `normalization.progress_pct`
  - `latestComparisonRun` — `{ id, mode: preview|final, status }` or `null`
  - `approvalStatus` — `{ overall, pending_count, approved_count, rejected_count }`

### Activity timeline

Screen Blueprint shows a 6-entry activity feed on Overview.

- [x] **A**: `activity` is included inside **`GET /api/v1/rfqs/:rfqId/overview`** (`data.activity`, newest first, capped at 20).
- [x] **B**: **`GET /api/v1/rfqs/:rfqId/activity?limit=20`** (optional `limit` 1–50) returns `{ data, meta: { limit, rfq_id } }` for smaller payloads when the UI loads activity separately.

Implemented event shape (WEB `use-rfq-overview` today):

- `id` (string)
- `timestamp` (ISO string)
- `type` (string)
- `actor` (string display name)
- `action` (string — human-readable detail line)

## Quote Lifecycle Live Slice

The following backend support is now live and consumed by the quote-lifecycle WEB screens in non-mock mode:

- [x] `GET /api/v1/quote-submissions` and `GET /api/v1/quote-submissions/{id}` return live quote rows and processing metadata.
- [x] `POST /api/v1/quote-submissions/{id}/reparse` resets processing state and requeues extraction.
- [x] `GET /api/v1/normalization/{rfqId}/source-lines`, `GET /api/v1/normalization/{rfqId}/conflicts`, and the mapping/conflict mutation routes return live tenant-scoped normalization data.
- [x] `POST /api/v1/comparison-runs/final` freezes live comparison snapshots.
- [x] `GET /api/v1/comparison-runs` returns the live comparison run list for the RFQ workspace.
- [x] `GET /api/v1/awards` and award mutations (`POST /{id}/signoff`, `POST /{id}/debrief/{vendorId}`) now operate on live award records.
- [x] `GET /api/v1/approvals` returns the live pending approval queue that the RFQ-scoped approvals page now consumes.

Remaining gaps for the quote lifecycle slice are now mostly UX polish and future workflow extensions, not core transport/API support.
