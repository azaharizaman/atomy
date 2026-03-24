# Nexus Sourcing Implementation Plan (RE-REVISED)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking tracking.

**Goal:** Create a strategic Sourcing domain for RFQs, Quotations, and Normalization.

**Architecture:** Layer 1 `Nexus\Sourcing` for domain, Layer 3 Laravel adapter with Repository.

**Tech Stack:** PHP 8.3, Laravel, Eloquent.

---

### Task 1: Scaffold Nexus\Sourcing Layer 1 Package

**Files:**
- Create: `packages/Sourcing/src/Contracts/QuotationInterface.php`
- Create: `packages/Sourcing/src/Contracts/QuotationRepositoryInterface.php`
- Create: `packages/Sourcing/src/Contracts/AwardInterface.php`
- Create: `packages/Sourcing/src/ValueObjects/NormalizationLine.php`
- Create: `packages/Sourcing/src/ValueObjects/Conflict.php`
- Create: `packages/Sourcing/composer.json`

- [ ] **Step 1: Create composer.json**

```json
{
    "name": "nexus/sourcing",
    "description": "Nexus Sourcing Domain Package",
    "type": "library",
    "license": "MIT",
    "autoload": { "psr-4": { "Nexus\\Sourcing\\": "src/" } },
    "require": { "php": "^8.3", "nexus/vendor": "*" }
}
```

- [ ] **Step 2: Create NormalizationLine and Conflict Value Objects**

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

final readonly class Conflict {
    public function __construct(
        public string $type,
        public string $message
    ) {}
}
```

- [ ] **Step 3: Create Interfaces (Quotation, Award, Repository)**

```php
<?php
declare(strict_types=1);
namespace Nexus\Sourcing\Contracts;

use Nexus\Sourcing\ValueObjects\NormalizationLine;

interface QuotationInterface {
    public function getId(): string;
    public function getVendorId(): string;
    public function getStatus(): string;
    /** @return array<NormalizationLine> */
    public function getNormalizationLines(): array;
}

interface AwardInterface {
    public function getId(): string;
    public function getQuotationId(): string;
    public function getVendorId(): string;
}

interface QuotationRepositoryInterface {
    /** @return array<QuotationInterface> */
    public function findBySourcingEvent(string $tenantId, string $rfqId): array;
    public function findById(string $tenantId, string $id): ?QuotationInterface;
}
```

- [ ] **Step 4: Commit**

```bash
git add packages/Sourcing
git commit -m "feat(sourcing): scaffold Layer 1 package"
```

---

### Task 2: Scaffold Laravel Sourcing Adapter (Layer 3)

**Files:**
- Create: `adapters/Laravel/Sourcing/composer.json`
- Create: `adapters/Laravel/Sourcing/src/SourcingServiceProvider.php`
- Create: `adapters/Laravel/Sourcing/src/Models/EloquentQuotation.php`
- Create: `adapters/Laravel/Sourcing/src/Models/EloquentAward.php`
- Create: `adapters/Laravel/Sourcing/src/Repositories/EloquentQuotationRepository.php`
- Create: `adapters/Laravel/Sourcing/database/migrations/2026_03_24_000002_create_nexus_quotations_table.php`
- Create: `adapters/Laravel/Sourcing/database/migrations/2026_03_24_000003_create_nexus_sourcing_awards_table.php`

- [ ] **Step 1: Create composer.json for Adapter**

```json
{
    "name": "nexus/sourcing-laravel-adapter",
    "autoload": { "psr-4": { "Nexus\\Adapter\\Laravel\\Sourcing\\": "src/" } },
    "extra": { "laravel": { "providers": [ "Nexus\\Adapter\\Laravel\\Sourcing\\SourcingServiceProvider" ] } }
}
```

- [ ] **Step 2: Create ServiceProvider**

```php
<?php
declare(strict_types=1);
namespace Nexus\Adapter\Laravel\Sourcing;

use Illuminate\Support\ServiceProvider;
use Nexus\Sourcing\Contracts\QuotationRepositoryInterface;
use Nexus\Adapter\Laravel\Sourcing\Repositories\EloquentQuotationRepository;

class SourcingServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(QuotationRepositoryInterface::class, EloquentQuotationRepository::class);
    }
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
```

- [ ] **Step 3: Create migrations (Quotations & Awards)**

```php
<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('nexus_quotations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('sourcing_event_id')->index();
            $table->ulid('vendor_id')->index();
            $table->string('status');
            $table->json('normalization_data')->nullable();
            $table->timestamps();
        });

        Schema::create('nexus_sourcing_awards', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('quotation_id')->index();
            $table->ulid('vendor_id')->index();
            $table->ulid('purchase_order_id')->nullable();
            $table->timestamps();
        });
    }
};
```

- [ ] **Step 4: Create Eloquent Models**

```php
<?php
declare(strict_types=1);
namespace Nexus\Adapter\Laravel\Sourcing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Sourcing\Contracts\QuotationInterface;
use Nexus\Sourcing\ValueObjects\NormalizationLine;

class EloquentQuotation extends Model implements QuotationInterface {
    use HasUlids;
    protected $table = 'nexus_quotations';
    protected $casts = ['normalization_data' => 'array'];

    public function getId(): string { return $this->id; }
    public function getVendorId(): string { return $this->vendor_id; }
    public function getStatus(): string { return $this->status; }
    
    public function getNormalizationLines(): array { 
        return array_map(function($line) {
            return new NormalizationLine(
                $line['id'], $line['description'], (float) $line['quantity'],
                $line['uom'], (float) $line['unit_price'], $line['rfq_line_id'] ?? null
            );
        }, $this->normalization_data ?? []);
    }
}
```

- [ ] **Step 5: Create Eloquent Repository**

```php
<?php
declare(strict_types=1);
namespace Nexus\Adapter\Laravel\Sourcing\Repositories;

use Nexus\Sourcing\Contracts\QuotationRepositoryInterface;
use Nexus\Sourcing\Contracts\QuotationInterface;
use Nexus\Adapter\Laravel\Sourcing\Models\EloquentQuotation;

class EloquentQuotationRepository implements QuotationRepositoryInterface {
    public function findBySourcingEvent(string $tenantId, string $rfqId): array {
        return EloquentQuotation::where('tenant_id', $tenantId)
            ->where('sourcing_event_id', $rfqId)
            ->get()
            ->all();
    }
    public function findById(string $tenantId, string $id): ?QuotationInterface {
        return EloquentQuotation::where('tenant_id', $tenantId)->find($id);
    }
}
```

- [ ] **Step 6: Commit adapter**

```bash
git add adapters/Laravel/Sourcing
git commit -m "feat(sourcing): add Laravel adapter"
```
