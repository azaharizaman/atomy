# Alpha Task 4 Live-Mode Fail-Loud Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Eliminate live-mode seed fallback on the Atomy-Q alpha golden path by making golden-path WEB hooks fail loudly in live mode, keeping mock behavior explicit, and rendering recovery-oriented unavailable states on the affected RFQ screens.

**Architecture:** Keep the change narrow and honest. Golden-path React Query hooks remain responsible for live/mock branching and runtime payload validation, while RFQ dashboard pages remain responsible for distinguishing successful empty business states from unavailable live dependencies. Do not add a large abstraction layer; use focused hook hardening, explicit page guards, and a uniform `.live.test.ts` verification contract.

**Tech Stack:** Next.js 16, React 19, TanStack Query, Axios, Vitest, Testing Library.

---

## File Map

- `apps/atomy-q/WEB/src/hooks/use-rfq.ts`
  - Remove live seed fallback for RFQ detail reads.
- `apps/atomy-q/WEB/src/hooks/use-rfqs.ts`
  - Keep existing live/mock separation and extend live-test coverage to the full fail-loud contract.
- `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts`
  - Remove live seed fallback for invitation reads and keep seed access mock-only.
- `apps/atomy-q/WEB/src/hooks/use-quote-submissions.ts`
  - Remove live seed fallback for quote-submission reads and keep seed access mock-only.
- `apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.ts`
  - Preserve live fail-loud behavior and add dedicated live-test coverage.
- `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`
  - Stop treating `undefined` live responses as an empty success result.
- `apps/atomy-q/WEB/src/hooks/use-comparison-runs.ts`
  - Remove live seed fallback and extend the existing live test matrix.
- `apps/atomy-q/WEB/src/hooks/use-comparison-run.ts`
  - Remove fabricated live fallback runs and keep RFQ/run mismatch validation.
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts`
  - Remove fabricated live fallback matrices and fail loudly on missing/malformed live matrix payloads.
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.ts`
  - Preserve live fail-loud behavior and add the full live-test contract.
- `apps/atomy-q/WEB/src/hooks/use-award.ts`
  - Remove live seed fallback for award reads and preserve fail-loud award data dependencies.
- `apps/atomy-q/WEB/src/hooks/use-rfq.live.test.ts`
  - Extend to cover success, API error, `undefined`, and malformed payload behavior.
- `apps/atomy-q/WEB/src/hooks/use-rfqs.live.test.ts`
  - Keep as the reference list-hook live test and extend to the full four-case matrix.
- `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.live.test.ts`
  - Extend to the full four-case matrix.
- `apps/atomy-q/WEB/src/hooks/use-quote-submissions.live.test.ts`
  - Extend to the full four-case matrix.
- `apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.live.test.ts`
  - Add the dedicated live-test matrix for source lines.
- `apps/atomy-q/WEB/src/hooks/use-normalization-review.live.test.ts`
  - Add the dedicated live-test matrix for normalization conflict reads.
- `apps/atomy-q/WEB/src/hooks/use-comparison-runs.live.test.ts`
  - Extend to the full four-case matrix including `undefined`.
- `apps/atomy-q/WEB/src/hooks/use-comparison-run.live.test.ts`
  - Add the dedicated live-test matrix for comparison-run detail.
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.live.test.ts`
  - Add the dedicated live-test matrix for comparison matrix reads.
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.live.test.ts`
  - Add the dedicated live-test matrix for readiness reads.
- `apps/atomy-q/WEB/src/hooks/use-award.live.test.ts`
  - Extend to the full four-case matrix while preserving existing create/signoff mutation coverage.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`
  - Distinguish empty vendor roster from live vendor-roster failure.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx`
  - Add page-level unavailable-state coverage for the vendors page.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx`
  - Distinguish quote-intake emptiness from submissions/review load failure.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.test.tsx`
  - Extend to cover unavailable states.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx`
  - Render an explicit unavailable state when live source lines or conflicts fail.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx`
  - Extend to cover unavailable-state rendering.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.tsx`
  - Distinguish a true zero-run state from a failed live load.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx`
  - Extend to cover unavailable-state rendering.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
  - Keep the explicit unavailable-state pattern aligned with stricter upstream hooks.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`
  - Keep the explicit unavailable-state assertions aligned with stricter hook failures.
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
  - Record shipped live/mock fail-loud behavior.
- `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`
  - Link this implementation plan from Section 9, Task 4.
- `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
  - Record Task 4 verification evidence and blocker A3 status.

