# Apply Package Documentation Standards to Existing Package

**Purpose:** Retroactively apply comprehensive documentation standards to an existing Nexus package to ensure compliance with `.github/prompts/create-package-instruction.prompt.md`.

**When to Use:** When bringing an existing package into compliance with the new documentation standards established in November 2025. When new package is created, the documentation standard must be applied.

**Reference Implementation:** See `packages/EventStream/DOCUMENTATION_COMPLIANCE_SUMMARY.md` for a complete example of this process.

**Gold Standard:** For excellent examples of well-documented packages, refer to: Nexus\Identity or Nexus\EventStream.

---

## 📋 Pre-Compliance Checklist

Before starting, verify:

- [ ] Package exists in `packages/PackageName/` directory
- [ ] Package has `composer.json` with `"php": "^8.3"` requirement
- [ ] Package has existing implementation (src/ folder with contracts and services)
- [ ] Package has existing tests (tests/ folder)
- [ ] You have reviewed `docs/NEXUS_PACKAGES_REFERENCE.md` to understand package purpose

---

## 🎯 Compliance Target: 15 Mandatory Items

Your goal is to ensure the package has all required documentation files:

1. ✅ `composer.json` - Package definition
2. ✅ `LICENSE` - MIT License
3. ✅ `.gitignore` - Package-specific ignores
4. ✅ `README.md` - Comprehensive usage guide
5. ✅ `IMPLEMENTATION_SUMMARY.md` - Implementation tracking
6. ✅ `REQUIREMENTS.md` - Detailed requirements
7. ✅ `TEST_SUITE_SUMMARY.md` - Test documentation
8. ✅ `VALUATION_MATRIX.md` - Package valuation metrics
9. ✅ `docs/getting-started.md` - Quick start guide
10. ✅ `docs/api-reference.md` - API documentation
11. ✅ `docs/integration-guide.md` - Framework integration examples
12. ✅ `docs/examples/basic-usage.php` - Basic code example
13. ✅ `docs/examples/advanced-usage.php` - Advanced code example
14. ✅ No duplicate documentation
15. ✅ No forbidden anti-patterns

---

## 🔄 Step-by-Step Workflow

### Step 1: Assess Current State

**Action:** Check which documentation files already exist.

**Commands:**
```bash
cd packages/PackageName
ls -la
ls -la docs/ 2>/dev/null || echo "docs/ folder does not exist"
```

**Expected Findings:**
- `composer.json`, `LICENSE` - Usually already exist
- `README.md` - Usually exists but may need enhancement
- `IMPLEMENTATION_SUMMARY.md`, `REQUIREMENTS.md` - May exist in root `docs/` folder (wrong location)
- `docs/` folder - Usually missing or incomplete
- `.gitignore`, `VALUATION_MATRIX.md` - Usually missing

---

### Step 2: Create Missing Package Root Files

#### 2.1 Create `.gitignore`

**Action:** Create package-specific `.gitignore` if it doesn't exist.

**File:** `packages/PackageName/.gitignore`

**Content Template:**
```
/vendor/
composer.lock
.phpunit.result.cache
.DS_Store
```

---

#### 2.2 Move/Create `IMPLEMENTATION_SUMMARY.md`

**Check:** Does `docs/PACKAGENAME_IMPLEMENTATION_SUMMARY.md` exist in root `docs/` folder?

**If YES:**
```bash
# move from root docs/ to package root
mv docs/PACKAGENAME_IMPLEMENTATION_SUMMARY.md packages/PackageName/IMPLEMENTATION_SUMMARY.md or similar implementation summary file for this package
```

**If NO:** Create new file using this template:

**File:** `packages/PackageName/IMPLEMENTATION_SUMMARY.md`

**Content Template:**
```markdown
# Implementation Summary: PackageName

**Package:** `Nexus\PackageName`  
**Status:** [Development | Feature Complete | Production Ready] (XX% complete)  
**Last Updated:** YYYY-MM-DD  
**Version:** 1.0.0

## Executive Summary
[Brief overview of what was accomplished and current state]

## Implementation Plan

### Phase 1: Core Implementation
- [x] Task 1
- [x] Task 2
- [ ] Task 3 (In Progress)

### Phase 2: Advanced Features (Planned)
- [ ] Feature 1
- [ ] Feature 2

## What Was Completed
[Detailed list of implemented features with file references]

## What Is Planned for Future
[Features planned but not yet implemented]

## What Was NOT Implemented (and Why)
[List of planned features that were deprioritized or cancelled with justification]

## Key Design Decisions
- **Decision 1:** Rationale
- **Decision 2:** Rationale

## Metrics

### Code Metrics
- Total Lines of Code: X,XXX
- Total Lines of actual code (excluding comments/whitespace): X,XXX
- Total Lines of Documentation: XXX
- Cyclomatic Complexity: XX
- Number of Classes: XX
- Number of Interfaces: XX
- Number of Service Classes: XX
- Number of Value Objects: XX
- Number of Enums: XX

### Test Coverage
- Unit Test Coverage: XX%
- Integration Test Coverage: XX%
- Total Tests: XXX

### Dependencies
- External Dependencies: X
- Internal Package Dependencies: X

## Known Limitations
[Current limitations and constraints]

## Integration Examples
[Links to example implementations in consuming applications]

## References
- Requirements: `REQUIREMENTS.md`
- Tests: `TEST_SUITE_SUMMARY.md`
- API Docs: `docs/api-reference.md`
```

