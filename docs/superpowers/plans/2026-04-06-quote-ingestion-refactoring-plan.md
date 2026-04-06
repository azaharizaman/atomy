# Quote Ingestion AI Normalization Refactoring Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor the Quote Ingestion and Quotation Intelligence modules to improve type safety, transactional integrity, and contract compliance based on the approved design spec.

**Architecture:** 
- Introduce `QuoteSubmissionInterface` to replace generic `object` types.
- Split `NormalizationSourceLineRepositoryInterface` into Query and Persist interfaces.
- Enhance `AtomyDecisionTrailWriter` with strict row-level locking (`lockForUpdate`).
- Align `InMemoryUomRepository` with its interface contract by throwing appropriate exceptions.

**Tech Stack:** PHP 8.2+, Laravel, Eloquent ORM.

---

### Task 1: Define QuoteSubmissionInterface

**Files:**
- Create: `orchestrators/QuoteIngestion/src/Contracts/QuoteSubmissionInterface.php`

- [ ] **Step 1: Create the interface**

```php
<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

interface QuoteSubmissionInterface
{
    public function getId(): string;
    public function getTenantId(): string;
    public function getRfqId(): string;
    public function getVendorName(): string;
}
```

- [ ] **Step 2: Commit**

```bash
git add orchestrators/QuoteIngestion/src/Contracts/QuoteSubmissionInterface.php
git commit -m "feat(quote-ingestion): add QuoteSubmissionInterface contract"
```

---

### Task 2: Update QuoteSubmissionQueryInterface and QuoteSubmissionPersistInterface

**Files:**
- Modify: `orchestrators/QuoteIngestion/src/Contracts/QuoteSubmissionQueryInterface.php`
- Modify: `orchestrators/QuoteIngestion/src/Contracts/QuoteSubmissionPersistInterface.php`

- [ ] **Step 1: Update QuoteSubmissionQueryInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

interface QuoteSubmissionQueryInterface
{
    public function find(string $tenantId, string $id): ?QuoteSubmissionInterface;
}
```

- [ ] **Step 2: Update QuoteSubmissionPersistInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

interface QuoteSubmissionPersistInterface
{
    public function updateStatus(QuoteSubmissionInterface $submission, string $status): void;
    public function markExtracting(QuoteSubmissionInterface $submission): void;
    public function markNormalizing(QuoteSubmissionInterface $submission): void;
    public function markCompleted(QuoteSubmissionInterface $submission, string $status, float $confidence, int $lineCount): void;
    public function markFailed(QuoteSubmissionInterface $submission, string $errorCode, ?string $errorMessage): void;
}
```

- [ ] **Step 3: Commit**

```bash
git add orchestrators/QuoteIngestion/src/Contracts/QuoteSubmissionQueryInterface.php \
        orchestrators/QuoteIngestion/src/Contracts/QuoteSubmissionPersistInterface.php
git commit -m "refactor(quote-ingestion): use QuoteSubmissionInterface in query and persist contracts"
```

---

### Task 3: Split NormalizationSourceLineRepositoryInterface

**Files:**
- Create: `orchestrators/QuoteIngestion/src/Contracts/NormalizationSourceLineQueryInterface.php`
- Create: `orchestrators/QuoteIngestion/src/Contracts/NormalizationSourceLinePersistInterface.php`
- Delete: `orchestrators/QuoteIngestion/src/Contracts/NormalizationSourceLineRepositoryInterface.php`

- [ ] **Step 1: Create NormalizationSourceLineQueryInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

interface NormalizationSourceLineQueryInterface
{
    public function findExisting(
        string $tenantId,
        string $quoteSubmissionId,
        string $rfqLineItemId
    ): ?object;
}
```

- [ ] **Step 2: Create NormalizationSourceLinePersistInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

interface NormalizationSourceLinePersistInterface
{
    public function upsert(
        string $tenantId,
        string $quoteSubmissionId,
        string $rfqLineItemId,
        array $data
    ): void;
}
```

