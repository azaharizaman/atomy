# Phase B - Gap Implementation Plan

**Branch:** `feature/procurement-operations-phase-b`  
**Created:** December 9, 2025  
**Status:** 🔄 In Progress

---

## Overview

This document tracks the implementation of Phase B Compliance & Controls for ProcurementOperations orchestrator, including parallel enhancements to the Nexus\Tax package.

## Scope Summary

### In Scope (Phase B)
1. ✅ Spend Policy Engine
2. ✅ Approval Limits Configuration  
3. ✅ SOX Control Points (5 controls: ApprovalChain, Documentation, PeriodEndCutoff, Materiality, SOD)
4. ✅ Withholding Tax Calculation (US/MY jurisdictions)
5. ✅ Tax Validation Service
6. ✅ Document Retention Policies
7. ✅ Audit Trail Enhancement
8. ✅ Tax Package WHT Enhancement (parallel work)

### Deferred to Phase C (UNPLANNED_GAP)
- Form 1099 year-end reporting & filing
- 1099 threshold tracking ($600) with vendor accumulation
- 1099-MISC vs 1099-NEC classification logic
- MY PCB integration with PayrollMysStatutory for vendor payments

---

## Implementation Checklist

### Pre-Implementation
- [x] Create feature branch `feature/procurement-operations-phase-b`
- [x] Create implementation plan document
- [x] Update orchestrator composer.json dependencies
- [ ] Create GAP_ANALYSIS.md for atomic packages (Tax, Compliance, Document)
- [ ] Update orchestrator GAP_ANALYSIS with UNPLANNED_GAP section

### Tax Package Enhancement (Parallel Work) ✅ COMPLETE
- [x] Create `WithholdingTaxCalculatorInterface`
- [x] Create `WithholdingTaxStrategyInterface`
- [x] Create `WithholdingTaxCalculator` service
- [x] Create `USWithholdingTaxStrategy` (24%/28%/30% federal rates)
- [x] Create `MYWithholdingTaxStrategy` (10%/15% rates per PcbTaxTable pattern)
- [x] Create WHT-related DTOs and Enums
- [x] Create data files for US/MY rates
- [x] Create `JurisdictionNotSupportedException`

### Step 1: Spend Policy Engine (5-7 days)
- [ ] Create `SpendPolicyInterface`
- [ ] Create `SpendPolicyEngineInterface`
- [ ] Create `SpendPolicyContextInterface`
- [ ] Create `SpendPolicyEngine` service
- [ ] Create `SpendPolicyRegistry` service
- [ ] Create `SpendPolicyViolationHandler` service
- [ ] Create Rules:
  - [ ] `CategorySpendLimitRule`
  - [ ] `VendorSpendLimitRule`
  - [ ] `ContractComplianceRule`
  - [ ] `PreferredVendorRule`
  - [ ] `ApprovalOverrideRequiredRule`
  - [ ] `MaverickSpendRule`
- [ ] Create DTOs:
  - [ ] `SpendPolicyRequest`
  - [ ] `SpendPolicyResult`
  - [ ] `SpendPolicyViolation`
  - [ ] `SpendPolicyContext`
- [ ] Create DataProviders:
  - [ ] `SpendPolicyDataProvider`
  - [ ] `HistoricalSpendDataProvider`
- [ ] Create Events:
  - [ ] `SpendPolicyViolatedEvent`
  - [ ] `SpendPolicyOverrideRequestedEvent`
  - [ ] `MaverickSpendDetectedEvent`
- [ ] Create Enums:
  - [ ] `SpendPolicyType`
  - [ ] `PolicyViolationSeverity`
  - [ ] `PolicyAction`

### Step 2: Approval Limits Configuration (3 days)
- [ ] Create `ApprovalLimitsManager` service
- [ ] Create Rules:
  - [ ] `ApprovalLimitRule`
  - [ ] `AmountThresholdRule`
  - [ ] `DepartmentApprovalRule`
- [ ] Create DTOs:
  - [ ] `ApprovalLimitConfig`
  - [ ] `ApprovalLimitCheckRequest`
  - [ ] `ApprovalLimitCheckResult`
- [ ] Create ValueObjects:
  - [ ] `ApprovalThreshold`
  - [ ] `ApprovalAuthority`

