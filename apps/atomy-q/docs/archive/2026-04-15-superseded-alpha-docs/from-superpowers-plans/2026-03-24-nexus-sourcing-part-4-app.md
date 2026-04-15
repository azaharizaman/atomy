# Nexus Sourcing & Vendor Ecosystem: App Refactoring Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking tracking.

**Goal:** Refactor Atomy-Q to use the new Nexus Sourcing and Vendor packages.

**Architecture:** Replace app-local models and logic with Nexus orchestrator calls.

**Tech Stack:** PHP 8.3, Laravel 11.

---

### Task 1: Refactor Atomy-Q Normalization Controller

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`

- [ ] **Step 1: Write failing test**
Create `apps/atomy-q/API/tests/Feature/NormalizationApiTest.php`.
- [ ] **Step 2: Update Controller**
Inject `SourcingOperationsInterface` and update `sourceLines()` to use it.
- [ ] **Step 3: Run test (Expect PASS)**
- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/API
git commit -m "refactor(atomy-q): use Nexus Sourcing orchestrator"
```

---

### Task 2: Cleanup and Final Verification

- [ ] **Step 1: Delete app-local models**
Delete `apps/atomy-q/API/app/Models/NormalizationSourceLine.php`.
Delete `apps/atomy-q/API/app/Models/NormalizationConflict.php`.
- [ ] **Step 2: Update database migrations**
Delete old app-local migrations if they conflict with new Nexus tables.
- [ ] **Step 3: Final Smoke Test**
Run: `php artisan test` in `apps/atomy-q/API`.
- [ ] **Step 4: Commit**

```bash
git commit -m "refactor(atomy-q): remove legacy normalization models"
```
