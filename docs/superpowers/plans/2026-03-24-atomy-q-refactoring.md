# Nexus Sourcing Operations & App Refactoring Plan (RE-REVISED)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking tracking.

**Goal:** Refactor Atomy-Q and Nexus orchestrators to use the new Sourcing domain.

**Architecture:** Refactor `Nexus\QuotationIntelligence` (AI-ML Service) and implement `Nexus\SourcingOperations` (Workflow).

**Tech Stack:** PHP 8.3, Laravel.

---

### Task 1: Refactor QuotationIntelligence (Layer 2)

**Files:**
- Modify: `orchestrators/QuotationIntelligence/src/Services/QuoteNormalizationService.php`

- [ ] **Step 1: Update Service to use Sourcing Domain**
Refactor the service to take raw input and return suggested `NormalizationLine` mappings.
Ensure it acts as a "Suggestion Engine" rather than owning the state.

- [ ] **Step 2: Commit**

```bash
git add orchestrators/QuotationIntelligence
git commit -m "refactor(sourcing): refactor QuotationIntelligence as suggestion engine"
```

---

### Task 2: Implement SourcingOperations Award-to-PO Logic

**Files:**
- Modify: `orchestrators/SourcingOperations/src/SourcingOperationsOrchestrator.php`

- [ ] **Step 1: Implement finalizeAward()**

```php
public function finalizeAward(string $tenantId, string $awardId): void {
    // 1. Fetch Award details
    // 2. Fetch Quotation lines
    // 3. Call PurchaseOrderManager to create PO
    // 4. Update Award with purchase_order_id
}
```

- [ ] **Step 2: Commit**

```bash
git add orchestrators/SourcingOperations
git commit -m "feat(sourcing): implement Award-to-PO orchestration"
```

---

### Task 3: Refactor Atomy-Q Controllers

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`

- [ ] **Step 1: Update sourceLines() to use Sourcing Repository**
- [ ] **Step 2: Remove old app-local models**
- [ ] **Step 3: Run full integration test**
- [ ] **Step 4: Commit refactor**

```bash
git add apps/atomy-q/API
git commit -m "refactor(atomy-q): complete transition to Nexus Sourcing"
```
