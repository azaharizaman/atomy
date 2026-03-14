# 📚 NEXUS FIRST-PARTY PACKAGES REFERENCE GUIDE

**Version:** 1.7  
**Last Updated:** March 15, 2026  
**Target Audience:** Coding Agents & Developers  
**Purpose:** Prevent architectural violations by explicitly documenting available packages and their proper usage patterns.

**Recent Updates (March 15, 2026):**
- **NEW:** Added Project & Delivery Layer 1 packages: `Nexus\Task`, `Nexus\TimeTracking`, `Nexus\Project`, `Nexus\ResourceAllocation`, `Nexus\Milestone` (production-ready, tests and 70%+ coverage target).
- **Task** - Task entity, dependencies, acyclicity (BUS-PRO-0090), Gantt schedule calculation; reusable outside projects.
- **TimeTracking** - Timesheet entry, hours validation (24h/day), approval workflow, immutability (BUS-PRO-0063); reusable.
- **Project** - Project entity, PM assignment (BUS-PRO-0042), completion rules (BUS-PRO-0096), client visibility.
- **ResourceAllocation** - Allocation %, 100% cap per user per day (BUS-PRO-0084), overallocation check (REL-PRO-0402); reusable.
- **Milestone** - Milestone entity, approvals, billing vs budget (BUS-PRO-0077) via BudgetReservationInterface.
- **IMPROVED:** ProjectManagementOperations orchestrator backs contracts with these L1 packages; see orchestrator README.

**Recent Updates (March 1, 2026):**
- **NEW:** Added `Nexus\FieldService` - Work order lifecycle, technician dispatch, and mobile sync (~85% implemented).
- **NEW:** Added `Nexus\Loyalty` - Point calculation engine, tier management, and redemption (100% implemented).
- **NEW:** Added `Nexus\GeneralLedger` - Core financial truth, double-entry validation, and trial balance (100% implemented).
- **NEW:** Added `Nexus\Blockchain` - Immutable ledger integration and smart contract coordination.
- **NEW:** Added `Nexus\ProjectManagementOperations` orchestrator - Cross-package project and resource coordination.
- **NEW:** Added `Nexus\SupplyChainOperations` orchestrator - End-to-end supply chain visibility and optimization.
- **IMPROVED:** Total package count increased to 94 atomic packages.
- **IMPROVED:** Total orchestrator count increased to 19 production-ready orchestrators.

**Recent Updates (February 26, 2026):**
- **ARCHITECTURAL RESTRUCTURING:** Renamed `Nexus\Analytics` to `Nexus\QueryEngine` and `Nexus\Monitoring` to `Nexus\Telemetry`.
- **NEW:** Added `Nexus\DataExchangeOperations` - High-integrity data movement (Import/Export/Storage).
- **NEW:** Added `Nexus\InsightOperations` - Business intelligence pipeline (Query -> Render -> Notify).
- **NEW:** Added `Nexus\IntelligenceOperations` - AI/ML model lifecycle management.
- **NEW:** Added `Nexus\ConnectivityOperations` - Resilient 3rd-party integration gateway.

---

## 🎯 Golden Rule for Implementation

> **BEFORE implementing ANY feature, ALWAYS check this guide first.**
>
> If a first-party Nexus package already provides the capability, you MUST use it via dependency injection. Creating a new implementation is an **architectural violation** unless the package doesn't exist or doesn't cover the use case.

---

## 🚨 Common Violations & How to Avoid Them

