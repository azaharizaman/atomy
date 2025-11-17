# EVERY LINES AND DETAILS IN THIS FILE IS TO BE FULLY UNDERSTOOD AND FOLLOWED BY THE CODING AGENT WHENEVER IT IS WORKING WITHIN THIS MONOREPO. DO NOT SKIM OR IGNORE ANY PART OF THIS FILE.

# GitHub Copilot Instructions for Nexus Monorepo

## Project Overview

You are working on **Nexus**, a modular PHP monorepo for an ERP system built on Laravel 12. This project follows a strict architectural pattern: **"Logic in Packages, Implementation in Applications."**

## Core Philosophy

**Decoupling is mandatory.** The monorepo has two main components:

- **📦 `packages/`**: Framework-agnostic, reusable business logic (the "engines")
- **🚀 `apps/`**: Runnable applications that implement and orchestrate packages (the "cars")

## Directory Structure

```
nexus/
├── packages/               # Atomic, publishable PHP packages
│   ├── Accounting/        # Financial accounting
│   ├── Analytics/         # Business intelligence
│   ├── AuditLogger/       # Audit logging
│   ├── Backoffice/        # Company structure
│   ├── Crm/               # Customer relationship management
│   ├── FieldService/      # Field service management
│   ├── Hrm/               # Human resources
│   ├── Manufacturing/     # Production management
│   ├── Marketing/         # Marketing campaigns
│   ├── OrgStructure/      # Organizational hierarchy
│   ├── Payroll/           # Payroll processing (Malaysia)
│   ├── Procurement/       # Purchase management
│   ├── ProjectManagement/ # Project tracking
│   ├── Sequencing/        # Auto-numbering
│   ├── Tenant/            # Multi-tenancy (if applicable)
│   ├── Uom/               # Unit of measurement
│   ├── Inventory/         # Inventory management
│   ├── Setting/           # Setting management
│   ├── Connector/         # Connector as integration hub engine (if applicable)
│   ├── Storage/           # Storage engine (if applicable)
│   ├── Document/          # Document engine (if applicable)
│   └── Workflow/          # Workflow engine (if applicable)
|   
└── apps/
    ├── Atomy/             # Headless Laravel ERP backend
    └── Edward/            # Terminal UI client (TUI)
```

## Critical Architectural Rules

### 🔑 The Golden Rule: Framework Agnosticism

Your package must be a **pure PHP engine** that is ignorant of the application it runs in.

- **Logic Over Implementation:** The package defines *what* needs to be done (the logic), not *how* it's done (the implementation).
- **NEVER Reference the Framework:** Do not use any classes, facades, or components specific to Laravel (e.g., `Illuminate\Http\Request`, `DB::`, or `Eloquent\Model`).
- **No Persistence:** Packages must not contain database migrations or concrete database querying logic. They only define the **interfaces** needed for persistence.

### When Working in `packages/`

**ALWAYS:**
- Write pure PHP or framework-agnostic code
- Define persistence needs via **Contracts (Interfaces)** only
- Use dependency injection via constructor
- Make packages publishable (include composer.json, LICENSE, README.md)
- Place interfaces in `src/Contracts/`
- Place business logic in `src/Services/`
- Place exceptions in `src/Exceptions/`
- Use **Value Objects** (immutable objects) for specific data types like money, time, or severity levels to enforce business rules early

**NEVER:**
- Use Laravel-specific classes like `Illuminate\Database\Eloquent\Model`, `Illuminate\Http\Request`, or facades
- Include database migrations or schema definitions
- Create Eloquent models or concrete database logic
- Depend on or reference any application code (e.g., `Atomy`, `Edward`)
- Use `Route::`, `DB::`, or any other Laravel facades

**ACCEPTABLE:**
- Light dependency on `illuminate/support` for Collections and Contracts (but avoid if possible)
- Framework-agnostic libraries like `psr/log`
- Requiring other atomic packages (e.g., `nexus/inventory` can require `nexus/uom`)

### 🧱 Package Design Principles

#### Contract-Driven Design