**How to populate metrics:**
```bash
# Count lines of code
find src/ -name "*.php" | xargs wc -l

# Count test files
find tests/ -name "*Test.php" | wc -l

# Run tests to get coverage
composer test
```

---

#### 2.3 Move/Create `REQUIREMENTS.md`

**Check:** Does `docs/REQUIREMENTS_PACKAGENAME.md`, or all related requirements file bearing other name format exist in root `docs/` folder?

**If YES:**
```bash
# Move from root docs/ to package root
mv docs/REQUIREMENTS_PACKAGENAME.md packages/PackageName/REQUIREMENTS.md
```

**If NO:** Create new file using standard format:

**File:** `packages/PackageName/REQUIREMENTS.md` 

**Update:** Remove all requirements that are not relevant to this package i its atomic nature or not confirming to Nexus standards of package atomicity, statelessness, and framework-agnostic design. Any requirements that are in application layer or the ochestration layer should be removed. Only requirements that pertain to the package itself, or its integration with first party Nexus packages should remain

**Content Template:**
```markdown
# Requirements: PackageName

**Total Requirements:** XX

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\PackageName` | Architectural Requirement | ARC-PKG-0001 | Package MUST be framework-agnostic | composer.json | ✅ Complete | No framework deps | YYYY-MM-DD |
| `Nexus\PackageName` | Business Requirements | BUS-PKG-0002 | System MUST validate input X | src/Services/Manager.php | ✅ Complete | - | YYYY-MM-DD |
| `Nexus\PackageName` | Functional Requirement | FUN-PKG-0003 | Provide method to do X | src/Contracts/Interface.php | ✅ Complete | - | YYYY-MM-DD |
```

**Requirement Types:**
- **ARC** - Architectural Requirement
- **BUS** - Business Requirements
- **FUN** - Functional Requirement
- **USE** - User Story
- **PER** - Performance Requirement
- **REL** - Reliability Requirement
- **SEC** - Security Requirement
- **INT** - Integration Requirement
- **USA** - Usability Requirement

**Status Indicators:**
- ✅ Complete
- ⏳ Pending
- 🚧 In Progress
- ❌ Blocked
- 🔄 Refactoring

---

#### 2.4 Create `VALUATION_MATRIX.md`

**Action:** Create comprehensive package valuation document.

**File:** `packages/PackageName/VALUATION_MATRIX.md`

**Content Template:**
```markdown
# Valuation Matrix: PackageName

**Package:** `Nexus\PackageName`  
**Category:** [Core Infrastructure | Business Logic | Integration | Compliance | Analytics | UI/UX]
**Valuation Date:** YYYY-MM-DD  
**Status:** [Development | Beta | Production Ready | Mature]

## Executive Summary

**Package Purpose:** [One-line description]

**Business Value:** [Why this package is valuable]

**Market Comparison:** [Comparable commercial products/services]

---

## Development Investment

### Time Investment
| Phase | Hours | Cost (@ $75/hr) | Notes |
|-------|-------|-----------------|-------|
| Requirements Analysis | XX | $X,XXX | [Notes] |
| Architecture & Design | XX | $X,XXX | [Notes] |
| Implementation | XXX | $XX,XXX | [Notes] |
| Testing & QA | XX | $X,XXX | [Notes] |
| Documentation | XX | $X,XXX | [Notes] |
| Code Review & Refinement | XX | $X,XXX | [Notes] |
| **TOTAL** | **XXX** | **$XX,XXX** | - |

### Complexity Metrics
- **Lines of Code (LOC):** X,XXX lines
- **Cyclomatic Complexity:** XX
- **Number of Interfaces:** XX
- **Number of Service Classes:** XX
- **Number of Value Objects:** XX
- **Number of Enums:** XX
- **Test Coverage:** XX%
- **Number of Tests:** XXX

---

## Technical Value Assessment