| ❌ Violation | ✅ Correct Approach |
|-------------|---------------------|
| Creating custom metrics collector | Use `Nexus\Telemetry\Contracts\TelemetryTrackerInterface` |
| Writing custom audit logging | Use `Nexus\AuditLogger\Contracts\AuditLogManagerInterface` |
| Building notification system | Use `Nexus\Notifier\Contracts\NotificationManagerInterface` |
| Implementing file storage | Use `Nexus\Storage\Contracts\StorageInterface` |
| Creating sequence generator | Use `Nexus\Sequencing\Contracts\SequencingManagerInterface` |
| Managing multi-tenancy context | Use `Nexus\Tenant\Contracts\TenantContextInterface` |
| Handling currency conversions | Use `Nexus\Currency\Contracts\CurrencyManagerInterface` |
| Processing events | Use `Nexus\EventStream` or publish to event dispatcher |
| **Building KYC verification** | **Use `Nexus\KycVerification\Contracts\KycVerificationManagerInterface`** |
| **Creating AML risk scoring** | **Use `Nexus\AmlCompliance\Contracts\AmlRiskAssessorInterface`** |
| **Managing data privacy/consent** | **Use `Nexus\DataPrivacy\Contracts\ConsentManagerInterface`** |
| **Screening against sanctions lists** | **Use `Nexus\Sanctions\Contracts\SanctionsScreenerInterface`** |
| **Custom encryption/hashing** | **Use `Nexus\Crypto\Contracts\CryptoManagerInterface`** |
| **Managing leave/attendance** | **Use `Nexus\HRM\Leave` or `Nexus\HRM\Attendance` packages** |
| **Building custom payment processor** | **Use `Nexus\Payment\Contracts\PaymentManagerInterface`** |
| **Managing loyalty points/tiers** | **Use `Nexus\Loyalty\Contracts\LoyaltyManagerInterface`** |
| **Field service work orders** | **Use `Nexus\FieldService\Contracts\WorkOrderManagerInterface`** |
| **Task / dependency / Gantt** | **Use `Nexus\Task\Contracts\TaskManagerInterface`, `DependencyGraphInterface`, `ScheduleCalculatorInterface`** |
| **Timesheet / time entry** | **Use `Nexus\TimeTracking\Contracts\TimesheetManagerInterface`, `TimesheetApprovalInterface`** |
| **Resource allocation / overallocation** | **Use `Nexus\ResourceAllocation\Contracts\AllocationManagerInterface`, `OverallocationCheckerInterface`** |
| **Project lifecycle** | **Use `Nexus\Project\Contracts\ProjectManagerInterface`** |
| **Milestone / billing vs budget** | **Use `Nexus\Milestone\Contracts\MilestoneManagerInterface`, `BudgetReservationInterface`** |

---

## 📦 Available Packages by Category

### 🔐 **1. Security & Identity**

#### **Nexus\Identity**
**Capabilities:**
- User authentication (session, token, MFA)
- **Authorization (RBAC + ABAC)**
- Role and permission management
- Password hashing and verification
- Token generation and validation
- Session management

#### **Nexus\SSO**
**Capabilities:**
- Single Sign-On (SSO) orchestration (SAML, OAuth2, OIDC)
- Azure AD, Google Workspace, Okta integration
- Just-In-Time (JIT) user provisioning

---

### 📊 **2. Observability & Monitoring**

#### **Nexus\Telemetry** (Formerly Monitoring)
**Capabilities:**
- Metrics tracking (counters, gauges, histograms)
- Performance monitoring (APM, distributed tracing)
- Health checks and availability monitoring
- Prometheus export format

#### **Nexus\AuditLogger**
**Capabilities:**
- Comprehensive audit trail (CRUD operations)
- User action tracking
- Timeline/feed views

---

### 💼 **3. Financial Management**

#### **Nexus\GeneralLedger** ✅ **NEW**
**Capabilities:**
- Double-entry bookkeeping validation
- Multi-currency ledger management
- Real-time balance computation
- Trial balance generation
- **Source of truth for all financial transactions**

#### **Nexus\Payment**
**Capabilities:**
- Complete payment transaction lifecycle
- Disbursement processing and scheduling
- Settlement and reconciliation
- Multi-strategy payment allocation engine (FIFO, LIFO, etc.)

#### **Nexus\Tax**
**Capabilities:**
- Multi-jurisdiction tax calculation (VAT, GST, Sales Tax)
- Tax exemption and reporting
- Withholding tax management

---

### 🛠️ **4. Operations & Logistics**

#### **Nexus\FieldService** ✅ **NEW**
**Capabilities:**
- Work order lifecycle management (NEW → SCHEDULED → IN_PROGRESS → COMPLETED)
- Technician skill-based dispatching and routing
- Mobile sync with offline support and conflict resolution
- Parts consumption and customer signature capture