- **Define Needs, Not Solutions:** Use **Interfaces (`Contracts/`)** to define every external dependency your package needs. This includes data structures (`EntityInterface`), persistence (`RepositoryInterface`), and external services (`DataSourceContract`).
- **One Interface Per Responsibility:** Keep interfaces small and focused.
- **The Consumer Implements:** Always remember that the consuming application (`Nexus\Atomy`) is responsible for providing the concrete class that fulfills your package's interface.

#### Clear Separation of Concerns

| Folder | Rule of Thumb | Example Content |
|--------|---------------|-----------------|
| **`src/Services/`** | **Public API:** Only expose the high-level logic the user needs to interact with (e.g., `AuditLogManager::log()`). | Managers, Coordinators, Façade accessors. |
| **`src/Core/`** | **Internal Engine:** Use this optional folder for complex, internal logic that is not part of the public API (e.g., `QueryExecutor.php`, `PipelineEngine.php`). | Internal Contracts, Value Objects, Engine components. |
| **`src/Exceptions/`** | **Domain-Specific Errors:** Create custom exceptions (e.g., `AuditLogNotFoundException`) that communicate specific domain failures to the consuming app. | All exceptions extending PHP's base exceptions. |

#### Dependency Management

- **Limit External Dependencies:** Keep the `composer.json` file as lean as possible. Only include truly necessary dependencies (e.g., a logging PSR interface or a date/time library).
- **Internal Dependencies are Fine:** It's acceptable and often necessary for a package to require another atomic package (e.g., `nexus/inventory` requiring `nexus/uom`). This is how you share logic across the monorepo.
- **NEVER Depend on an App:** Your package must never require or reference code from the `apps/` directory (`Atomy` or `Edward`).

### When Working in `apps/Atomy/`

**Atomy is the headless Laravel orchestrator. This is where implementation happens.**

**ALWAYS:**
- Implement all package Contracts with concrete classes
- Place Eloquent models in `app/Models/` (these implement package interfaces)
- Place repositories in `app/Repositories/` (these implement repository interfaces)
- Create all database migrations in `database/migrations/`
- Bind interfaces to implementations in `app/Providers/AppServiceProvider.php`
- Expose functionality via API/GraphQL routes
- Keep `resources/views/` empty (headless principle)

**NEVER:**
- Create business logic here (it belongs in packages)
- Implement features without checking if logic should be in a package first

### When Working in `apps/Edward/`

**Edward is a Terminal UI client that consumes Atomy's API.**