### Innovation Score (1-10)
| Criteria | Score | Justification |
|----------|-------|---------------|
| **Architectural Innovation** | X/10 | [Novel patterns, unique approach] |
| **Technical Complexity** | X/10 | [Difficulty to replicate] |
| **Code Quality** | X/10 | [PSR compliance, best practices] |
| **Reusability** | X/10 | [Framework-agnostic, portable] |
| **Performance Optimization** | X/10 | [Efficiency, scalability] |
| **Security Implementation** | X/10 | [Security measures, hardening] |
| **Test Coverage Quality** | X/10 | [Comprehensive, edge cases] |
| **Documentation Quality** | X/10 | [Completeness, clarity] |
| **AVERAGE INNOVATION SCORE** | **X.X/10** | - |

### Technical Debt
- **Known Issues:** [List]
- **Refactoring Needed:** [Areas]
- **Debt Percentage:** X%

---

## Business Value Assessment

### Market Value Indicators
| Indicator | Value | Notes |
|-----------|-------|-------|
| **Comparable SaaS Product** | $XX/month | [Name] |
| **Comparable Open Source** | [Yes/No] | [Name] |
| **Build vs Buy Cost Savings** | $XX,XXX | [Cost to license] |
| **Time-to-Market Advantage** | XX months | [Time saved] |

### Strategic Value (1-10)
| Criteria | Score | Justification |
|----------|-------|---------------|
| **Core Business Necessity** | X/10 | [Essential to operations] |
| **Competitive Advantage** | X/10 | [Unique capabilities] |
| **Revenue Enablement** | X/10 | [Revenue impact] |
| **Cost Reduction** | X/10 | [Cost savings] |
| **Compliance Value** | X/10 | [Regulatory requirements] |
| **Scalability Impact** | X/10 | [Growth support] |
| **Integration Criticality** | X/10 | [Dependencies] |
| **AVERAGE STRATEGIC SCORE** | **X.X/10** | - |

### Revenue Impact
- **Direct Revenue Generation:** $XXX,XXX/year
- **Cost Avoidance:** $XX,XXX/year
- **Efficiency Gains:** XX hours/month saved

---

## Intellectual Property Value

### IP Classification
- **Patent Potential:** [High | Medium | Low | None]
- **Trade Secret Status:** [Description]
- **Copyright:** [Original code, documentation]
- **Licensing Model:** MIT

### Proprietary Value
- **Unique Algorithms:** [List]
- **Domain Expertise Required:** [Specialized knowledge]
- **Barrier to Entry:** [Difficulty to replicate]

---

## Dependencies & Risk Assessment

### External Dependencies
| Dependency | Type | Risk Level | Mitigation |
|------------|------|------------|------------|
| PHP 8.3+ | Language | Low | Standard requirement |

### Internal Package Dependencies
- **Depends On:** [List Nexus packages]
- **Depended By:** [List Nexus packages]
- **Coupling Risk:** [High/Medium/Low]

### Maintenance Risk
- **Bus Factor:** X developers
- **Update Frequency:** [Active | Stable | Legacy]
- **Breaking Change Risk:** [High/Medium/Low]

---

## Market Positioning

### Comparable Products/Services
| Product/Service | Price | Our Advantage |
|-----------------|-------|---------------|
| [Product 1] | $XX/month | [Advantage] |

### Competitive Advantages
1. **[Advantage 1]:** [Description]
2. **[Advantage 2]:** [Description]
3. **[Advantage 3]:** [Description]

---

## Valuation Calculation

### Cost-Based Valuation
```
Development Cost:        $XX,XXX
Documentation Cost:      $X,XXX
Testing & QA Cost:       $X,XXX
Multiplier (IP Value):   X.Xx
----------------------------------------
Cost-Based Value:        $XXX,XXX
```

### Market-Based Valuation
```
Comparable Product Cost: $XX,XXX/year
Lifetime Value (5 years): $XXX,XXX
Customization Premium:   $XX,XXX
----------------------------------------
Market-Based Value:      $XXX,XXX
```

### Income-Based Valuation
```
Annual Cost Savings:     $XX,XXX
Annual Revenue Enabled:  $XX,XXX
Discount Rate:           10%
Projected Period:        5 years
NPV Calculation:         ($XX,XXX) × 3.79
----------------------------------------
NPV (Income-Based):      $XXX,XXX
```

### **Final Package Valuation**
```
Weighted Average:
- Cost-Based (30%):      $XX,XXX
- Market-Based (40%):    $XX,XXX
- Income-Based (30%):    $XX,XXX
========================================
ESTIMATED PACKAGE VALUE: $XXX,XXX
========================================
```

---

## Future Value Potential

### Planned Enhancements
- **Enhancement 1:** Expected value add: $X,XXX
- **Enhancement 2:** Expected value add: $X,XXX

### Market Growth Potential
- **Addressable Market Size:** $XXX million
- **Our Market Share Potential:** X%
- **5-Year Projected Value:** $XXX,XXX

---

## Valuation Summary

