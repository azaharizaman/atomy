# Gap 9 Fail-Loud Design Spec

Date: 2026-04-14  
Scope: `apps/atomy-q/WEB/src/hooks/*` and pages using `NEXT_PUBLIC_USE_MOCKS`  
Source: `ALPHA_REMAINING_GAPS_IMPLEMENTATION_SPEC_2026-04-09.md` (Gap 9)  
Reference: `use-rfqs.ts` fail-loud pattern, `use-rfqs.live.test.ts`

---

## 1. Objective

Close Gap 9 (Mock Fallback Elimination) by ensuring all hooks that read `NEXT_PUBLIC_USE_MOCKS` fail loudly in live mode — when the API call fails, throw a descriptive error instead of silently returning seed/mock data.

**Non-negotiable alpha criteria (per mitigation plan):**
> Live-mode UI paths fail loudly on API failure; no silent mock/seed fallback.

---

## 2. Current Problems

### 2.1 Anti-Pattern Found

Most hooks follow this pattern:

```typescript
export function useComparisonRuns(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['comparison-runs', rfqId],
    queryFn: async () => {
      if (useMocks) {
        return getSeedComparisonRunsByRfqId(rfqId).map(...);  // ← Silent fallback
      }
      const { data } = await api.get('/comparison-runs', { params: { rfq_id: rfqId } });
      // If API throws, useQuery catches it and sets error state...
      // ...but user sees loading/empty, not a clear error message
      return normalizeComparisonRuns(data);
    },
  });
}
```

### 2.2 Already Fixed (Reference Implementation)

`use-rfqs.ts` implements fail-loud correctly (lines 165-176):

```typescript
if (!useMocks) {
  const { data } = await api.get('/rfqs', { params: apiParams });
  // ...validation...
  return { items, meta: metaFromApi };
}
```

And `use-rfqs.live.test.ts` tests it:

```typescript
it('surfaces API errors instead of silently falling back to seed data', async () => {
  getMock.mockRejectedValueOnce(new Error('Network unavailable'));
  // ...assert result.current.isError === true
});
```

---

## 3. Solution

### 3.1 Utility Function

Create `src/lib/api-live.ts` with a reusable function:

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
    // Signal caller to use seed data
    return undefined;
  }

  try {
    const { data } = await api.get(endpoint, options);
    return data as T;
  } catch (e) {
    const message = e instanceof Error ? e.message : 'Unknown error';
    // Include endpoint in error for easier debugging
    throw new Error(`Failed to load ${endpoint}: ${message}`);
  }
}
```

### 3.2 Updated Hook Pattern

**Before:**
```typescript
if (useMocks) {
  return getSeedComparisonRunsByRfqId(rfqId);
}
const { data } = await api.get('/comparison-runs', { params: { rfq_id: rfqId } });
return normalizeComparisonRuns(data);
```

**After:**
```typescript
const data = await fetchLiveOrFail<{ data: ComparisonRunRow[] }>(
  `/comparison-runs?rfq_id=${rfqId}`
);
if (data === undefined) {
  return getSeedComparisonRunsByRfqId(rfqId);
}
return normalizeComparisonRuns(data);
```

### 3.3 Key Differences from `use-rfqs.ts`

| Aspect | `use-rfqs.ts` | New Pattern |
|--------|---------------|------------|
| Error handling | Inline try/catch in hook | Centralized in utility |
| Return on mocks | Handled in hook | Utility returns `undefined` |
| Endpoint logging | None | Endpoint in error message |
| Extensibility | N/A | Easy to add retry/logging |

---

## 4. Feature Areas and Hooks

### 4.1 Comparison (Priority: Highest)

| Hook | File | Priority |
|------|------|----------|
| `useComparisonRuns` | `use-comparison-runs.ts` | P0 |
| `useComparisonRun` | `use-comparison-run.ts` | P0 |
| `useComparisonRunMatrix` | `use-comparison-run-matrix.ts` | P0 |
| `useComparisonRunReadiness` | `use-comparison-run-readiness.ts` | P0 |

### 4.2 Quote Intake (Priority: High)

| Hook | File | Priority |
|------|------|----------|
| `useQuoteSubmissions` | `use-quote-submissions.ts` | P1 |
| `useQuoteSubmission` | `use-quote-submission.ts` | P1 |
| `useNormalizationReview` | `use-normalization-review.ts` | P1 |
| `useNormalizationSourceLines` | `use-normalization-source-lines.ts` | P1 |

### 4.3 RFQ Details (Priority: High)

| Hook | File | Priority |
|------|------|----------|
| `useRfq` | `use-rfq.ts` | P1 |
| `useRfqOverview` | `use-rfq-overview.ts` | P1 |
| `useRfqCounts` | `use-rfq-counts.ts` | P1 |

### 4.4 Awards (Priority: Medium)

| Hook | File | Priority |
|------|------|----------|
| `useAward` | `use-award.ts` | P2 |

### 4.5 Vendors (Priority: Medium)

| Hook | File | Priority |
|------|------|----------|
| `useRfqVendors` | `use-rfq-vendors.ts` | P2 |

### 4.6 Projects (Priority: Medium)

| Hook | File | Priority |
|------|------|----------|
| `useProjects` | `use-projects.ts` | P2 |

### 4.7 Approvals (Priority: Low)

| Hook | File | Priority |
|------|------|----------|
| `useApprovals` | `use-approvals.ts` (3 instances) | P3 |

### 4.8 Auth Pages (Priority: Low)

| Page | File | Priority |
|------|------|----------|
| Login | `app/(auth)/login/page.tsx` | P3 |
| Forgot Password | `app/(auth)/forgot-password/page.tsx` | P3 |
| Reset Password | `app/(auth)/reset-password/page.tsx` | P3 |

### 4.9 Dashboard (Priority: Low)

| Page | File | Priority |
|------|------|----------|
| Dashboard Home | `app/(dashboard)/page.tsx` | P3 |

---

## 5. Testing Strategy

### 5.1 Test File Pattern

Following `use-rfqs.live.test.ts`, create `*.live.test.ts` files for each area:

```
src/hooks/use-comparison-runs.live.test.ts
src/hooks/use-quote-submissions.live.test.ts
...
```

### 5.2 Test Structure

```typescript
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: { get: getMock },
}));

