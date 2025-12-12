# ProcurementOperations: Phase B Backlog & Phase C Implementation Plan

**Document Version:** 2.0  
**Date:** December 12, 2025  
**Status:** Approved for Implementation  
**Target Version:** v2.0.0-phase-c  

---

## 1. Executive Summary

Following a comprehensive gap analysis and code audit, the status of the `Nexus\ProcurementOperations` orchestrator is as follows:

- **Phase A (Critical Foundation):** ✅ **95% Complete** (16/16 planned features implemented + bonus features).
- **Phase B (Compliance & Controls):** ⚠️ **20% Complete** (Only Spend Policy Engine partially implemented).
- **Phase C (Advanced Features):** 📅 **Planned** (Scope defined below).

**Strategic Decision:**
To ensure enterprise compliance and prevent technical debt, the outstanding Phase B deliverables have been merged into the start of Phase C as a **MANDATORY HARD GATE**. No advanced Phase C features will be implemented until the compliance foundation is secure.

**Target Outcome:**
Completion of this plan will raise the orchestrator's P2P coverage from ~30% to **~72%**, delivering a fully SOX-compliant, enterprise-ready procurement system.

---

## 2. Implementation Strategy & Architecture

### Architectural Pattern
We will continue using the **Advanced Orchestrator Pattern v1.1**:
- **Coordinators**: Stateless traffic cops (direct flow, don't implement logic).
- **Services**: Pure business logic (calculations, complex builds).
- **DataProviders**: Aggregate data from atomic packages into Context DTOs.
- **Rules**: Single-responsibility validation constraints.
- **Workflows**: Long-running stateful sagas with compensation steps.

### Key Technical Decisions (Approved)
1.  **SOX Controls**: Default enabled (`true`) for all tenants. Performance monitoring via `Nexus\Monitoring` tracking P95 latency. Automated opt-out recommendations for low-risk tenants (requires manual approval).
2.  **Bank File Generation**: Strategy pattern with versioned generators (e.g., `NachaFileGenerator2025`). Scheduled cutover support. 24-hour emergency rollback capability with finance-team-only notifications.
3.  **Vendor Portal**: OAuth2 authentication. Persistent tiered rate limiting via `Nexus\Storage` (Standard/Premium/Enterprise). Bi-directional tier monitoring (promotion/demotion) requiring manual approval.
4.  **Delegation**: Strict enforcement of the 3-level delegation limit established in Phase A.

---

## 3. Detailed Implementation Steps

### STEP 1: [HARD GATE] Phase B Compliance Foundation (1 Week)
**Objective:** Establish the mandatory compliance layer before adding new features.

- **SOX Control Framework**
    - Implement `SOXControlPointService` with tenant-configurable enforcement.
    - Create `SOXPerformanceMonitor` to track control validation latency (P95).
    - Build `SOXOptOutApprovalCoordinator` for exemption workflows.
    - **Constraint:** Respect 3-level delegation limit for approvals.
- **Tax Compliance**
    - Implement `InvoiceTaxValidationService` (integrating `Nexus\Tax`).
    - Create `WithholdingTaxCoordinator` for payment interception.
- **Migration**
    - Create tenant setting migration: `sox_compliance_enabled = true`.

**Deliverables:**
- 12 DTOs (ControlResult, ComplianceContext, etc.)
- 10 Events (ControlFailed, ExemptionRequested, etc.)
- 2 DataProviders
- 38 Unit Tests

---

### STEP 2: RFQ & Competitive Sourcing (1.5 Weeks)
**Objective:** Enable competitive bidding and strategic sourcing.

- **Sourcing Workflow**
    - Implement `SourceToContractCoordinator`.
    - Build `SourceSelectionWorkflow` saga with 5 compensating steps.
- **Bid Comparison**
    - Create `RFQComparisonService` with weighted scoring engine:
        - Price: 40%
        - Quality: 30%
        - Delivery: 20%
        - History: 10%
    - Implement `ComparisonMatrix` DTO.

**Deliverables:**
- 10 DTOs
- 5 Events
- 4 Rules
- 2 Coordinator Interfaces

---

### STEP 3: Vendor Onboarding with Risk Assessment (1 Week)
**Objective:** Formalize vendor intake with due diligence.

- **Onboarding Process**
    - Implement `VendorOnboardingCoordinator`.
    - Build `VendorOnboardingWorkflow` (4 steps: Info, Risk, Compliance, Approval).
- **Risk Engine**
    - Create `VendorRiskAssessmentService` with composite scoring:
        - Financial Health: 40%
        - Compliance Status: 35%
        - Performance History: 25%
- **Data Quality**
    - Implement `VendorDeduplicationService` (Levenshtein distance > 85% + Tax ID match).

**Deliverables:**
- 8 DTOs (including `VendorRiskScore` breakdown)
- 6 Events
- 5 Rules

---

### STEP 4: QC Inspection, Service Receipt & Consignment (1 Week)
**Objective:** Handle complex receipt scenarios beyond simple goods.

- **Quality Control**
    - Create `QualityInspectionCoordinator` (Hold/Release logic via `Nexus\Inventory`).
    - Inject `QualityInspectionStep` into existing `ReceiveGoodsStep`.
- **Specialized Receipts**
    - Implement `ServiceReceiptCoordinator` for non-stock/service POs.
    - Implement `ConsignmentReceiptCoordinator` for vendor-owned inventory.

**Deliverables:**
- 7 DTOs
- 6 Events
- 4 Rules
- 2 Enums (`InspectionResult`, `ReceiptType`)

---

### STEP 5: RTV, Payment Run & Bank Files (1.5 Weeks)
**Objective:** Complete the payment lifecycle with secure file generation.

- **Payment Execution**
    - Build `PaymentRunCoordinator` and `PaymentApprovalWorkflow`.
    - Implement `ReturnToVendorCoordinator` (RTV) for rejected goods.
- **Bank File Engine**
    - Define `BankFileGeneratorInterface`.
    - Implement Versioned Generators:
        - `PositivePayGenerator`
        - `NachaFileGenerator2025` / `NachaFileGenerator2026`
        - `SwiftFileGenerator`
    - Create `BankFileGeneratorFactory` (reads `bank_file_format_version` + `cutover_date`).
- **Safety Mechanisms**
    - Build `BankFileRollbackCoordinator` (24-hour window).
    - Implement Finance-Team-Only immediate notification for rollbacks.

**Deliverables:**
- 13 DTOs (including `BankFileRollbackAuditReport`)
- 11 Events
- 5 Rules
- Integration Tests with sample bank files

---

### STEP 6: Vendor Portal Foundation (3 Days)
**Objective:** Establish the API layer for external vendor interaction.

- **API Coordination**
    - Create `VendorPortalCoordinator` with stub methods for Phase E.
- **Security & Access**
    - Define OAuth2 contracts: `VendorPortalAuthInterface`, `OAuth2TokenInterface`.
    - Implement **Persistent Tiered Rate Limiting** (via `Nexus\Storage`):
        - Standard: 100 req/min
        - Premium: 500 req/min
        - Enterprise: 1000 req/min
- **Tier Management**
    - Implement `VendorTierMonitorService` (Bi-directional):
        - **Promotion**: Standard > 10K calls/month → Candidate for Premium.
        - **Demotion**: Premium < 1K calls/month (3 consecutive months) → Candidate for Standard.
    - Create `VendorTierReviewDashboard` DTOs for manual approval.

**Deliverables:**
- 8 Mock Response DTOs
- 6 Request DTOs
- 4 Tier Monitoring DTOs

---

### STEP 7: Phase B Completion - Audit & Retention (3 Days)
**Objective:** Finalize remaining compliance requirements.

- **Retention Policy**
    - Implement `DocumentRetentionService` with configurable policies:
        - SOX Data: 7 Years
        - Vendor Contracts: 3 Years
        - RFQ Data: 2 Years
- **Audit Reporting**
    - Create `ProcurementAuditService` for SOX 404 evidence generation.
    - Implement `AuditTrailDataProvider`.
- **Spend Policy Events**
    - Finalize `SpendPolicyViolatedEvent`, `SpendPolicyOverrideRequestedEvent`, `MaverickSpendDetectedEvent`.

**Deliverables:**
- 7 DTOs
- 4 Rules
- 3 Enums

---

### STEP 8: Documentation, Testing & Release (1 Week)
**Objective:** Ensure quality and maintainability.

- **Documentation**
    - Update `IMPLEMENTATION_SUMMARY.md` (Target: 350+ files, 33K LOC).
    - Create `PHASE_C_API_REFERENCE.md`.
    - Create `SOX_PERFORMANCE_MONITORING_GUIDE.md`.
    - Create `BANK_FILE_MIGRATION_GUIDE.md`.
- **Testing**
    - Write 95 Unit Tests.
    - Write 8 Workflow Integration Tests.
    - Write 5 Bank File Format Validation Tests.
    - **Target Coverage:** > 83%.
- **Release**
    - Tag `v2.0.0-phase-c`.
    - Update Gap Analysis to reflect ~72% coverage.

---

## 4. Resource Requirements

- **Estimated Duration:** 5-6 Weeks
- **Total New Files:** ~350
- **Dependencies:**
    - `Nexus\Monitoring` (Telemetry)
    - `Nexus\Tax` (Validation)
    - `Nexus\Storage` (Rate Limiting/Files)
    - `Nexus\Inventory` (QC/RTV)
    - `Nexus\Identity` (OAuth2)

---

**Approved By:** Nexus Architecture Team