### Task 1: Lock The Live-Test Contract For RFQ, Vendor, And Quote Hooks

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-rfq.live.test.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-rfqs.live.test.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.live.test.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-quote-submissions.live.test.ts`

- [ ] **Step 1: Extend the existing live tests to the four-case contract before editing implementation**

Add the missing `undefined` and malformed-payload assertions so each file covers:

- API errors surface to consumers
- valid live data succeeds
- `undefined` fails loudly
- malformed payloads fail loudly

Example additions for `use-rfq.live.test.ts`:

```ts
it('throws when live RFQ detail resolves to undefined', async () => {
  getMock.mockResolvedValueOnce({ data: undefined });
  const { useRfq } = await import('@/hooks/use-rfq');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useRfq('rfq-1'), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isError).toBe(true));
  expect((result.current.error as Error).message).toContain('RFQ');
});

it('rejects malformed RFQ detail payloads without an id', async () => {
  getMock.mockResolvedValueOnce({
    data: { title: 'Malformed RFQ', status: 'active' },
  });
  const { useRfq } = await import('@/hooks/use-rfq');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useRfq('rfq-1'), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isError).toBe(true));
  expect((result.current.error as Error).message).toContain('missing id');
});
```

Example additions for `use-rfq-vendors.live.test.ts`:

```ts
it('throws when live invitation data resolves to undefined', async () => {
  getMock.mockResolvedValueOnce({ data: undefined });
  const { useRfqVendors } = await import('./use-rfq-vendors');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useRfqVendors('rfq-1'), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isError).toBe(true));
  expect((result.current.error as Error).message).toContain('vendor');
});

it('rejects malformed invitation rows', async () => {
  getMock.mockResolvedValueOnce({
    data: { data: ['bad-row'] },
  });
  const { useRfqVendors } = await import('./use-rfq-vendors');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useRfqVendors('rfq-1'), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isError).toBe(true));
  expect((result.current.error as Error).message).toContain('expected object');
});
```

- [ ] **Step 2: Run the focused live-hook tests to confirm they fail before implementation**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/hooks/use-rfq.live.test.ts \
  src/hooks/use-rfqs.live.test.ts \
  src/hooks/use-rfq-vendors.live.test.ts \
  src/hooks/use-quote-submissions.live.test.ts
```

Expected: FAIL because current live-mode implementations still allow seed fallback or permissive success on `undefined` for at least some of these hooks.

- [ ] **Step 3: Remove live fallback behavior from the RFQ, vendor, and quote hooks**

Update the hooks so seed data is available only in explicit mock mode and live `undefined` throws.

Example `use-rfq.ts` shape:

```ts
export function useRfq(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['rfqs', rfqId],
    queryFn: async () => {
      if (useMocks) {
        const { getSeedRfqDetail } = await import('@/data/seed');
        const detail = getSeedRfqDetail(rfqId);
        if (!detail) throw new Error(`Mock RFQ "${rfqId}" not found.`);
        return normalizeRfq(detail);
      }

      const data = await fetchLiveOrFail<{ data: RfqDetail }>(`/rfqs/${encodeURIComponent(rfqId)}`);
      if (data === undefined) {
        throw new Error(`RFQ "${rfqId}" is unavailable from the live API.`);
      }

      return normalizeRfq(data);
    },
    enabled: Boolean(rfqId),
  });
}
```

Example `use-quote-submissions.ts` shape:

```ts
export function useQuoteSubmissions(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['quote-submissions', 'list', rfqId],
    queryFn: async (): Promise<QuoteSubmissionRow[]> => {
      if (useMocks) {
        const { getSeedQuotesByRfqId } = await import('@/data/seed');
        return normalizeQuoteSubmissionRows({
          data: getSeedQuotesByRfqId(rfqId).map((row) => ({
            id: row.id,
            rfq_id: row.rfqId,
            vendor_id: row.vendorId,
            vendor_name: row.vendorName,
            file_name: row.fileName,
            status: row.status,
            confidence: row.confidence,
            submitted_at: row.uploadedAt,
            blocking_issue_count: 0,
            original_filename: row.fileName,
          })),
        });
      }

      const data = await fetchLiveOrFail<{ data: QuoteSubmissionRow[] }>('/quote-submissions', {
        params: { rfq_id: rfqId },
      });
      if (data === undefined) {
        throw new Error(`Quote submissions unavailable for RFQ "${rfqId}".`);
      }

      return normalizeQuoteSubmissionRows(data);
    },
    enabled: Boolean(rfqId),
  });
}
```

- [ ] **Step 4: Re-run the focused live-hook tests and confirm they pass**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/hooks/use-rfq.live.test.ts \
  src/hooks/use-rfqs.live.test.ts \
  src/hooks/use-rfq-vendors.live.test.ts \
  src/hooks/use-quote-submissions.live.test.ts
```

Expected: PASS with the full four-case contract covered.

- [ ] **Step 5: Commit the slice after implementation passes**

```bash
git add \
  apps/atomy-q/WEB/src/hooks/use-rfq.ts \
  apps/atomy-q/WEB/src/hooks/use-rfqs.live.test.ts \
  apps/atomy-q/WEB/src/hooks/use-rfq.live.test.ts \
  apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts \
  apps/atomy-q/WEB/src/hooks/use-rfq-vendors.live.test.ts \
  apps/atomy-q/WEB/src/hooks/use-quote-submissions.ts \
  apps/atomy-q/WEB/src/hooks/use-quote-submissions.live.test.ts
git commit -m "test(web): enforce fail-loud RFQ live hooks"
```

### Task 2: Add Missing Normalization Live Tests And Harden Normalization Hooks

**Files:**
- Add: `apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.live.test.ts`
- Add: `apps/atomy-q/WEB/src/hooks/use-normalization-review.live.test.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`

- [ ] **Step 1: Write the missing normalization live tests first**

Create dedicated `.live.test.ts` files that cover the mandatory four-case contract.

Suggested `use-normalization-source-lines.live.test.ts`:

```ts
it('surfaces API errors to consumers', async () => {
  getMock.mockRejectedValueOnce(new Error('Normalization source lines unavailable'));
  const { useNormalizationSourceLines } = await import('./use-normalization-source-lines');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useNormalizationSourceLines('rfq-1'), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isError).toBe(true));
  expect((result.current.error as Error).message).toContain('Normalization source lines unavailable');
});

it('throws when live source lines resolve to undefined', async () => {
  getMock.mockResolvedValueOnce({ data: undefined });
  const { useNormalizationSourceLines } = await import('./use-normalization-source-lines');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useNormalizationSourceLines('rfq-1'), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isError).toBe(true));
  expect((result.current.error as Error).message).toContain('rfq-1');
});
```

Suggested `use-normalization-review.live.test.ts`:

```ts
it('returns live conflict payload on success', async () => {
  getMock.mockResolvedValueOnce({
    data: {
      data: [
        {
          id: 'conflict-1',
          conflict_type: 'missing_price',
          resolution: null,
          normalization_source_line_id: 'line-1',
        },
      ],
      meta: {
        rfq_id: 'rfq-1',
        has_blocking_issues: true,
        blocking_issue_count: 1,
      },
    },
  });
  const { useNormalizationReview } = await import('./use-normalization-review');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useNormalizationReview('rfq-1'), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isSuccess).toBe(true));
  expect(result.current.conflicts).toHaveLength(1);
  expect(result.current.hasBlockingIssues).toBe(true);
});

it('throws when live conflict payload resolves to undefined', async () => {
  getMock.mockResolvedValueOnce({ data: undefined });
  const { useNormalizationReview } = await import('./use-normalization-review');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useNormalizationReview('rfq-1'), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isError).toBe(true));
  expect((result.current.error as Error).message).toContain('normalization');
});
```

- [ ] **Step 2: Run the normalization live tests to confirm they fail first**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/hooks/use-normalization-source-lines.live.test.ts \
  src/hooks/use-normalization-review.live.test.ts
```

