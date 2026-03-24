# Nexus Sourcing & Vendor Ecosystem Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Decouple strategic sourcing from operational procurement by creating dedicated Sourcing and Vendor packages, and orchestrating the Award-to-PO workflow.

**Architecture:** Three-Layer Architecture. Layer 1 (Pure PHP) for domain logic, Layer 2 (Orchestrators) for cross-package coordination, Layer 3 (Adapters) for Laravel-specific persistence.

**Tech Stack:** PHP 8.3+, Laravel 12.x, Nexus Core Mandates.

---

### Task 1: Scaffold New Packages and Orchestrator

**Files:**
- Create: `packages/Vendor/src/Contracts/VendorProfileInterface.php`
- Create: `packages/Vendor/src/ValueObjects/VendorProfileVO.php`
- Create: `packages/Sourcing/src/Contracts/SourcingEventInterface.php`
- Create: `packages/Sourcing/src/Contracts/QuotationInterface.php`
- Create: `packages/SourcingScoring/src/Contracts/ScoringEngineInterface.php`
- Create: `orchestrators/SourcingOperations/src/SourcingOperationsCoordinator.php`

- [ ] **Step 1: Create `Nexus\Vendor` package structure**
- [ ] **Step 2: Create `Nexus\Sourcing` package structure**
- [ ] **Step 3: Create `Nexus\SourcingScoring` package structure**
- [ ] **Step 4: Create `Nexus\SourcingOperations` orchestrator structure**
- [ ] **Step 5: Run `composer install` in each to initialize**
- [ ] **Step 6: Commit**

```bash
git add packages/Vendor packages/Sourcing packages/SourcingScoring orchestrators/SourcingOperations
git commit -m "chore: scaffold sourcing and vendor ecosystem packages"
```

---

### Task 2: Implement Vendor Identity Role (Layer 1)

**Files:**
- Create: `packages/Vendor/src/Contracts/VendorProfileInterface.php`
- Create: `packages/Vendor/src/ValueObjects/VendorProfileVO.php`
- Create: `packages/Vendor/src/Services/VendorRoleManager.php`

- [ ] **Step 1: Define `VendorProfileInterface`**
- [ ] **Step 2: Implement `VendorProfileVO` (Immutable)**
- [ ] **Step 3: Implement `VendorRoleManager` for linking `PartyId` to `VendorId`**
- [ ] **Step 4: Write unit test for `VendorRoleManager`**
- [ ] **Step 5: Run tests and verify**
- [ ] **Step 6: Commit**

---

### Task 3: Extract RFQ/Quote Domain from Procurement (Layer 1)

**Files:**
- Modify: `packages/Procurement/src/Contracts/VendorQuoteInterface.php` (Deprecate or Move)
- Create: `packages/Sourcing/src/Contracts/QuotationInterface.php`
- Create: `packages/Sourcing/src/Contracts/NormalizationLineInterface.php`

- [ ] **Step 1: Move `VendorQuote` logic to `Nexus\Sourcing`**
- [ ] **Step 2: Define `NormalizationLineInterface` and `AwardInterface` in `Sourcing`**
- [ ] **Step 3: Refactor `Procurement` to remove strategic sourcing logic**
- [ ] **Step 4: Update `Procurement` to reference `Sourcing` via IDs**
- [ ] **Step 5: Commit**

---

### Task 4: Implement Sourcing Scoring Engine (Layer 1)

**Files:**
- Create: `packages/SourcingScoring/src/Services/WeightedScoringEngine.php`
- Create: `packages/SourcingScoring/src/ValueObjects/ScoringDimensions.php`

- [ ] **Step 1: Implement `WeightedScoringEngine` with MCDA dimensions (Price, Risk, Delivery, Sustainability)**
- [ ] **Step 2: Write tests for deterministic scoring math**
- [ ] **Step 3: Commit**

---

### Task 5: Refactor QuotationIntelligence to Suggestion Engine (Layer 2)

**Files:**
- Modify: `orchestrators/QuotationIntelligence/src/Coordinators/QuotationIntelligenceCoordinator.php`
- Modify: `orchestrators/QuotationIntelligence/src/Services/AiSemanticMapper.php`

- [ ] **Step 1: Refactor Coordinator to return `NormalizationSuggestions` instead of final mappings**
- [ ] **Step 2: Move risk heuristics to `Sourcing` package**
- [ ] **Step 3: Commit**

---

### Task 6: Implement SourcingOperations Orchestrator (Layer 2)

**Files:**
- Create: `orchestrators/SourcingOperations/src/SourcingOperationsCoordinator.php`
- Create: `orchestrators/SourcingOperations/src/Actions/AwardToPoAction.php`
- Create: `orchestrators/SourcingOperations/src/Actions/InviteVendorAction.php`

- [ ] **Step 1: Implement "Happy Path" RFQ creation in `SourcingOperations`**
- [ ] **Step 2: Implement `InviteVendorAction` (Uses `Nexus\Notifier` to trigger notifications)**
- [ ] **Step 3: Implement `AwardToPoAction` (Orchestrates `Sourcing` -> `Procurement`)**
- [ ] **Step 4: Integrate `Nexus\PolicyEngine` in `AwardToPoAction` to verify authorization thresholds**
- [ ] **Step 5: Use `Nexus\AuditLogger` for decision trail**
- [ ] **Step 6: Use `Nexus\Idempotency` for replay safety**
- [ ] **Step 7: Write integration tests with `InMemory` repositories**
- [ ] **Step 8: Commit**

---

### Task 7: Implement Laravel Adapters (Layer 3)

**Files:**
- Create: `adapters/Laravel/Sourcing/SourcingLaravelAdapter.php`
- Create: `adapters/Laravel/Vendor/VendorLaravelAdapter.php`

- [ ] **Step 1: Implement Eloquent models and migrations for `Sourcing` entities**
- [ ] **Step 2: Implement Eloquent models and migrations for `Vendor` entities**
- [ ] **Step 3: Apply `tenant_id` global scopes**
- [ ] **Step 4: Implement `SourcingRepository` using Eloquent**
- [ ] **Step 5: Commit**

---

### Task 8: Atomy-Q Application Refactoring

**Files:**
- Modify: `apps/atomy-q/src/Http/Controllers/NormalizationController.php`
- Modify: `apps/atomy-q/src/Http/Controllers/SourcingController.php`

- [ ] **Step 1: Replace local Sourcing models with `Nexus\SourcingOperations` calls**
- [ ] **Step 2: Remove stubbed 501 responses in `NormalizationController`**
- [ ] **Step 3: Update UI to reference new Vendor package endpoints**
- [ ] **Step 4: Commit**

---

### Task 9: Verification and Alpha Gap Closure

- [ ] **Step 1: Verify `GET /normalization/{id}/source-lines`**
- [ ] **Step 2: Verify `POST /awards` generates PO**
- [ ] **Step 3: Run full suite of smoke tests**
- [ ] **Step 4: Final commit**
