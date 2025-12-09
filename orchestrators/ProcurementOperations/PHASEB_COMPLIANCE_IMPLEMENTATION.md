# Phase B - ProcurementOperations Compliance & Controls Implementation Plan

**Version:** 1.0  
**Created:** December 9, 2025  
**Branch:** `feature/procurement-operations-phase-b`  
**Estimated Duration:** 3-4 weeks  
**Prerequisite:** Phase A completed ✅

---

## Overview

Implement compliance and controls features for SOX/regulatory readiness, addressing audit, tax, and governance gaps identified in `GAP_ANALYSIS_PROCUREMENT_OPERATIONS.md`.

### Key Features

1. Spend Policy Engine
2. Approval Limits Configuration
3. SOX Control Points
4. Withholding Tax Calculation
5. Tax Validation Service
6. Document Retention Policies
7. Audit Trail Enhancement

### Package Dependencies (Additional to Phase A)

| Package | Interfaces Used |
|---------|-----------------|
| `Nexus\Tax` | `TaxCalculatorInterface`, `TaxJurisdictionResolverInterface`, `TaxExemptionManagerInterface` |
| `Nexus\Compliance` | `ComplianceManagerInterface`, `SodManagerInterface` |
| `Nexus\Document` | `DocumentManagerInterface`, `RetentionPolicyInterface` (if available) |
| `Nexus\AuditLogger` | `AuditLogManagerInterface` (enhanced usage) |
| `Nexus\Setting` | `SettingsManagerInterface` (policy configuration) |

---

## Implementation Steps

### Step 1: Spend Policy Engine (5-7 days)

**Objective:** Block purchases that violate configurable spending policies (category limits, vendor restrictions, contract requirements).

**Services to Create:**

```
src/Services/Compliance/
├── SpendPolicyEngine.php              # Core policy evaluation engine
├── SpendPolicyRegistry.php            # Registry of active policies
└── SpendPolicyViolationHandler.php    # Handles violations (block/warn/approve-required)
```

**Contracts to Create:**

```
src/Contracts/
├── SpendPolicyInterface.php           # Individual policy contract
├── SpendPolicyEngineInterface.php     # Engine contract
└── SpendPolicyContextInterface.php    # Context for policy evaluation
```

**Rules to Create:**

```
src/Rules/SpendPolicy/
├── CategorySpendLimitRule.php         # Max spend per category/period
├── VendorSpendLimitRule.php           # Max spend per vendor/period
├── ContractComplianceRule.php         # Must use contracted vendor if available
├── PreferredVendorRule.php            # Enforce preferred vendor policy
├── ApprovalOverrideRequiredRule.php   # Flag items needing override approval
└── MaverickSpendRule.php              # Detect non-compliant purchases
```

**DataProviders to Create:**

```
src/DataProviders/
├── SpendPolicyDataProvider.php        # Aggregates policy context
└── HistoricalSpendDataProvider.php    # Retrieves spend history for limit checks
```

**DTOs to Create:**

```
src/DTOs/
├── SpendPolicyRequest.php             # Input for policy check
├── SpendPolicyResult.php              # Result with violations/warnings
├── SpendPolicyViolation.php           # Individual violation detail
└── SpendPolicyContext.php             # Context DTO for rules
```

**Events to Create:**

```
src/Events/
├── SpendPolicyViolatedEvent.php       # Policy violation detected
├── SpendPolicyOverrideRequestedEvent.php  # Override requested
└── MaverickSpendDetectedEvent.php     # Non-compliant spend detected
```

**Enums to Create:**

```
src/Enums/
├── SpendPolicyType.php                # CATEGORY_LIMIT, VENDOR_LIMIT, CONTRACT_REQUIRED, PREFERRED_VENDOR
├── PolicyViolationSeverity.php        # BLOCKING, WARNING, INFORMATIONAL
└── PolicyAction.php                   # BLOCK, WARN, REQUIRE_APPROVAL, ALLOW
```

