# Refactoring Exercise 12: Monorepo Structure with Integration Packages
### 📂 Top-Level Directory Map

```text
nexus/
├── apps/                    # Entry Points (sample applications.)
│   ├── laravel-nexus-saas/               # The sample laravel app
│   └── atomy-api/              # The sample Symfony API app
|
├── packages/                # PURE PHP BUSINESS LOGIC (Framework Agnostic), atomic packages publishable to packagist.org as isolated packages of business logic
│   ├── [Simple Domain Packages]/     # e.g., Uom, Sequencing, Currency, etc (Keep Flat Structure)
│   ├── [Complex Domain Packages]/    # e.g., Inventory, Finance, etc (Adopt DDD Internal Layers)
│   ├── SharedKernel/        # (New Package) Common VOs, Contracts, Traits (extracted out from other packages). No business logic here and this package MUST NOT depend on any other package, but it provides common building blocks for other atomic packages.
│   └── README.md/        # Documentation about package structure guidelines (what simple domain and complex domain packages criterias are and their standard folder structure), architechtural decisions and Do's and Don'ts, summary of packages, etc. (No examples of code snippets here, just guidelines)
|
├── orchestrators/                # PURE PHP BUSINESS LOGIC (Framework Agnostic) that ochestrate 2 or more packages
│   ├── OrderManagement/     
│   ├── TalentManagement/    
│   ├── ProcurementManagement/    # procure -to - pay    
│   └── README.md/        # Documentation about orchestrators package structure guidelines (what are the criterias of an orchestrator are and their standard folder structure), architechtural decisions and Do's and Don'ts, summary of orchestrator packages, etc. (No examples of code snippets here, just guidelines)
│
├── adapters/                # INFRASTRUCTURE BRIDGE (Framework Specific) (as guideline only, will not be implemented at this time)
│   │── Laravel/             # Implementation details for Laravel
│   │   ├── Finance/         # Eloquent Models, Migrations, Jobs for Finance
│   │   └── Inventory/       # Eloquent Models, Migrations for Inventory
│   └── README.md/        # Documentation about adapters package structure guidelines (what are the criterias of an adapters are and their standard folder structure), architechtural decisions and Do's and Don'ts, summary of adapters packages, etc. (No examples of code snippets here, just guidelines)
│
└── ...
```

### Namespace Convention

atomic domain packages -> Nexus\Tenant, Nexus\Tax, Nexus\Identity, etc
orchestrator packages -> Nexus\TalentManagement, Nexus\ProcurementManagement, etc
adapter packages -> Nexus\Laravel\Finance, etc
apps -> based on each application framework namespaces. Out of the scope of this monorepo
-----

### 1\. 🧱 Simple Atomic Packages (Utility/Foundational)

**Usage:** For packages with low cyclomatic complexity or pure utility functions.
**Examples:** `Nexus\Uom`, `Nexus\Sequencing`, `Nexus\Notifier`.

```text
packages/Uom/
├── composer.json
├── docs/                     # Package-level, User Facing, documentation
├── src/
│   ├── Contracts/           # Interfaces
│   ├── Services/            # Stateless Services
│   ├── ValueObjects/        # Immutable Data
│   ├── Exceptions/
│   └── README.md        # Summary of Contracts, Services, VOs, Exceptions in this package, their responsibilities, relationships, etc. table format preferred
├── tests/
├── IMPLEMENTATION_SUMMARY.md   # progressive documentation about what was implemented in this package. What features has been implemented and what is not and any decisions related to this package
├── TODO.md   # list of pending tasks for this package. Bugs, improvements, future planning etc. What has been done must be removed from this list
├── REQUIREMENTS.md   
├── TEST_SUIT_SUMMARY.md   
├── VALUATION_MATRIX.md   
├── LICENSE.md   
└── README.md
```

  * **Rule:** Do not over-engineer these. The flat structure works perfectly here.

-----

### 2\. 🏭 Complex Atomic Packages (Core Domains)

**Usage:** For heavy business domains requiring strict boundaries.
**Examples:** `Nexus\Inventory`, `Nexus\Finance`, `Nexus\Manufacturing`.
**Change:** Adopt internal DDD layering here to manage complexity.