- [ ] **Step 3: Commit**

```bash
git add orchestrators/QuoteIngestion/src/Contracts/NormalizationSourceLineQueryInterface.php \
        orchestrators/QuoteIngestion/src/Contracts/NormalizationSourceLinePersistInterface.php
git rm orchestrators/QuoteIngestion/src/Contracts/NormalizationSourceLineRepositoryInterface.php
git commit -m "refactor(quote-ingestion): split NormalizationSourceLineRepositoryInterface into Query and Persist"
```

---

### Task 4: Update QuoteIngestionOrchestrator

**Files:**
- Modify: `orchestrators/QuoteIngestion/src/QuoteIngestionOrchestrator.php`

- [ ] **Step 1: Update dependencies and type hints**
- Update constructor to use split repository interfaces.
- Update `process`, `persistSourceLines`, `writeDecisionTrail`, and `handleFailure` to use `QuoteSubmissionInterface`.
- Simplify `calculateAvgConfidence` (remove redundant ternary).
- Add logging in `persistSourceLines` when skipping overrides.

- [ ] **Step 2: Commit**

```bash
git add orchestrators/QuoteIngestion/src/QuoteIngestionOrchestrator.php
git commit -m "refactor(quote-ingestion): update orchestrator with typed submission and split repos"
```

---

### Task 5: Implement Contract Compliance in InMemoryUomRepository

**Files:**
- Modify: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/Support/InMemoryUomRepository.php`

- [ ] **Step 1: Update save methods to throw exceptions**
- Implement duplicate checks and throw `DuplicateUnitCodeException`, `DuplicateDimensionCodeException`, and `InvalidConversionRatioException`.
- Add `@SuppressWarnings(PHPMD.UnusedFormalParameter)` to `getUnitsBySystem`.
- Add `reset()` method.

- [ ] **Step 2: Commit**

```bash
git add apps/atomy-q/API/app/Adapters/QuotationIntelligence/Support/InMemoryUomRepository.php
git commit -m "refactor(uom): enforce interface contract in InMemoryUomRepository"
```

---

### Task 6: Secure Decision Trail Writes

**Files:**
- Modify: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/AtomyDecisionTrailWriter.php`

- [ ] **Step 1: Add row-level locking**
- Wrap sequential reads (previous hash, max sequence) in `lockForUpdate()`.

- [ ] **Step 2: Commit**

```bash
git add apps/atomy-q/API/app/Adapters/QuotationIntelligence/AtomyDecisionTrailWriter.php
git commit -m "fix(quotation-intelligence): add row-level locking for decision trail writes"
```

---

### Task 7: Update Eloquent Repository and Controller

**Files:**
- Modify: `apps/atomy-q/API/app/Adapters/QuoteIngestion/EloquentNormalizationSourceLineRepository.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`

- [ ] **Step 1: Filter keys in repository**
- Implement split interfaces and filter `tenant_id`, `quote_submission_id`, `rfq_line_item_id` in `upsert`.

- [ ] **Step 2: Update controller validation**
- Use `trim((string)...) !== ''` for `override` and `resolution` checks.

- [ ] **Step 3: Commit**

```bash
git add apps/atomy-q/API/app/Adapters/QuoteIngestion/EloquentNormalizationSourceLineRepository.php \
        apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php
git commit -m "refactor: apply repository filtering and controller validation fixes"
```

---

### Task 8: Test Cleanup and Final Verification

**Files:**
- Modify: `apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php`

- [ ] **Step 1: Implement tearDown in test**
- Recursively delete `$testStorageRoot`.

- [ ] **Step 2: Run all tests**
- `php vendor/bin/phpunit tests/Feature/QuoteIngestionIntelligenceTest.php`

- [ ] **Step 3: Commit**

```bash
git add apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php
git commit -m "test: implement cleanup in QuoteIngestionIntelligenceTest"
```