**Current Package Value:** $XXX,XXX  
**Development ROI:** XXX%  
**Strategic Importance:** [Critical | High | Medium | Low]  
**Investment Recommendation:** [Expand | Maintain | Monitor | Sunset]

### Key Value Drivers
1. **[Primary Driver]:** [Description]
2. **[Secondary Driver]:** [Description]

### Risks to Valuation
1. **[Risk 1]:** Impact and mitigation
2. **[Risk 2]:** Impact and mitigation

---

**Valuation Prepared By:** Nexus Architecture Team  
**Review Date:** YYYY-MM-DD  
**Next Review:** YYYY-MM-DD (Quarterly)
```

**How to estimate development hours:**
- Review git commit history for the package
- Count lines of code (estimate 50-100 lines per hour)
- Review IMPLEMENTATION_SUMMARY.md for phase tracking
- Conservative estimate: 10-20 hours per major interface + tests

---

### Step 3: Create `docs/` Folder Structure

**Action:** Create comprehensive user-facing documentation.

#### 3.1 Create docs/ directory structure

```bash
cd packages/PackageName
mkdir -p docs/examples
```

---

#### 3.2 Create `docs/getting-started.md`

**File:** `packages/PackageName/docs/getting-started.md`

**Content Template:**
```markdown
# Getting Started with Nexus PackageName

## Prerequisites

- PHP 8.3 or higher
- Composer
- [Any other dependencies]

## Installation

```bash
composer require nexus/package-name:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ [Use case 1]
- ✅ [Use case 2]
- ✅ [Use case 3]

Do NOT use this package for:
- ❌ [Anti-pattern 1]
- ❌ [Anti-pattern 2]

## Core Concepts

### Concept 1: [Name]
[Explanation of key concept]

### Concept 2: [Name]
[Explanation of key concept]

## Basic Configuration

### Step 1: Implement Required Interfaces

```php
// Example of implementing repository interface
namespace App\Repositories;

use Nexus\PackageName\Contracts\RepositoryInterface;

final readonly class DatabaseRepository implements RepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db
    ) {}
    
    public function findById(string $id): EntityInterface
    {
        // Implementation
    }
}
```

### Step 2: Bind Interfaces in Service Provider

```php
// Laravel example
$this->app->singleton(
    RepositoryInterface::class,
    DatabaseRepository::class
);

// Symfony example (services.yaml)
Nexus\PackageName\Contracts\RepositoryInterface:
    class: App\Repositories\DatabaseRepository
```

### Step 3: Use the Package

```php
use Nexus\PackageName\Contracts\ManagerInterface;

final readonly class YourService
{
    public function __construct(
        private ManagerInterface $manager
    ) {}
    
    public function doSomething(): void
    {
        $this->manager->performAction();
    }
}
```

## Your First Integration

[Complete working example showing basic usage]

```php
// Complete example
```

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples
- See [Examples](examples/) for more code samples

## Troubleshooting

### Common Issues

**Issue 1: [Problem description]**
- Cause: [Explanation]
- Solution: [Fix]

**Issue 2: [Problem description]**
- Cause: [Explanation]
- Solution: [Fix]
```

---

#### 3.3 Create `docs/api-reference.md`

**File:** `packages/PackageName/docs/api-reference.md`

**Content Template:**
```markdown
# API Reference: PackageName

## Interfaces

### InterfaceName

**Location:** `src/Contracts/InterfaceName.php`

**Purpose:** [What this interface defines]

**Methods:**

#### methodName()

```php
public function methodName(string $param): ReturnType;
```

**Description:** [What this method does]

**Parameters:**
- `$param` (string) - Description

**Returns:** `ReturnType` - Description

**Throws:**
- `ExceptionType` - When [condition]

**Example:**
```php
$result = $manager->methodName('value');
```

---

[Repeat for each interface]

---

## Services

### ServiceName

**Location:** `src/Services/ServiceName.php`

**Purpose:** [What this service does]

**Constructor Dependencies:**
- `InterfaceA` - Description
- `InterfaceB` - Description

**Public Methods:**

#### methodName()

[Documentation similar to interface methods]

---

## Value Objects

### ValueObjectName

**Location:** `src/ValueObjects/ValueObjectName.php`

**Purpose:** [What this value object represents]

**Properties:**
- `$property1` (type) - Description
- `$property2` (type) - Description

**Methods:**

#### constructor

```php
public function __construct(
    public readonly Type $property1,
    public readonly Type $property2
)
```

**Validation Rules:**
- Rule 1
- Rule 2

**Example:**
```php
$vo = new ValueObjectName(
    property1: $value1,
    property2: $value2
);
```

---

## Enums

### EnumName

**Location:** `src/Enums/EnumName.php`

**Purpose:** [What this enum represents]

**Cases:**
- `Case1` - Description
- `Case2` - Description
- `Case3` - Description

**Example:**
```php
$status = EnumName::Case1;
```

---

## Exceptions

### ExceptionName

**Location:** `src/Exceptions/ExceptionName.php`

**Extends:** `ExceptionType`

**Purpose:** [When this exception is thrown]

**Factory Methods:**

#### factoryMethod()

```php
public static function factoryMethod(string $param): self
```

**Returns:** Exception with message "[Message template]"

**Example:**
```php
throw ExceptionName::factoryMethod('value');
```

---

## Usage Patterns

### Pattern 1: [Name]

[Description of common usage pattern]

```php
// Example code
```

### Pattern 2: [Name]

[Description of common usage pattern]

```php
// Example code
```
```

