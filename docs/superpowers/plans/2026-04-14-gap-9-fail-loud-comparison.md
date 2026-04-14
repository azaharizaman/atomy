# Gap 9 Fail-Loud: Comparison Hooks Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Update Comparison hooks (`use-comparison-runs`, `use-comparison-run`, `use-comparison-run-matrix`, `use-comparison-run-readiness`) to fail loudly in live mode when API calls fail, following the pattern in `use-rfqs.ts`.

**Architecture:** Create `api-live.ts` utility, update each hook to use it, add live-mode tests. This is Phase 1 (P0) of Gap 9 closure.

**Tech Stack:** TypeScript, React Query, Vitest

---

## Task 1: Create api-live.ts Utility

**Files:**
- Create: `apps/atomy-q/WEB/src/lib/api-live.ts`

- [ ] **Step 1: Create api-live.ts with fetchLiveOrFail utility**

```typescript
// src/lib/api-live.ts
'use client';

import { api } from '@/lib/api';

/**
 * Fetches from API in live mode, throwing a descriptive error on failure.
 * In mock mode, returns undefined to signal the caller should use seed data.
 *
 * @param endpoint - API endpoint path (with or without query params)
 * @param options - Optional: signal for abort, headers, etc.
 * @returns API response data, or undefined if useMocks is true
 * @throws Error with descriptive message in live mode on API failure
 */
export async function fetchLiveOrFail<T>(
  endpoint: string,
  options?: { signal?: AbortSignal; headers?: Record<string, string> }
): Promise<T | undefined> {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  if (useMocks) {
    return undefined;
  }

  try {
    const { data } = await api.get(endpoint, options);
    return data as T;
  } catch (e) {
    const message = e instanceof Error ? e.message : 'Unknown error';
    throw new Error(`Failed to load ${endpoint}: ${message}`);
  }
}
```

- [ ] **Step 2: Verify file is discoverable**

Run: `ls -la apps/atomy-q/WEB/src/lib/api-live.ts`

Expected: File exists

- [ ] **Step 3: Commit**

```bash
git add apps/atomy-q/WEB/src/lib/api-live.ts
git commit -m "feat(gap-9): add fetchLiveOrFail utility for fail-loud API calls"
```

---

## Task 2: Update useComparisonRuns to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-runs.ts`

Reference: `use-rfqs.ts` lines 165-176 for pattern

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/hooks/use-comparison-runs.ts`

Expected: Shows lines 45-70 with current mock/seed fallback pattern

- [ ] **Step 2: Update to use fetchLiveOrFail**

Replace the queryFn section (lines 49-66):

```typescript
queryFn: async (): Promise<ComparisonRunRow[]> => {
  const data = await fetchLiveOrFail<{ data: ComparisonRunRow[] }>(
    `/comparison-runs?rfq_id=${rfqId}`
  );
  
  if (data === undefined) {
    return getSeedComparisonRunsByRfqId(rfqId).map((r) => ({
      id: r.id,
      rfq_id: r.rfqId,
      date: r.date,
      type: r.type,
      status: r.status,
      name: r.type === 'final' ? 'Final comparison' : 'Preview comparison',
      created_at: null,
    }));
  }

  return normalizeComparisonRuns(data);
},
```

- [ ] **Step 3: Verify build passes**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | head -30`

Expected: No errors related to the modified file

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-comparison-runs.ts
git commit -m "feat(gap-9): make useComparisonRuns fail loudly in live mode"
```

---

## Task 3: Update useComparisonRun to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-run.ts`

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/hooks/use-comparison-run.ts`

- [ ] **Step 2: Update queryFn to use fetchLiveOrFail**

Replace lines that call API in mock branch with:

```typescript
const data = await fetchLiveOrFail<{ data: ComparisonRunDetail }>(
  `/comparison-runs/${runId}`
);