```text
packages/Inventory/
├── composer.json
├── src/
│   ├── Domain/              # THE TRUTH (Pure Logic)
│   │   ├── Entities/        # Pure PHP Entities (Not Eloquent)
│   │   ├── ValueObjects/
│   │   ├── Events/
│   │   ├── Contracts/       # Repository Interfaces defined here
│   │   ├── Services/        # Domain Services (e.g., StockCalculator)
│   │   ├── Policies/        # Business Rules
│   │   └── README.md        # Summary of Domains in this package, their responsibilities, relationships, etc. table format preferred
│   │
│   ├── Application/         # THE USE CASES
│   │   ├── DTOs/            # Input/Output Data Transfer Objects
│   │   ├── Commands/        # e.g., ReceiveStockCommand
│   │   ├── Queries/         # e.g., GetStockLevelQuery
│   │   ├── Handlers/        # Logic that orchestrates Domain Services
│   │   └── README.md        # Summary of Applications in this package, their responsibilities, relationships, etc. table format preferred
│   │
│   └── Infrastructure/      # INTERNAL ADAPTERS (Optional)
│       ├── InMemory/        # In-memory repositories for unit testing
│       ├── Mappers/         # To map Domain Objects to Arrays/DTOs
│       └── README.md         # Summary of Infrastructures in this package, their responsibilities, relationships, etc. table format preferred
│
├── tests/
├── IMPLEMENTATION_SUMMARY.md   # progressive documentation about what was implemented in this package. What features has been implemented and what is not and any decisions related to this package
├── TODO.md   # list of pending tasks for this package. Bugs, improvements, future planning etc. What has been done must be removed from this list
├── REQUIREMENTS.md   
├── TEST_SUIT_SUMMARY.md   
├── VALUATION_MATRIX.md   
├── LICENSE.md   
└── README.md
```

  * **Rule:** **NEVER** put Laravel code (Eloquent/Jobs) here.
  * **Rule:** The `Infrastructure` folder here is for *internal* package infrastructure (like in-memory stores), not database implementations.

-----

### 3\. 🔗 Orchestrator Packages (The "Wiring" Layer)

**Usage:** Orchestrates workflows that cross multiple domains.
**Examples:** `Nexus\OrderManagement` (Wires Sales + Inventory + Finance).

```text
orchestrators/OrderManagement/
├── composer.json            # Requires: Nexus/Sales, Nexus/Inventory
├── src/
│   ├── Workflows/           # Stateful processes (e.g., OrderToCash)
│   ├── Coordinators/        # Stateless glue code
│   └── Listeners/           # Reacts to events from Atomic packages
├── tests/
├── IMPLEMENTATION_SUMMARY.md   # progressive documentation about what was implemented in this package. What features has been implemented and what is not and any decisions related to this package
├── TODO.md   # list of pending tasks for this package. Bugs, improvements, future planning etc. What has been done must be removed from this list
├── REQUIREMENTS.md   
├── TEST_SUIT_SUMMARY.md   
├── VALUATION_MATRIX.md   
├── LICENSE.md  
└── README.md        # Documentation about this package and package's workflows, coordinators, listeners, their responsibilities, relationships, etc. table format preferred
```

  * **Rule:** These packages can depend on multiple Atomic packages. Atomic packages cannot depend on these.

-----

### 4\. 🔌 Adapter Layer (The "Dirty" Layer)

**Usage:** Concrete implementations of the interfaces defined in `packages/`.
**Location:** `adapters/Laravel/<Domain>/` (Keeps the `packages/` folder pure).

```text
adapters/Laravel/Finance/
├── composer.json            # Requires: Nexus/Finance, illuminate/database
├── src/
│   ├── Providers/           # ServiceProviders (Bind Interfaces -> Eloquent)
│   ├── Models/              # Eloquent Models (extends Illuminate\Database\Eloquent\Model)
│   ├── Repositories/        # Concrete Repositories using Eloquent
│   ├── Database/
│   │   ├── Migrations/
│   │   └── Seeders/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Resources/       # API Resources
│   └── Jobs/                # Laravel Queued Jobs
├── tests/                   # Integration tests requiring DB connection
├── IMPLEMENTATION_SUMMARY.md   # progressive documentation about what was implemented in this package. What features has been implemented and what is not and any decisions related to this package
├── TODO.md   # list of pending tasks for this package. Bugs, improvements, future planning etc. What has been done must be removed from this list
├── REQUIREMENTS.md   
├── TEST_SUIT_SUMMARY.md   
├── VALUATION_MATRIX.md   
├── LICENSE.md  
└── README.md        # Documentation about this package and package's Providers, Models, Repositories, etc. table format preferred

```

  * **Rule:** This is the **ONLY** place where `use Illuminate\...` is allowed.

-----

### 📋 Refactoring Guidelines for the Team

1.  **The "Use" Test:**

      * If you can use the package in a generic PHP script without `composer require laravel/framework`, it stays in `packages/`.
      * If it requires `artisan`, `Eloquent`, or `Blade`, it moves to `adapters/Laravel/`.

2.  **Dependency Direction:**

      * `adapters/` -\> depends on -\> `packages/`
      * `packages/` -\> **NEVER** depends on -\> `adapters/`
      * `apps/atomy-api` -\> depends on -\> `adapters/` AND `packages/`

3.  **Shared Kernel (`Nexus\SharedKernel`):**

      * Move all shared VOs (like `TenantId`, `Money`) and shared Contracts (`LoggerInterface`) to `packages/SharedKernel`.
      * All other packages depend on `SharedKernel`.

----
## Attention to Orchestrators/Integration layer of packages

This is the definitive guide for structuring **Layer 3: Orchestration/Integration Packages**.

An Orchestration Package (e.g., `Nexus\OrderManagement`, `Nexus\ProcurementManagement`) is fundamentally different from an Atomic Package because its primary job is **behavior**, not data definition. It does not own the "Truth" (Entities); it owns the "Flow" (Processes).