**ALWAYS:**
- Use `app/Http/Clients/AtomyApiClient.php` to communicate with Atomy
- Build UI using Laravel Artisan commands
- Treat Atomy as a remote API (even though it's in the same monorepo)

**NEVER:**
- Access the Atomy database directly
- Require any atomic packages (like `nexus/tenant`)
- Share models or repositories with Atomy

## Package Structure Template

When creating a new package, use this structure (based on `Nexus\Tenant`):

```
packages/NewPackage/
├── composer.json              # Package definition
├── README.md                  # Package documentation
├── LICENSE                    # Licensing information
└── src/
    ├── Contracts/             # REQUIRED: Interfaces
    │   ├── EntityInterface.php
    │   └── RepositoryInterface.php
    ├── Exceptions/            # REQUIRED: Domain exceptions
    │   └── EntityNotFoundException.php
    ├── Services/              # REQUIRED: Business logic
    │   └── EntityManager.php
    ├── Core/                  # OPTIONAL: Internal engine (see organization rules below)
    │   ├── Engine/
    │   ├── ValueObjects/
    │   └── Entities/
    └── ServiceProvider.php    # OPTIONAL: Laravel integration
```

## Package Organization: When to Use `Core/` Folder

The `Core/` folder is an organizational pattern to **protect the package's internal engine** and clearly distinguish between the **Public API** (what consumers use) and the **Internal Engine** (implementation details).

### ✅ When to Create a `Core/` Folder (Recommended for Complex Packages)

Create a `Core/` folder when your package is **complex** and contains internal components that are essential for the package to work but should **never be accessed directly** by the consuming application.

| Scenario | Example | Files to place in `Core/` |
|----------|---------|---------------------------|
| **High Complexity** | The package implements a complex system (like `Analytics` or `Workflow` engine). | Finite State Machines, Expression Evaluators, Internal Entities, Value Objects. |
| **Internal Contracts** | You need to use interfaces for internal dependency injection that are irrelevant to the consumer. | Contracts used only by `Core/Engine/` components. |
| **Data Protection** | You have **Value Objects** or **Internal Entities** that should be instantiated and handled only by the main **`Services/Manager`**. | `PredictionRequest.php`, `AuditLevel.php`, `QueryDefinition.php`. |
| **Engine Logic** | You want to enforce that the main `Manager` class is merely an **orchestrator** for a more complex component. | `Engine/QueryExecutor.php`, `Engine/PipelineEngine.php`. |

**Example structure for complex package (`Nexus\Analytics`):**

```
src/
├── Contracts/                 # Public API interfaces
│   └── AnalyticsManagerInterface.php
├── Services/                  # Public API services
│   └── AnalyticsManager.php   # Orchestrator only
├── Core/                      # Internal engine (do not expose)
│   ├── Engine/
│   │   ├── QueryExecutor.php
│   │   └── PredictionEngine.php
│   ├── ValueObjects/
│   │   ├── QueryDefinition.php
│   │   └── PredictionRequest.php
│   └── Contracts/             # Internal contracts
│       └── ExecutorInterface.php
└── Exceptions/
```

**In the Nexus monorepo, using a `Core/` folder is highly recommended** for packages like `Analytics`, `Workflow`, `Manufacturing`, and `Inventory` to maintain the **"Pure Logic"** principle.

### ❌ When to Skip the `Core/` Folder (Simple Packages)

Skip the `Core/` folder when your package is **simple** and the distinction between the API and the engine is minimal.

| Scenario | Example | Structure |
|----------|---------|-----------|
| **Low Complexity** | The package performs a few simple, well-defined tasks (like `Uom` or `Tenant`). | All logic can go directly into `src/Services/Manager.php`. |
| **Minimal Files** | The package contains fewer than 10 total files and no internal helper contracts or value objects. | `src/Contracts/`, `src/Exceptions/`, `src/Services/` |

**Example structure for simple package (`Nexus\Tenant`):**

```
src/
├── Contracts/
│   └── TenantManagerInterface.php
├── Exceptions/
│   └── TenantNotFoundException.php
└── Services/
    └── TenantManager.php      # Both API and engine in one file
```

**Rule of thumb:** If your package's main `Manager` class is under 200 lines and doesn't need internal helpers, skip `Core/`. If it exceeds 300 lines or requires internal components, introduce `Core/` for better separation.

## Development Workflows

### Implementing a New Feature in Atomy

Follow this decision tree:

1. **Is core logic missing?** → Create/update package
2. **How is logic stored?** → Create migrations and models in Atomy
3. **How is logic orchestrated?** → Create service/controller in Atomy
4. **How is logic exposed?** → Add API endpoint in Atomy
5. **How does user access it?** → Add command/client method in Edward

### Creating a New Package

1. Create `packages/PackageName/` directory
2. Run `composer init` (set name to `nexus/package-name`)
3. Define PSR-4 autoloader: `"Nexus\\PackageName\\": "src/"`
4. Create Contracts in `src/Contracts/`
5. Create Services in `src/Services/`
6. Update root `composer.json` repositories array
7. Install in Atomy: `composer require nexus/package-name:"*@dev"`
8. Implement contracts in Atomy with migrations, models, and repositories
9. Bind implementations in `AppServiceProvider.php`

## Code Generation Guidelines

### When I Ask for Package Code

Generate:
- Interface definitions with clear docblocks
- Service classes with constructor dependency injection
- Custom exceptions extending base PHP exceptions
- Framework-agnostic validation logic
- Pure PHP business rules

### When I Ask for Atomy Code

Generate:
- Eloquent models implementing package interfaces
- Repository classes implementing repository contracts
- Laravel migrations with proper schema
- API controllers using package services
- Service provider bindings
- API routes in `routes/api.php`

### When I Ask for Edward Code

Generate:
- Artisan commands in `app/Console/Commands/`
- API client methods in `AtomyApiClient.php`
- Terminal UI formatting (colors, tables, menus)
- Input validation and error handling

## Naming Conventions

- **Packages**: PascalCase (e.g., `Tenant`, `AuditLogger`)
- **Composer names**: kebab-case (e.g., `nexus/audit-logger`)
- **Namespaces**: `Nexus\PackageName`
- **Interfaces**: Descriptive with `Interface` suffix (e.g., `TenantRepositoryInterface`)
- **Services**: Domain-specific managers (e.g., `TenantManager`, `StockManager`)
- **Exceptions**: Descriptive with `Exception` suffix (e.g., `TenantNotFoundException`)

## Available Packages

The following packages are planned or under development in this monorepo:

1. **Nexus\Accounting** - Financial accounting, chart of accounts, journal entries, fiscal periods
2. **Nexus\Analytics** - Business intelligence, predictive models, data analytics
3. **Nexus\AuditLogger** - Comprehensive audit logging with CRUD tracking, retention policies
4. **Nexus\Backoffice** - Company structure, offices, departments, staff organizational units
5. **Nexus\Crm** - Customer relationship management, leads, opportunities, sales pipeline
6. **Nexus\FieldService** - Work orders, technicians, service contracts, SLA management
7. **Nexus\Hrm** - Human resource management, leave, attendance, performance reviews
8. **Nexus\Manufacturing** - Bill of materials, work orders, production planning, MRP
9. **Nexus\Marketing** - Campaigns, lead nurturing, A/B testing, GDPR compliance
10. **Nexus\OrgStructure** - Organizational hierarchy and structure management
11. **Nexus\Payroll** - Malaysian payroll processing, EPF, SOCSO, PCB tax calculations
12. **Nexus\Procurement** - Purchase requisitions, POs, goods receipt, 3-way matching
13. **Nexus\ProjectManagement** - Projects, tasks, timesheets, milestones, resource allocation
14. **Nexus\Sequencing** - Auto-numbering with patterns, scopes, and counter management
15. **Nexus\Uom** - Unit of measurement management and conversions
16. **Nexus\Workflow** - Workflow engine, process automation, state machines
17. **Nexus\Tenant** - Multi-tenancy context and isolation engine
18. **Nexus\Inventory** - Inventory and stock management with lot/serial tracking
19. **Nexus\...** - Future packages as needed

## Quality Standards

- Always use strict types: `declare(strict_types=1);`
- All auto-incrementing primary keys are ULIDs (UUID v4) strings.
- Use type hints for all parameters and return types
- Write comprehensive docblocks with `@param`, `@return`, `@throws`
- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Validate inputs in services before processing
- Throw descriptive exceptions for error cases

## ✨ Modern PHP 8.x Standards (Targeting 8.3+)

The coding agent MUST strictly adhere to these modern conventions to reduce boilerplate, enhance type safety, and enforce immutability:

1.  **Constructor Property Promotion:** Use for all injected dependencies and properties initialized in `__construct`.
2.  **`readonly` Modifier:** All properties (especially in Services, Managers, Repositories, and Value Objects) defined via property promotion MUST be declared as `readonly`.
3.  **Native PHP Enums:** Use native `enum` (backed by `int` or `string`) instead of defining constants within classes for fixed value sets (statuses, levels, types).
4.  **`match` Expression:** Use the `match` expression exclusively instead of the traditional `switch` statement.
5.  **New/Throw in Expressions:** Use `new` and `throw` within expressions for simplified conditional object creation and exception handling (e.g., `?? throw new Exception()`).

## Attributes Over DocBlocks (PHP 8.0+)

The agent **MUST** use native PHP Attributes for all metadata configuration, especially in testing and application-level configuration, instead of relying on DocBlock annotations.

* **Testing:** Use `#[DataProvider]`, `#[Group]`, `#[CoversClass]`, `#[Test]`, etc., instead of the corresponding `@annotation` in DocBlocks.
* **Custom Metadata:** If generating custom package configuration (e.g., marking a class as tenant-aware), define and use a dedicated Attribute class (e.g., `#[TenantAware]`) placed in a `src/Attributes/` directory.
* **Laravel Attributes:** When working in `apps/Atomy`, use Laravel's native PHP 8 Attributes for routing, validation, and model casting instead of DocBlock annotations.
* **No DocBlock Annotations:** Avoid using DocBlock annotations for metadata purposes; reserve DocBlocks for descriptive comments only.

-----

## 🚫 Strict Anti-Pattern: Facade & Global Helper Prohibition

The use of Laravel Facades (`Log::`, `Cache::`, `DB::`) and global helpers (`now()`, `config()`, `dd()`, `app()`) is **strictly forbidden** in all code within the `packages/` directory.

### 1. 🛑 Absolute Prohibitions (Zero Tolerance)

The following must **NEVER** appear in any file within the `packages/` directory:

| Forbidden Laravel Artifact | Atomic Replacement Principle |
| :--- | :--- |
| **`Log::...`** (Facade) | Must use an **injected `LoggerInterface`** (from PSR-3). |
| **`Cache::...`** (Facade) | Must use an **injected `CacheRepositoryInterface`** (from package Contracts). |
| **`DB::...`** or **`\Illuminate\Database\...`** | Must use an **injected Repository Interface** (e.g., `UserRepositoryInterface`). |
| **`Config::...`** (Facade) | Must use an **injected `SettingsManager`** (from `Nexus\Setting`). |
| **`Mail::...`** (Facade) | Must use an **injected `NotifierInterface`** (from `Nexus\Notifier`). |
| **`Storage::...`** (Facade) | Must use an **injected `StorageInterface`** (from `Nexus\Storage`). |
| **`Event::...`** (Facade) | Must use an **injected `EventDispatcherInterface`** (PSR-14 or package-specific). |
| **`Queue::...`** (Facade) | Must use an **injected `QueueInterface`** (from package Contracts). |
| **Global Helpers** (`now()`, `today()`, `config()`, `app()`, `dd()`, `response()`, `abort()`, `redirect()`, `session()`, `request()`, `auth()`, `bcrypt()`, `collect()`, `env()`, `old()`, `route()`, `url()`, `view()`) | Must use injected services or native PHP functions/classes. |

-----

### 2. ✅ Required Replacements (The "How To")

When the agent attempts to use a forbidden artifact, it must immediately stop and refactor the code to use the following dependency injection pattern:

#### A. Logging Example

**❌ WRONG (Forbidden):**
```php
use Illuminate\Support\Facades\Log;

public function processData(array $data): void
{
    Log::info('Processing data', ['count' => count($data)]);
}
```

**✅ CORRECT (Framework-Agnostic):**

1. **Modify Constructor:** Inject the required interface.
    ```php
    use Psr\Log\LoggerInterface;

    public function __construct(
        private readonly LoggerInterface $logger // Must be PSR-3 compliant
    ) {}
    ```

2. **Use Injected Dependency:**
    ```php
    public function processData(array $data): void
    {
        $this->logger->info('Processing data', ['count' => count($data)]);
    }
    ```

#### B. Caching Example

**❌ WRONG (Forbidden):**
```php
use Illuminate\Support\Facades\Cache;

public function getTenantConfig(string $tenantId): array
{
    return Cache::remember("tenant.{$tenantId}", 3600, function() use ($tenantId) {
        return $this->fetchFromDatabase($tenantId);
    });
}
```

**✅ CORRECT (Framework-Agnostic):**

1. **Define Contract in Package:**
    ```php
    namespace Nexus\YourPackage\Contracts;

    interface CacheRepositoryInterface
    {
        public function get(string $key, mixed $default = null): mixed;
        public function put(string $key, mixed $value, int $ttl): bool;
        public function remember(string $key, int $ttl, callable $callback): mixed;
        public function forget(string $key): bool;
    }
    ```

2. **Inject and Use in Service:**
    ```php
    use Nexus\YourPackage\Contracts\CacheRepositoryInterface;

    public function __construct(
        private readonly CacheRepositoryInterface $cache
    ) {}

    public function getTenantConfig(string $tenantId): array
    {
        return $this->cache->remember(
            "tenant.{$tenantId}",
            3600,
            fn() => $this->fetchFromDatabase($tenantId)
        );
    }
    ```

3. **Implement in Atomy (`apps/Atomy/app/Repositories/LaravelCacheRepository.php`):**
    ```php
    namespace App\Repositories;

    use Illuminate\Support\Facades\Cache;
    use Nexus\YourPackage\Contracts\CacheRepositoryInterface;

    final class LaravelCacheRepository implements CacheRepositoryInterface
    {
        public function remember(string $key, int $ttl, callable $callback): mixed
        {
            return Cache::remember($key, $ttl, $callback);
        }
        // ... implement other methods
    }
    ```

#### C. Time/Date Example

**❌ WRONG (Forbidden):**
```php
public function isExpired(): bool
{
    return $this->expiresAt < now(); // 'now()' is a Laravel helper
}
```

**✅ CORRECT (Framework-Agnostic):**

1. **Define Clock Contract (Recommended Pattern):**
    ```php
    namespace Nexus\YourPackage\Contracts;

    interface ClockInterface
    {
        public function getCurrentTime(): \DateTimeImmutable;
        public function getCurrentDate(): \DateTimeImmutable;
    }
    ```

2. **Inject and Use in Service:**
    ```php
    use Nexus\YourPackage\Contracts\ClockInterface;

    public function __construct(
        private readonly ClockInterface $clock
    ) {}

    public function isExpired(\DateTimeImmutable $expiresAt): bool
    {
        return $expiresAt < $this->clock->getCurrentTime();
    }
    ```

3. **Or Use Native PHP (Simpler for Basic Cases):**
    ```php
    public function isExpired(\DateTimeImmutable $expiresAt): bool
    {
        return $expiresAt < new \DateTimeImmutable('now');
    }
    ```

**Note:** The `ClockInterface` pattern is crucial for testing time-sensitive logic without relying on the system clock.

#### D. Configuration Example

**❌ WRONG (Forbidden):**
```php
use Illuminate\Support\Facades\Config;

public function getMaxRetries(): int
{
    return config('services.api.max_retries', 3); // Global helper
}
```

**✅ CORRECT (Framework-Agnostic):**

1. **Inject Settings Manager:**
    ```php
    use Nexus\Setting\Services\SettingsManager;

    public function __construct(
        private readonly SettingsManager $settings
    ) {}
    ```

2. **Use Injected Dependency:**
    ```php
    public function getMaxRetries(): int
    {
        return $this->settings->getInt('services.api.max_retries', 3);
    }
    ```

#### E. Database Query Example

**❌ WRONG (Forbidden):**
```php
use Illuminate\Support\Facades\DB;

public function getUserCount(): int
{
    return DB::table('users')->count();
}
```

**✅ CORRECT (Framework-Agnostic):**

1. **Define Repository Contract:**
    ```php
    namespace Nexus\YourPackage\Contracts;

    interface UserRepositoryInterface
    {
        public function count(): int;
        public function findById(string $id): ?UserInterface;
    }
    ```

2. **Inject and Use in Service:**
    ```php
    use Nexus\YourPackage\Contracts\UserRepositoryInterface;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function getUserCount(): int
    {
        return $this->userRepository->count();
    }
    ```

#### F. Collection Helper Example

**❌ WRONG (Acceptable but Discouraged):**
```php
$items = collect([1, 2, 3])->map(fn($n) => $n * 2); // Laravel helper
```

**✅ CORRECT (Framework-Agnostic):**
```php
use Illuminate\Support\Collection;

$items = new Collection([1, 2, 3]);
$result = $items->map(fn($n) => $n * 2);
```

**Note:** If using `Illuminate\Support\Collection` directly, ensure `illuminate/collections` is listed in the package's `composer.json` as a dependency. For ultimate framework agnosticism, use native PHP arrays with `array_map()`, `array_filter()`, etc.

-----

### 3. 🔍 Detection and Self-Correction Protocol

Before committing any code to the `packages/` directory, the agent MUST run this mental checklist:

1. **Facade Scan:** Does the code contain any class name ending in `::` that is not a static method call on a package-owned class?
   - If YES → **STOP. Refactor using dependency injection.**

2. **Global Helper Scan:** Does the code contain any of these function calls: `now()`, `config()`, `app()`, `dd()`, `dump()`, `abort()`, `redirect()`, `session()`, `request()`, `auth()`, `bcrypt()`, `collect()`, `env()`, `old()`, `route()`, `url()`, `view()`?
   - If YES → **STOP. Replace with injected services or native PHP.**

3. **Illuminate Namespace Scan:** Does the code import any class from `Illuminate\` **except** `Illuminate\Support\Collection` (and only if it's in `composer.json`)?
   - If YES → **STOP. Replace with PSR interfaces or package contracts.**

4. **Repository Pattern Check:** Does the code directly query a database (e.g., using PDO, Eloquent, or any ORM)?
   - If YES → **STOP. Define a repository interface and inject it.**

5. **Configuration Hardcoding Check:** Does the code contain any hardcoded configuration values that should be externalized?
   - If YES → **Inject `SettingsManager` and retrieve the value dynamically.**

-----

### 4. 📋 Quick Reference: Common Violations and Fixes

| Violation | Forbidden Code | Correct Replacement |
|-----------|----------------|---------------------|
| **Logging** | `Log::info('message')` | `$this->logger->info('message')` (Inject `LoggerInterface`) |
| **Caching** | `Cache::get('key')` | `$this->cache->get('key')` (Inject `CacheRepositoryInterface`) |
| **Database** | `DB::table('users')->get()` | `$this->repository->getAll()` (Inject `RepositoryInterface`) |
| **Config** | `config('app.timezone')` | `$this->settings->getString('app.timezone')` (Inject `SettingsManager`) |
| **Time** | `now()` | `$this->clock->getCurrentTime()` (Inject `ClockInterface`) or `new \DateTimeImmutable()` |
| **Collections** | `collect([1,2,3])` | `new Collection([1,2,3])` (Import and list in `composer.json`) |
| **Environment** | `env('APP_ENV')` | `$this->settings->getString('app.env')` (Inject `SettingsManager`) |
| **Request Data** | `request()->input('name')` | Pass as method parameter: `public function process(string $name)` |
| **Auth** | `auth()->user()` | `$this->authContext->getCurrentUser()` (Inject `AuthContextInterface`) |
| **Storage** | `Storage::put('file.txt', 'content')` | `$this->storage->write('file.txt', 'content')` (Inject `StorageInterface`) |

-----

### 5. ⚠️ Acceptable Exceptions (Rare Cases)

The following are the **ONLY** acceptable uses of Laravel-specific code in packages:

1. **`Illuminate\Support\Collection`** - Only if explicitly listed in the package's `composer.json` dependencies.
2. **`Illuminate\Contracts\Support\Arrayable`** - For compatibility with Laravel's array conversion.
3. **Package Service Provider** - A package MAY include a `ServiceProvider.php` for Laravel integration, but this file must **only** register bindings and should not contain business logic.

-----

### 6. 🎯 Golden Rule Summary

**If you're working in `packages/`, ask yourself:**
> "Could this code run in Symfony, Slim, or even a plain PHP CLI script without any modifications?"

**If the answer is NO, you're violating the framework-agnostic principle.**

The correct approach is:
1. **Define the need** (via an Interface in `src/Contracts/`)
2. **Use the need** (via constructor injection in `src/Services/`)
3. **Implement the need** (in `apps/Atomy/app/Repositories/` or `app/Services/`)

By explicitly defining these forbidden patterns and providing the precise, contract-driven replacements, the agent will significantly improve its adherence to the framework-agnostic architecture.

-----

## 🧠 Thought Process: Application Service Provider Bindings

The primary goal of an Application Service Provider (in `apps/Atomy/app/Providers`) is to act as the **Orchestrator's Configuration Hub**. It wires the application's unique, concrete implementations to the generic contracts defined in the atomic packages.

### Step 1: Verify the Core Nexus Principle (The "Why")

Ask: **What is the job of an application service provider in Nexus?**

  * **Answer:** Its job is to bind **Package Contracts** to **Application Implementations**. It is *not* responsible for telling Laravel how to resolve classes it already knows how to resolve.
  * **The Check:** If a class is a concrete class defined in a package (`Nexus\Tenant\Services\TenantLifecycleService`), Laravel's auto-resolver (Reflection) can handle it unless a dependency requires an interface that hasn't been bound yet.

-----

### Step 2: The Binding Decision Tree (The "What to Bind")

When looking at a class name in a binding, determine its type and origin:

| Decision Rule | Class Type/Origin | Binding Action in `Atomy` Provider |
| :--- | :--- | :--- |
| **Rule A: The Essential Bindings** | **Interface** (from Package Contracts) | **MANDATORY.** Bind to the application's concrete implementation (`DbTenantRepository::class` or `LaravelCacheRepository::class`). |
| **Rule B: The Package's Default** | **Interface** (from Package Contracts) **$\rightarrow$** **Package Concrete Class** | **MANDATORY.** Use this when the package provides a concrete default that the application wants to use (e.g., `TenantContextInterface` $\rightarrow$ `TenantContextManager`). |
| **Rule C: The Redundant Bindings** | **Concrete Class** (from Package Services, e.g., `TenantLifecycleService`) | **REMOVE (Redundant).** Laravel resolves this automatically via IoC. Explicit binding is only needed for mocking or complex initial setup that belongs in the package's provider. |
| **Rule D: Application Utilities** | **Concrete Class** (from App Services, e.g., `App\Services\FileUploader`) | **OPTIONAL.** Only bind if the class has complex constructor arguments or needs to be a singleton. Often, these are auto-resolvable too. |

-----

### Step 3: Self-Correction Checklist

Before finalizing the `register()` method, run this mental checklist:

1.  **Is every Repository Interface bound?** (e.g., `TenantRepositoryInterface` $\rightarrow$ `DbTenantRepository`). **(Must be YES)**
2.  **Does any bound class originate from the `Nexus\...` namespace and *not* implement a contract?** (e.g., binding `TenantImpersonationService::class`). **(Must be NO)**
3.  **If I remove all bindings to concrete package classes, will the application still run?** (Yes, because Laravel's IoC container will automatically construct them, pulling in the dependencies I correctly bound in Step 1).

**Error Correction Example (Tenant Package):**

The agent previously included:

```php
$this->app->singleton(TenantLifecycleService::class);
```

**Correction:** Apply **Rule C**. `TenantLifecycleService` is a concrete class from the package. It must be **removed**. Its dependencies (which are interfaces like `TenantRepositoryInterface`) are already correctly bound, so Laravel handles the rest.

## Testing Approach

- Package tests should be unit tests (no database)
- Mock repository implementations in package tests
- Atomy tests can be feature tests (with database)
- Test contract implementations in Atomy
- Test API endpoints in Atomy
- Test commands in Edward

## Key Reminders

1. **Packages are engines**: Pure logic, no persistence
2. **Atomy is the implementation**: Database, models, API
3. **Edward is the demo client**: Terminal UI, no database access
4. **Always check ARCHITECTURE.md** before making architectural decisions
5. **When in doubt, put logic in packages, implementation in apps**

## Important Documentation
- Package README files (e.g., `packages/AuditLogger/README.md`)
- Architecture guidelines (`ARCHITECTURE.md`)
- Requirements and implementation docs in `docs/` folder
- Consolidated Requirements in `REQUIREMENTS.csv`