**How to populate:**
1. List all interfaces from `src/Contracts/`
2. Document all public methods with parameters, return types, exceptions
3. List all value objects from `src/ValueObjects/`
4. List all enums from `src/Enums/`
5. List all exceptions from `src/Exceptions/`

---

#### 3.4 Create `docs/integration-guide.md`

**File:** `packages/PackageName/docs/integration-guide.md`

**Content Template:**
```markdown
# Integration Guide: PackageName

This guide shows how to integrate the PackageName package into your application.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/package-name:"*@dev"
```

### Step 2: Create Database Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            // ... other columns
            $table->timestamps();
        });
    }
};
```

### Step 3: Create Eloquent Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\PackageName\Contracts\EntityInterface;

class Entity extends Model implements EntityInterface
{
    protected $fillable = ['field1', 'field2'];
    
    public function getId(): string
    {
        return $this->id;
    }
    
    // Implement other interface methods
}
```

### Step 4: Create Repository Implementation

```php
<?php

namespace App\Repositories;

use Nexus\PackageName\Contracts\RepositoryInterface;
use Nexus\PackageName\Contracts\EntityInterface;
use App\Models\Entity;

final readonly class EloquentRepository implements RepositoryInterface
{
    public function findById(string $id): EntityInterface
    {
        return Entity::findOrFail($id);
    }
    
    public function save(EntityInterface $entity): void
    {
        $entity->save();
    }
    
    // Implement other methods
}
```

### Step 5: Create Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\PackageName\Contracts\RepositoryInterface;
use Nexus\PackageName\Contracts\ManagerInterface;
use Nexus\PackageName\Services\Manager;
use App\Repositories\EloquentRepository;

class PackageNameServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            RepositoryInterface::class,
            EloquentRepository::class
        );
        
        // Bind manager
        $this->app->singleton(
            ManagerInterface::class,
            Manager::class
        );
    }
}
```

### Step 6: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\PackageNameServiceProvider::class,
],
```

### Step 7: Use in Controller

```php
<?php

namespace App\Http\Controllers;

use Nexus\PackageName\Contracts\ManagerInterface;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    public function __construct(
        private readonly ManagerInterface $manager
    ) {}
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'field1' => 'required|string',
            'field2' => 'required|string',
        ]);
        
        $entity = $this->manager->create($validated);
        
        return response()->json($entity);
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/package-name:"*@dev"
```

### Step 2: Create Doctrine Entity

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\PackageName\Contracts\EntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'table_name')]
class Entity implements EntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;
    
    #[ORM\Column(type: 'string')]
    private string $field1;
    
    // Implement interface methods
}
```

### Step 3: Create Repository

```php
<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\PackageName\Contracts\RepositoryInterface;
use App\Entity\Entity;

class EntityRepository extends ServiceEntityRepository implements RepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entity::class);
    }
    
    public function findById(string $id): EntityInterface
    {
        return $this->find($id) ?? throw new \RuntimeException("Not found");
    }
    
    // Implement other methods
}
```

### Step 4: Configure Services

`config/services.yaml`:

```yaml
services:
    # Repository binding
    Nexus\PackageName\Contracts\RepositoryInterface:
        class: App\Repository\EntityRepository
        
    # Manager binding
    Nexus\PackageName\Contracts\ManagerInterface:
        class: Nexus\PackageName\Services\Manager
        arguments:
            $repository: '@Nexus\PackageName\Contracts\RepositoryInterface'
```

### Step 5: Use in Controller

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nexus\PackageName\Contracts\ManagerInterface;

class EntityController extends AbstractController
{
    public function __construct(
        private readonly ManagerInterface $manager
    ) {}
    
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $entity = $this->manager->create($data);
        
        return $this->json($entity);
    }
}
```

---

## Common Patterns

### Pattern 1: Dependency Injection

Always inject interfaces, never concrete classes:

```php
// ✅ CORRECT
public function __construct(
    private readonly ManagerInterface $manager
) {}

// ❌ WRONG
public function __construct(
    private readonly Manager $manager  // Concrete class!
) {}
```

