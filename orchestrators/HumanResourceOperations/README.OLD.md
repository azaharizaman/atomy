# 📘 **Nexus ERP – Human Resource (HR) Module**

The **Nexus ERP Human Resource Module** provides a fully modular, domain-driven, and extensible platform for managing the complete employee lifecycle, including leave, attendance, payroll, shift scheduling, and employee profile management.

This module is designed using **Clean Architecture**, **DDD (Domain-Driven Design)**, and **Atomic Package Principles**, allowing every HR domain (e.g. Leave, Attendance, Payroll) to operate independently while being orchestrated through a cohesive application layer.

---

# 🧱 **Architecture Overview**

The HR module is structured into **two layers**:

## **1. Atomic HR Domain Packages (Pure DDD Domain)**

These packages contain all domain entities, value objects, policies, and calculation engines.
They are **framework-free**, **stateless**, and **individually publishable** as standalone components.

Atomic domain packages include:

| Package              | Namespace                    | Purpose                                              |
| -------------------- | ---------------------------- | ---------------------------------------------------- |
| Leave      | `Nexus\Leave`      | Leave rules, entitlements, balances, accrual engines |
| AttendanceManagement | `Nexus\AttendanceManagement` | Time tracking, anomalies, work schedules             |
| PayrollCore          | `Nexus\PayrollCore`          | Payroll rules, formulas, adjustments, payslips       |
| EmployeeProfile      | `Nexus\EmployeeProfile`      | Employee master data, contracts, eligibility         |
| Shift      | `Nexus\Shift`      | Shift templates, assignments, compliance             |

These domain packages do **not** contain use cases, pipelines, or infrastructure implementations.

---

## **2. HumanResourceOperations (Orchestration Layer)**

Namespace: **`Nexus\HumanResourceOperations`**

This layer acts as the **application service layer** for all HR features.
It coordinates logic across domain packages through:

* **UseCases** (entrypoints for business processes)
* **Pipelines** (multi-step workflows)
* **Orchestrators** (cross-domain logic consolidators)
* **Adapters** (integration with external systems)
* **Gateways** (interfaces for devices/APIs)

This ensures the domain packages remain pure, reusable, and loosely coupled.

---

# 🏗️ **Package Structure**

```
Nexus\HumanResourceOperations
├── UseCases/
│   ├── Leave/
│   ├── Attendance/
│   ├── Payroll/
│   └── Employee/
│
├── Pipelines/
│   ├── Leave/
│   ├── Payroll/
│   └── Attendance/
│
├── Services/
│   ├── Leave/
│   ├── Payroll/
│   ├── Attendance/
│   └── Employee/
│
├── Adapters/
│   ├── Leave/
│   ├── Payroll/
│   ├── Attendance/
│   └── Employee/
│
├── Contracts/         # Gateways & external interfaces
└── Exceptions/
```

This structure strictly separates orchestration concerns from domain logic.

---

# 🔄 **Core Workflow Examples**

### **Apply Leave Workflow**

1. User submits leave request → UseCase (`ApplyLeaveHandler`)
2. Pipeline executes:

   * Validate request
   * Run domain policy rules
   * Resolve accrual strategy (country, employee type, company rules)
   * Deduct leave balance
3. Save via domain repository
4. Return standardized result DTO

### **Process Payroll**

1. Triggered manually or scheduled
2. Pipeline:

   * Load payroll period
   * Fetch components and adjustments
   * Calculate: basic pay → OT → allowances → deductions → contributions → net pay
3. Persist payslip via PayrollCore domain repository
4. Publish event or export

### **Attendance Sync**

1. Sync punch logs through adapter (device → DTO)
2. Validate & resolve schedule
3. Apply policies (late, early out, overtime)
4. Persist validated attendance entries

---

# ⚙️ **How the Layers Interact**

```
Application (HumanResourceOperations)
    ↓ Calls
Domain (Atomic Packages: Leave, Payroll, Attendance)
    ↓ Implemented By
Infrastructure (Adapters, Gateways, ERP storage, device connectors)
```

* The application layer **never contains business rules**.
* The domain layer **never knows about persistence, APIs, frameworks, or devices**.
* The infrastructure layer **never contains domain logic**.

This ensures the HR module is maintainable and test-friendly.

---

# 🌍 **Country-Law-Aware Accrual System**

The HR module includes a sophisticated **Accrual Strategy Resolver**, enabling:

* Country-specific leave laws
* Company-level overrides
* Employee contract–based variations
* Dynamic accrual strategies using:

  * Monthly accrual
  * Fixed annual allocation
  * No-deduction leave
  * Hybrid/custom strategies

Integrators may register custom strategies for countries or industries.

---

# 🧩 **Extensibility**

### You can extend the module by adding:

* New domain packages (e.g., Claims, Training, Onboarding)
* New UseCases for workflows
* New adapters for:

  * Time attendance devices
  * Government data sources
  * Payroll bank file exporters
* New policies or accrual strategies

### Atomic packages guarantee:

* Reusability
* Versionability
* Independent releases
* Clean dependency boundaries

---

# 🧪 **Testing Strategy**

Each layer is independently testable:

| Layer      | Testing Approach                             |
| ---------- | -------------------------------------------- |
| Domain     | Pure unit tests (no database needed)         |
| Operations | Integration tests (mock domain repositories) |
| Pipelines  | Behavioural tests (multi-step validation)    |
| Adapters   | Infrastructure-mocked tests                  |
| End-to-End | ERP-wide scenario testing                    |

---

# 🚀 **Why This Architecture Works**

* Reduces coupling between features
* Enables micro-module deployment
* Supports multi-country HR rules
* Cleaner code ownership (domain vs operations vs infrastructure)
* Very high testability
* Safe for future scaling (multi-company, multi-tenant ERP)

---

# 📦 **Installation & Usage (Developer Notes)**

Each domain package is a standalone composer package:

```
composer require azaharizaman/nexus-leave-management
composer require azaharizaman/nexus-attendance-management
composer require azaharizaman/nexus-payroll-core
composer require azaharizaman/nexus-employee-profile
composer require azaharizaman/nexus-shift-management
composer require azaharizaman/nexus-human-resource-operations
```

Then bind repositories & adapters inside your ERP’s DI container.

---

# 📚 **Documentation Links**

* **Leave Management Domain Docs**
* **Attendance Domain Docs**
* **Payroll Core Domain Docs**
* **Employee Profile Domain Docs**
* **Shift Management Docs**
* **Orchestration & UseCase Reference**
* **Accrual Strategy Customization Guide**
* **Country Law Resolver Implementation Guide**

(Each atomic package includes its own `/docs` directory.)

---

# 📞 Support & Contribution

This module is part of the **Nexus ERP Core Framework**.
For issues, feature requests, or contributions, please follow our organizational guidelines and submit requests through the internal engineering channels.