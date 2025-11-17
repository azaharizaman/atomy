# Nexus Architecture: Nexus Monorepo Architectural Guidelines & Rules

This document outlines the architecture of the `nexus` monorepo. Its purpose is to enforce a clean, scalable, and decoupled component structure. Adhering to these rules is mandatory for all development.

**The Core Philosophy: "Logic in Packages, Implementation in Applications."**

Our architecture is built on one primary concept: **Decoupling**.

  * **`📦 packages/`** contain pure, framework-agnostic business logic. They are the "engines."
  * **`🚀 apps/`** are the runnable applications. They are the "cars" that use the engines.

## Packages:

  * Must be atomic, self-contained units of functionality.
  * Must define their persistence needs via **Contracts (Interfaces)**.
  * Must not contain any database logic or framework-specific code.
  * Must be publishable to Packagist independently.
  * Allowed to depend on other atomic packages in the monorepo
      (e.g., `nexus/uom` can be required by `nexus/inventory`).
  * Allowed to require framework-agnostic libraries (e.g., `psr/log`).
  * Allowed to require low evel libra for Laravel support (e.g., `illuminate/support`). But should avoid framework-specific code.
  * Is mostly engine and logic processing thus persistence agnostic. It does not care how data is stored. It only provides the interfaces. Most case it does not provide any model traits or classes.
  * All auto-incrementing primary keys are ULIDs (UUID v4) strings.

## List of Packages:

   1.  **Nexus\\Tenant:** Multi-Tenancy Management
   2.  **Nexus\\Uom:** Unit of Measurement Management
   3.  **Nexus\\Inventory:** Inventory & Stock Management (Depends on `nexus/uom`)
   4.  **Nexus\\Hrm:** Human Resource Management
   5.  **Nexus\\Workflow:** Workflow Engine & Process Automation
   

## Applications:

   There are two tyes of application in this monorepo:

### **Headless Orchestrator (e.g., `nexus/atomy`):**
    * The main ERP backend developed with aLaravel 12. Its a full application and not a Packagist package. It is the main application that we are developing in this repo, our tangibe product. All the requirements set forth here is to satisfy this application needs and it's user's needs. However, to laverage on the time and efforts given to this project, I want all its features-provideing engine to be reusable and publishable as independent packages. Hence the concept of packages and applications. So when any requirement comes in, we first see if it can be satisfied by an existing package. If not, we create a new package to satisfy the requirement. Then we implement the package's persistence and orchestration logic in this application. Whatever can be packed into a package, should be packed into a package, while making sure the application remains fully functional and the packages comply with the rules set forth here.
        * Implements all Contracts defined by the packages.
        * Contains all database migrations and Eloquent models.
        * Exposes functionality via API/GraphQL.

### **Terminal Client (e.g., `nexus/edward`):**
    * A TUI (Terminal User Interface) client developed with Laravel Artisan commands.
    * Consumes the API provided by the Headless Orchestrator.
    * Must never access the database directly.
    * Must never require any of the atomic packages (like `nexus/tenant`).
    * Is a fully decoupled client application served as a demo application for consuming the headless orchestrator. It shouws the capability of the headless orchestrator to be consumed by any client application, be it a web frontend, mobile app, or terminal client.


-----

## 1\. 🌲 Proposed Monorepo Structure

This visual map illustrates the physical layout of the monorepo. The **`Nexus\Tenant`** package is expanded to serve as the template for all other atomic packages.