### Pattern 2: Multi-Tenancy

All repositories should automatically scope by tenant:

```php
public function findAll(): array
{
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    return $this->model
        ->where('tenant_id', $tenantId)
        ->get();
}
```

### Pattern 3: Exception Handling

```php
use Nexus\PackageName\Exceptions\EntityNotFoundException;

try {
    $entity = $this->manager->findById($id);
} catch (EntityNotFoundException $e) {
    // Handle not found
    return response()->json(['error' => 'Not found'], 404);
}
```

---

## Troubleshooting

### Issue: Interface not bound

**Error:**
```
Target interface [Nexus\PackageName\Contracts\RepositoryInterface] is not instantiable.
```

**Solution:**
Ensure you've registered the service provider and bound the interface in your DI container.

**Laravel:**
```php
$this->app->singleton(RepositoryInterface::class, EloquentRepository::class);
```

**Symfony:**
```yaml
Nexus\PackageName\Contracts\RepositoryInterface:
    class: App\Repository\EntityRepository
```

---

### Issue: Tenant context missing

**Error:**
```
Call to a member function getCurrentTenantId() on null
```

**Solution:**
Ensure `Nexus\Tenant` package is installed and tenant middleware is active.

---

## Performance Optimization

### Database Indexes

Always index foreign keys and tenant_id:

```php
$table->string('tenant_id', 26)->index();
$table->foreign('entity_id')->references('id')->on('entities');
```

### Caching

Use repository-level caching for frequently accessed data:

```php
public function findById(string $id): EntityInterface
{
    return $this->cache->remember(
        "entity.{$id}",
        3600,
        fn() => $this->model->findOrFail($id)
    );
}
```

---

## Testing

### Unit Testing Package Logic

```php
use Nexus\PackageName\Services\Manager;
use Nexus\PackageName\Contracts\RepositoryInterface;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    public function test_create_entity(): void
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $manager = new Manager($repository);
        
        $repository->expects($this->once())
            ->method('save');
        
        $entity = $manager->create(['field' => 'value']);
        
        $this->assertInstanceOf(EntityInterface::class, $entity);
    }
}
```

### Integration Testing (Laravel)

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_create_entity_via_api(): void
    {
        $response = $this->postJson('/api/entities', [
            'field1' => 'value1',
            'field2' => 'value2',
        ]);
        
        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'field1', 'field2']);
        
        $this->assertDatabaseHas('entities', [
            'field1' => 'value1',
        ]);
    }
}
```
```

---

#### 3.5 Create `docs/examples/basic-usage.php`

**File:** `packages/PackageName/docs/examples/basic-usage.php`

**Content Template:**
```php
<?php

declare(strict_types=1);

/**
 * Basic Usage Example: PackageName
 * 
 * This example demonstrates:
 * 1. [Feature 1]
 * 2. [Feature 2]
 * 3. [Feature 3]
 */

use Nexus\PackageName\Contracts\ManagerInterface;
use Nexus\PackageName\Contracts\RepositoryInterface;

// ============================================
// Step 1: [Description]
// ============================================

// Example implementation
class Example
{
    public function __construct(
        private readonly ManagerInterface $manager
    ) {}
    
    public function doSomething(): void
    {
        // Implementation
    }
}

// ============================================
// Step 2: [Description]
// ============================================

// More examples

// ============================================
// Usage Example
// ============================================

// Initialize service
$service = new Example($manager);

// Use the service
$result = $service->doSomething();

echo "Result: {$result}\n";

// Expected output:
// Result: [expected value]
```

---

#### 3.6 Create `docs/examples/advanced-usage.php`

**File:** `packages/PackageName/docs/examples/advanced-usage.php`

**Content Template:**
```php
<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: PackageName
 * 
 * This example demonstrates:
 * 1. [Advanced feature 1]
 * 2. [Advanced feature 2]
 * 3. [Advanced feature 3]
 */

// Advanced examples showing complex scenarios
```

---

### Step 4: Update Existing README.md

**Action:** Add Documentation section to existing README.md

**Find the section near the end of README.md** (usually before License section) and add:

```markdown
## 📖 Documentation

### Package Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide with prerequisites, concepts, and first integration
- **[API Reference](docs/api-reference.md)** - Complete documentation of all interfaces, value objects, and exceptions
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples
- **[Basic Usage Example](docs/examples/basic-usage.php)** - Simple usage patterns
- **[Advanced Usage Example](docs/examples/advanced-usage.php)** - Advanced scenarios and patterns

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress and metrics
- `REQUIREMENTS.md` - Detailed requirements
- `TEST_SUITE_SUMMARY.md` - Test coverage and results
- `VALUATION_MATRIX.md` - Package valuation metrics
- See root `ARCHITECTURE.md` for overall system architecture
```