**Integration Points:**
- Hook into `ProcurementCoordinator.createRequisition()` 
- Hook into `BlanketPurchaseOrderCoordinator.createReleaseOrder()`
- Read policy configuration from `Nexus\Setting`

---

### Step 2: Approval Limits Configuration (3 days)

**Objective:** Configurable approval thresholds by role, department, and document type.

**Services to Create:**

```
src/Services/Approval/
└── ApprovalLimitsManager.php          # Manages approval limits configuration
```

**Rules to Create:**

```
src/Rules/Approval/
├── ApprovalLimitRule.php              # Validates user within approval authority
├── AmountThresholdRule.php            # Validates against amount thresholds
└── DepartmentApprovalRule.php         # Validates department-specific limits
```

**DTOs to Create:**

```
src/DTOs/
├── ApprovalLimitConfig.php            # Configuration DTO
├── ApprovalLimitCheckRequest.php      # Input for limit check
└── ApprovalLimitCheckResult.php       # Result of limit validation
```

**ValueObjects to Create:**

```
src/ValueObjects/
├── ApprovalThreshold.php              # Immutable threshold definition
└── ApprovalAuthority.php              # User's approval authority limits
```

**Configuration Schema (via Nexus\Setting):**

```php
// Setting keys for approval limits
'procurement.approval.limits' => [
    'default' => [
        'requisition' => ['max_cents' => 500000],    // $5,000
        'purchase_order' => ['max_cents' => 1000000], // $10,000
        'payment' => ['max_cents' => 2500000],       // $25,000
    ],
    'roles' => [
        'department_manager' => [
            'requisition' => ['max_cents' => 2500000],
            'purchase_order' => ['max_cents' => 5000000],
            'payment' => ['max_cents' => 10000000],
        ],
        'finance_director' => [
            'requisition' => ['max_cents' => 10000000],
            'purchase_order' => ['max_cents' => 25000000],
            'payment' => ['max_cents' => 50000000],
        ],
    ],
];
```

---

### Step 3: SOX Control Points (3 days)

**Objective:** Implement SOX (Sarbanes-Oxley) compliance control points for financial transactions.

**Services to Create:**

```
src/Services/Compliance/
├── SOXControlPointService.php         # SOX control point validation
└── SOXAuditTrailService.php           # Enhanced audit trail for SOX
```

**Rules to Create:**

```
src/Rules/SOX/
├── SOXApprovalChainRule.php           # Validates complete approval chain exists
├── SOXDocumentationRule.php           # Validates required documentation attached
├── SOXTimelinesRule.php               # Validates within SOX reporting timelines
├── SOXPeriodEndCutoffRule.php         # Validates period-end cutoff rules
└── SOXMaterialityThresholdRule.php    # Flags items above materiality threshold
```

**Enums to Create:**

```
src/Enums/
├── SOXControlPoint.php                # APPROVAL_CHAIN, DOCUMENTATION, TIMELINE, CUTOFF, MATERIALITY
└── SOXRiskLevel.php                   # LOW, MEDIUM, HIGH, CRITICAL
```

**DTOs to Create:**

```
src/DTOs/
├── SOXControlCheckRequest.php         # Input for SOX control check
├── SOXControlCheckResult.php          # Result with control point status
└── SOXControlViolation.php            # Individual control violation
```

**Events to Create:**

```
src/Events/
├── SOXControlFailedEvent.php          # Control point failed
└── SOXMaterialItemFlaggedEvent.php    # Material item flagged for review
```

**Integration Points:**
- Hook into `PaymentProcessingCoordinator.processPayment()`
- Hook into `InvoiceMatchingCoordinator.matchInvoice()`
- Hook into period close processes

---

### Step 4: Withholding Tax Calculation (3 days)

**Objective:** Calculate and track withholding taxes on vendor payments.

**Services to Create:**

```
src/Services/Tax/
├── WithholdingTaxService.php          # Calculate withholding tax
└── Form1099TrackingService.php        # US 1099 reporting tracking (optional)
```

**Rules to Create:**