Expected: FAIL because `use-normalization-review` currently treats `undefined` as `{ data: [], meta: {} }` and the new files do not exist yet.

- [ ] **Step 3: Harden normalization hook behavior**

Keep `use-normalization-source-lines` strict in live mode and update `use-normalization-review` to stop converting live `undefined` into success.

Suggested `use-normalization-review.ts` query function:

```ts
queryFn: async (): Promise<ConflictsResponse> => {
  const data = await fetchLiveOrFail<ConflictsResponse>(`/normalization/${encodeURIComponent(rfqId)}/conflicts`);

  if (data === undefined) {
    throw new Error(`Normalization review unavailable for RFQ "${rfqId}".`);
  }

  if (!Array.isArray(data.data)) {
    throw new Error('Invalid normalization review response: expected data array.');
  }

  return data;
},
```

- [ ] **Step 4: Re-run the normalization live tests and confirm they pass**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/hooks/use-normalization-source-lines.live.test.ts \
  src/hooks/use-normalization-review.live.test.ts
```

Expected: PASS.

- [ ] **Step 5: Commit the normalization slice**

```bash
git add \
  apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.ts \
  apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.live.test.ts \
  apps/atomy-q/WEB/src/hooks/use-normalization-review.ts \
  apps/atomy-q/WEB/src/hooks/use-normalization-review.live.test.ts
