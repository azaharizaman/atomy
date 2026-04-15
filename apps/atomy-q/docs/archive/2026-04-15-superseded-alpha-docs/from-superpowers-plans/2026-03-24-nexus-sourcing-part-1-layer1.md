# Nexus Sourcing & Vendor Ecosystem: Layer 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking tracking.

**Goal:** Establish Layer 1 domain primitives for Strategic Sourcing and Vendor roles.

**Architecture:** Pure PHP Layer 1 packages following Nexus standards.

**Tech Stack:** PHP 8.3, PHPUnit.

---

### Task 1: Nexus\Vendor Layer 1 Package

**Files:**
- Create: `packages/Vendor/composer.json`
- Create: `packages/Vendor/phpunit.xml`
- Create: `packages/Vendor/src/Contracts/VendorProfileInterface.php`
- Create: `packages/Vendor/src/Contracts/VendorRepositoryInterface.php`
- Create: `packages/Vendor/src/ValueObjects/VendorStatus.php`
- Create: `packages/Vendor/tests/Unit/VendorProfileTest.php`

- [ ] **Step 1: Create `packages/Vendor/composer.json`**

```json
{
    "name": "nexus/vendor",
    "description": "Nexus Vendor Domain Package",
    "type": "library",
    "license": "MIT",
    "autoload": { "psr-4": { "Nexus\\Vendor\\": "src/" } },
    "require": { "php": "^8.3" }
}
```

- [ ] **Step 2: Create `packages/Vendor/src/ValueObjects/VendorStatus.php`**

```php
<?php
declare(strict_types=1);
namespace Nexus\Vendor\ValueObjects;

enum VendorStatus: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ONBOARDING = 'onboarding';
}
```

- [ ] **Step 3: Create Interfaces**

```php
<?php
// packages/Vendor/src/Contracts/VendorProfileInterface.php
namespace Nexus\Vendor\Contracts;
use Nexus\Vendor\ValueObjects\VendorStatus;
interface VendorProfileInterface {
    public function getId(): string;
    public function getPartyId(): string;
    public function getStatus(): VendorStatus;
}

// packages/Vendor/src/Contracts/VendorRepositoryInterface.php
namespace Nexus\Vendor\Contracts;
interface VendorRepositoryInterface {
    public function findById(string $tenantId, string $id): ?VendorProfileInterface;
    public function save(string $tenantId, VendorProfileInterface $vendor): void;
}
```

- [ ] **Step 4: Write failing test**

```php
// packages/Vendor/tests/Unit/VendorProfileTest.php
namespace Nexus\Vendor\Tests\Unit;
use PHPUnit\Framework\TestCase;
class VendorProfileTest extends TestCase {
    public function test_it_can_be_instantiated() {
        $this->markTestIncomplete('Implement domain model first');
    }
}
```

- [ ] **Step 5: Commit scaffold**

```bash
git add packages/Vendor
git commit -m "feat(vendor): scaffold Layer 1 package"
```

---

### Task 2: Nexus\Sourcing Layer 1 Package

**Files:**
- Create: `packages/Sourcing/composer.json`
- Create: `packages/Sourcing/src/Contracts/QuotationInterface.php`
- Create: `packages/Sourcing/src/Contracts/QuotationRepositoryInterface.php`
- Create: `packages/Sourcing/src/ValueObjects/NormalizationLine.php`

- [ ] **Step 1: Create `packages/Sourcing/composer.json`**

```json
{
    "name": "nexus/sourcing",
    "autoload": { "psr-4": { "Nexus\\Sourcing\\": "src/" } },
    "require": { "php": "^8.3", "nexus/vendor": "*" }
}
```

- [ ] **Step 2: Create `packages/Sourcing/src/ValueObjects/NormalizationLine.php`**

```php
<?php
declare(strict_types=1);
namespace Nexus\Sourcing\ValueObjects;

final readonly class NormalizationLine {
    public function __construct(
        public string $id,
        public string $description,
        public float $quantity,
        public string $uom,
        public float $unitPrice,
        public ?string $rfqLineId = null
    ) {}
}
```

- [ ] **Step 3: Create Interfaces**

```php
<?php
// packages/Sourcing/src/Contracts/QuotationInterface.php
namespace Nexus\Sourcing\Contracts;
interface QuotationInterface {
    public function getId(): string;
    public function getVendorId(): string;
    public function getStatus(): string;
    public function getNormalizationLines(): array;
}

// packages/Sourcing/src/Contracts/QuotationRepositoryInterface.php
namespace Nexus\Sourcing\Contracts;
interface QuotationRepositoryInterface {
    public function findBySourcingEvent(string $tenantId, string $rfqId): array;
    public function findById(string $tenantId, string $id): ?QuotationInterface;
}
```

- [ ] **Step 4: Commit**

```bash
git add packages/Sourcing
git commit -m "feat(sourcing): scaffold Layer 1 package"
```