#### **Nexus\Inventory**
**Capabilities:**
- Multi-valuation stock tracking (FIFO, Weighted Average, Standard Cost)
- Lot tracking with FEFO (First-Expiry-First-Out)
- Serial number management and stock reservations

#### **Nexus\Manufacturing**
**Capabilities:**
- Bill of Materials (BOM) and Routing management
- Work Order processing and MRP Engine
- Capacity planning and demand forecasting

---

### 🤝 **5. CRM & Loyalty**

#### **Nexus\Loyalty** ✅ **NEW**
**Capabilities:**
- Point calculation engine (spend-to-points)
- Multi-tier management (Silver, Gold, Platinum)
- Redemption validation and FIFO point expiry
- Adjustment and manual override handling

#### **Nexus\CRM**
**Capabilities:**
- Lead and opportunity management
- Sales pipeline tracking
- Activity logging and sales forecasting

---

### 📋 **6. Project & Delivery**

#### **Nexus\Task**
**Capabilities:**
- Task entity, assignees, priority, due dates (FUN-PRO-0565)
- Dependency graph and cycle detection (BUS-PRO-0090)
- Early/late schedule calculation for Gantt (FUN-PRO-0570)
- Reusable outside projects (support, campaigns, HR goals)

#### **Nexus\TimeTracking**
**Capabilities:**
- Timesheet entry and validation (FUN-PRO-0566)
- Hours rules: 0–24 per user per day (BUS-PRO-0056)
- Approval workflow; approved timesheets immutable (BUS-PRO-0063, FUN-PRO-0575)
- Reusable for any time-logging context

#### **Nexus\Project**
**Capabilities:**
- Project entity, name, client, start/end, budget (FUN-PRO-0564)
- Project manager required (BUS-PRO-0042)
- Completion rule: status completed only when no incomplete tasks (BUS-PRO-0096)
- Client visibility (BUS-PRO-0106); lessons learned after completed/cancelled (BUS-PRO-0121)

#### **Nexus\ResourceAllocation**
**Capabilities:**
- Allocation percentage per user per day (FUN-PRO-0571)
- 100% cap per user per day (BUS-PRO-0084)
- Overallocation detection and double-booking prevention (REL-PRO-0402)
- Reusable for capacity planning

#### **Nexus\Milestone**
**Capabilities:**
- Milestone entity, approvals, deliverables (FUN-PRO-0569)
- Billing amount vs remaining budget (BUS-PRO-0077) via BudgetReservationInterface
- Revenue recognition (BUS-PRO-0111); resumable approval (REL-PRO-0408)

---

## 🔀 Orchestrators (Layer 2)

Orchestrators coordinate multiple Layer 1 packages to fulfill complex business workflows.

| Orchestrator | Packages Coordinated |
|--------------|----------------------|
| **ProcurementOperations** | Procurement, Inventory, Payable, Tax, Currency |
| **AccountingOperations** | Accounting, GeneralLedger, Period, Tax |
| **HumanResourceOperations** | HRM, Attendance, Leave, Payroll, Identity |
| **QuotationIntelligence** | Procurement, MachineLearning, Currency, Document |
| **SupplyChainOperations** | Inventory, Manufacturing, Warehouse, Procurement |
| **ProjectManagementOperations** | Project, Task, TimeTracking, ResourceAllocation, Milestone, Budget, Receivable, Notifier |
| **InsightOperations** | QueryEngine, Notifier, Reporting |

---

## 📖 How to Use This Guide

1. **Identify your need** (e.g., "I need to calculate points for a purchase").
2. **Search this guide** for the keyword ("Loyalty").
3. **Check the interfaces** listed for that package.
4. **Inject the contract** into your service or orchestrator.
5. **Never import concrete classes** from Layer 1 into Layer 2 or 3.

---

**Package Count:** 99 Atomic Packages (94 + 5 Project & Delivery)  
**Orchestrator Count:** 19 Orchestrators  
**Architecture Version:** 3.0 (Nexus Three-Layer)
