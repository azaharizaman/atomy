# 📘 **Nexus ERP — Human Resource Module**

## **Developer Rules & Do / Don’ts Guide**

This guide sets the coding standards and architectural boundaries for all developers working on the HumanResourceOperations layer and the Atomic HR Domain Packages.
Following these rules ensures the HR module remains scalable, maintainable, and clean.

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

**Never inside HRO.**

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

* Leave application flow
* Payroll processing
* Recruitment interview flow
* Onboarding checklist

Pipelines increase clarity and extendibility.

---

### ✔ **DO depend on interfaces (ports), not implementations**

All integrations must reference contracts:

```
Contracts/AttendanceDeviceGatewayInterface
Contracts/BankExportGatewayInterface
Contracts/DocumentStorageGatewayInterface
```

Adapters implement these interfaces.

---

### ✔ **DO create Services for cross-domain logic**

Services should support UseCases and Pipelines, not contain domain rules.

Examples:

* Country law resolvers
* Sanction scoring
* Interview ranking
* Notification dispatchers

---

### ✔ **DO write Exceptions only in the correct layer**

* Domain exceptions inside atomic packages
* Application exceptions inside HRO
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

### ✔ **DO keep HumanResourceOperations thin and readable**

If it feels “thick,” that logic probably belongs to a domain module or a service.

---

### ✔ **DO enforce immutability inside Value Objects**

VOs should be:

* immutable
* validating themselves
* behavior-rich

---

### ✔ **DO record every HR workflow as a dedicated UseCase**

One action → one UseCase.

This is how we maintain clarity.

---

### ✔ **DO ensure modules remain independent**

Recruitment must not depend on Payroll.
LeaveManagement must not depend on PerformanceReview.
Training must not depend on Onboarding.

Interactions go **only** through HRO.

---

# ❌ **3. DON’Ts — What You MUST NOT Do**

### ❌ **DON’T put business rules inside HumanResourceOperations**

No policy checks.
No accrual logic.
No scoring logic.
No entitlement rules.
No disciplinary rules.

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

### ❌ **DON’T use static helpers for HR calculations**

Static helpers become untestable and leak business rules.

Use domain services.

---

### ❌ **DON’T mix concerns**

Examples of what NOT to do:

* Leave logic inside Payroll
* Recruitment scoring inside PerformanceReview
* Attendance device parsing inside the Attendance domain
* Country-specific logic inside domain packages

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

Never let a domain object touch storage.

---

### ❌ **DON’T create cross-domain dependencies**

Domains must remain atomic and publishable individually.

HRO is the only place where domains interact.

---

### ❌ **DON’T put DTOs inside domain modules**

DTOs live in `Nexus\Common`, not inside domains.

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

→ Infrastructure (not in HRO or domain)

---

# 🏆 **5. Guiding Principles**

> **Separation of domain and orchestration isn’t a luxury —
> it's the foundation of long-term ERP resilience.**

> **Workflows belong in pipelines. Rules belong in domains.
> Integrations belong in adapters.**

> **The system must remain replaceable, testable, and predictable.**

---
