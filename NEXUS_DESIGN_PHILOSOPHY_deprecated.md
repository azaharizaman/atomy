# 📘 **Nexus ERP – Design Philosophy & Developer Guidelines**

## **Design Philosophy Summary**

### 🎯 **Purpose of the Operations Layer**

The **Operations Layer** (e.g., `HumanResourceOperations`, `AccountingOperations`, `LogisticsOperations`) acts as the **Application Layer** for a specific business suite.
Its job is to **coordinate domain logic**, enforce workflows, and integrate external systems — **without containing business rules**.

The business rules live inside the *Atomic Domain Packages*.
The Operations Layer serves as the orchestrator that connects everything into real-world business processes.

---

# 🧱 **1. Architectural Foundation**

### **Clean Architecture**

We separate the system into clear boundaries:

* **Domain Layer (Atomic Packages):** Pure rules, policies, entities
* **Application Layer (Operations):** Orchestration, UseCases, workflows
* **Infrastructure Layer:** Databases, adapters, external services

This ensures a system that is **flexible, scalable, and resilient to change**.

### **Domain-Driven Design (DDD)**
Each business domain (e.g., Inventory, Finance, HR, Sales) is a standalone module with:

* Entities
* Value Objects
* Policies
* Domain Services
* Interfaces

They are **framework-free** and **publishable independently**.

---

# 🔧 **2. Operations Layer Responsibilities**

### ✔ **UseCases: Application Entrypoints**

Every business action (e.g., Place Order, Process Payroll, Post Journal Entry) is a dedicated class.
UseCases are small, explicit, and easy to test.
Their job is:

* Validate input
* Coordinate domain services
* Trigger workflows
* Persist results
* Return DTOs

UseCases **never** contain business rules.

---

### ✔ **Pipelines: Workflow Engine**

Business processes are often multi-step and rule-heavy.
Pipelines break them into well-defined steps:

* Input validation
* Policy enforcement
* Rule execution
* Persistence
* Notifications

This produces **clarity**, **testability**, and **extensibility** for complex workflows.

---

### ✔ **Services: Cross-Domain Logic**

Services provide reusable logic that does not belong to any single domain.

Examples:

* Regulatory/Compliance resolvers
* Notification dispatchers
* Ranking / Scoring engines
* Integration coordinators
* Progress tracking

They support UseCases & Pipelines but **do not execute domain rules**.

---

### ✔ **Adapters: Integration Layer**

The system uses Hexagonal Architecture (Ports & Adapters).

All external systems connect via adapters:

* IoT Devices
* Banking Interfaces
* Third-party APIs
* Document Storage
* Legacy Systems

Adapters implement interfaces inside `Contracts/`, allowing systems to be swapped easily.

---

# 📚 **3. Atomic Domain Packages**

Each domain is its own package (e.g., `Nexus\Leave`, `Nexus\Inventory`, `Nexus\Finance`).

Each package is:

* Pure business rules
* No orchestration
* No infrastructure
* No dependencies on frameworks, except Nexus\Common
* Publishable & versionable independently
* Stateless, testable on its own

The Operations Layer orchestrates across these domains.

---

# 📐 **4. Why This Architecture Matters**

### **Enterprise-Ready**

Supports multi-country, multi-company, and regulatory differences.

### **Replaceable Integrations**

Devices, bank formats, portals can be swapped by changing adapters.

### **High Testability**

Domains = pure unit tests
Operations = integration tests
Pipelines = behavioural tests

### **Safer Long-Term Evolution**

Changes in one domain do not break others.

### **Clear Developer Responsibility**

* Domain engineers write policies, rules, engines
* Application engineers write UseCases, pipelines, adapters

No confusion, no overlap.

---

# 🏁 **5. Guiding Philosophy**

> **Build domains to be timeless, pure, and reusable.
> Build operations to orchestrate workflows, not rules.
> Build infrastructure to serve domains, never invade them.**

This philosophy ensures the Nexus ERP remains **maintainable**, **modular**, and **scalable** for years to come.

---

# 📘 **Developer Rules & Do / Don’ts Guide**

This guide sets the coding standards and architectural boundaries for all developers working on the Operations Layer and the Atomic Domain Packages.
Following these rules ensures the system remains scalable, maintainable, and clean.

---

# 🧱 **1. Core Principles**

These rules are grounded in:

* **Clean Architecture**
* **Domain-Driven Design**
* **Hexagonal Architecture**
* **Atomic Packages**
* **Workflow Orchestration**
* **Testability & Replaceability**

Every developer must understand these principles before contributing.

---

# ✅ **2. DOs — What You SHOULD Do**

### ✔ **DO keep domain logic inside Atomic Domain Packages**

