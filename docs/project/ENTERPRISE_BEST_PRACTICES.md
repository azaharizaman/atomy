# Enterprise Multi-Module ERP Best Practices Guide

> Extracted from Gemini conversation on Feature Flag Management & Enterprise Architecture
> Date: February 28, 2026

## Executive Summary

This document outlines the best practices for building a scalable, maintainable multi-module ERP system based on expert guidance from the Gemini conversation. These practices align with our existing 3-layer architecture (Layer 1: Stateless Packages, Layer 2: Orchestrator, Layer 3: Adapters) and provide actionable recommendations for feature management, data security, and system scalability.

---

## Table of Contents

1. [Feature Flag Management](#1-feature-flag-management)
2. [Hierarchical Entitlements](#2-hierarchical-entitlements)
3. [Row-Level Security](#3-row-level-security)
4. [JWT Token Strategy](#4-jwt-token-strategy)
5. [Event-Driven Communication](#5-event-driven-communication)
6. [Distributed Transactions (Saga Pattern)](#6-distributed-transactions-saga-pattern)
7. [Command Bus Pattern](#7-command-bus-pattern)
8. [API Gateway & Micro-frontends](#8-api-gateway--micro-frontends)
9. [Observability & Logging](#9-observability--logging)
10. [Schema Migrations](#10-schema-migrations)
11. [Rate Limiting & Resource Management](#11-rate-limiting--resource-management)

---

## 1. Feature Flag Management

### Overview

Feature flags at enterprise scale are not just simple `if` statements—they require a comprehensive lifecycle management system. In a modular ERP with 80+ packages, proper flag management prevents "flag debt" and ensures maintainability.

### Categories of Feature Flags

| Category | Lifespan | Purpose | Example |
|----------|----------|---------|---------|
| **Release Toggles** | Short-lived | Trunk-based development to hide unfinished features | `beta_checkout_flow` |
| **Experimentation Toggles** | Short-to-medium | A/B testing or canary releases | `pricing_experiment_v2` |
| **Ops Toggles** | Long-lived | Kill switches for graceful degradation | `high_volume_billing_killswitch` |
| **Permission/Entitlement Toggles** | Long-lived | Control module/tier access for enterprise clients | `advanced_analytics_module` |

### Implementation Recommendations

#### 1.1 Centralized Configuration Service

- **Use a dedicated service** (LaunchDarkly, Unleash, or custom internal API) as the Single Source of Truth
- **Decouple logic**: Flags should toggle without code deployment
- **Client-side evaluation**: Use SDKs that download flag rulesets and evaluate locally within modules

#### 1.2 Abstraction Layer (Provider Pattern)

Avoid importing feature flag SDKs directly into every module. Create a Feature Flag Wrapper/Interface in your core library:

```php
// Layer 2 - Define standard interface in core module
interface FeatureManager {
    public function isEnabled(string $featureKey, UserContext $context): bool;
}

// Layer 1 - Module only interacts with the interface
if ($featureManager->isEnabled('new-billing-engine', $user)) {
    return new HighVolumeBilling();
}
```

> **Why this matters**: If you switch providers (e.g., from Unleash to LaunchDarkly), you only update the adapter implementation—not every module.

#### 1.3 Cross-Module Dependencies

In enterprise ERP, Module B might depend on a feature being active in Module A:

- **Prerequisite Flags**: Set dependencies where "Advanced Analytics" cannot be true unless "Data Collector" is active
- **Namespace flags**: Use strict naming convention: `[Module]_[Sub-system]_[FeatureName]`
  - Example: `inventory_api_v2_migration`

#### 1.4 The "Cleanup" Workflow

The biggest risk is "Dead Code" from stale flags:

- **Flag Expiry**: Set "Expected Life" metadata on flags. If exceeded, trigger alerts
- **Automated PRs**: Use tools like Piranha (by Uber) to scan code and generate PRs to delete stale flag code paths

### Alignment with Project Objectives

- **Scalability**: Centralized flag management supports 80+ packages without complexity explosion
- **Maintainability**: Abstraction layer prevents vendor lock-in
- **Security**: Prerequisite flags ensure proper module dependencies

---

## 2. Hierarchical Entitlements

### Overview

The industry gold standard for enterprise SaaS is **Hierarchical Entitlements**—separating "what the code can do" from "who is allowed to see it."

### The Three-Level Hierarchy

```
┌─────────────────────────────────────────────────────────────┐
│                  GLOBAL LEVEL (Code)                        │
│  "Is the feature flag enabled in the code at all?"         │
│  (Kill switch - DevOps can disable for all tenants)        │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              TENANT LEVEL (Entitlements)                     │
│  "Did this organization pay for Module A?"                 │
│  (Based on subscription plan: Basic/Pro/Enterprise)         │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│               USER LEVEL (Permissions)                       │
│  "Does this user have the required role for Feature FA1?"  │
│  (Role-based: Manager, Admin, Viewer)                       │
└─────────────────────────────────────────────────────────────┘
```

### Implementation: The Context Object

To avoid messy code, pass a Context Object rather than simple flags:

```php
// The Developer Experience
$context = new UserContext(
    tenantId: "acme_corp",
    userId: "user_123",
    plan: "premium",
    roles: ["admin", "billing"]
);

if ($featureManager->isEnabled("FA1", $context)) {
    // Logic for Feature A1
}
```

### Implementation: Feature Flag Middleware

For the Command Bus pattern, implement middleware that automatically checks flags:

```php
// Layer 3 - Feature Flag Middleware
class FeatureFlagMiddleware implements Middleware
{
    public function __construct(
        private FeatureManagerInterface $featureManager,
        private UserContext $context
    ) {}

    public function execute($command, callable $next)
    {
        // Use PHP Attributes to declare required features
        $reflection = new ReflectionClass($command);
        $attribute = $reflection->getAttributes(RequiresFeature::class)[0] ?? null;

        if ($attribute) {
            $featureName = $attribute->newInstance()->featureName;
            
            if (!$this->featureManager->isEnabled($featureName, $this->context)) {
                throw new FeatureDisabledException(
                    "Feature {$featureName} is not active for this tenant."
                );
            }
        }

        return $next($command);
    }
}
```

Usage in Layer 2:

```php
#[RequiresFeature('procurement_v2_advanced_billing')]
class CreatePurchaseOrder
{
    public function __construct(
        public string $vendorId,
        public array $items
    ) {}
}
```

### Potential Pitfalls

| Pitfall | Solution |
|---------|----------|
| **Performance Latency** | Use Redis Cache or Local Rule Engine that syncs in background |
| **"Double Gate" Problem** | Always use Strict-Down approach: Tenant Admin off = User Admin cannot override |
| **UI Syncing** | Ensure frontend respects flags to prevent "Ghost Buttons" leading to 403 errors |

### Alignment with Project Objectives

- **Monetization**: Easily create Basic/Pro/Enterprise tiers
- **Security**: Company Admins can enforce Least Privilege
- **Module Decoupling**: Developers only care if a feature is on—they don't need to know why

---

## 3. Row-Level Security

### Overview

When moving from Feature Access ("Can I use this tool?") to Data Access ("Can I see this specific row?"), use **Policy-Based Access Control (PBAC)** or **Attribute-Based Access Control (ABAC)**.

### Strategies

#### 3.1 Database-Level (PostgreSQL RLS)

Most robust method—push isolation to the database engine:

```sql
-- Enable RLS on table
ALTER TABLE invoices ENABLE ROW LEVEL SECURITY;

-- Create policy
CREATE POLICY tenant_isolation_policy ON invoices
    USING (tenant_id = current_setting('app.current_tenant'));
```

**Benefit**: Even if a developer writes `SELECT * FROM Invoices`, the database automatically filters results.

#### 3.2 Application-Level (Interceptor Pattern)

For complex business logic, use Query Interceptors:

```php
// Layer 3 - Shared Data Library
class TenantIsolationInterceptor
{
    public function __invoke(Query $query, $next): mixed
    {
        if ($query->getModel() === 'Invoice') {
            $query->where('tenant_id', $this->context->tenantId);
            $query->orWhere(function ($q) {
                $q->where('owner_id', $this->context->userId)
                  ->orWhereIn('department_id', $this->context->allowedDepts);
            });
        }
        
        return $next($query);
    }
}
```

#### 3.3 Policy-as-Code (OPA)

For massive systems with complex overlapping rules, use **Open Policy Agent**:

```
# Policy file (Rego)
package example

allow {
    input.user.role == "manager"
    input.resource.tenant_id == input.user.tenant_id
}
```

### Integration with Feature Flags

| Level | Controlled By | Question Answered |
|-------|---------------|-------------------|
| Module Level | Feature Flag | "Is the 'Invoicing' module active for this Tenant?" |
| Action Level | Permissions (RBAC) | "Is this User allowed to click 'Delete'?" |
| Data Level | Data Policy (RLS) | "Which specific rows is this User allowed to see?" |

### Alignment with Project Objectives

- **Security**: Prevents data leaks across tenants
- **Compliance**: Audit trails for data access
- **Developer Experience**: "Silent" multi-tenancy—developers write simple queries without thinking about tenant isolation

---

## 4. JWT Token Strategy

### Overview

JWTs carry the critical context (Tenant ID, User Roles, Plan Level) so modules don't query the database for every request.

### Token Structure

```
┌────────────────────────────────────────────────────────────┐
│  HEADER                                                    │
│  { "alg": "HS256", "typ": "JWT" }                        │
├────────────────────────────────────────────────────────────┤
│  PAYLOAD (Claims)                                          │
│  {                                                         │
│    "sub": "user_123456",        // User ID               │
│    "tenant_id": "acme_corp_001", // CRITICAL for RLS      │
│    "plan": "enterprise",        // For Feature Flags     │
│    "roles": ["admin", "billing"], // For Permissions      │
│    "exp": 1715644800            // Expiration            │
│  }                                                         │
├────────────────────────────────────────────────────────────┤
│  SIGNATURE                                                 │
│  HMACSHA256(base64UrlEncode(header) + "." +               │
│   base64UrlEncode(payload), secret)                       │
└────────────────────────────────────────────────────────────┘
```

### Why It's Great for Modular Systems

- **Statelessness**: No shared session database needed
- **Performance**: All info for RLS and Feature Flags in the request header
- **Decoupling**: Module doesn't need to know how user logged in—just that token is valid

### Security Golden Rules

1. JWTs are **encoded, NOT encrypted**—anyone can read the data
2. Never put passwords or PII in JWTs
3. Always use HTTPS to prevent token theft

### Alignment with Project Objectives

- **Performance**: Reduces database queries for every request
- **Scalability**: Stateless authentication scales horizontally
- **Security**: Carrier of tenant context for RLS and Feature Flags

---

## 5. Event-Driven Communication

### Overview

Modules should NOT call each other directly (synchronously). If Inventory Module calls Accounting API and Accounting is down, the sale fails.

### The Solution: Asynchronous Event Bus

Use RabbitMQ, Kafka, or AWS EventBridge:

```
┌──────────────┐     OrderCreated      ┌──────────────┐
│   Sales     │ ──────────────────►  │  Accounting │
│   Module    │                      │   Module    │
└──────────────┘                      └──────────────┘
       │                                     │
       │ OrderCreated                        │ UpdateInventory
       ▼                                     ▼
┌──────────────┐                      ┌──────────────┐
│  Inventory   │ ◄──────────────────── │   Shipping   │
│   Module     │    ReserveStock       │    Module    │
└──────────────┘                      └──────────────┘
```

**Benefit**: If Shipping is offline, it processes events when back online—no data lost.

### Alignment with Project Objectives

- **Resilience**: Modules don't depend on each other's uptime
- **Scalability**: Event processing can be async and distributed
- **Maintainability**: Loose coupling between modules

---

## 6. Distributed Transactions (Saga Pattern)

### Overview

In a multi-module ERP, a single business action (e.g., "Confirm Order") spans multiple databases. Standard SQL transactions don't work across services.

### The Saga Pattern

Each module performs its local transaction and publishes an event. If the next step fails, **compensating transactions** undo previous steps:

```
┌─────────────────────────────────────────────────────────────┐
│                     CREATE ORDER SAGA                       │
├─────────────────────────────────────────────────────────────┤
│  Step 1: Create Order        → Local Transaction + Event    │
│  Step 2: Reserve Inventory   → Local Transaction + Event    │
│  Step 3: Charge Payment      → Local Transaction + Event    │
│                                                            
│  IF Step 3 FAILS:                                       │
│  → Compensate: Refund Payment                           │
│  → Compensate: Release Inventory                        │
│  → Compensate: Cancel Order                             │
└─────────────────────────────────────────────────────────────┘
```

### Alignment with Project Objectives

- **Data Consistency**: Ensures consistency across module databases
- **Resilience**: Automatic rollback on failures
- **Business Continuity**: Keeps system in consistent state during failures

---

## 7. Command Bus Pattern

### Overview

The Command Bus prevents coordinators from becoming massive, impossible-to-test "God Classes." It acts as a "postal service" for your Orchestrator layer.

### Three Components

| Component | Purpose | Example |
|-----------|---------|---------|
| **Command** | DTO carrying data for action | `CreatePurchaseOrder` |
| **Handler** | Single-purpose orchestration logic | `CreatePurchaseOrderHandler` |
| **Bus** | Mechanism routing Command → Handler | Symfony Messenger / Laravel |

### Implementation Example

```php
// Layer 2 - Command (DTO)
class CreatePurchaseOrder
{
    public function __construct(
        public string $tenantId,
        public string $vendorId,
        public array $items
    ) {}
}

// Layer 2 - Handler
class CreatePurchaseOrderHandler
{
    public function __construct(
        private PurchaseLogic $logic,           // Layer 1
        private VendorRepositoryInterface $vendorRepo  // Layer 3 via Interface
    ) {}

    public function handle(CreatePurchaseOrder $command): void
    {
        // 1. Logic check via Layer 1
        $this->logic->validateVendor($command->vendorId);
        
        // 2. Perform action
        $this->vendorRepo->recordOrder($command);
    }
}
```

### Benefits

| Benefit | Description |
|---------|-------------|
| **Avoids God Classes** | Files stay small—one class per action |
| **Testability** | Mock only what one action needs |
| **Cross-Cutting Concerns** | Middleware automatically adds Logging/Auth/Transactions |
| **Scalability** | Easy to move one Handler to background queue |

### Alignment with Project Objectives

- **Maintainability**: Keeps Layer 2 orchestrators manageable
- **Testability**: Small, focused classes are easier to test
- **Extensibility**: Add new commands without modifying existing code

---

## 8. API Gateway & Micro-frontends

### Overview

With 10+ modules, you don't want 10 different API URLs in your frontend.

### API Gateway

Single entry point (e.g., `api.myerp.com`) that:

- Routes requests to correct module (`/billing/*` → Billing Module)
- Handles JWT validation centrally
- Provides rate limiting and logging

### Micro-frontends

Frontend should be as modular as backend:

- Module A (Vue.js) and Module B (React) can coexist in same "Shell" UI
- Teams deploy updates to HR Module without touching Warehouse Module
- Use Next.js as BFF (Backend for Frontend)

### Performance Tweak

Since Layer 1 & 2 are vanilla PHP, consider **Swoole** or **RoadRunner** for the API App:

- Persistent memory environment = 5x-10x faster than standard FPM
- Stateless packages perform excellently in this environment

### Alignment with Project Objectives

- **User Experience**: Single URL for all modules
- **Performance**: Reduced latency with persistent processes
- **Team Autonomy**: Independent frontend deployments

---

## 9. Observability & Logging

### Overview

When a user says "I got an error," and that report involves three modules, finding the bug is impossible without proper observability.

### Correlation IDs

Every request gets a unique `X-Correlation-ID`:

```
Request: /api/orders/123
  │
  ├─► API Gateway: assign correlation_id=abc-123
  │
  ├─► Sales Module: log[correlation_id=abc-123] "Processing order"
  │
  ├─► Inventory Module: log[correlation_id=abc-123] "Reserving stock"
  │
  └─► Error: log[correlation_id=abc-123] "Stock unavailable"
```

Search ELK Stack/Datadog for `abc-123` to see entire request lifecycle.

### Alignment with Project Objectives

- **Debugging**: Trace errors across module boundaries
- **Performance**: Identify bottlenecks in request flow
- **Compliance**: Audit trail for every user action

---

## 10. Schema Migrations

### Overview

When updating Module A's database schema, you can't have "Stop the World" maintenance in an enterprise ERP.

### Expand and Contract Pattern

```
┌────────────────────────────────────────────────────────────┐
│  Step 1: EXPAND                                            │
│  → Add new column (e.g., `order_priority`)                 │
│  → Old code still works (column is nullable)               │
├────────────────────────────────────────────────────────────┤
│  Step 2: MIGRATE                                           │
│  → Update code to support both old and new columns         │
│  → Backfill data from old to new column                   │
├────────────────────────────────────────────────────────────┤
│  Step 3: CONTRACT                                          │
│  → Remove old column                                       │
│  → Code only uses new column                               │
└────────────────────────────────────────────────────────────┘
```

### Blue-Green Deployment

Run old and new versions simultaneously to ensure Module B doesn't crash during update.

### Alignment with Project Objectives

- **Zero Downtime**: Updates without service interruption
- **Safety**: Gradual rollout catches issues before full deployment
- **Module Isolation**: One module's migration doesn't break others

---

## 11. Rate Limiting & Resource Management

### Overview

In a multi-tenant ERP, one "Power User" running a massive 10,000-page report shouldn't slow down everyone else.

### Strategies

| Strategy | Description |
|----------|-------------|
| **Tenant Throttling** | Rate limit at API Gateway based on `tenant_id` from JWT |
| **Resource Quotas** | Limit CPU/Memory per module using Docker/Kubernetes |
| **Feature Flags** | Limit which tenants can access heavy features |

### Alignment with Project Objectives

- **Fairness**: One tenant can't hog all resources
- **Stability**: One buggy module can't crash the whole server
- **Predictability**: Consistent performance for all tenants

---

## Implementation Priority Matrix

| Priority | Practice | Impact | Effort |
|----------|----------|--------|--------|
| **P0** | JWT Token Strategy | Security, Performance | Medium |
| **P0** | Row-Level Security | Security, Compliance | High |
| **P0** | Feature Flag Infrastructure | Scalability, Monetization | Medium |
| **P1** | Command Bus Pattern | Maintainability, Testability | Medium |
| **P1** | Event-Driven Communication | Resilience, Decoupling | High |
| **P1** | Saga Pattern | Data Consistency | High |
| **P2** | API Gateway | Performance, Security | Medium |
| **P2** | Observability | Debugging, Compliance | Low |
| **P2** | Rate Limiting | Stability, Fairness | Low |

---

## Conclusion

These best practices provide a comprehensive framework for building an enterprise-grade multi-module ERP. Our existing 3-layer architecture (Layer 1: Stateless Packages, Layer 2: Orchestrator, Layer 3: Adapters) provides an excellent foundation for implementing these patterns.

The key principles are:

1. **Decoupling**: Modules should be independent and communicate via events/commands
2. **Security**: Tenant isolation at every level (feature, action, data)
3. **Observability**: Traceability across module boundaries
4. **Scalability**: Stateless design with centralized state management

By adopting these practices, we ensure our ERP system is maintainable, secure, and ready for enterprise-scale growth.

---

*Document generated from Gemini conversation on February 28, 2026*
