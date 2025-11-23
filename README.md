# Nexus ERP Monorepo

Nexus is a modern, modular, and headless ERP (Enterprise Resource Planning) system designed with a strict separation of concerns. It leverages a monorepo architecture to decouple pure business logic from framework implementations, ensuring scalability, maintainability, and testability.

## 📖 The Story: "Logic in Packages, Implementation in Applications"

The core philosophy of Nexus is **Decoupling**. We believe that business rules should not be tightly bound to a specific framework or database implementation.

- **Atomic Packages (`packages/`)**: These are the "engines." They contain pure, framework-agnostic business logic. They define *what* needs to be done and *what* data is needed via **Contracts (Interfaces)**, but they don't care *how* it's stored.
- **Applications (`apps/`)**: These are the "cars." They consume the packages, implement the persistence contracts (using Eloquent, etc.), and expose the functionality to the world via APIs.

## 🏗️ Architecture

### 📦 Atomic Packages
Located in `packages/`, these are self-contained units of functionality. They are designed to be:
- **Framework-Agnostic:** Pure PHP logic.
- **Persistence-Agnostic:** No migrations or models. Data access is defined via Interfaces.
- **Publishable:** Each package can be published independently to Packagist.

**Available Packages:**

| Domain | Package | Description |
| :--- | :--- | :--- |
| **Core Infrastructure** | `Nexus\Tenant` | Multi-tenancy context and isolation engine. |
| | `Nexus\Setting` | Global and tenant-specific configuration management. |
| | `Nexus\Identity` | User identity, authentication, and authorization contracts. |
| | `Nexus\Notifier` | Notification dispatching and management. |
| | `Nexus\Scheduler` | Task scheduling and background job management. |
| | `Nexus\EventStream` | Event sourcing and stream processing capabilities. |
| | `Nexus\Audit` | Cryptographically-verified, immutable audit trails for compliance. |
| | `Nexus\AuditLogger` | CRUD operation tracking with user context and retention policies. |
| | `Nexus\Period` | Fiscal period management and transaction validation. |
| | `Nexus\Crypto` | Cryptographic operations and data encryption. |
| | `Nexus\Monitoring` | Telemetry, health checks, alerting, SLO tracking, automated retention. |
| **Finance & Accounting** | `Nexus\Accounting` | Double-entry bookkeeping and general ledger. |
| | `Nexus\Finance` | Financial management and reporting. |
| | `Nexus\Currency` | Currency management and exchange rates. |
| | `Nexus\Payable` | Accounts payable management. |
| | `Nexus\Receivable` | Accounts receivable management. |
| | `Nexus\Budget` | Budget allocation, commitment tracking, and variance analysis. |
| | `Nexus\CashManagement` | Bank account management, reconciliation, and cash flow forecasting. |
| **HR & Payroll** | `Nexus\Hrm` | Human Resource Management (Employees, Departments). |
| | `Nexus\Payroll` | Payroll processing engine. |
| | `Nexus\PayrollMysStatutory` | Malaysian statutory payroll calculations (EPF, SOCSO, PCB). |
| **Operations & Supply Chain** | `Nexus\Inventory` | Inventory and stock management with lot/serial tracking. |
| | `Nexus\Product` | Product catalog management with template-variant architecture. |
| | `Nexus\Procurement` | Purchase requisitions, orders, and goods receipt. |
| | `Nexus\Sales` | Sales quotations, orders, and pricing engine. |
| | `Nexus\Uom` | Unit of Measurement management and conversion. |
| | `Nexus\Warehouse` | Warehouse management and picking optimization. |
| | `Nexus\Routing` | Route optimization and Vehicle Routing Problem (VRP) solver. |
| | `Nexus\Geo` | Geocoding, geofencing, and geospatial calculations. |
| | `Nexus\Assets` | Fixed asset management with progressive delivery tiers. |
| **Workflow & Process** | `Nexus\Workflow` | Workflow engine for process automation and approvals. |
| | `Nexus\Sequencing` | Number sequence generation (e.g., Invoice #). |
| **Data & Integration** | `Nexus\Connector` | External system integration and API connectors. |
| | `Nexus\Import` | Data import utilities with transformation and validation. |
| | `Nexus\Export` | Data export utilities with multi-format generation. |
| | `Nexus\DataProcessor` | ETL and data transformation logic. |
| | `Nexus\Document` | Enterprise document management with versioning. |
| | `Nexus\Storage` | File and asset storage abstraction. |
| | `Nexus\Intelligence` | AI-powered anomaly detection and predictive analytics. |
| **Organization & Master Data** | `Nexus\Backoffice` | Company structure, offices, and departments. |
| | `Nexus\Party` | Master data management for individuals and organizations. |
| **Reporting & Analytics** | `Nexus\Analytics` | Business intelligence and data analysis. |
| | `Nexus\Reporting` | Scheduled report generation and distribution. |
| **Compliance & Governance** | `Nexus\Compliance` | Regulatory compliance and process enforcement. |
| | `Nexus\Statutory` | Statutory reporting and tax requirements. |

### 🚀 Applications
Located in `apps/`, these are the deployable units.

- **Atomy (`apps/Atomy`)**: The Headless Orchestrator.
    - Built with **Laravel**.
    - Implements all package Contracts (Repositories, Models).
    - Manages the Database and Migrations.
    - Exposes a unified **API** for clients.

## 🛠️ Getting Started

### Prerequisites
- PHP 8.3+
- Composer

### Installation

1.  **Clone the repository:**
    ```bash
    git clone <repository-url> nexus
    cd nexus
    ```

2.  **Install Monorepo Dependencies:**
    ```bash
    composer install
    ```

3.  **Setup Atomy (The Application):**
    ```bash
    cd apps/Atomy
    cp .env.example .env
    composer install
    php artisan key:generate
    php artisan migrate
    ```

## 🤝 Contribution

Please refer to [ARCHITECTURE.md](ARCHITECTURE.md) for detailed architectural guidelines and rules for creating new packages or features.

### Key Rules:
1.  **Packages** must never depend on **Applications**.
2.  **Packages** must define persistence needs via **Contracts**.
3.  **Applications** implement the Contracts and provide the Database.