### Step 3: SOX Control Points (3 days)
- [ ] Create `SOXControlPointService` service
- [ ] Create `SOXControlPointRegistry` (tenant-aware via Nexus\Setting)
- [ ] Create `SOXAuditTrailService` service
- [ ] Create Rules:
  - [ ] `SOXApprovalChainRule`
  - [ ] `SOXDocumentationRule`
  - [ ] `SOXPeriodEndCutoffRule`
  - [ ] `SOXMaterialityThresholdRule`
  - [ ] `SOXSegregationOfDutiesRule`
- [ ] Create DTOs:
  - [ ] `SOXControlCheckRequest`
  - [ ] `SOXControlCheckResult`
  - [ ] `SOXControlViolation`
  - [ ] `SOXControlConfig`
- [ ] Create Events:
  - [ ] `SOXControlFailedEvent`
  - [ ] `SOXMaterialItemFlaggedEvent`
- [ ] Create Enums:
  - [ ] `SOXControlPoint`
  - [ ] `SOXRiskLevel`

### Step 4: Withholding Tax Calculation (3 days)
- [ ] Create `WithholdingTaxService` (orchestrator layer)
- [ ] Create `Form1099CategoryService` (basic categorization only)
- [ ] Create Rules:
  - [ ] `WithholdingTaxApplicableRule`
  - [ ] `WithholdingTaxRateRule`
  - [ ] `VendorTaxIdValidRule`
- [ ] Create DTOs:
  - [ ] `WithholdingTaxRequest`
  - [ ] `WithholdingTaxResult`
  - [ ] `WithholdingTaxLine`
  - [ ] `VendorTaxInfo`
- [ ] Create ValueObjects:
  - [ ] `WithholdingTaxRate`
  - [ ] `TaxJurisdiction`
- [ ] Create Events:
  - [ ] `WithholdingTaxCalculatedEvent`
  - [ ] `WithholdingTaxRemittedEvent`
- [ ] Create Enums:
  - [ ] `WithholdingTaxType`
  - [ ] `TaxFormType`
  - [ ] `Form1099Category`

### Step 5: Tax Validation Service (3 days)
- [ ] Create `InvoiceTaxValidationService` service
- [ ] Create `TaxCodeMappingService` service
- [ ] Create `TaxValidationDataProvider`
- [ ] Create Rules:
  - [ ] `TaxAmountValidationRule`
  - [ ] `TaxCodeValidRule`
  - [ ] `TaxRateValidRule`
  - [ ] `TaxExemptionValidRule`
- [ ] Create DTOs:
  - [ ] `TaxValidationRequest`
  - [ ] `TaxValidationResult`
  - [ ] `TaxDiscrepancy`
  - [ ] `TaxContext`
- [ ] Create Events:
  - [ ] `TaxValidationPassedEvent`
  - [ ] `TaxValidationFailedEvent`
  - [ ] `TaxDiscrepancyFlaggedEvent`
- [ ] Create Enums:
  - [ ] `TaxValidationStatus`
  - [ ] `TaxDiscrepancyType`

### Step 6: Document Retention Policies (2 days)
- [ ] Create `DocumentRetentionService` service
- [ ] Create Rules:
  - [ ] `DocumentRetentionRule`
  - [ ] `DocumentArchivalRule`
- [ ] Create DTOs:
  - [ ] `RetentionPolicyCheckRequest`
  - [ ] `RetentionPolicyCheckResult`
  - [ ] `DocumentRetentionInfo`
- [ ] Create ValueObjects:
  - [ ] `RetentionPeriod`
- [ ] Create Events:
  - [ ] `DocumentRetentionExpiredEvent`
  - [ ] `DocumentLegalHoldAppliedEvent`
- [ ] Create Enums:
  - [ ] `RetentionPolicyType`
  - [ ] `DocumentLifecycleStage`

### Step 7: Audit Trail Enhancement (3 days)
- [ ] Create `ProcurementAuditService` service
- [ ] Create `AuditTrailReportService` service
- [ ] Create `ComplianceAuditExportService` service
- [ ] Create DataProviders:
  - [ ] `AuditTrailDataProvider`
  - [ ] `ComplianceReportDataProvider`
- [ ] Create DTOs:
  - [ ] `AuditTrailEntry`
  - [ ] `AuditTrailQuery`
  - [ ] `AuditTrailReport`
  - [ ] `ComplianceMetrics`
  - [ ] `ComplianceAuditExport`
- [ ] Create Enums:
  - [ ] `AuditEventType`
  - [ ] `AuditSeverity`
  - [ ] `ComplianceReportType`

