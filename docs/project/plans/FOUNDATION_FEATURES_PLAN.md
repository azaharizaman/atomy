# Foundational SaaS Feature Roadmap (Nexus Boilerplate API)

**Date:** 2026-02-28
**Goal:** Establish a high-impact, technical "blank slate" foundation using existing Nexus packages and orchestrators. This foundation allows business-specific packages to be added later without worrying about core SaaS infrastructure.

---

## 🏗️ Ranked Foundation Features

### 1. IdentityOperations (IAM & RBAC) - **CRITICAL**
**The "Who" and "Can"**
The second pillar of SaaS after multi-tenancy. Elevates simple user management to a professional Identity and Access Management system.

*   **Capabilities:**
    *   Centralized Authentication & JWT issuance.
    *   Role-Based Access Control (RBAC) with cross-tenant permission inheritance.
    *   User Profile & Identity lifecycle management.
    *   Multi-Factor Authentication (MFA) and SSO readiness.
*   **Nexus Packages:** `Identity`, `SSO`, `Crypto`.
*   **Impact:** Ensures all future business packages can query permissions (e.g., `$identity->hasPermission('create-invoice')`) without knowing how users or roles are managed.

### 2. DataExchangeOperations (Webhooks & Event Streaming) - **VERY HIGH**
**The "Pipes"**
Decouples business packages using a modular, asynchronous communication layer.

*   **Capabilities:**
    *   **Outbound Webhooks:** Allow tenants to subscribe to system events (e.g., `invoice.paid`).
    *   **Internal Event Stream:** Decouple package interactions (Accounting reacting to a Sales event).
    *   **Asynchronous Job Queuing:** Offload heavy tasks from the API request lifecycle.
*   **Nexus Packages:** `EventStream`, `Notifier`, `Connector`.
*   **Impact:** Provides the standardized infrastructure for "announcing" changes and integrating with third-party systems.

### 3. ComplianceOperations (Privacy & Data Governance) - **HIGH**
**The "Legal Shield"**
Implements "Privacy by Design" to comply with GDPR, PDPA, and other data regulations.

*   **Capabilities:**
    *   **Data Portability:** Automated JSON/CSV exports of all data related to a single user/tenant.
    *   **Right to be Forgotten:** Surgical, cascading deletion across multiple packages.
    *   **Data Masking:** Automatic PII redaction in logs and non-production environments.
*   **Nexus Packages:** `DataPrivacy`, `GDPR`, `PDPA`, `Audit`.
*   **Impact:** Prevents manual compliance work. New packages simply implement a `DataSubjectInterface` to be fully compliant.

### 4. SystemAdministration (Telemetry & Resource Quotas) - **HIGH**
**The "Guardrails"**
Protects the platform from "Noisy Neighbor" syndrome and manages resource constraints.

*   **Capabilities:**
    *   **API Rate Limiting:** Throttling per tenant/user to protect infrastructure.
    *   **Storage Quotas:** Tracking and blocking file/data writes when limits are reached.
    *   **System Health:** Technical telemetry and usage reporting.
*   **Nexus Packages:** `Telemetry`, `Storage`, `Scheduler`.
*   **Impact:** Provides the technical metrics needed to monetize the platform (e.g., "Tier 1: 5GB storage, 100 API calls/min").

### 5. ConnectivityOperations (Unified Integration Layer) - **MEDIUM-HIGH**
**The "Bridge"**
Standardizes how the platform interacts with third-party services.

*   **Capabilities:**
    *   **OAuth Credential Management:** Securely storing third-party tokens (Slack, Google, etc.).
    *   **External API Key Management:** Allowing customers to generate tokens for their own developers.
    *   **Standardized Messaging:** One interface to send notifications via Email, Slack, or SMS.
*   **Nexus Packages:** `Connector`, `Messaging`, `QueryEngine`.
*   **Impact:** Prevents "Integration Bloat" by centralizing external communication logic.

---

## 🚀 Recommended Implementation Strategy

1.  **Phase 1 (IAM):** Implement `IdentityOperations` next to secure the existing `Tenant` and `Setting` resources with real permissions.
2.  **Phase 2 (Events):** Integrate `DataExchangeOperations` to allow the foundation to emit `tenant.onboarded` or `setting.updated` events.
3.  **Phase 3 (Governance):** Layer in `ComplianceOperations` to ensure all foundation data is auditable and exportable.