---

### Step 5: Create Compliance Summary

**Action:** Document what was accomplished.

**File:** `packages/PackageName/DOCUMENTATION_COMPLIANCE_SUMMARY.md`

**Content Template:**
```markdown
# PackageName Package Documentation Compliance Summary

**Date:** YYYY-MM-DD  
**Package:** `Nexus\PackageName`  
**Compliance Target:** New Package Documentation Standards

---

## ✅ Compliance Status: [COMPLETE | IN PROGRESS]

[Brief summary of compliance effort]

---

## 📋 Mandatory Files Checklist

| File | Status | Notes |
|------|--------|-------|
| **composer.json** | ✅ Exists | [Notes] |
| **LICENSE** | ✅ Exists | MIT License |
| **.gitignore** | ✅ Created | Package-specific ignores |
| **README.md** | ✅ Updated | Added Documentation section |
| **IMPLEMENTATION_SUMMARY.md** | ✅ Created | [Notes] |
| **REQUIREMENTS.md** | ✅ Created | XX requirements |
| **TEST_SUITE_SUMMARY.md** | [Status] | [Notes] |
| **VALUATION_MATRIX.md** | ✅ Created | Estimated value: $XX,XXX |

---

## 📁 docs/ Folder Structure

| File/Folder | Status | Lines | Notes |
|-------------|--------|-------|-------|
| **docs/getting-started.md** | ✅ Created | XXX | [Notes] |
| **docs/api-reference.md** | ✅ Created | XXX | [Notes] |
| **docs/integration-guide.md** | ✅ Created | XXX | [Notes] |
| **docs/examples/basic-usage.php** | ✅ Created | XXX | [Notes] |
| **docs/examples/advanced-usage.php** | ✅ Created | XXX | [Notes] |

**Total Documentation:** X,XXX+ lines

---

## 📊 Documentation Quality Metrics

### Coverage Analysis
- ✅ **All X interfaces documented**
- ✅ **All X value objects documented**
- ✅ **All exceptions documented**
- ✅ **Framework integration examples**
- ✅ **2 working code examples**

---

## 💰 Valuation Summary

- **Package Value:** $XX,XXX (estimated)
- **Development Investment:** $XX,XXX
- **ROI:** XXX%
- **Strategic Score:** X.X/10

---

## 🎯 Strategic Importance

- **Category:** [Category]
- **Dependencies:** [Packages that depend on this]

---

**Prepared By:** [Your Name/Team]  
**Review Date:** YYYY-MM-DD
```

---

## 🔗 Orchestrator Documentation Standards (Advanced Pattern v1.1)

**For Orchestrators Only** (`orchestrators/*`)

Orchestrators have the same 15 mandatory items as packages, plus additional component-specific documentation.

### Component Documentation Requirements

Each orchestrator component type requires specific documentation in `docs/api-reference.md`:

**Coordinators:**
```markdown
### Coordinators

#### HiringCoordinator

**Purpose:** Orchestrate employee hiring workflow across Identity, Hrm, and Payroll packages.

**Method:** `hire(HiringRequest $request): HiringResult`

**Flow:**
1. Get context via EmployeeProfileProvider
2. Validate via HiringRuleRegistry
3. Execute via EmployeeCreationService
4. Notify via NotificationManagerInterface

**Dependencies (≤5):**
- EmployeeProfileProvider (DataProvider)
- HiringRuleRegistry (Rules)
- EmployeeCreationService (Service)
- NotificationManagerInterface (Package)
- AuditLogManagerInterface (Package)

**Request DTO:** `HiringRequest(userId, departmentId, branchId, salary)`
**Result DTO:** `HiringResult(success, employeeId, errors)`
```

**DataProviders:**
```markdown
### DataProviders

#### EmployeeProfileProvider

**Purpose:** Aggregate employee context from multiple packages.

**Method:** `getContext(HiringRequest $request): EmployeeContext`

**Aggregates:**
- User from Identity package
- Department from Backoffice package
- Branch from Backoffice package

**Returns:** `EmployeeContext` DTO with typed properties
```

**Rules:**
```markdown
### Rules

#### UserActiveRule

**Purpose:** Validate that user is active before hiring.

**Method:** `check(EmployeeContext $context): RuleResult`

**Validation:** User status must be "active"

**Returns:** RuleResult (pass/fail with message)
```

**Services:**
```markdown
### Services

#### SalaryCalculationService

**Purpose:** Calculate final salary with department coefficient and allowances.

**Method:** `calculate(Money $base, Department $dept, array $allowances): Money`

**Business Logic:**
1. Apply department salary coefficient
2. Add allowances
3. Return total salary

**Does NOT:** Fetch data, validate, persist
```

### Orchestrator README.md Required Sections