if (data === undefined) {
  // return seed data
}
```

- [ ] **Step 3: Verify build passes**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | head -30`

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-comparison-run.ts
git commit -m "feat(gap-9): make useComparisonRun fail loudly in live mode"
```

---

## Task 4: Update useComparisonRunMatrix to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts`

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts`

- [ ] **Step 2: Update to use fetchLiveOrFail**

Following the same pattern as Task 2

- [ ] **Step 3: Verify build passes**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | head -30`

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts
git commit -m "feat(gap-9): make useComparisonRunMatrix fail loudly in live mode"
```

---

## Task 5: Update useComparisonRunReadiness to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.ts`

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.ts`

- [ ] **Step 2: Update to use fetchLiveOrFail**

Following the same pattern as Task 2

- [ ] **Step 3: Verify build passes**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | head -30`

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.ts
git commit -m "feat(gap-9): make useComparisonRunReadiness fail loudly in live mode"
```

---

## Task 6: Add Live-Mode Tests for Comparison Hooks

**Files:**
- Create: `apps/atomy-q/WEB/src/hooks/use-comparison-runs.live.test.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-comparison-run.live.test.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.live.test.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.live.test.ts`

Reference: `use-rfqs.live.test.ts` for test pattern

- [ ] **Step 1: Create use-comparison-runs.live.test.ts**

```typescript
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: { get: getMock },
}));

vi.mock('@/data/seed', () => ({
  getSeedComparisonRunsByRfqId: vi.fn(() => []),
}));

describe('useComparisonRuns (live mode)', () => {
  const originalMocks = process.env.NEXT_PUBLIC_USE_MOCKS;

  beforeEach(() => {
    process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
    getMock.mockReset();
  });

  afterEach(() => {
    process.env.NEXT_PUBLIC_USE_MOCKS = originalMocks;
  });

  it('surfaces API errors instead of silently falling back to seed data', async () => {
    getMock.mockRejectedValueOnce(new Error('Network unavailable'));
    const { useComparisonRuns } = await import('./use-comparison-runs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRuns('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API succeeds', async () => {
    getMock.mockResolvedValueOnce({
      data: [{ id: 'run-1', rfq_id: 'rfq-1', type: 'preview', status: 'completed' }],
    });
    const { useComparisonRuns } = await import('./use-comparison-runs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRuns('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toHaveLength(1);
  });
});
```

- [ ] **Step 2: Create remaining live test files**

Following the same pattern for:
- `use-comparison-run.live.test.ts`
- `use-comparison-run-matrix.live.test.ts`
- `use-comparison-run-readiness.live.test.ts`

- [ ] **Step 3: Run tests**

Run: `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-comparison-runs.live.test.ts src/hooks/use-comparison-run.live.test.ts src/hooks/use-comparison-run-matrix.live.test.ts src/hooks/use-comparison-run-readiness.live.test.ts`

Expected: All tests pass

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-comparison-runs.live.test.ts apps/atomy-q/WEB/src/hooks/use-comparison-run.live.test.ts apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.live.test.ts apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.live.test.ts
git commit -m "test(gap-9): add live-mode tests for comparison hooks"
```

---

## Task 7: Final Verification

**Files:**
- Verify: All Comparison hooks + tests

- [ ] **Step 1: Run full test suite**

Run: `cd apps/atomy-q/WEB && npm run test:unit 2>&1 | tail -20`

Expected: All tests pass

- [ ] **Step 2: Run lint**

Run: `cd apps/atomy-q/WEB && npm run lint 2>&1 | tail -10`

Expected: Pass (warnings acceptable)

- [ ] **Step 3: Run build**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | tail -10`

Expected: Pass

- [ ] **Step 4: Commit final verification**

```bash
git add -A
git commit -m "chore(gap-9): complete Phase 1 - comparison hooks fail-loud"
```

---

## Success Criteria

- [ ] `api-live.ts` utility created
- [ ] `use-comparison-runs.ts` updated + commits
- [ ] `use-comparison-run.ts` updated + commits
- [ ] `use-comparison-run-matrix.ts` updated + commits
- [ ] `use-comparison-run-readiness.ts` updated + commits
- [ ] Live-mode tests created for all 4 hooks
- [ ] Tests pass
- [ ] Lint passes
- [ ] Build passes