git commit -m "test(web): harden normalization live hooks"
```

### Task 3: Add Missing Comparison Live Tests And Harden Comparison Hooks

**Files:**
- Add: `apps/atomy-q/WEB/src/hooks/use-comparison-run.live.test.ts`
- Add: `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.live.test.ts`
- Add: `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.live.test.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-runs.live.test.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-runs.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-run.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.ts`

- [ ] **Step 1: Add the missing comparison live tests and extend the existing list test**

Add the missing files and update `use-comparison-runs.live.test.ts` so the list hook also covers `undefined`.

Suggested `use-comparison-run.live.test.ts`:

```ts
it('returns live comparison-run detail on success', async () => {
  getMock.mockResolvedValueOnce({
    data: {
      data: {
        id: 'run-1',
        rfq_id: 'rfq-1',
        name: 'Final comparison',
        status: 'frozen',
        is_preview: false,
        snapshot: {
          rfq_version: 2,
          normalized_lines: [],
          resolutions: [],
          currency_meta: {},
          vendors: [],
        },
      },
    },
  });
  const { useComparisonRun } = await import('./use-comparison-run');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useComparisonRun('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isSuccess).toBe(true));
  expect(result.current.data?.id).toBe('run-1');
});

it('throws when live comparison-run detail resolves to undefined', async () => {
  getMock.mockResolvedValueOnce({ data: undefined });
  const { useComparisonRun } = await import('./use-comparison-run');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useComparisonRun('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isError).toBe(true));
  expect((result.current.error as Error).message).toContain('Comparison run');
});
```

Suggested `use-comparison-run-matrix.live.test.ts`:

```ts
it('throws when live matrix resolves to undefined', async () => {
  getMock.mockResolvedValueOnce({ data: undefined });
  const { useComparisonRunMatrix } = await import('./use-comparison-run-matrix');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useComparisonRunMatrix('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isError).toBe(true));
  expect((result.current.error as Error).message).toContain('matrix');
});

it('rejects malformed live matrix clusters', async () => {
  getMock.mockResolvedValueOnce({
    data: {
      data: {
        id: 'run-1',
        clusters: [{ cluster_key: 'c-1', basis: 'taxonomy', offers: [] }],
      },
    },
  });
  const { useComparisonRunMatrix } = await import('./use-comparison-run-matrix');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useComparisonRunMatrix('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isError).toBe(true));
  expect((result.current.error as Error).message).toContain('statistics');
});
```

- [ ] **Step 2: Run the focused comparison live tests to confirm they fail first**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/hooks/use-comparison-runs.live.test.ts \
  src/hooks/use-comparison-run.live.test.ts \
  src/hooks/use-comparison-run-matrix.live.test.ts \
  src/hooks/use-comparison-run-readiness.live.test.ts
```

Expected: FAIL because the detail and matrix hooks still fabricate live fallback payloads and the new test files do not exist yet.

- [ ] **Step 3: Remove fabricated live fallback from the comparison hooks**

Update the hooks so only mock mode can build mock runs or matrices.

Suggested `use-comparison-run.ts` shape:

```ts
const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

queryFn: async (): Promise<ComparisonRunDetail> => {
  if (useMocks) {
    return buildMockComparisonRun(runId, options?.rfqId);
  }

  const data = await fetchLiveOrFail<{ data: ComparisonRunDetail }>(`/comparison-runs/${encodeURIComponent(runId)}`);
  if (data === undefined) {
    throw new Error(`Comparison run "${runId}" is unavailable from the live API.`);
  }

  return normalizeAndValidateComparisonRun(data, options?.rfqId);
},
```

Suggested `use-comparison-run-matrix.ts` shape:

```ts
const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

queryFn: async (): Promise<ComparisonRunMatrix> => {
  if (useMocks) {
    return buildMockMatrix(runId, options?.rfqId);
  }

  const data = await fetchLiveOrFail<{ data: ComparisonRunMatrix }>(`/comparison-runs/${encodeURIComponent(runId)}/matrix`);
  if (data === undefined) {
    throw new Error(`Comparison matrix unavailable for run "${runId}".`);
  }

  return normalizeComparisonRunMatrix(data);
},
```

- [ ] **Step 4: Re-run the focused comparison live tests and confirm they pass**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/hooks/use-comparison-runs.live.test.ts \
  src/hooks/use-comparison-run.live.test.ts \
  src/hooks/use-comparison-run-matrix.live.test.ts \
  src/hooks/use-comparison-run-readiness.live.test.ts
```

Expected: PASS.

- [ ] **Step 5: Commit the comparison slice**

```bash
git add \
  apps/atomy-q/WEB/src/hooks/use-comparison-runs.ts \
  apps/atomy-q/WEB/src/hooks/use-comparison-runs.live.test.ts \
  apps/atomy-q/WEB/src/hooks/use-comparison-run.ts \
  apps/atomy-q/WEB/src/hooks/use-comparison-run.live.test.ts \
  apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts \
  apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.live.test.ts \
  apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.ts \
  apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.live.test.ts
git commit -m "test(web): harden comparison live hooks"
```

### Task 4: Extend Award Live Tests And Remove Live Award Seed Fallback

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-award.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-award.live.test.ts`

- [ ] **Step 1: Extend the existing award live test to explicitly cover `undefined` and malformed list envelopes**

Keep the current create/signoff mutation coverage, but add the Task 4 fail-loud read contract.

```ts
it('throws when live awards resolve to undefined', async () => {
  getMock.mockResolvedValueOnce({ data: undefined });
  const { useAward } = await import('./use-award');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isError).toBe(true));
  expect((result.current.error as Error).message).toContain('awards');
});

it('rejects malformed live award envelopes without a data array', async () => {
  getMock.mockResolvedValueOnce({
    data: { meta: { rfq_id: 'rfq-1' } },
  });
  const { useAward } = await import('./use-award');
  const { Wrapper } = createTestWrapper();

  const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isError).toBe(true));
  expect((result.current.error as Error).message).toContain('data array');
});
```

- [ ] **Step 2: Run the award live test to confirm it fails before implementation**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run src/hooks/use-award.live.test.ts
```

Expected: FAIL because `useAward` still uses seed-backed read fallback in live mode.

- [ ] **Step 3: Remove live read fallback from `use-award.ts`**

Only mock mode should read seed award data. Live mode must require a proper awards envelope.

Suggested query-function shape:

```ts
export function useAward(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';
  const qc = useQueryClient();

  const query = useQuery({
    queryKey: ['awards', rfqId],
    queryFn: async (): Promise<AwardRecord[]> => {
      if (useMocks) {
        const { getSeedAwardByRfqId } = await import('@/data/seed');
        const seed = getSeedAwardByRfqId(rfqId);
        return seed ? [/* existing mapped seed row */] : [];
      }

      const data = await fetchLiveOrFail<{ data: AwardRecord[] }>('/awards', { params: { rfq_id: rfqId } });
      if (data === undefined) {
        throw new Error(`Live awards unavailable for RFQ "${rfqId}".`);
      }

      return normalizeAwardRows(data);
    },
    enabled: Boolean(rfqId),
  });
```

- [ ] **Step 4: Re-run the award live test and confirm it passes**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run src/hooks/use-award.live.test.ts
```

Expected: PASS, including existing mutation coverage.

- [ ] **Step 5: Commit the award slice**

```bash
git add \
  apps/atomy-q/WEB/src/hooks/use-award.ts \
  apps/atomy-q/WEB/src/hooks/use-award.live.test.ts
git commit -m "test(web): remove live award seed fallback"
```

### Task 5: Add Page-Level Unavailable States For RFQ Golden-Path Screens

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`
- Add: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`

- [ ] **Step 1: Add page tests for unavailable-state rendering before changing the pages**

Create or extend page tests to ensure each page treats live failure differently from an empty business result.

Suggested `vendors/page.test.tsx`:

```tsx
vi.mock('@/hooks/use-rfq-vendors', () => ({
  useRfqVendors: () => ({
    data: [],
    isLoading: false,
    isError: true,
    error: new Error('Live vendor roster unavailable'),
  }),
}));

it('renders an explicit unavailable state when vendor data fails to load', async () => {
  renderWithProviders(<RfqVendorsPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);

  expect(await screen.findByText(/vendor roster unavailable/i)).toBeInTheDocument();
  expect(screen.queryByText(/no vendors invited yet/i)).not.toBeInTheDocument();
});
```

Suggested `quote-intake/page.test.tsx` extension:

```tsx
vi.mock('@/hooks/use-quote-submissions', () => ({
  useQuoteSubmissions: () => ({
    data: [],
    isLoading: false,
    isError: true,
    error: new Error('Quote submissions unavailable'),
  }),
}));

it('renders an explicit unavailable state when quote submissions fail to load', async () => {
  renderWithProviders(<QuoteIntakeListContent rfqId="rfq-1" />);

  expect(await screen.findByText(/quote intake unavailable/i)).toBeInTheDocument();
  expect(screen.queryByText(/no submissions yet/i)).not.toBeInTheDocument();
});
```

- [ ] **Step 2: Run the affected page tests and confirm they fail first**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/vendors/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/'[quoteId]'/normalize/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/award/page.test.tsx
```

Expected: FAIL because the first four pages do not yet render dedicated unavailable states.

- [ ] **Step 3: Add explicit unavailable-state guards to the affected pages**

Follow the comparison-run detail page pattern: loading, then explicit unavailable state, then successful content.

Suggested `vendors/page.tsx` guard:

```tsx
const vendorsQuery = useRfqVendors(rfqId);
const vendors = vendorsQuery.data ?? [];

if (vendorsQuery.isError) {
  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader title="Invited vendors" subtitle="Vendor roster unavailable" />
      <SectionCard title="Vendor roster unavailable">
        <EmptyState
          icon={<Users size={20} />}
          title="Could not load invited vendors"
          description={
            vendorsQuery.error instanceof Error
              ? vendorsQuery.error.message
              : 'The live vendor roster could not be loaded.'
          }
        />
      </SectionCard>
    </div>
  );
}
```

Suggested `quote-intake/page.tsx` guard:

```tsx
const submissionsQuery = useQuoteSubmissions(rfqId);
const submissions = submissionsQuery.data ?? [];

if (submissionsQuery.isError || (!useMocks && norm.isError)) {
  const pageError = submissionsQuery.error ?? norm.error;
  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader title="Quote Intake" subtitle="Quote intake unavailable" />
      <Card className="p-4">
        <p className="text-sm text-slate-700">The live quote-intake workspace could not be loaded.</p>
        <p className="mt-2 text-sm text-red-600">
          {pageError instanceof Error ? pageError.message : 'Live quote intake data is unavailable.'}
        </p>
      </Card>
    </div>
  );
}
```

Suggested `comparison-runs/page.tsx` guard:

```tsx
const runsQuery = useComparisonRuns(rfqId);
const runs = runsQuery.data ?? [];

if (runsQuery.isError) {
  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader title="Comparison Runs" subtitle="Comparison runs unavailable" />
      <SectionCard title="Comparison runs unavailable">
        <EmptyState
          icon={<Layers3 size={20} />}
          title="Could not load comparison runs"
          description={
            runsQuery.error instanceof Error
              ? runsQuery.error.message
              : 'The live comparison-run list could not be loaded.'
          }
        />
      </SectionCard>
    </div>
  );
}
```

- [ ] **Step 4: Re-run the page tests and confirm they pass**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/vendors/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/'[quoteId]'/normalize/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/award/page.test.tsx
```

Expected: PASS.

- [ ] **Step 5: Commit the page-level UX slice**

```bash
git add \
  apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs/'[rfqId]'/vendors/page.tsx \
  apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs/'[rfqId]'/vendors/page.test.tsx \
  apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/page.tsx \
  apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/page.test.tsx \
  apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/'[quoteId]'/normalize/page.tsx \
  apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/'[quoteId]'/normalize/page.test.tsx \
  apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/page.tsx \
  apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/page.test.tsx \
  apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs/'[rfqId]'/award/page.test.tsx
git commit -m "test(web): add unavailable states for RFQ golden path"
```

### Task 6: Update Docs, Record Evidence, And Run Final Verification

**Files:**
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`

- [ ] **Step 1: Update WEB implementation docs to describe the shipped behavior**

Add a concise implementation-summary section that records:

- live mode now fails loudly on golden-path hook failures
- mock mode remains local/demo behavior only
- affected pages render unavailable states instead of silent empty success

Suggested summary text:

```md
## Task 4: Live-Mode Fail-Loud Golden Path

- Golden-path hooks now keep seed access behind explicit `NEXT_PUBLIC_USE_MOCKS === 'true'` branches only.
- Live reads reject `undefined` and malformed payloads instead of falling back to seed data.
- Vendors, quote intake, normalize, and comparison-runs pages now distinguish empty business states from unavailable live dependencies.
- The `.live.test.ts` matrix now covers API error, success, `undefined`, and malformed payload behavior for every Task 4 hook.
```

- [ ] **Step 2: Update the alpha release plan and checklist with Task 4 references and evidence**

Add the implementation-plan link under Section 9 Task 4, then record verification evidence in the checklist.

Suggested checklist block:

```md
### Task 4 Verification

- `npx vitest run src/hooks/use-rfq.live.test.ts ... src/hooks/use-award.live.test.ts` - PASS
- `npx vitest run src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx ... src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx` - PASS
- `npm run build` - PASS
- Blocker A3 status: closed after live golden-path hooks stopped using seed fallback in live mode.
```

- [ ] **Step 3: Run the final WEB verification commands**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/hooks/use-rfq.live.test.ts \
  src/hooks/use-rfqs.live.test.ts \
  src/hooks/use-rfq-vendors.live.test.ts \
  src/hooks/use-quote-submissions.live.test.ts \
  src/hooks/use-normalization-source-lines.live.test.ts \
  src/hooks/use-normalization-review.live.test.ts \
  src/hooks/use-comparison-runs.live.test.ts \
  src/hooks/use-comparison-run.live.test.ts \
  src/hooks/use-comparison-run-matrix.live.test.ts \
  src/hooks/use-comparison-run-readiness.live.test.ts \
  src/hooks/use-award.live.test.ts
```

Expected: PASS.

- [ ] **Step 4: Run the targeted page tests and production build**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/vendors/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/'[quoteId]'/normalize/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/'[runId]'/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/award/page.test.tsx

cd apps/atomy-q/WEB && npm run build
```

Expected: PASS.

- [ ] **Step 5: Commit the docs and verification slice**

```bash
git add \
  apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md \
  apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md \
  apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md
git commit -m "docs(alpha): record live-mode fail-loud verification"
```