### Coordinator Updates
- [ ] Update `ProcurementCoordinator` with spend policy validation
- [ ] Update `InvoiceMatchingCoordinator` with tax validation
- [ ] Update `PaymentProcessingCoordinator` with WHT and SOX controls

### Post-Implementation
- [ ] Verify GAP_ANALYSIS_PROCUREMENT_OPERATIONS.md reflects all Phase B work
- [ ] Commit all changes
- [ ] Create Pull Request

---

## Settings Key Schema

### SOX Controls Configuration

**Key:** `procurement.sox.controls`  
**Resolution:** Automatic tenant-aware via `SettingsManagerInterface`

```json
[
  {
    "control_id": "APPROVAL_CHAIN",
    "enabled": true,
    "risk_level": "HIGH",
    "threshold_cents": 1000000,
    "require_documentation": true
  },
  {
    "control_id": "DOCUMENTATION",
    "enabled": true,
    "risk_level": "MEDIUM",
    "required_documents": ["invoice", "po", "receipt"]
  },
  {
    "control_id": "PERIOD_END_CUTOFF",
    "enabled": true,
    "risk_level": "HIGH",
    "cutoff_days_before": 3,
    "cutoff_days_after": 2
  },
  {
    "control_id": "MATERIALITY",
    "enabled": true,
    "risk_level": "CRITICAL",
    "threshold_cents": 5000000
  },
  {
    "control_id": "SEGREGATION_OF_DUTIES",
    "enabled": true,
    "risk_level": "HIGH",
    "restricted_combinations": [
      ["requisitioner", "approver"],
      ["approver", "receiver"],
      ["receiver", "payer"]
    ]
  }
]
```

### Approval Limits Configuration

**Key:** `procurement.approval.limits`

```json
{
  "default": {
    "requisition": {"max_cents": 500000},
    "purchase_order": {"max_cents": 1000000},
    "payment": {"max_cents": 2500000}
  },
  "roles": {
    "department_manager": {
      "requisition": {"max_cents": 2500000},
      "purchase_order": {"max_cents": 5000000},
      "payment": {"max_cents": 10000000}
    },
    "finance_director": {
      "requisition": {"max_cents": 10000000},
      "purchase_order": {"max_cents": 25000000},
      "payment": {"max_cents": 50000000}
    }
  }
}
```

### Document Retention Configuration

**Key:** `procurement.retention.policies`

```json
{
  "purchase_order": {"years": 7, "legal_hold_overrides": true},
  "invoice": {"years": 7, "legal_hold_overrides": true},
  "payment": {"years": 7, "legal_hold_overrides": true},
  "contract": {"years": 10, "legal_hold_overrides": true},
  "requisition": {"years": 3, "legal_hold_overrides": true}
}
```

---

## Jurisdiction Strategy Pattern (Withholding Tax)

### US Withholding Rates
| Payment Type | Rate | Form |
|--------------|------|------|
| Non-resident services | 30% | 1042-S |
| Backup withholding (no W-9) | 24% | 1099 |
| Non-resident royalties | 30% | 1042-S |
| FATCA non-compliant | 28% | 1099 |

### MY Withholding Rates
| Payment Type | Rate | Section |
|--------------|------|---------|
| Royalties | 10% | S109 |
| Technical/Management fees | 10% | S109B |
| Interest | 15% | S109 |
| Contract payments (non-resident) | 10%/3% | S107A |

---

## Deliverables Summary

| Category | Count |
|----------|-------|
| New Services | 12 |
| New Rules | 16 |
| New DTOs | 22 |
| New DataProviders | 4 |
| New Events | 12 |
| New Enums | 12 |
| New ValueObjects | 5 |
| New Contracts | 3 |
| Coordinator Updates | 3 |
| Tax Package Interfaces | 2 |
| Tax Package Strategies | 2 |

---

## Commit Strategy

1. **Initial Setup** - Branch, plan document, dependencies
2. **Tax Package Enhancement** - WHT interfaces and strategies
3. **Package GAP_ANALYSIS Files** - Tax, Compliance, Document
4. **Spend Policy Engine** - Complete Step 1
5. **Approval Limits** - Complete Step 2
6. **SOX Controls** - Complete Step 3
7. **WHT Service** - Complete Step 4
8. **Tax Validation** - Complete Step 5
9. **Document Retention** - Complete Step 6
10. **Audit Enhancement** - Complete Step 7
11. **Coordinator Integration** - Update all coordinators
12. **Final** - GAP_ANALYSIS update, PR creation

---

**Last Updated:** December 9, 2025
