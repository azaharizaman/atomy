# 📚 NEXUS FIRST-PARTY PACKAGES REFERENCE GUIDE

**Version:** 1.11  
**Last Updated (March 24, 2026)**  
**Target Audience:** Coding Agents & Developers  
**Purpose:** Prevent architectural violations by explicitly documenting available packages and their proper usage patterns.

**Recent Updates (March 24, 2026):**
- **NEW:** [Recipe: Webhook-style integrations (composition)](#recipe-webhook-style-integrations-composition) — inbound/outbound flows using existing packages (`Idempotency`, `Outbox`, `Crypto`, `Tenant`, `EventStream`, etc.); **no** standalone `Nexus\Webhook` package is required for these mechanisms.

**Recent Updates (March 22, 2026):**
- **NEW:** Added `Nexus\Outbox` (Layer 1) — transactional outbox model, publish lifecycle (`Pending` → `Sending` → `Sent`|`Failed`), tenant-scoped dedup keys, claim tokens; no queue/HTTP/DB in package.
- **Outbox** — `enqueue` / `claimNextPending` / `markSent` / `markFailed` / `scheduleRetry` with `OutboxStoreInterface` + `OutboxClockInterface`; `InMemoryOutboxStore` for tests; EventStream dual-write mapping documented in package `docs/event-stream-bridge.md`.

**Recent Updates (March 21, 2026):**
- **NEW:** Added `Nexus\Idempotency` (Layer 1) — tenant-scoped command idempotency, fingerprint conflict detection, and replay-safe `ResultEnvelope` storage (no outbox/HTTP/DB in package).
- **Idempotency** — `begin` / `complete` / `fail` with `IdempotencyStoreInterface` + `IdempotencyClockInterface`; `InMemoryIdempotencyStore` for tests.

**Recent Updates (March 20, 2026):**
- **NEW:** Added `Nexus\PolicyEngine` (Layer 1) - deterministic policy evaluation for authorization, workflow, and threshold decisions.
- **PolicyEngine** - supports typed policy model, JSON-to-domain decoding, priority-based evaluation strategies, and tenant-scoped policy lookup.
- **IMPROVED:** Documented multi-layer usage: Layer 1 owns evaluation/validation/decoding; Layer 2 orchestrates context assembly; Layer 3 owns persistence adapters and admin UI.

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
| **Policy evaluation / rule decisions** | **Use `Nexus\PolicyEngine\Contracts\PolicyEngineInterface`, `Nexus\PolicyEngine\Contracts\PolicyRegistryInterface`, `Nexus\PolicyEngine\Contracts\PolicyDefinitionDecoderInterface`** |
| **Command idempotency / replay of mutating API results** | **Use `Nexus\Idempotency\Contracts\IdempotencyServiceInterface`, `Nexus\Idempotency\Contracts\IdempotencyStoreInterface` (or `IdempotencyQueryInterface` / `IdempotencyPersistInterface` where only reads or writes are needed), `Nexus\Idempotency\Contracts\IdempotencyClockInterface`** |
| **Transactional outbox / integration fan-out after commit** | **Use `Nexus\Outbox\Contracts\OutboxServiceInterface`, `Nexus\Outbox\Contracts\OutboxStoreInterface` (or `OutboxQueryInterface` / `OutboxPersistInterface`), `Nexus\Outbox\Contracts\OutboxClockInterface`** |
| **“Webhook platform” (ingress verification, retries, fan-out)** | **Compose** `Nexus\Idempotency`, `Nexus\Outbox`, `Nexus\Crypto`, `Nexus\Tenant`, `Nexus\EventStream` (optional dual-write), `Nexus\AuditLogger` / `Nexus\Telemetry` — see [recipe below](#recipe-webhook-style-integrations-composition). **Do not** introduce a monolithic `Nexus\Webhook` that duplicates these responsibilities. |

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

#### **Nexus\PolicyEngine** ✅ **NEW**
**Capabilities:**
- Deterministic policy evaluation (authorization, workflow, threshold)
- Rule priority and strategy (`first_match`, `collect_all`) execution
- JSON policy decoding into typed domain objects
- Tenant-scoped policy lookup through contracts
- Framework-agnostic Layer 1 evaluator and validator

#### **Nexus\Idempotency** ✅ **NEW**
**Capabilities:**
- Tenant-scoped idempotency keys with operation name + client key (composite identity)
- Request fingerprint equality for intent detection; conflict on key reuse with different fingerprint
- `begin` / `complete` / `fail` lifecycle with opaque `ResultEnvelope` replay for safe retries
- `InMemoryIdempotencyStore` for unit tests; persistence via `IdempotencyStoreInterface` (Layer 3 adapters)

---

### 🔗 **2. Integration & Messaging**

#### **Nexus\Outbox** ✅ **NEW**
**Capabilities:**
- Tenant-scoped enqueue with deduplication key (e.g. EventStream `event_id`)
- Publish lifecycle with claim token and lease expiry between `Sending` and terminal states
- `enqueue` / `claimNextPending` / `markSent` / `markFailed` / `scheduleRetry`
- `InMemoryOutboxStore` for unit tests; persistence via `OutboxStoreInterface` (Layer 3 adapters)
- EventStream bridge documentation for dual-write ordering (append + enqueue in one transaction)

---

#### Recipe: Webhook-style integrations (composition)

**Why this exists:** Inbound HTTP callbacks and outbound HTTP delivery touch **verification**, **idempotency**, **durable send**, and **tenant scope**. Those concerns are already split across first-party packages. A single “webhook” Layer 1 package that also owns retries, signatures, and HTTP types would **overlap** `Nexus\Outbox`, `Nexus\Idempotency`, and `Nexus\Crypto`. Instead, wire the following **roles** (Layer 3 owns controllers, HTTP clients, and queues).

**Inbound (vendor → Nexus): e.g. Stripe, Twilio**

| Step | Package / contract | Role |
|------|---------------------|------|
| Resolve tenant & secrets | `Nexus\Tenant\Contracts\TenantContextInterface` (+ app config / DB in L3) | Know which signing secret and idempotency scope apply. |
| Verify authenticity | `Nexus\Crypto` (e.g. HMAC via `CryptoManagerInterface` / signer capabilities) | Constant-time compare of provider signature over raw body; **no** duplicate “webhook crypto” package. |
| Deduplicate retries | `Nexus\Idempotency` (`IdempotencyServiceInterface`, …) | Use provider event id (or composite key) as `clientKey` + stable `operationRef` so duplicate deliveries replay the same outcome. |
| Optional audit | `Nexus\AuditLogger` | Record receipt / processing for compliance. |
| Domain reaction | Bounded-context services (e.g. `Nexus\Payment`, invoices, etc.) | After verification + idempotency gate, run business logic. |

**Outbound (Nexus → customer URL): e.g. “order confirmed” callback**

| Step | Package / contract | Role |
|------|---------------------|------|
| Same transaction as domain change | `Nexus\EventStream` (optional) + **`Nexus\Outbox`** | Append domain event if applicable; **`enqueue`** an outbox row with tenant-scoped `DedupKey` (see Outbox README). HTTP happens **after** commit. |
| Sign request (if subscribers verify HMAC) | `Nexus\Crypto` | Compute signature over canonical body + timestamp in L3 before POST. |
| Reliability | **`Nexus\Outbox`** | Worker claims pending (`claimNextPending`), POSTs via HTTP client in L3, then `markSent` / `markFailed` / `scheduleRetry`. |
| Metrics / tracing | `Nexus\Telemetry` | Latency, failures, retry counts. |

**Sketch (pseudocode — not copy-paste API surface):**

```text
// Inbound POST handler (L3)
tenantId = TenantContext::current();
rawBody = request->getContent();
if (!Crypto::verifyProviderSignature(rawBody, headers, secret)) { return 401; }

decision = IdempotencyService::begin(tenantId, 'webhook:stripe', providerEventId, fingerprint(rawBody));
if (decision->Replay) { return 200 with cached response body; }
if (decision->InProgress) { return 409; }

try {
    DomainService::applyStripeEvent(parsed(rawBody));
    IdempotencyService::complete(..., ResultEnvelope::fromHttpResponse(...));
} catch (...) {
    IdempotencyService::fail(...);
    throw;
}

// Outbound after successful commit (L3 + worker)
OutboxService::enqueue(OutboxEnqueueCommand(..., dedupKey: streamEventId, payload: callbackPayload));
// Async worker: claimNextPending -> HTTP POST -> markSent / scheduleRetry
```

**Payment-specific webhooks** may additionally use payment-bounded implementations in the monorepo (e.g. gateway webhook processors) **on top of** the same primitives where appropriate.

---

### 📊 **3. Observability & Monitoring**

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

### 💼 **4. Financial Management**

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

### 🛠️ **5. Operations & Logistics**

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

### 🤝 **6. CRM & Loyalty**

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

### 📋 **7. Project & Delivery**

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

**Package Count:** 102 Atomic Packages (includes PolicyEngine, Idempotency, Outbox)  
**Orchestrator Count:** 19 Orchestrators  
**Architecture Version:** 3.0 (Nexus Three-Layer)
