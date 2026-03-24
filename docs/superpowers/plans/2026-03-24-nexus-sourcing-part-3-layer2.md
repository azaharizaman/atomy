# Nexus Sourcing & Vendor Ecosystem: Layer 2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking tracking.

**Goal:** Implement orchestrators for Sourcing workflows and AI-ML integration.

**Architecture:** Layer 2 orchestrators following "Zero Framework Leakage" mandate.

**Tech Stack:** PHP 8.3.

---

### Task 1: Nexus\SourcingOperations Orchestrator

**Files:**
- Create: `orchestrators/SourcingOperations/composer.json`
- Create: `orchestrators/SourcingOperations/src/SourcingOperationsOrchestrator.php`
- Create: `orchestrators/SourcingOperations/src/Contracts/SourcingOperationsInterface.php`

- [ ] **Step 1: Create composer.json**

```json
{
    "name": "nexus/sourcing-operations",
    "autoload": { "psr-4": { "Nexus\\SourcingOperations\\": "src/" } },
    "require": { "php": "^8.3", "nexus/sourcing": "*", "nexus/procurement": "*" }
}
```

- [ ] **Step 2: Create Orchestrator with Award-to-PO logic**

```php
<?php
declare(strict_types=1);
namespace Nexus\SourcingOperations;
use Nexus\SourcingOperations\Contracts\SourcingOperationsInterface;
use Nexus\Sourcing\Contracts\QuotationRepositoryInterface;
use Nexus\Procurement\Contracts\VendorQuoteRepositoryInterface;
final readonly class SourcingOperationsOrchestrator implements SourcingOperationsInterface {
    public function __construct(
        private QuotationRepositoryInterface $quotationRepo,
        private VendorQuoteRepositoryInterface $procurementRepo
    ) {}
    public function finalizeAward(string $tenantId, string $quotationId): void {
        $quotation = $this->quotationRepo->findById($tenantId, $quotationId);
        // Orchestrate PO creation
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add orchestrators/SourcingOperations
git commit -m "feat(sourcing): add SourcingOperations orchestrator"
```

---

### Task 2: Refactor QuotationIntelligence (AI Suggestion Engine)

**Files:**
- Modify: `orchestrators/QuotationIntelligence/src/Services/QuoteNormalizationService.php`

- [ ] **Step 1: Refactor to Suggester Pattern**
Update the service to provide AI suggestions for `NormalizationLine` mappings.
- [ ] **Step 2: Commit**

```bash
git add orchestrators/QuotationIntelligence
git commit -m "refactor(sourcing): refactor QuotationIntelligence as suggestion engine"
```