// Mock the seed function
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
    getMock.mockResolvedValueOnce({ data: [{ id: 'run-1', rfq_id: 'rfq-1' }] });
    const { useComparisonRuns } = await import('./use-comparison-runs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRuns('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toHaveLength(1);
  });
});
```

### 5.3 Test Coverage Goals

- Each priority area (P0, P1, P2, P3) gets at least one live-mode test
- Tests assert `isError` state when API fails
- Tests assert data returned when API succeeds

---

## 6. File Changes Summary

### New Files
- `src/lib/api-live.ts` — Utility function

### Modified Files (by area)

| Area | Files |
|------|-------|
| Comparison | `use-comparison-runs.ts`, `use-comparison-run.ts`, `use-comparison-run-matrix.ts`, `use-comparison-run-readiness.ts` |
| Quote Intake | `use-quote-submissions.ts`, `use-quote-submission.ts`, `use-normalization-review.ts`, `use-normalization-source-lines.ts` |
| RFQ Details | `use-rfq.ts`, `use-rfq-overview.ts`, `use-rfq-counts.ts` |
| Awards | `use-award.ts` |
| Vendors | `use-rfq-vendors.ts` |
| Projects | `use-projects.ts` |
| Approvals | `use-approvals.ts` |
| Auth | `app/(auth)/login/page.tsx`, `app/(auth)/forgot-password/page.tsx`, `app/(auth)/reset-password/page.tsx` |
| Dashboard | `app/(dashboard)/page.tsx` |

### New Test Files

| Area | Test File |
|------|----------|
| Comparison | `use-comparison-runs.live.test.ts`, `use-comparison-run.live.test.ts`, `use-comparison-run-matrix.live.test.ts`, `use-comparison-run-readiness.live.test.ts` |
| Quote Intake | `use-quote-submissions.live.test.ts`, `use-quote-submission.live.test.ts`, `use-normalization-review.live.test.ts` |
| RFQ Details | `use-rfq.live.test.ts`, `use-rfq-overview.live.test.ts` |
| Awards | `use-award.live.test.ts` |
| Vendors | `use-rfq-vendors.live.test.ts` |
| Projects | `use-projects.live.test.ts` |
| Approvals | `use-approvals.live.test.ts` |

---

## 7. Rollout Plan

Execute by priority area:

1. **Phase 1 (P0):** Comparison hooks + tests
2. **Phase 2 (P1):** Quote Intake + RFQ Details hooks + tests
3. **Phase 3 (P2):** Awards, Vendors, Projects hooks + tests
4. **Phase 4 (P3):** Approvals, Auth, Dashboard hooks + tests

Each phase:
1. Update utility (if new features needed)
2. Update all hooks in area
3. Add live-mode tests
4. Verify `npm run test:unit` passes

---

## 8. Success Criteria

- [ ] `api-live.ts` utility created and tested
- [ ] All P0 hooks (Comparison) fail loudly + tests pass
- [ ] All P1 hooks (Quote Intake, RFQ Details) fail loudly + tests pass
- [ ] All P2 hooks (Awards, Vendors, Projects) fail loudly + tests pass
- [ ] All P3 hooks (Approvals, Auth, Dashboard) fail loudly + tests pass
- [ ] `npm run test:unit` passes
- [ ] `npm run lint` passes
- [ ] `npm run build` passes

---

## 9. Reference Implementation

- `use-rfqs.ts` — Current fail-loud pattern (lines 165-176)
- `use-rfqs.live.test.ts` — Test pattern