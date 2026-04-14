# Gap 9 Fail-Loud: Quote Intake Hooks Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Update Quote Intake hooks (`useQuoteSubmissions`, `useQuoteSubmission`, `useNormalizationReview`, `useNormalizationSourceLines`) to fail loudly in live mode when API calls fail.

**Architecture:** Reuse `api-live.ts` utility from Phase 1. This is Phase 2a (P1) of Gap 9 closure.

**Tech Stack:** TypeScript, React Query, Vitest

---

## Task 1: Reuse api-live.ts Utility

**Files:**
- Verify: `apps/atomy-q/WEB/src/lib/api-live.ts`

- [ ] **Step 1: Verify utility exists**

Run: `ls apps/atomy-q/WEB/src/lib/api-live.ts`

Expected: File exists from Phase 1

---

## Task 2: Update useQuoteSubmissions to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-quote-submissions.ts`

Reference: `use-rfqs.ts` lines 165-176 for pattern

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/hooks/use-quote-submissions.ts`

- [ ] **Step 2: Update queryFn to use fetchLiveOrFail**

Replace the mock/seed branch:

```typescript
const data = await fetchLiveOrFail<{ data: QuoteSubmissionRow[] }>(
  `/quote-submissions?rfq_id=${rfqId}`
);

if (data === undefined) {
  return getSeedQuotesByRfqId(rfqId).map(...);
}

return normalizeQuoteSubmissionRows(data);
```

- [ ] **Step 3: Verify build passes**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | head -30`

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-quote-submissions.ts
git commit -m "feat(gap-9): make useQuoteSubmissions fail loudly in live mode"
```

---

## Task 3: Update useQuoteSubmission to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-quote-submission.ts`

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/hooks/use-quote-submission.ts`

- [ ] **Step 2: Update to use fetchLiveOrFail**

- [ ] **Step 3: Verify build passes**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | head -30`

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-quote-submission.ts
git commit -m "feat(gap-9): make useQuoteSubmission fail loudly in live mode"
```

---

## Task 4: Update useNormalizationReview to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`

- [ ] **Step 2: Update to use fetchLiveOrFail**

- [ ] **Step 3: Verify build passes**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | head -30`

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-normalization-review.ts
git commit -m "feat(gap-9): make useNormalizationReview fail loudly in live mode"
```

---

## Task 5: Update useNormalizationSourceLines to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.ts`

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.ts`

- [ ] **Step 2: Update to use fetchLiveOrFail**

- [ ] **Step 3: Verify build passes**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | head -30`

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.ts
git commit -m "feat(gap-9): make useNormalizationSourceLines fail loudly in live mode"
```

---

## Task 6: Add Live-Mode Tests for Quote Intake Hooks

**Files:**
- Create: `apps/atomy-q/WEB/src/hooks/use-quote-submissions.live.test.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-quote-submission.live.test.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-normalization-review.live.test.ts`

Reference: `use-rfqs.live.test.ts` for test pattern

- [ ] **Step 1: Create use-quote-submissions.live.test.ts**

```typescript
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: { get: getMock },
}));

vi.mock('@/data/seed', () => ({
  getSeedQuotesByRfqId: vi.fn(() => []),
}));

describe('useQuoteSubmissions (live mode)', () => {
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
    const { useQuoteSubmissions } = await import('./use-quote-submissions');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useQuoteSubmissions('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API succeeds', async () => {
    getMock.mockResolvedValueOnce({
      data: [{ id: 'quote-1', rfq_id: 'rfq-1', vendor_id: 'v-1' }],
    });
    const { useQuoteSubmissions } = await import('./use-quote-submissions');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useQuoteSubmissions('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toHaveLength(1);
  });
});
```

- [ ] **Step 2: Create remaining live test files**

- [ ] **Step 3: Run tests**

Run: `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-quote-submissions.live.test.ts src/hooks/use-quote-submission.live.test.ts src/hooks/use-normalization-review.live.test.ts`

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-quote-submissions.live.test.ts apps/atomy-q/WEB/src/hooks/use-quote-submission.live.test.ts apps/atomy-q/WEB/src/hooks/use-normalization-review.live.test.ts
git commit -m "test(gap-9): add live-mode tests for quote intake hooks"
```

---

## Task 7: Final Verification

- [ ] **Step 1: Run unit tests**

Run: `cd apps/atomy-q/WEB && npm run test:unit 2>&1 | tail -20`

- [ ] **Step 2: Run lint**

Run: `cd apps/atomy-q/WEB && npm run lint 2>&1 | tail -10`

- [ ] **Step 3: Run build**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | tail -10`

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "chore(gap-9): complete Phase 2a - quote intake hooks fail-loud"
```

---

## Success Criteria

- [ ] `useQuoteSubmissions` updated
- [ ] `useQuoteSubmission` updated
- [ ] `useNormalizationReview` updated
- [ ] `useNormalizationSourceLines` updated
- [ ] Live-mode tests created
- [ ] Tests pass
- [ ] Lint passes
- [ ] Build passes