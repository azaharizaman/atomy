# Gap 9 Fail-Loud: Awards, Vendors, Projects Hooks Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Update Awards (`useAward`), Vendors (`useRfqVendors`), Projects (`useProjects`) hooks to fail loudly in live mode when API calls fail.

**Architecture:** Reuse `api-live.ts` utility from Phase 1. This is Phase 3 (P2) of Gap 9 closure.

**Tech Stack:** TypeScript, React Query, Vitest

---

## Task 1: Reuse api-live.ts Utility

- [ ] **Verify utility exists**

Run: `ls apps/atomy-q/WEB/src/lib/api-live.ts`

---

## Task 2: Update useAward to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-award.ts`

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/hooks/use-award.ts`

- [ ] **Step 2: Update to use fetchLiveOrFail**

- [ ] **Step 3: Verify build passes**

Run: `cd apps/atomy-q/WEB && npm run build 2>&1 | head -30`

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-award.ts
git commit -m "feat(gap-9): make useAward fail loudly in live mode"
```

---

## Task 3: Update useRfqVendors to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts`

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts`

- [ ] **Step 2: Update to use fetchLiveOrFail**

- [ ] **Step 3: Verify build passes**

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts
git commit -m "feat(gap-9): make useRfqVendors fail loudly in live mode"
```

---

## Task 4: Update useProjects to Fail Loudly

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-projects.ts`

- [ ] **Step 1: Read current implementation**

Run: `rg -n "useMocks" apps/atomy-q/WEB/src/hooks/use-projects.ts`

- [ ] **Step 2: Update to use fetchLiveOrFail**

- [ ] **Step 3: Verify build passes**

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-projects.ts
git commit -m "feat(gap-9): make useProjects fail loudly in live mode"
```

---

## Task 5: Add Live-Mode Tests

**Files:**
- Create: `src/hooks/use-award.live.test.ts`
- Create: `src/hooks/use-rfq-vendors.live.test.ts`
- Create: `src/hooks/use-projects.live.test.ts`

Reference: `use-rfqs.live.test.ts`

- [ ] **Step 1: Create tests**

- [ ] **Step 2: Run tests**

Run: `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-award.live.test.ts src/hooks/use-rfq-vendors.live.test.ts src/hooks/use-projects.live.test.ts`

- [ ] **Step 3: Commit**

```bash
git add src/hooks/use-award.live.test.ts src/hooks/use-rfq-vendors.live.test.ts src/hooks/use-projects.live.test.ts
git commit -m "test(gap-9): add live-mode tests for awards/vendors/projects hooks"
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
git commit -m "chore(gap-9): complete Phase 3 - awards/vendors/projects hooks fail-loud"
```

---

## Success Criteria

- [ ] `useAward` updated
- [ ] `useRfqVendors` updated
- [ ] `useProjects` updated
- [ ] Live-mode tests created
- [ ] Tests pass
- [ ] Lint passes
- [ ] Build passes