# Nexus Sourcing & Vendor Ecosystem: Layer 3 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking tracking.

**Goal:** Implement Laravel persistence for Vendor and Sourcing domains.

**Architecture:** Layer 3 Laravel adapters with Eloquent models and Repositories.

**Tech Stack:** PHP 8.3, Laravel 11, Eloquent.

---

### Task 1: Laravel Vendor Adapter

**Files:**
- Create: `adapters/Laravel/Vendor/composer.json`
- Create: `adapters/Laravel/Vendor/src/VendorServiceProvider.php`
- Create: `adapters/Laravel/Vendor/src/Models/EloquentVendorProfile.php`
- Create: `adapters/Laravel/Vendor/src/Repositories/EloquentVendorRepository.php`
- Create: `adapters/Laravel/Vendor/database/migrations/2026_03_24_000001_create_nexus_vendor_profiles_table.php`

- [ ] **Step 1: Create composer.json**

```json
{
    "name": "nexus/vendor-laravel-adapter",
    "autoload": { "psr-4": { "Nexus\\Adapter\\Laravel\\Vendor\\": "src/" } },
    "extra": { "laravel": { "providers": [ "Nexus\\Adapter\\Laravel\\Vendor\\VendorServiceProvider" ] } }
}
```

- [ ] **Step 2: Create ServiceProvider**

```php
<?php
declare(strict_types=1);
namespace Nexus\Adapter\Laravel\Vendor;
use Illuminate\Support\ServiceProvider;
use Nexus\Vendor\Contracts\VendorRepositoryInterface;
use Nexus\Adapter\Laravel\Vendor\Repositories\EloquentVendorRepository;
class VendorServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(VendorRepositoryInterface::class, EloquentVendorRepository::class);
    }
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
```

- [ ] **Step 3: Create Model and Repository**

```php
<?php
// adapters/Laravel/Vendor/src/Models/EloquentVendorProfile.php
namespace Nexus\Adapter\Laravel\Vendor\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Vendor\Contracts\VendorProfileInterface;
use Nexus\Vendor\ValueObjects\VendorStatus;
class EloquentVendorProfile extends Model implements VendorProfileInterface {
    use HasUlids;
    protected $table = 'nexus_vendor_profiles';
    protected $fillable = ['tenant_id', 'party_id', 'status'];
    public function getId(): string { return $this->id; }
    public function getPartyId(): string { return $this->party_id; }
    public function getStatus(): VendorStatus { return VendorStatus::from($this->status); }
}

// adapters/Laravel/Vendor/src/Repositories/EloquentVendorRepository.php
namespace Nexus\Adapter\Laravel\Vendor\Repositories;
use Nexus\Vendor\Contracts\VendorRepositoryInterface;
use Nexus\Vendor\Contracts\VendorProfileInterface;
use Nexus\Adapter\Laravel\Vendor\Models\EloquentVendorProfile;
class EloquentVendorRepository implements VendorRepositoryInterface {
    public function findById(string $tenantId, string $id): ?VendorProfileInterface {
        return EloquentVendorProfile::where('tenant_id', $tenantId)->find($id);
    }
    public function save(string $tenantId, VendorProfileInterface $vendor): void {
        // Implementation
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add adapters/Laravel/Vendor
git commit -m "feat(vendor): add Laravel adapter"
```

---

### Task 2: Laravel Sourcing Adapter

**Files:**
- Create: `adapters/Laravel/Sourcing/composer.json`
- Create: `adapters/Laravel/Sourcing/src/Models/EloquentQuotation.php`
- Create: `adapters/Laravel/Sourcing/src/Repositories/EloquentQuotationRepository.php`

- [ ] **Step 1: Create composer.json**
- [ ] **Step 2: Create Model with Hydration**

```php
<?php
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
        return array_map(fn($l) => new NormalizationLine(...$l), $this->normalization_data ?? []);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add adapters/Laravel/Sourcing
git commit -m "feat(sourcing): add Laravel adapter"
```
