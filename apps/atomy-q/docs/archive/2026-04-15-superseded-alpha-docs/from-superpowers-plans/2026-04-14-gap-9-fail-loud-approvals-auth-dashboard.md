# Gap 9 Fail-Loud: Approvals, Auth, Dashboard Hooks Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Update Approvals (`useApprovals`), Auth pages (Login, Forgot Password, Reset Password), and Dashboard hooks to fail loudly in live mode when API calls fail.

**Architecture:** Reuse `api-live.ts` utility from Phase 1. This is Phase 4 (P3) of Gap 9 closure.

**Tech Stack:** TypeScript, React Query, Vitest

---

## Task 1: Reuse api-live.ts Utility

- [ ] **Verify utility exists**

Run: `ls apps/atomy-q/WEB/src/lib/api-live.ts`

---

## Task 2: Update useApprovals to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-approvals.ts`

Note: This file has 3 instances of useMocks at lines 120, 198, 255

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/hooks/use-approvals.ts`

- [ ] **Step 2: Update all instances to use fetchLiveOrFail**

- [ ] **Step 3: Verify build passes**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | head -30`

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-approvals.ts
git commit -m "feat(gap-9): make useApprovals fail loudly in live mode"
```

---

## Task 3: Update Auth Pages to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(auth)/login/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(auth)/forgot-password/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(auth)/reset-password/page.tsx`

- [ ] **Step 1: Read current implementations**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/app/\(auth\)/`

- [ ] **Step 2: Update each page to use fetchLiveOrFail**

- [ ] **Step 3: Verify build passes**

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/app/\(auth\)/login/page.tsx apps/atomy-q/WEB/src/app/\(auth\)/forgot-password/page.tsx apps/atomy-q/WEB/src/app/\(auth\)/reset-password/page.tsx
git commit -m "feat(gap-9): make auth pages fail loudly in live mode"
```

---

## Task 4: Update Dashboard to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/page.tsx`

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/app/\(dashboard\)/page.tsx`

- [ ] **Step 2: Update to use fetchLiveOrFail**

- [ ] **Step 3: Verify build passes**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | head -30`

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/app/\(dashboard\)/page.tsx
git commit -m "feat(gap-9): make dashboard fail loudly in live mode"
```

---

## Task 5: Add Live-Mode Tests (Optional for P3)

**Files:**
- Create: `src/hooks/use-approvals.live.test.ts` (if not already covered)

Reference: `use-rfqs.live.test.ts`

- [ ] **Step 1: Create test (optional)**

- [ ] **Step 2: Run tests**

Run: `cd apps/atomy-q/WEB && npm run test:unit 2>&1 | tail -20`

- [ ] **Step 3: Commit**

```bash
git add src/hooks/use-approvals.live.test.ts
git commit -m "test(gap-9): add live-mode tests for approvals hooks"
```

---

## Task 6: Final Verification

- [ ] **Step 1: Run unit tests**

Run: `cd apps/atomy-q/WEB && npm run test:unit 2>&1 | tail -20`

- [ ] **Step 2: Run lint**

Run: `cd apps/atomy-q/WEB && npm run lint 2>&1 | tail -10`

- [ ] **Step 3: Run build**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | tail -10`

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "chore(gap-9): complete Phase 4 - approvals/auth/dashboard fail-loud"
```

---

## Success Criteria

- [ ] `useApprovals` updated
- [ ] Auth pages updated
- [ ] Dashboard updated
- [ ] Tests pass
- [ ] Lint passes
- [ ] Build passes
- [ ] Gap 9 fully closed across all hooks