```md
nexus/
├── .gitignore
├── composer.json               # Root monorepo workspace configuration (defines 'path' repositories)
├── ARCHITECTURE.md             # (This document)
├── README.md
│
├── 📦 packages/                  # Atomic, publishable PHP packages
│   ├── Hrm/                      # Nexus\Hrm
│   │   ├── composer.json
│   │   └── src/
│   ├── Inventory/                # Nexus\Inventory (Requires 'nexus/uom')
│   │   ├── composer.json
│   │   └── src/
│   ├── Tenant/                   # Nexus\Tenant (The Expanded Template)
│   │   ├── composer.json         # Defines 'nexus/tenant', autoloading
│   │   ├── README.md             # Package-specific documentation
│   │   ├── LICENSE               # Package licensing file
│   │   └── src/                  # The source code root
│   │       ├── Contracts/        # REQUIRED: Interfaces defining persistence needs and data structures
│   │       │   ├── TenantInterface.php         # Data structure contract (What a Tenant IS)
│   │       │   └── TenantRepositoryInterface.php # Persistence contract (How to SAVE/FIND a Tenant)
│   │       ├── Exceptions/       # REQUIRED: Domain-specific exceptions
│   │       │   └── TenantNotFoundException.php
│   │       ├── Services/         # REQUIRED: Core business logic (The "Engine")
│   │       │   └── TenantManager.php           # e.g., createNewTenant(data), switchTenant(id)
│   │       └── NexusTenantServiceProvider.php  # OPTIONAL: Laravel integration point
│   ├── Uom/                      # Nexus\Uom
│   │   ├── composer.json
│   │   └── src/
│   └── Workflow/                 # Nexus\Workflow
│       ├── composer.json
│       └── src/
│
└── 🚀 apps/                      # Deployable applications
    ├── Atomy/                    # Nexus\Atomy (Headless Laravel Orchestrator)
    │   ├── .env.example
    │   ├── artisan
    │   ├── composer.json         # Requires all 'nexus/*' packages
    │   ├── /app
    │   │   ├── /Console
    │   │   ├── /Http/Controllers/Api/
    │   │   ├── /Models           # Eloquent Models (implements package Contracts)
    │   │   └── /Repositories     # Concrete Repository implementations
    │   ├── /config/features.php
    │   ├── /database/migrations/ # ALL migrations for the ERP
    │   └── /routes/api.php
    └── Edward/                   # Edward (Terminal Client)
        ├── .env.example
        ├── artisan
        ├── composer.json
        ├── /app/Console/Commands/
        └── /app/Http/Clients/AtomyApiClient.php

```

-----

## 2\. 📦 The "Packages" Directory (The Logic)

This is the most strictly controlled part of the monorepo. All code in this directory must follow these rules.

> **The Golden Rule:** A package must **never** depend on an application. Applications **always** depend on packages. `Nexus\Tenant` can *never* know what `Nexus\Atomy` is.

### Rules of Atomicity

1.  **Must Be Framework-Agnostic:**

      * Packages must be "pure PHP" or, at most, depend on framework-agnostic libraries (e.g., `psr/log`).
      * **DO NOT** use Laravel-specific classes like `Illuminate\Database\Eloquent\Model`, `Illuminate\Http\Request`, or `Illuminate\Support\Facades\Route`.
      * A light dependency on `illuminate/support` (for Collections, Contracts, etc.) is acceptable if needed, but it should be avoided if possible.

2.  **Must NOT Have Persistence (The "Contract-Driven" Pattern):**

      * Packages **must not** contain database migrations.
      * Packages **must not** contain Eloquent Models or any concrete database logic.
      * Instead, a package *defines its need for persistence* by providing **interfaces (Contracts)** (e.g., `Nexus\Uom\Contracts\UomRepositoryInterface`).

3.  **Must Define Explicit Dependencies:**

      * If a package requires another atomic package, it must be explicitly defined in its own `packages/MyPackage/composer.json`.
      * **Example:** `packages/Inventory/composer.json` must contain `"require": { "nexus/uom": "^1.0" }`.

4.  **Must Be Publishable:**

      * Every package must be a complete, self-contained unit that *could* be published to Packagist at any time. It must have its own `composer.json`, `LICENSE`, and `README.md`.

-----

## 3\. 🚀 The "Apps" Directory (The Implementation)

Applications are the consumers of the packages. They provide the "glue" that connects the logic, the database, and the user.

### Nexus\\Atomy (The Headless Orchestrator)

`Atomy` is the central "headless" ERP backend. It assembles the atomic packages into a single, cohesive application.

  * **Its Job is to Implement Contracts:** `Atomy` contains the *concrete implementations* of the interfaces defined in the packages (e.g., `app/Repositories/DbUomRepository.php`).
  * **Its Job is to Provide Persistence:** `Atomy` is where all **`database/migrations`** and **Eloquent `app/Models`** live. It defines the schema that fulfills the needs of the packages.
  * **Its Job is to Orchestrate Logic:** `Atomy` creates new, higher-level services by combining one or more atomic packages (e.g., `StaffLeaveApprovalWorkflow` using `Nexus\Workflow` and `Nexus\Hrm`).
  * **Its Job is to Be Headless:** All functionality must be exposed via **API/GraphQL**. The `resources/views` directory must remain empty.