```
src/Rules/Tax/
├── WithholdingTaxApplicableRule.php   # Determines if WHT applies
├── WithholdingTaxRateRule.php         # Validates correct rate applied
└── VendorTaxIdValidRule.php           # Validates vendor tax ID for withholding
```

**DTOs to Create:**

```
src/DTOs/
├── WithholdingTaxRequest.php          # Input for WHT calculation
├── WithholdingTaxResult.php           # Result with tax amounts
├── WithholdingTaxLine.php             # Individual WHT line item
└── VendorTaxInfo.php                  # Vendor's tax information
```

**ValueObjects to Create:**

```
src/ValueObjects/
├── WithholdingTaxRate.php             # Immutable WHT rate
└── TaxJurisdiction.php                # Tax jurisdiction details
```

**Enums to Create:**

```
src/Enums/
├── WithholdingTaxType.php             # FEDERAL, STATE, LOCAL
└── TaxFormType.php                    # FORM_1099, FORM_W9, etc.
```

**Events to Create:**

```
src/Events/
├── WithholdingTaxCalculatedEvent.php  # WHT calculated on payment
└── WithholdingTaxRemittedEvent.php    # WHT remitted to authority
```

**Integration Points:**
- Integrate with `Nexus\Tax\Contracts\TaxCalculatorInterface`
- Hook into `PaymentProcessingCoordinator` before payment execution
- Update `PaymentResult` DTO to include WHT breakdown

---

### Step 5: Tax Validation Service (3 days)

**Objective:** Validate tax amounts and codes on invoices before matching/payment.

**Services to Create:**

```
src/Services/Tax/
├── InvoiceTaxValidationService.php    # Validates invoice tax amounts
└── TaxCodeMappingService.php          # Maps vendor tax codes to internal codes
```

**Rules to Create:**

```
src/Rules/Tax/
├── TaxAmountValidationRule.php        # Validates tax amount is correct
├── TaxCodeValidRule.php               # Validates tax code is valid
├── TaxRateValidRule.php               # Validates tax rate matches jurisdiction
└── TaxExemptionValidRule.php          # Validates exemption certificates
```

**DataProviders to Create:**

```
src/DataProviders/
└── TaxValidationDataProvider.php      # Aggregates tax context for validation
```

**DTOs to Create:**

```
src/DTOs/
├── TaxValidationRequest.php           # Input for tax validation
├── TaxValidationResult.php            # Result with validation status
├── TaxDiscrepancy.php                 # Individual tax discrepancy
└── TaxContext.php                     # Context for tax validation
```

**Enums to Create:**

```
src/Enums/
├── TaxValidationStatus.php            # VALID, INVALID, NEEDS_REVIEW
└── TaxDiscrepancyType.php             # AMOUNT_MISMATCH, RATE_MISMATCH, CODE_INVALID, EXEMPTION_INVALID
```

**Events to Create:**

```
src/Events/
├── TaxValidationPassedEvent.php       # Tax validation passed
├── TaxValidationFailedEvent.php       # Tax validation failed
└── TaxDiscrepancyFlaggedEvent.php     # Tax discrepancy flagged for review
```

**Integration Points:**
- Hook into `InvoiceMatchingCoordinator.matchInvoice()`
- Use `Nexus\Tax\Contracts\TaxCalculatorInterface` for recalculation
- Use `Nexus\Tax\Contracts\TaxJurisdictionResolverInterface` for jurisdiction lookup

---

### Step 6: Document Retention Policies (2 days)

**Objective:** Enforce document retention requirements for compliance.

**Services to Create:**

```
src/Services/Compliance/
└── DocumentRetentionService.php       # Manages retention policy enforcement
```

**Rules to Create:**

```
src/Rules/Compliance/
├── DocumentRetentionRule.php          # Validates document is within retention
└── DocumentArchivalRule.php           # Validates archival requirements met
```

**DTOs to Create:**

```
src/DTOs/
├── RetentionPolicyCheckRequest.php    # Input for retention check
├── RetentionPolicyCheckResult.php     # Result with retention status
└── DocumentRetentionInfo.php          # Document retention metadata
```

**ValueObjects to Create:**

