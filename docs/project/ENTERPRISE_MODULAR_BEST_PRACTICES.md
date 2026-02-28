# Enterprise Modular System Best Practices

This document outlines the best practices for managing a large-scale, multi-module enterprise ERP, adapted from industry standards and expert discussions specifically for the **Atomy (Nexus)** architecture.

---

## 1. Advanced Feature Flag Management

In a system with 80+ packages, feature flags must be more than simple "if" statements. They are a lifecycle management tool.

### 1.1 Categorization (Avoiding Flag Debt)
Treat flags differently based on their intent:
- **Release Toggles**: Short-lived (weeks). For trunk-based development.
- **Ops Toggles**: Long-lived (permanent). "Kill switches" for high-load features or graceful degradation.
- **Experimentation Toggles**: Short-to-medium lived. For A/B testing or canary rollouts.
- **Permission/Entitlement Toggles**: Long-lived. Control which modules/tiers a tenant can access.

### 1.2 The "Waterfall" Evaluation Filter
To ensure clean logic, follow the **Industry Gold Standard** for evaluation:
1. **Global Level (Code)**: Is the feature enabled in this environment at all?
2. **Tenant Level (Entitlements)**: Did this organization subscribe to this module/feature?
3. **User Level (Permissions)**: Does this specific user have the role required?

### 1.3 Implementation in Nexus 3-Layer Architecture
- **Layer 1 (`Nexus\FeatureFlags`)**: Define categories and interfaces. Use Enums for flag types.
- **Layer 2 (Orchestrators)**: Use `FeatureGateInterface` in coordinators. Never hardcode SDKs.
- **Layer 3 (Adapters)**: Implement the provider (e.g., Unleash, LaunchDarkly, or custom SQL) and handle local caching (e.g., Redis) to ensure $O(1)$ lookup performance.

---

## 2. Orchestrator Refactoring: The Command Bus Pattern

As Layer 2 Orchestrators grow, they risk becoming "God Classes." The **Command Bus Pattern** should be adopted to maintain SOLID principles.

### 2.1 Decoupling via Commands
- **Command**: A simple DTO (e.g., `CreatePurchaseOrder`) representing an intent.
- **Handler**: A single-purpose class in Layer 2 that coordinates Layer 1 packages.
- **Bus**: The dispatcher (implemented in Layer 3) that routes commands to handlers.

### 2.2 Middleware: The "Gatekeeper"
Use Bus Middleware to handle cross-cutting concerns automatically:
- **Feature Gate Middleware**: Checks if the feature is enabled for the tenant before the handler is even called.
- **Audit Middleware**: Automatically logs every command and its result for a perfect audit trail.
- **Transaction Middleware**: Starts a database transaction at the start of the command and commits/rolls back at the end.

---

## 3. Data Isolation & Row-Level Security (RLS)

Manual filtering by `tenantId` is error-prone. We should move towards **Transparent Multi-Tenancy**.

### 3.1 Transparent Filtering
- **Database Level**: Utilize PostgreSQL Row-Level Security (RLS). Set the `current_tenant` session variable at the start of the request; the DB will then filter all queries automatically.
- **Application Level**: Use ORM Interceptors (e.g., Eloquent Global Scopes) to inject `tenantId` into every `SELECT`, `UPDATE`, and `DELETE` query silently.

### 3.2 Context Propagation (JWT)
Ensure the JWT carries the full execution context to minimize DB round-trips:
- `tenant_id`: For data isolation.
- `plan_level`: For entitlement checks.
- `roles/scopes`: For user permission checks.

---

## 4. Distributed Transactions & Sagas

In a multi-module ERP, business actions often span multiple package boundaries (e.g., Sales -> Inventory -> Accounting).

### 4.1 The Saga Pattern
For cross-package consistency, use **Sagas (Process Managers)**:
1. **Orchestrated Saga**: A coordinator in Layer 2 manages the sequence of commands.
2. **Compensating Transactions**: Every action must have a "Reverse" action. If Step 3 (Payment) fails, the Saga must trigger Step 2's reverse (Unreserve Inventory) and Step 1's reverse (Cancel Order).

### 4.2 Event-Driven "Glue"
Modules should communicate asynchronously via an Event Bus (e.g., RabbitMQ, Kafka) to prevent system-wide outages if one module is down.
- **Nexus Rule**: Layer 1 packages dispatch events; Layer 2/3 listeners react.

---

## 5. Performance & Observability

### 5.1 Correlation IDs
Every request must be assigned a unique `X-Correlation-ID`. This ID must travel through:
- API Gateway -> Orchestrators -> Packages -> Logs -> Outgoing Events.
This allows tracing the "life story" of a request across 80+ packages and multiple apps.

### 5.2 Local Rule Evaluation
Avoid network calls for feature flag checks. The Feature Manager should:
- Fetch rules on startup.
- Cache them in memory.
- Evaluate context (Tenant, User) locally within the request lifecycle.

---

## 6. Cleanup & Flag Debt
- **Flag Expiry**: Every feature flag must have an "Owner" and an "Expiry Date".
- **Automated Alerts**: Trigger Jira/Slack alerts when a flag is stale (e.g., 100% rolled out for 30 days).
- **Dead Code Removal**: Regularly schedule "Cleanup Sprints" to remove code paths for retired flags.