### Edward (The Terminal Client)

`Edward` is a TUI (Terminal User Interface) client. It is a consumer of `Atomy`.

  * **Golden Rule:** `Edward` **must never** access the `Atomy` database directly. It **must never** `require` any of the atomic packages (like `nexus/tenant`).
  * **It is Fully Decoupled:** Treat `Edward` as if it were a React frontend or a native mobile app. Its *only* connection to the system is the API provided by `Atomy`.
  * **It is API-Driven:** All its functionality is built on top of an API client (e.g., `app/Http/Clients/AtomyApiClient.php`) that consumes `Atomy`'s endpoints.
  * **Its UI is the Console:** The entire user interface is built using Laravel Artisan commands (e.g., `php artisan edward:dashboard`).

-----

## 4\. 🗺️ Developer Workflow: How to Implement an Atomy Feature

When given a user story for `Atomy`, follow this decision-making process.

**User Story Example:** "As a staff member, I want to view the current stock level of a product in kilograms."

1.  **Question 1: Is the *core logic* missing?**

      * *Analysis:* The core logic for stock management (`StockManager`) and UoM conversion (`UomConverter`) already exists in **`packages/Inventory`** and **`packages/Uom`**. No new atomic package code needed.

2.  **Question 2: How is this logic *stored*?**

      * *Analysis:* We need the `Product` and `Unit` models (which implement the package interfaces). These must exist in `Atomy`.
      * *Action:* Verify that `apps/Atomy/database/migrations/` has the tables and `apps/Atomy/app/Models/` has the corresponding Eloquent models (`Product.php`, `Unit.php`) and Repositories that bind the contracts.

3.  **Question 3: How is this logic *orchestrated*?**

      * *Analysis:* No complex orchestration is needed here; the service call is direct.
      * *Action:* Define a simple service or use the `StockManager` directly in a controller.

4.  **Question 4: How is this logic *exposed*?**

      * *Action:* Go to `apps/Atomy`.
      * Add a new endpoint in `routes/api.php`:
        `Route::get('/v1/inventory/products/{sku}/stock', [InventoryController::class, 'getStock']);`
      * The `InventoryController` will inject the `StockManager` and return the result via JSON.

5.  **Question 5: How does the *user access* this?**

      * *Action:* Go to `apps/Edward`.
      * Add a `getStockLevel` method to `app/Http/Clients/AtomyApiClient.php` to call the new endpoint.
      * Create a new command `app/Console/Commands/ViewStockCommand.php` that uses the client and formats the green text output.

-----

## 5\. 🏗️ Developer Workflow: How to Create a New Atomic Package

When a new business domain is required (e.g., `Nexus\Crm` or `Nexus\AuditLogger`), follow these steps, using the structure of `Nexus\Tenant` as a reference.

1.  **Create Directory:** Create the `packages/AuditLogger` folder.
2.  **Init Composer:** `cd packages/AuditLogger` and run `composer init`.
      * Set the name to `nexus/audit-logger`.
      * Define the PSR-4 autoloader: `"Nexus\\AuditLogger\\": "src/"`.
3.  **Define Contracts:** Define all persistence and model needs as interfaces in `packages/AuditLogger/src/Contracts/`.
      * *Example:* `AuditLogEntryInterface.php`, `AuditLogRepositoryInterface.php`.
4.  **Update Monorepo Root:** Go to the root `nexus/` directory and add your new package to the `repositories` path array.
5.  **Install in Atomy:** `cd apps/Atomy` and run `composer require nexus/audit-logger:"*@dev"`.
6.  **Implement in Atomy:** Go back to `apps/Atomy` and create the necessary migrations, models (`App\Models\AuditLog`), and repositories (`DbAuditLogRepository`) that implement the contracts from `Nexus\AuditLogger`.
7.  **Bind Implementation:** Bind the interface to the concrete implementation in `apps/Atomy/app/Providers/AppServiceProvider.php`.

-----