All business rules, policies, calculations, and behavior must be in:

* Entities
* Value Objects
* Policies
* Domain Services

**Never inside the Operations Layer.**

---

### ✔ **DO write UseCases that only coordinate steps**

UseCases should:

* Validate input
* Call domain services
* Trigger pipelines
* Persist via repositories
* Return DTOs

Keep UseCases small and readable.

---

### ✔ **DO use Pipelines for multi-step workflows**

If a process has **more than 2 steps**, it should be a pipeline.

Examples:

* Order fulfillment flow
* Month-end closing process
* Application approval flow
* Onboarding checklist

Pipelines increase clarity and extendibility.

---

### ✔ **DO depend on interfaces (ports), not implementations**

All integrations must reference contracts:

```
Contracts/DeviceGatewayInterface
Contracts/PaymentGatewayInterface
Contracts/StorageGatewayInterface
```

Adapters implement these interfaces.

---

### ✔ **DO create Services for cross-domain logic**

Services should support UseCases and Pipelines, not contain domain rules.

Examples:

* Tax calculation resolvers
* Risk scoring engines
* Notification dispatchers
* Global compliance checks

---

### ✔ **DO write Exceptions only in the correct layer**

* Domain exceptions inside atomic packages
* Application exceptions inside Operations Layer
* Infrastructure exceptions inside adapters

Never mix them.

---

### ✔ **DO keep domain packages framework-free**

Domain modules must have:

❌ no Laravel
❌ no database
❌ no HTTP
❌ no Carbon
❌ no queues
❌ no logging
❌ no external API calls

Only pure PHP, business rules, and contracts.

---

### ✔ **DO keep the Operations Layer thin and readable**

If it feels “thick,” that logic probably belongs to a domain module or a service.

---

### ✔ **DO enforce immutability inside Value Objects**

VOs should be:

* immutable
* validating themselves
* behavior-rich

---

### ✔ **DO record every workflow as a dedicated UseCase**

One action → one UseCase.

This is how we maintain clarity.

---

### ✔ **DO ensure modules remain independent**

Domain A must not depend on Domain B directly.
Inventory must not depend on Finance directly.

Interactions go **only** through the Operations Layer.

---

# ❌ **3. DON’Ts — What You MUST NOT Do**

### ❌ **DON’T put business rules inside the Operations Layer**

No policy checks.
No calculation logic.
No scoring logic.
No entitlement rules.
No validation rules (business logic).

These belong to domain modules.

---

### ❌ **DON’T access the database inside domains**

Repositories = interfaces only.
Infrastructure decides implementation.

Never let a domain object touch storage.

---

### ❌ **DON’T embed workflows inside domain services**

Domain services must be deterministic.
Workflows belong to pipelines.

---

### ❌ **DON’T use static helpers for business calculations**

Static helpers become untestable and leak business rules.

Use domain services.

---

### ❌ **DON’T mix concerns**

Examples of what NOT to do:

* Inventory logic inside Finance
* Scoring logic inside Reporting
* Device parsing inside the Domain layer
* Country-specific logic inside generic domain packages

---

### ❌ **DON’T allow “God classes”**

If a class feels large, it is automatically suspicious.
Split responsibilities across pipeline steps, services, or domain policies.

---

### ❌ **DON’T bypass UseCases**

UI, API, or controllers must **never** call domains directly.
Always go through a UseCase.

---

### ❌ **DON’T implement adapters without interfaces**

Every integration must follow:

```
Contract → Adapter → UseCase
```

Never skip the interface.

---

### ❌ **DON’T create cross-domain dependencies**

Domains must remain atomic and publishable individually.

The Operations Layer is the only place where domains interact.

---

### ❌ **DON’T put DTOs inside domain modules**

DTOs live in `Nexus\Common` or the Application Layer, not inside domains.

---

# 🧭 **4. How Developers Decide Where Code Belongs**

Ask yourself:

### **(1) “Is this a business rule?”**

→ Put in **domain package**

### **(2) “Is this a workflow with multiple steps?”**

→ Put in **Pipeline**

### **(3) “Is this a single user-facing action?”**

→ Put in **UseCase**

### **(4) “Is this integrating with devices or external systems?”**

→ Put in **Adapters + Contracts**

### **(5) “Is this cross-domain supportive logic?”**

→ Put in **Services**

### **(6) “Is this storage / framework / DB logic?”**

→ Infrastructure (not in Operations or Domain)

---

# 🏆 **5. Guiding Principles**

> **Separation of domain and orchestration isn’t a luxury —
> it's the foundation of long-term ERP resilience.**

> **Workflows belong in pipelines. Rules belong in domains.
> Integrations belong in adapters.**

> **The system must remain replaceable, testable, and predictable.**
