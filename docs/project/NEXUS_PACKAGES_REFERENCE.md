# 📚 NEXUS FIRST-PARTY PACKAGES REFERENCE GUIDE

**Version:** 1.6  
**Last Updated:** March 1, 2026  
**Target Audience:** Coding Agents & Developers  
**Purpose:** Prevent architectural violations by explicitly documenting available packages and their proper usage patterns.

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

## 🔀 Orchestrators (Layer 2)

Orchestrators coordinate multiple Layer 1 packages to fulfill complex business workflows.

| Orchestrator | Packages Coordinated |
|--------------|----------------------|
| **ProcurementOperations** | Procurement, Inventory, Payable, Tax, Currency |
| **AccountingOperations** | Accounting, GeneralLedger, Period, Tax |
| **HumanResourceOperations** | HRM, Attendance, Leave, Payroll, Identity |
| **QuotationIntelligence** | Procurement, MachineLearning, Currency, Document |
| **SupplyChainOperations** | Inventory, Manufacturing, Warehouse, Procurement |
| **ProjectManagementOperations** | Projects, HRM, Budget, Telemetry |
| **InsightOperations** | QueryEngine, Notifier, Reporting |

---

## 📖 How to Use This Guide

1. **Identify your need** (e.g., "I need to calculate points for a purchase").
2. **Search this guide** for the keyword ("Loyalty").
3. **Check the interfaces** listed for that package.
4. **Inject the contract** into your service or orchestrator.
5. **Never import concrete classes** from Layer 1 into Layer 2 or 3.

---

**Package Count:** 94 Atomic Packages  
**Orchestrator Count:** 19 Orchestrators  
**Architecture Version:** 3.0 (Nexus Three-Layer)