In addition to standard sections, orchestrators must include:

```markdown
## Workflow Diagrams

[Mermaid sequence diagrams showing cross-package flows]

## Component Architecture

### Advanced Pattern v1.1

This orchestrator follows the Advanced Orchestrator Pattern v1.1:

- **7 Component Types:** Coordinators, DataProviders, Rules, Services, Workflows, Listeners, Exceptions
- **Reference:** AccountingOperations (benchmark), HumanResourceOperations

### Component Breakdown

- **Coordinators (3):** HiringCoordinator, TerminationCoordinator, TransferCoordinator
- **DataProviders (2):** EmployeeProfileProvider, CompensationDataProvider
- **Rules (5):** UserActiveRule, DepartmentCapacityRule, etc.
- **Services (2):** SalaryCalculationService, BenefitsCalculationService
- **Workflows (1):** EmployeeOnboardingWorkflow (stateful, 7-day process)
- **Listeners (2):** TriggerPayrollSetupListener, SendWelcomeEmailListener

## Integration Examples

### Example 1: Hire New Employee

[Complete working code example showing Request DTO → Coordinator → Result DTO]

### Example 2: Employee Transfer

[Complete working code example showing cross-department transfer workflow]
```

### Orchestrator Compliance Checklist

- [ ] All 15 mandatory package items complete
- [ ] **Component documentation** in `docs/api-reference.md` for each component
- [ ] **Workflow diagrams** in README.md (Mermaid sequence diagrams)
- [ ] **Component breakdown** section showing 7 component types
- [ ] **Integration examples** showing complete Request→Coordinator→Result flows
- [ ] **Reference implementations** cited (AccountingOperations, HumanResourceOperations)
- [ ] **Advanced Pattern v1.1** compliance statement

---

## 🎯 Execution Checklist

Use this checklist when applying standards to a package:

- [ ] **Step 1: Assess Current State**
  - [ ] Check existing files in package root
  - [ ] Check for files in root `docs/` folder
  
- [ ] **Step 2: Create Package Root Files**
  - [ ] Create `.gitignore`
  - [ ] Move/Create `IMPLEMENTATION_SUMMARY.md`
  - [ ] Move/Create `REQUIREMENTS.md`
  - [ ] Create `VALUATION_MATRIX.md`
  
- [ ] **Step 3: Create docs/ Folder**
  - [ ] Create `docs/` directory structure
  - [ ] Create `docs/getting-started.md`
  - [ ] Create `docs/api-reference.md`
  - [ ] Create `docs/integration-guide.md`
  - [ ] Create `docs/examples/basic-usage.php`
  - [ ] Create `docs/examples/advanced-usage.php`
  
- [ ] **Step 4: Update README.md**
  - [ ] Add Documentation section with links
  
- [ ] **Step 5: Create Compliance Summary**
  - [ ] Create `DOCUMENTATION_COMPLIANCE_SUMMARY.md`
  
- [ ] **Step 6: Validate**
  - [ ] No duplicate documentation
  - [ ] No forbidden anti-patterns
  - [ ] All links working
  - [ ] Code examples tested

---

## 📊 Success Metrics

A successfully compliant package should have:

- **15/15 mandatory items** complete (100%)
- **1,000+ lines** of documentation in `docs/` folder
- **2+ working code examples**
- **Framework integration guides** (Laravel + Symfony)
- **VALUATION_MATRIX.md** with estimated value
- **No documentation duplication** Documents that you referred to to complete this task must be removed when they no longer serve a purpose or become redundant if they exist elsewhere.
- **Clean directory structure**

---

## 🚫 Common Pitfalls to Avoid

1. **Duplicating content** - Each piece of info should exist in only one place
2. **Creating TODO.md** - Use IMPLEMENTATION_SUMMARY.md instead
3. **Incomplete API reference** - Document ALL public interfaces
4. **Missing integration examples** - Both Laravel and Symfony required
5. **Vague valuation estimates** - Use actual metrics from code/tests
6. **Skipping examples** - Code examples are mandatory
7. **Not updating README.md** - Must link to docs/ folder

---

## 📝 Example Prompt for Coding Agent

When using this prompt with a coding agent, say:

> "Apply package documentation standards to the Nexus\[PackageName] package using the instructions in `.github/prompts/apply-documentation-standards.prompt.md`. Follow the step-by-step workflow to create all mandatory documentation files. Use `packages/EventStream/DOCUMENTATION_COMPLIANCE_SUMMARY.md` as a reference example."

Replace `[PackageName]` with the actual package name (e.g., `Receivable`, `Finance`, `Inventory`, etc.).

---

**Last Updated:** 2025-11-24  
**Reference Implementation:** `packages/EventStream/` (November 2024)  
**Maintained By:** Nexus Architecture Team
