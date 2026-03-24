# Nexus Vendor Implementation Plan (FINAL REVISED)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking tracking.

**Goal:** Establish a stable Vendor identity role separate from the base Party identity.

**Architecture:** Create a Layer 1 `Nexus\Vendor` package for domain logic and a Layer 3 Laravel Adapter for persistence via a Repository.

**Tech Stack:** PHP 8.3, Laravel, Eloquent.

---

### Task 1: Scaffold Nexus\Vendor Layer 1 Package

**Files:**
- Create: `packages/Vendor/src/Contracts/VendorProfileInterface.php`
- Create: `packages/Vendor/src/Contracts/VendorRepositoryInterface.php`
- Create: `packages/Vendor/src/ValueObjects/VendorStatus.php`
- Create: `packages/Vendor/composer.json`

- [ ] **Step 1: Create composer.json**

```json
{
    "name": "nexus/vendor",
    "description": "Nexus Vendor Domain Package",
    "type": "library",
    "license": "MIT",
    "autoload": {
        "psr-4": {
            "Nexus\\Vendor\\": "src/"
        }
    },
    "require": {
        "php": "^8.3"
    }
}
```

- [ ] **Step 2: Create VendorStatus Value Object**

```php
<?php
declare(strict_types=1);
namespace Nexus\Vendor\ValueObjects;

enum VendorStatus: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
```

- [ ] **Step 3: Create VendorProfileInterface**

```php
<?php
declare(strict_types=1);
namespace Nexus\Vendor\Contracts;

use Nexus\Vendor\ValueObjects\VendorStatus;

interface VendorProfileInterface {
    public function getId(): string;
    public function getPartyId(): string;
    public function getStatus(): VendorStatus;
}
```

- [ ] **Step 4: Create VendorRepositoryInterface**

```php
<?php
declare(strict_types=1);
namespace Nexus\Vendor\Contracts;

interface VendorRepositoryInterface {
    public function findById(string $tenantId, string $id): ?VendorProfileInterface;
    public function save(string $tenantId, VendorProfileInterface $vendor): void;
}
```

- [ ] **Step 5: Commit scaffold**

```bash
git add packages/Vendor
git commit -m "feat(vendor): scaffold Layer 1 package"
```

---

### Task 2: Scaffold Laravel Vendor Adapter (Layer 3)

**Files:**
- Create: `adapters/Laravel/Vendor/composer.json`
- Create: `adapters/Laravel/Vendor/src/VendorServiceProvider.php`
- Create: `adapters/Laravel/Vendor/src/Models/EloquentVendorProfile.php`
- Create: `adapters/Laravel/Vendor/src/Repositories/EloquentVendorRepository.php`
- Create: `adapters/Laravel/Vendor/database/migrations/2026_03_24_000001_create_nexus_vendor_profiles_table.php`

- [ ] **Step 1: Create composer.json for Adapter**

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

- [ ] **Step 3: Create migration**

```php
<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('nexus_vendor_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('party_id')->index();
            $table->string('status');
            $table->timestamps();
        });
    }
};
```

- [ ] **Step 4: Create Eloquent Model**

```php
<?php
declare(strict_types=1);
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
```

- [ ] **Step 5: Create Eloquent Repository**

```php
<?php
declare(strict_types=1);
namespace Nexus\Adapter\Laravel\Vendor\Repositories;

use Nexus\Vendor\Contracts\VendorRepositoryInterface;
use Nexus\Vendor\Contracts\VendorProfileInterface;
use Nexus\Adapter\Laravel\Vendor\Models\EloquentVendorProfile;

class EloquentVendorRepository implements VendorRepositoryInterface {
    public function findById(string $tenantId, string $id): ?VendorProfileInterface {
        return EloquentVendorProfile::where('tenant_id', $tenantId)->find($id);
    }
    public function save(string $tenantId, VendorProfileInterface $vendor): void {
        // save implementation
    }
}
```

- [ ] **Step 6: Commit adapter**

```bash
git add adapters/Laravel/Vendor
git commit -m "feat(vendor): add Laravel adapter"
```

---

### Task 3: TDD Vendor Persistence

**Files:**
- Create: `adapters/Laravel/Vendor/tests/Feature/VendorProfileTest.php`

- [ ] **Step 1: Write failing test**

```php
public function test_it_can_find_vendor_profile_via_repository() {
    $profile = EloquentVendorProfile::create([
        'tenant_id' => Str::ulid(),
        'party_id' => Str::ulid(),
        'status' => 'active'
    ]);
    $repo = app(VendorRepositoryInterface::class);
    $found = $repo->findById((string)$profile->tenant_id, (string)$profile->id);
    $this->assertNotNull($found);
}
```

- [ ] **Step 2: Run test (Expect FAIL)**
- [ ] **Step 3: Fix and run test (Expect PASS)**
- [ ] **Step 4: Commit**

```bash
git add adapters/Laravel/Vendor/tests
git commit -m "test(vendor): verify repository"
```