```
src/ValueObjects/
└── RetentionPeriod.php                # Immutable retention period definition
```

**Enums to Create:**

```
src/Enums/
├── RetentionPolicyType.php            # LEGAL_HOLD, TAX_RECORDS, SOX_RECORDS, STANDARD
└── DocumentLifecycleStage.php         # ACTIVE, ARCHIVED, PENDING_DELETION, LEGAL_HOLD
```

**Events to Create:**

```
src/Events/
├── DocumentRetentionExpiredEvent.php  # Document retention period expired
└── DocumentLegalHoldAppliedEvent.php  # Legal hold applied to document
```

**Configuration Schema (via Nexus\Setting):**

```php
'procurement.retention.policies' => [
    'purchase_order' => ['years' => 7, 'legal_hold_overrides' => true],
    'invoice' => ['years' => 7, 'legal_hold_overrides' => true],
    'payment' => ['years' => 7, 'legal_hold_overrides' => true],
    'contract' => ['years' => 10, 'legal_hold_overrides' => true],
    'requisition' => ['years' => 3, 'legal_hold_overrides' => true],
];
```

---

### Step 7: Audit Trail Enhancement (3 days)

**Objective:** Enhanced audit trail for compliance reporting and forensic analysis.

**Services to Create:**

```
src/Services/Audit/
├── ProcurementAuditService.php        # Procurement-specific audit logging
├── AuditTrailReportService.php        # Generate audit trail reports
└── ComplianceAuditExportService.php   # Export audit data for compliance
```

**DataProviders to Create:**

```
src/DataProviders/
├── AuditTrailDataProvider.php         # Aggregates audit trail data
└── ComplianceReportDataProvider.php   # Aggregates compliance metrics
```

**DTOs to Create:**

```
src/DTOs/
├── AuditTrailEntry.php                # Individual audit entry
├── AuditTrailQuery.php                # Query parameters for audit trail
├── AuditTrailReport.php               # Audit trail report output
├── ComplianceMetrics.php              # Compliance dashboard metrics
└── ComplianceAuditExport.php          # Export format for auditors
```

**Enums to Create:**

```
src/Enums/
├── AuditEventType.php                 # CREATED, UPDATED, APPROVED, REJECTED, MATCHED, PAID, etc.
├── AuditSeverity.php                  # INFO, WARNING, ERROR, CRITICAL
└── ComplianceReportType.php           # SOX, DAILY_TRANSACTIONS, APPROVAL_AUDIT, PAYMENT_AUDIT
```

**Integration Points:**
- Enhance all coordinators with detailed audit logging
- Use `Nexus\AuditLogger\Contracts\AuditLogManagerInterface`
- Provide export capability for external audit tools

---

## Coordinator Updates

### Update `InvoiceMatchingCoordinator`

Add tax validation before matching:

```php
public function matchInvoice(MatchInvoiceRequest $request): MatchingResult
{
    // 1. Validate tax amounts
    $taxValidation = $this->taxValidationService->validate(
        new TaxValidationRequest(
            tenantId: $request->tenantId,
            invoiceId: $request->invoiceId,
            invoiceAmount: $request->invoiceAmount,
            taxLines: $request->taxLines,
        )
    );
    
    if (!$taxValidation->isValid) {
        return MatchingResult::failure('Tax validation failed', $taxValidation->discrepancies);
    }
    
    // 2. Existing matching logic...
}
```

### Update `PaymentProcessingCoordinator`

Add withholding tax calculation and SOX controls:

```php
public function processPayment(ProcessPaymentRequest $request): PaymentResult
{
    // 1. SOX control point validation
    $soxResult = $this->soxControlService->validateControlPoints(
        new SOXControlCheckRequest(
            tenantId: $request->tenantId,
            documentType: 'payment',
            documentId: $request->paymentId,
            amount: $request->amount,
        )
    );
    
    if ($soxResult->hasFailures()) {
        return PaymentResult::failure('SOX control failed', $soxResult->violations);
    }
    
    // 2. Calculate withholding tax
    $whtResult = $this->withholdingTaxService->calculate(
        new WithholdingTaxRequest(
            vendorId: $request->vendorId,
            paymentAmount: $request->amount,
            jurisdiction: $request->jurisdiction,
        )
    );
    
    // 3. Adjust payment amount for WHT
    $netPayment = $request->amount->subtract($whtResult->totalWithholding);
    
    // 4. Continue with existing payment logic...
}
```