Here is the standard folder structure and coding guidelines for your team.

### 📂 Folder Structure: Integration Packages

**Example:** `orchestrators/OrderManagement/`

```text
orchestrators/OrderManagement/
├── composer.json             # Depends on: Nexus/Sales, Nexus/Inventory, Nexus/Finance
├── README.md                 # As explained above plus Diagrams of the workflows handled here
├── src/
│   ├── Workflows/            # Stateful Processes (Sagas / State Machines)
│   │   ├── OrderToCash/
│   │   │   ├── Steps/        # Individual steps (e.g., ReserveStockStep)
│   │   │   ├── States/       # Process States (e.g., PaymentPending)
│   │   │   └── OrderToCashWorkflow.php
│   │   └── ReturnMerchandise/
│   │
│   ├── Coordinators/         # Stateless Orchestrators (Synchronous Logic)
│   │   ├── FulfillmentCoordinator.php
│   │   └── RefundCoordinator.php
│   │
│   ├── Listeners/            # Reactive Logic (Event Subscribers)
│   │   ├── TriggerShippingOnPaymentCaptured.php
│   │   └── ReleaseStockOnOrderCancelled.php
│   │
│   ├── Contracts/            # Dependency Inversion for the Workflow
│   │   ├── WorkflowStateRepositoryInterface.php
│   │   └── PaymentGatewayInterface.php (if abstracting specific integration needs)
│   │
│   ├── DTO/                  # Data Transfer Objects specific to the Process
│   │   ├── FulfillmentRequest.php
│   │   └── RefundInstruction.php
│   │
│   └── Exceptions/           # Process Failures
│       ├── FulfillmentFailedException.php
│       └── InconsistentOrderStateException.php
│
└── tests/                    # Integration Tests (Mocking Atomic Packages)
```

-----

### 🧬 Component Definitions

#### 1\. `src/Workflows/` (The Long-Running Processes)

This folder contains logic for processes that happen over time or involve multiple distinct transactional steps (Sagas).

  * **What goes here:** Logic that tracks "State".
  * **Example:** `OrderToCashWorkflow`. It knows that *after* Payment is captured, *then* Shipping must be requested. It handles rollbacks if Shipping fails.

#### 2\. `src/Coordinators/` (The Synchronous Glue)

These are services that simply call multiple Atomic packages in a specific order to achieve a result immediately.

  * **What goes here:** "Facade" style logic.
  * **Example:** `FulfillmentCoordinator::fulfill($orderId)`.
    1.  Calls `Inventory\StockManager::reserve()`.
    2.  Calls `Sales\OrderManager::updateStatus()`.
    3.  Calls `Notifier\NotificationManager::sendConfirmation()`.

#### 3\. `src/Listeners/` (The Reactive Glue)

This is where the Integration Package hooks into the events emitted by Atomic Packages.

  * **Rule:** Atomic packages blindly fire events. Integration packages listen to them to trigger the next step in a workflow.
  * **Example:** `TriggerShippingOnPaymentCaptured` listens to `Nexus\Receivable\Events\PaymentCaptured`.

#### 4\. `src/DTO/` (Process Data)

Integration packages often need to reshape data to pass it from one domain to another.

  * **Example:** `FulfillmentRequest` might take data from a `SalesOrder` entity and format it into exactly what the `Inventory` package needs, stripped of pricing data.

-----

### 📜 Coding Guidelines for Orchestrator Packages

#### ✅ DO:

1.  **Depend on Atomic Packages:** It is required to `use Nexus\Sales\Contracts\...` and `use Nexus\Inventory\Services\...`.
2.  **Inject Interfaces:** Always inject the interfaces defined in the Atomic packages (e.g., `InventoryManagerInterface`), never concrete implementations.
3.  **Own "Process State":** If a workflow needs to save its progress (e.g., "Step 2 of 5 completed"), this package defines the `WorkflowStateRepositoryInterface`.
4.  **Fail Gracefully:** If one Atomic package fails (e.g., Inventory is down/locked), the Integration logic must decide whether to rollback the whole transaction or queue a retry.

#### ❌ DON'T:

1.  **Define Core Entities:** Never define `class Order` or `class Product` here. Those belong in Atomic packages.
2.  **Access Databases Directly:** Do not write SQL or Eloquent queries here. All data access must go through the Atomic Package's Repository Interfaces.
3.  **Contain Framework Code:** No Controllers, no HTTP Routes, no Console Commands. This package is still **Pure PHP**. The Framework Adapter layer (Layer 5) will wrap these Coordinators into Jobs or Controllers.

### 🔍 Integration vs. Atomic Distinction Checklist

If you are unsure where code belongs, ask:

> "Does this code define **what** a thing is, or **how** it moves through the system?"

  * **"What it is"** (Data, Validation, Statuses) $\rightarrow$ **Atomic Package** (e.g., `Nexus\Sales`).
  * **"How it moves"** (If Paid, then Ship; If Cancelled, then Refund) $\rightarrow$ **Integration Package** (e.g., `Nexus\OrderManagement`).