### Update `ProcurementCoordinator`

Add spend policy validation:

```php
public function createRequisition(CreateRequisitionRequest $request): RequisitionResult
{
    // 1. Spend policy validation
    $policyResult = $this->spendPolicyEngine->evaluate(
        new SpendPolicyRequest(
            tenantId: $request->tenantId,
            categoryId: $request->categoryId,
            vendorId: $request->vendorId,
            amount: $request->amount,
            requesterId: $request->requesterId,
        )
    );
    
    if ($policyResult->hasBlockingViolations()) {
        return RequisitionResult::failure('Spend policy violation', $policyResult->violations);
    }
    
    // 2. Approval limit validation
    $limitResult = $this->approvalLimitsManager->checkLimit(
        new ApprovalLimitCheckRequest(
            userId: $request->requesterId,
            documentType: 'requisition',
            amount: $request->amount,
        )
    );
    
    // 3. Continue with existing requisition logic...
}
```

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

---

## Testing Requirements

### Unit Tests

- All new services with mocked dependencies
- All new rules with various input scenarios
- Tax calculation edge cases (exemptions, multi-jurisdiction)
- Spend policy combinations

### Integration Tests

- End-to-end: Requisition → Spend Policy → Approval → PO
- End-to-end: Invoice → Tax Validation → Matching → WHT → Payment
- SOX control point scenarios
- Document retention lifecycle

### Compliance Test Scenarios

1. **SOX Controls:**
   - Approval chain completeness
   - Documentation requirements
   - Period-end cutoff validation
   - Materiality threshold flagging

2. **Tax Validation:**
   - Tax amount recalculation
   - Multi-jurisdiction validation
   - Exemption certificate validation
   - Withholding tax calculation

3. **Spend Policies:**
   - Category limit enforcement
   - Vendor spend concentration
   - Contract compliance
   - Maverick spend detection

---

## Atomic Package Dependencies Update

Add to `composer.json`:

```json
{
    "require": {
        "nexus/tax": "*@dev",
        "nexus/compliance": "*@dev",
        "nexus/document": "*@dev"
    }
}
```

---

## Progress Tracking

- [ ] Step 1: Spend Policy Engine (5-7 days)
- [ ] Step 2: Approval Limits Configuration (3 days)
- [ ] Step 3: SOX Control Points (3 days)
- [ ] Step 4: Withholding Tax Calculation (3 days)
- [ ] Step 5: Tax Validation Service (3 days)
- [ ] Step 6: Document Retention Policies (2 days)
- [ ] Step 7: Audit Trail Enhancement (3 days)
- [ ] Integration Testing
- [ ] Documentation Update

---

## Risk Mitigation

| Risk | Mitigation |
|------|------------|
| Tax calculation complexity | Use `Nexus\Tax` package extensively; don't reinvent |
| SOX compliance scope creep | Focus on key control points; document what's out of scope |
| Performance impact | Lazy-load policies; cache tax rates; batch audit writes |
| Configuration complexity | Provide sensible defaults; document all settings |

---

## Post-Phase B State

After completing Phase B, ProcurementOperations will have:

- ✅ **Compliance Coverage:** ~60% (up from ~30%)
- ✅ **SOX Readiness:** Basic controls implemented
- ✅ **Tax Compliance:** Withholding and validation
- ✅ **Audit Trail:** Enhanced for compliance reporting
- ✅ **Spend Control:** Policy engine with configurable rules

**Ready for:** Phase C (Advanced Features) including RFQ/Sourcing, Vendor Onboarding, Quality Inspection, and Payment Run Workflow.

---

**Last Updated:** December 9, 2025
