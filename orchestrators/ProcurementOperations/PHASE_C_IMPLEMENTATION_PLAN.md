# Phase C - Advanced Features Implementation Plan

**Version:** 1.0  
**Created:** December 10, 2025  
**Branch:** `feature/procurement-operations-phase-c`  
**Estimated Duration:** 4-6 weeks  
**Prerequisites:** 
- Phase A (Critical Foundation) ✅ COMPLETE
- Phase B (Compliance & Controls) ⚠️ MUST COMPLETE FIRST

---

## Executive Summary

Phase C focuses on implementing **advanced procurement lifecycle features** that enable full Procure-to-Pay (P2P) operations in enterprise environments. This phase builds upon the compliance foundation from Phase B to deliver sophisticated features for vendor management, quality control, service procurement, payment optimization, and sourcing workflows.

### Strategic Objectives

1. **Complete Vendor Lifecycle Management** - Onboarding, risk assessment, performance tracking
2. **Quality & Service Receipt** - Inspection workflows, service procurement, returns
3. **Payment Optimization** - Early payment discounts, payment runs, remittance
4. **Competitive Sourcing** - RFQ/RFP workflows with vendor selection
5. **Advanced Payment Features** - Void/cancel, partial payments, multi-invoice settlement

### Business Value

- **Cost Savings:** Early payment discount capture, dynamic discounting, vendor performance optimization
- **Risk Mitigation:** Vendor risk scoring, quality inspection, structured onboarding
- **Operational Efficiency:** Automated payment runs, service receipt processing, RFQ automation
- **Compliance Enhancement:** Vendor compliance tracking, audit trail for sourcing decisions

---

## Phase C Scope Overview

### Features Included in Phase C

| Feature Category | Priority | Estimated Effort |
|------------------|----------|------------------|
| **1. RFQ/Sourcing Workflow** | 🔴 Critical | 1 week |
| **2. Vendor Onboarding Workflow** | 🔴 Critical | 1 week |
| **3. Quality Inspection Integration** | 🟡 High | 3 days |
| **4. Service Receipt Processing** | 🔴 Critical | 3 days |
| **5. Return to Vendor (RTV)** | 🔴 Critical | 3 days |
| **6. Early Payment Discount Capture** | 🔴 Critical | 3 days |
| **7. Payment Run Workflow** | 🔴 Critical | 1 week |
| **8. Payment Void/Cancellation** | 🟡 High | 2 days |
| **9. Vendor Performance Tracking** | 🟡 High | 3 days |
| **10. ASN Processing** | 🟡 High | 2 days |

**Total Estimated Effort:** 4-6 weeks (20-30 working days)

### Features Deferred to Phase D (Analytics & Optimization)

- Spend Analytics Dashboard
- AP Aging Reports
- Cash Flow Forecasting
- Maverick Spend Detection (detection logic in Phase B; reporting in Phase D)
- Vendor Spend Concentration Analysis

### Features Deferred to Phase E (Integrations)

- Bank File Generation (NACHA, Positive Pay, SWIFT)
- EDI Support (850/810/856)
- Vendor Portal Foundation
- External Tax Engine Integration (Avalara, Vertex)
- Punch-out Catalog Integration

---

## Detailed Implementation Plan

### Feature 1: RFQ/Sourcing Workflow (5-7 days)

**Objective:** Enable competitive bidding for purchases through Request for Quotation (RFQ) process with vendor quote comparison and award decision.

**Business Scenarios:**
- Capital equipment purchases requiring competitive bids
- High-value services requiring multiple quotes
- New vendor evaluation through pilot sourcing events
- Strategic sourcing for annual contracts

**Coordinators to Create:**

```
src/Coordinators/
├── SourceToContractCoordinator.php     # Orchestrates RFQ → Quote → Award → Contract/PO
└── RFQManagementCoordinator.php        # Manages RFQ lifecycle
```

**Workflows to Create:**

```
src/Workflows/Sourcing/
├── RFQWorkflow.php                     # RFQ lifecycle workflow
└── Steps/
    ├── CreateRFQStep.php               # Create RFQ document
    ├── InviteVendorsStep.php           # Send RFQ to selected vendors
    ├── CollectQuotesStep.php           # Receive and validate vendor quotes
    ├── EvaluateQuotesStep.php          # Score and rank quotes
    ├── ApprovalStep.php                # Approve sourcing decision
    └── AwardStep.php                   # Award to winning vendor(s)
```

**Services to Create:**

```
src/Services/Sourcing/
├── QuoteEvaluationService.php          # Score quotes based on criteria (price, delivery, quality)
├── VendorSelectionService.php          # Select vendors for RFQ invitation
├── QuoteComparisonService.php          # Side-by-side quote comparison
└── AwardDecisionService.php            # Execute award decision
```

**Rules to Create:**

```
src/Rules/Sourcing/
├── MinimumQuotesRule.php               # Require minimum number of quotes (e.g., 3)
├── QuoteValidityRule.php               # Validate quote is within validity period
├── VendorEligibilityRule.php           # Validate vendor eligible to quote
├── AwardJustificationRule.php          # Require justification if not lowest price
└── SourcingApprovalRule.php            # Determine approval requirements for award
```

**DTOs to Create:**

```
src/DTOs/Sourcing/
├── CreateRFQRequest.php                # Input for RFQ creation
├── RFQResult.php                       # RFQ creation result
├── InviteVendorRequest.php             # Vendor invitation request
├── VendorQuoteRequest.php              # Vendor quote submission
├── VendorQuoteResponse.php             # Vendor quote details
├── QuoteEvaluationRequest.php          # Input for quote evaluation
├── QuoteEvaluationResult.php           # Evaluation scores and ranking
├── AwardDecisionRequest.php            # Award decision request
├── AwardDecisionResult.php             # Award outcome
└── SourcingContext.php                 # Context for sourcing rules
```

**DataProviders to Create:**

```
src/DataProviders/
├── SourcingDataProvider.php            # Aggregates RFQ context
└── QuoteEvaluationDataProvider.php     # Historical quote performance data
```

**Events to Create:**

```
src/Events/Sourcing/
├── RFQCreatedEvent.php                 # RFQ document created
├── RFQInvitationSentEvent.php          # RFQ sent to vendor
├── VendorQuoteReceivedEvent.php        # Quote received from vendor
├── QuotesEvaluatedEvent.php            # Quotes evaluated and ranked
├── AwardDecidedEvent.php               # Sourcing award decision made
└── RFQCancelledEvent.php               # RFQ cancelled/withdrawn
```

**Enums to Create:**

```
src/Enums/
├── RFQStatus.php                       # DRAFT, PUBLISHED, ACTIVE, CLOSED, AWARDED, CANCELLED
├── QuoteStatus.php                     # INVITED, SUBMITTED, UNDER_REVIEW, ACCEPTED, REJECTED
├── EvaluationCriteria.php              # PRICE, DELIVERY_TIME, QUALITY_SCORE, PAST_PERFORMANCE
└── AwardType.php                       # SINGLE_AWARD, SPLIT_AWARD, VOLUME_AGREEMENT
```

**Atomic Package Dependencies:**

| Package | Interfaces Used |
|---------|-----------------|
| `Nexus\Workflow` | `WorkflowInterface`, `WorkflowStepInterface` |
| `Nexus\Setting` | `SettingsManagerInterface` (evaluation criteria weights) |
| `Nexus\Notifier` | `NotificationManagerInterface` (vendor invitations) |
| `Nexus\Document` | `DocumentManagerInterface` (attach RFQ specs) |
| `Nexus\AuditLogger` | `AuditLogManagerInterface` (audit sourcing decisions) |

**Integration Points:**
- Hook into `ProcurementCoordinator.createRequisition()` to suggest RFQ for high-value items
- Link awarded RFQ to `BlanketPurchaseOrderCoordinator` or `ProcurementCoordinator`
- Integrate with `VendorOnboardingWorkflow` for new vendor discovery

---

### Feature 2: Vendor Onboarding Workflow (5-7 days)

**Objective:** Structured workflow for vetting and approving new vendors before they can receive purchase orders or payments.

**Business Scenarios:**
- New vendor registration and approval
- Vendor reactivation after hold/block
- Vendor information updates requiring approval
- Compliance certification validation

**Coordinators to Create:**

```
src/Coordinators/
└── VendorOnboardingCoordinator.php     # Orchestrates vendor onboarding lifecycle
```

**Workflows to Create:**

```
src/Workflows/VendorOnboarding/
├── VendorOnboardingWorkflow.php        # Complete onboarding workflow
└── Steps/
    ├── CollectVendorInfoStep.php       # Gather vendor details (W-9, bank, insurance)
    ├── ComplianceCheckStep.php         # Validate compliance requirements
    ├── RiskAssessmentStep.php          # Assess vendor financial/operational risk
    ├── DueDiligenceStep.php            # Background checks, sanctions screening
    ├── ApprovalStep.php                # Multi-level approval for new vendor
    └── ActivationStep.php              # Activate vendor in master data
```

**Services to Create:**

```
src/Services/Vendor/
├── VendorRiskAssessmentService.php     # Calculate vendor risk score
├── VendorComplianceChecker.php         # Validate compliance requirements
├── VendorDeduplicationService.php      # Prevent duplicate vendor records
├── VendorSegmentationService.php       # Classify vendors (strategic, preferred, approved)
└── VendorDocumentValidator.php         # Validate vendor certifications/insurance
```

**Rules to Create:**

```
src/Rules/Vendor/
├── VendorDuplicateRule.php             # Check for duplicate vendor records
├── VendorComplianceRule.php            # Validate compliance requirements met
├── VendorRiskThresholdRule.php         # Block high-risk vendors
├── VendorCertificationRule.php         # Validate required certifications
├── VendorInsuranceRule.php             # Validate insurance coverage
├── VendorBankDetailsRule.php           # Validate bank account details
└── VendorTaxIdRule.php                 # Validate tax ID (W-9, EIN)
```

**DTOs to Create:**

```
src/DTOs/Vendor/
├── VendorOnboardingRequest.php         # Input for vendor onboarding
├── VendorOnboardingResult.php          # Onboarding outcome
├── VendorRiskAssessmentRequest.php     # Input for risk assessment
├── VendorRiskAssessmentResult.php      # Risk score and factors
├── VendorComplianceCheckRequest.php    # Input for compliance validation
├── VendorComplianceCheckResult.php     # Compliance status
├── VendorSegmentationRequest.php       # Input for vendor classification
├── VendorSegmentationResult.php        # Vendor segment assignment
└── VendorOnboardingContext.php         # Context for onboarding rules
```

**DataProviders to Create:**

```
src/DataProviders/
├── VendorOnboardingDataProvider.php    # Aggregates onboarding context
├── VendorRiskDataProvider.php          # Historical vendor performance data
├── VendorComplianceDataProvider.php    # Compliance requirements (already exists from Phase A)
└── VendorPerformanceDataProvider.php   # Vendor performance metrics
```

**Events to Create:**

```
src/Events/Vendor/
├── VendorOnboardingInitiatedEvent.php  # Onboarding started
├── VendorRiskAssessedEvent.php         # Risk assessment completed
├── VendorComplianceValidatedEvent.php  # Compliance checks passed
├── VendorApprovedEvent.php             # Vendor approved for business
├── VendorActivatedEvent.php            # Vendor activated in system
├── VendorRejectedEvent.php             # Vendor rejected/denied
└── VendorSegmentChangedEvent.php       # Vendor classification changed
```

**Enums to Create:**

```
src/Enums/
├── VendorOnboardingStatus.php          # PENDING, IN_REVIEW, APPROVED, REJECTED, ACTIVE
├── VendorRiskLevel.php                 # LOW, MEDIUM, HIGH, CRITICAL
├── VendorSegment.php                   # STRATEGIC, PREFERRED, APPROVED, CONDITIONAL, BLOCKED
├── ComplianceRequirement.php           # W9, INSURANCE, CERTIFICATION, BACKGROUND_CHECK
└── VendorActivationStatus.php          # INACTIVE, ACTIVE, SUSPENDED, TERMINATED
```

**ValueObjects to Create:**

```
src/ValueObjects/
├── VendorRiskScore.php                 # Immutable risk score (0-100)
├── ComplianceCertification.php         # Certification details with expiry
└── VendorSegmentRating.php             # Segment classification with criteria
```

**Atomic Package Dependencies:**

| Package | Interfaces Used |
|---------|-----------------|
| `Nexus\Workflow` | `WorkflowInterface`, `WorkflowStepInterface` |
| `Nexus\Compliance` | `SanctionsScreeningInterface`, `BackgroundCheckInterface` |
| `Nexus\Party` | `VendorRepositoryInterface`, `VendorManagerInterface` |
| `Nexus\Document` | `DocumentManagerInterface` (W-9, insurance certificates) |
| `Nexus\AuditLogger` | `AuditLogManagerInterface` |
| `Nexus\Notifier` | `NotificationManagerInterface` |

**Integration Points:**
- Link with `SpendPolicyEngine` for preferred vendor enforcement
- Connect to `VendorHoldService` for activation/deactivation
- Feed into `VendorPerformanceTracking` for ongoing monitoring

---

### Feature 3: Quality Inspection Integration (3 days)

**Objective:** Hold inventory receipt pending quality inspection approval to prevent defective goods from entering stock.

**Business Scenarios:**
- Manufacturing materials requiring QC testing
- High-value equipment requiring inspection
- Food/pharma items requiring certification
- Import shipments requiring customs clearance

**Coordinators to Create:**

```
src/Coordinators/
└── QualityInspectionCoordinator.php    # Manages QC workflow for goods receipt
```

**Services to Create:**

```
src/Services/Quality/
├── InspectionSchedulingService.php     # Schedule QC inspections
└── InspectionResultProcessor.php       # Process inspection outcomes
```

**Rules to Create:**

```
src/Rules/GoodsReceipt/
├── QualityInspectionRequiredRule.php   # Determine if QC required
├── InspectionPassedRule.php            # Validate inspection passed
└── InspectionSampleSizeRule.php        # Validate sample size adequate
```

**DTOs to Create:**

```
src/DTOs/Quality/
├── QualityInspectionRequest.php        # Input for QC inspection
├── QualityInspectionResult.php         # Inspection outcome
├── InspectionScheduleRequest.php       # Schedule inspection
├── InspectionCriteriaContext.php       # Inspection criteria for item
└── InspectionHoldContext.php           # Context for QC hold
```

**Events to Create:**

```
src/Events/Quality/
├── QualityInspectionScheduledEvent.php # Inspection scheduled
├── QualityInspectionCompletedEvent.php # Inspection completed
├── QualityInspectionPassedEvent.php    # Items passed QC
├── QualityInspectionFailedEvent.php    # Items failed QC (trigger RTV)
└── InspectionHoldReleasedEvent.php     # QC hold released, stock available
```

**Enums to Create:**

```
src/Enums/
├── InspectionStatus.php                # PENDING, SCHEDULED, IN_PROGRESS, PASSED, FAILED, WAIVED
├── InspectionType.php                  # VISUAL, SAMPLING, FULL_BATCH, CERTIFICATION
└── InspectionOutcome.php               # ACCEPT, REJECT, CONDITIONAL_ACCEPT, REWORK
```

**Atomic Package Dependencies:**

| Package | Interfaces Used |
|---------|-----------------|
| `Nexus\Inventory` | `InventoryManagerInterface`, `StockReservationInterface` (QC hold) |
| `Nexus\Product` | `ProductRepositoryInterface` (inspection criteria) |
| `Nexus\Workflow` | `TaskInterface` (inspection tasks) |

**Integration Points:**
- Hook into `GoodsReceiptCoordinator` to hold stock pending QC
- Trigger `ReturnToVendorCoordinator` if inspection fails
- Release to inventory via `InventoryManagerInterface` after pass

---

### Feature 4: Service Receipt Processing (3 days)

**Objective:** Enable receipt and verification of services (non-physical goods) for three-way matching with service-based invoices.

**Business Scenarios:**
- Professional services (consulting, legal, accounting)
- Maintenance contracts and field services
- Subscription services (SaaS, licenses)
- Outsourced services (cleaning, security, IT)

**Coordinators to Create:**

```
src/Coordinators/
└── ServiceReceiptCoordinator.php       # Process service receipt/acceptance
```

**Services to Create:**

```
src/Services/Service/
├── ServiceAcceptanceService.php        # Validate service completion
└── ServiceMilestoneTracker.php         # Track milestone-based services
```

**Rules to Create:**

```
src/Rules/ServiceReceipt/
├── ServiceCompletionRule.php           # Validate service completed
├── ServiceQualityRule.php              # Validate service quality standards
├── ServiceMilestoneRule.php            # Validate milestone achieved
└── ServicePeriodRule.php               # Validate service period
```

**DTOs to Create:**

```
src/DTOs/Service/
├── ServiceReceiptRequest.php           # Input for service receipt
├── ServiceReceiptResult.php            # Service receipt outcome
├── ServiceAcceptanceRequest.php        # Service acceptance request
├── ServiceMilestoneContext.php         # Milestone tracking context
└── ServiceReceiptContext.php           # Context for service receipt rules
```

**Events to Create:**

```
src/Events/Service/
├── ServiceReceiptCreatedEvent.php      # Service receipt recorded
├── ServiceAcceptedEvent.php            # Service accepted
├── ServiceRejectedEvent.php            # Service rejected
├── ServiceMilestoneAchievedEvent.php   # Milestone completed
└── ServicePeriodClosedEvent.php        # Service period closed
```

**Enums to Create:**

```
src/Enums/
├── ServiceReceiptType.php              # TIME_BASED, MILESTONE_BASED, DELIVERABLE_BASED, SUBSCRIPTION
├── ServiceAcceptanceStatus.php         # PENDING, ACCEPTED, REJECTED, PARTIAL
└── ServiceQualityRating.php            # EXCELLENT, SATISFACTORY, UNSATISFACTORY, FAILED
```

**Atomic Package Dependencies:**

| Package | Interfaces Used |
|---------|-----------------|
| `Nexus\Procurement` | `ServiceOrderRepositoryInterface` |
| `Nexus\AuditLogger` | `AuditLogManagerInterface` |

**Integration Points:**
- Link to `ThreeWayMatchingService` for service PO-GR-Invoice matching
- Feed into `VendorPerformanceTracking` for service quality metrics
- Connect to `InvoiceMatchingCoordinator` for service invoice validation

---

### Feature 5: Return to Vendor (RTV) (3 days)

**Objective:** Formal process for returning rejected goods to vendor with credit memo generation and inventory adjustment.

**Business Scenarios:**
- Quality inspection failures
- Damaged goods on receipt
- Wrong items delivered
- Over-shipment returns
- Warranty returns

**Coordinators to Create:**

```
src/Coordinators/
└── ReturnToVendorCoordinator.php       # Orchestrates RTV lifecycle
```

**Services to Create:**

```
src/Services/Returns/
├── ReturnAuthorizationService.php      # Generate RMA (Return Merchandise Authorization)
├── ReturnShippingService.php           # Arrange return shipment
└── ReturnCreditProcessor.php           # Process vendor credit for return
```

**Rules to Create:**

```
src/Rules/Returns/
├── ReturnEligibilityRule.php           # Validate return is eligible (timeframe, condition)
├── ReturnQuantityRule.php              # Validate return quantity vs received
├── VendorReturnPolicyRule.php          # Enforce vendor return policy
└── ReturnAuthorizationRule.php         # Validate RMA required/approved
```

**DTOs to Create:**

```
src/DTOs/Returns/
├── ReturnToVendorRequest.php           # Input for RTV creation
├── ReturnToVendorResult.php            # RTV outcome
├── ReturnAuthorizationRequest.php      # RMA request
├── ReturnAuthorizationResult.php       # RMA details
├── ReturnShipmentRequest.php           # Return shipment request
└── ReturnCreditRequest.php             # Credit memo request
```

**Events to Create:**

```
src/Events/Returns/
├── ReturnToVendorInitiatedEvent.php    # RTV initiated
├── ReturnAuthorizationIssuedEvent.php  # RMA issued by vendor
├── ReturnShipmentCreatedEvent.php      # Return shipment created
├── ReturnReceivedByVendorEvent.php     # Vendor confirmed receipt
├── ReturnCreditIssuedEvent.php         # Credit memo issued
└── ReturnCompletedEvent.php            # RTV closed
```

**Enums to Create:**

```
src/Enums/
├── ReturnReason.php                    # QUALITY_DEFECT, DAMAGE, WRONG_ITEM, OVERSHIPPED, WARRANTY
├── ReturnStatus.php                    # INITIATED, AUTHORIZED, SHIPPED, RECEIVED, CREDITED, CLOSED
├── ReturnDisposition.php               # CREDIT, REPLACEMENT, REPAIR, SCRAP
└── RMAStatus.php                       # REQUESTED, APPROVED, DENIED, EXPIRED
```

**Atomic Package Dependencies:**

| Package | Interfaces Used |
|---------|-----------------|
| `Nexus\Inventory` | `InventoryManagerInterface` (reverse receipt) |
| `Nexus\Payable` | `PayableManagerInterface` (credit memo) |
| `Nexus\Warehouse` | `ShipmentManagerInterface` (return shipment) |

**Integration Points:**
- Trigger from `QualityInspectionCoordinator` on inspection failure
- Link to `MemoCoordinator` for credit memo processing
- Feed into `VendorPerformanceTracking` for quality metrics

---

### Feature 6: Early Payment Discount Capture (3 days)

**Objective:** Automatically identify and capture early payment discount opportunities (e.g., 2/10 Net 30) to optimize cash flow and reduce procurement costs.

**Business Scenarios:**
- Standard payment terms with early payment discount (2/10 Net 30)
- Dynamic discounting offers from vendors
- Volume discount tiering
- Seasonal discount programs

**Services to Create:**

```
src/Services/Payment/
├── EarlyPaymentDiscountService.php     # Calculate discount opportunities
├── DynamicDiscountingService.php       # Dynamic discount negotiations
└── DiscountOptimizationService.php     # Optimize discount vs cash flow
```

**Rules to Create:**

```
src/Rules/Payment/
├── DiscountEligibilityRule.php         # Validate invoice eligible for discount
├── DiscountDateRule.php                # Validate payment within discount period
├── MinimumDiscountRule.php             # Skip if discount below threshold
└── CashAvailabilityRule.php            # Validate cash available for early payment
```

**DTOs to Create:**

```
src/DTOs/Payment/
├── EarlyPaymentDiscountRequest.php     # Input for discount calculation
├── EarlyPaymentDiscountResult.php      # Discount amount and due date
├── DynamicDiscountOfferRequest.php     # Dynamic discount offer
├── DiscountOpportunityContext.php      # Context for discount rules
└── DiscountOptimizationResult.php      # Optimal discount strategy
```

**DataProviders to Create:**

```
src/DataProviders/
├── DiscountOpportunityDataProvider.php # Invoices eligible for discounts
└── CashPositionDataProvider.php        # Available cash for early payment
```

**Events to Create:**

```
src/Events/Payment/
├── EarlyPaymentDiscountIdentifiedEvent.php  # Discount opportunity found
├── EarlyPaymentDiscountCapturedEvent.php    # Discount captured
├── DynamicDiscountOfferedEvent.php          # Dynamic discount offered
└── DiscountMissedEvent.php                  # Discount opportunity missed
```

**Enums to Create:**

```
src/Enums/
├── DiscountType.php                    # EARLY_PAYMENT, VOLUME, SEASONAL, DYNAMIC
└── DiscountStrategy.php                # MAXIMIZE_DISCOUNT, OPTIMIZE_CASH, BALANCED
```

**ValueObjects to Create:**

```
src/ValueObjects/
├── PaymentTerms.php                    # Immutable payment terms with discount
└── DiscountRate.php                    # Discount percentage with eligibility
```

**Atomic Package Dependencies:**

| Package | Interfaces Used |
|---------|-----------------|
| `Nexus\Finance` | `CashManagerInterface` (cash availability) |
| `Nexus\Setting` | `SettingsManagerInterface` (discount policies) |

**Integration Points:**
- Hook into `PaymentRunCoordinator` for discount-eligible invoice prioritization
- Link to `PaymentProcessingCoordinator` for discount application
- Feed into analytics for discount capture rate reporting

---

### Feature 7: Payment Run Workflow (5-7 days)

**Objective:** Batch payment processing with scheduled payment runs, approval workflow, and bank file generation.

**Business Scenarios:**
- Weekly/bi-weekly scheduled payment runs
- Check runs for mailed payments
- ACH batch processing
- Wire transfer approvals
- Multi-bank payment orchestration

**Coordinators to Create:**

```
src/Coordinators/
├── PaymentRunCoordinator.php           # Orchestrates payment run lifecycle (may exist from Phase A)
└── PaymentReconciliationCoordinator.php # Reconcile payments with bank confirmations
```

**Workflows to Create:**

```
src/Workflows/PaymentRun/
├── PaymentRunWorkflow.php              # Complete payment run workflow
└── Steps/
    ├── SelectInvoicesStep.php          # Select invoices for payment
    ├── ApplyDiscountsStep.php          # Apply early payment discounts
    ├── GroupByPaymentMethodStep.php    # Group by payment method/bank
    ├── ValidatePaymentsStep.php        # Validate all payments
    ├── GenerateBatchFilesStep.php      # Generate payment batch files
    ├── ApprovalStep.php                # Approve payment batch
    ├── ExecutePaymentsStep.php         # Execute payments
    └── GenerateRemittanceStep.php      # Generate remittance advice
```

**Services to Create:**

```
src/Services/Payment/
├── PaymentRunScheduler.php             # Schedule payment runs
├── PaymentBatchValidator.php           # Validate payment batch
├── RemittanceAdviceGenerator.php       # Generate remittance advice
├── PaymentStatusTracker.php            # Track payment status/confirmations
└── BankReconciliationService.php       # Reconcile with bank statements
```

**Rules to Create:**

```
src/Rules/Payment/
├── PaymentRunApprovalRule.php          # Determine approval requirements
├── PaymentBatchLimitRule.php           # Validate batch amount limits
├── BankBalanceRule.php                 # Validate sufficient bank balance
└── PaymentMethodAvailabilityRule.php   # Validate payment method available
```

**DTOs to Create:**

```
src/DTOs/Payment/
├── PaymentRunRequest.php               # Input for payment run (may exist from Phase A)
├── PaymentRunResult.php                # Payment run outcome (may exist from Phase A)
├── PaymentBatchRequest.php             # Payment batch creation
├── PaymentBatchResult.php              # Batch processing result
├── RemittanceAdviceData.php            # Remittance data (may exist from Phase A)
├── PaymentConfirmationData.php         # Bank payment confirmation
└── PaymentRunContext.php               # Context for payment run rules
```

**Events to Create:**

```
src/Events/Payment/
├── PaymentRunScheduledEvent.php        # Payment run scheduled
├── PaymentRunStartedEvent.php          # Payment run execution started
├── PaymentRunApprovedEvent.php         # Payment run approved
├── PaymentRunCompletedEvent.php        # Payment run completed (may exist from Phase A)
├── PaymentBatchGeneratedEvent.php      # Payment batch file generated
├── RemittanceAdviceSentEvent.php       # Remittance sent to vendor
├── PaymentConfirmedEvent.php           # Payment confirmed by bank
└── PaymentRunFailedEvent.php           # Payment run failed
```

**Enums to Create:**

```
src/Enums/
├── PaymentRunStatus.php                # SCHEDULED, IN_PROGRESS, PENDING_APPROVAL, APPROVED, EXECUTED, COMPLETED, FAILED
├── PaymentRunFrequency.php             # DAILY, WEEKLY, BIWEEKLY, MONTHLY, AD_HOC
├── PaymentConfirmationStatus.php       # PENDING, CONFIRMED, REJECTED, RETURNED
└── RemittanceMethod.php                # EMAIL, EDI, VENDOR_PORTAL, MAIL
```

**Atomic Package Dependencies:**

| Package | Interfaces Used |
|---------|-----------------|
| `Nexus\Finance` | `BankAccountRepositoryInterface`, `CashManagerInterface` |
| `Nexus\Scheduler` | `SchedulerInterface` (recurring payment runs) |
| `Nexus\Notifier` | `NotificationManagerInterface` (remittance advice) |
| `Nexus\Workflow` | `WorkflowInterface` |

**Integration Points:**
- Use `PaymentMethodSelector` from Phase A
- Integrate `EarlyPaymentDiscountService` for discount capture
- Link to bank file generation (deferred to Phase E)
- Feed payment confirmations to `PaymentReconciliationCoordinator`

---

### Feature 8: Payment Void/Cancellation (2 days)

**Objective:** Cancel or void issued payments with proper controls and audit trail.

**Business Scenarios:**
- Check stop payment requests
- ACH reversal before settlement
- Wire transfer cancellation
- Duplicate payment correction
- Vendor disputes requiring payment hold

**Coordinators to Create:**

```
src/Coordinators/
└── PaymentVoidCoordinator.php          # Manage payment void/cancel lifecycle
```

**Services to Create:**

```
src/Services/Payment/
├── PaymentVoidService.php              # Void/cancel payments
└── PaymentReversalService.php          # Reverse accounting entries
```

**Rules to Create:**

```
src/Rules/Payment/
├── VoidEligibilityRule.php             # Validate payment can be voided
├── VoidTimingRule.php                  # Validate void within allowed timeframe
├── VoidApprovalRule.php                # Require approval for void
└── ReversalAccountingRule.php          # Validate accounting impact
```

**DTOs to Create:**

```
src/DTOs/Payment/
├── PaymentVoidRequest.php              # Input for payment void (may exist from Phase A)
├── PaymentVoidResult.php               # Void outcome
├── PaymentReversalRequest.php          # Reversal request
└── PaymentVoidContext.php              # Context for void rules
```

**Events to Create:**

```
src/Events/Payment/
├── PaymentVoidRequestedEvent.php       # Void requested
├── PaymentVoidApprovedEvent.php        # Void approved
├── PaymentVoidedEvent.php              # Payment voided
├── PaymentReversedEvent.php            # Accounting reversed
└── PaymentVoidRejectedEvent.php        # Void request rejected
```

**Enums to Create:**

```
src/Enums/
├── VoidReason.php                      # DUPLICATE, WRONG_VENDOR, WRONG_AMOUNT, VENDOR_DISPUTE, ERROR
└── VoidStatus.php                      # REQUESTED, APPROVED, VOIDED, REJECTED
```

**Atomic Package Dependencies:**

| Package | Interfaces Used |
|---------|-----------------|
| `Nexus\Finance` | `GeneralLedgerInterface` (reverse accounting) |
| `Nexus\Payable` | `PayableManagerInterface` (reopen invoice) |

**Integration Points:**
- Link to SOD validation from Phase B (approver ≠ payer)
- Feed into audit trail for compliance reporting
- Update vendor balance via `PayableManagerInterface`

---

### Feature 9: Vendor Performance Tracking (3 days)

**Objective:** Track and score vendor performance based on quality, delivery, and price metrics for continuous improvement and vendor management.

**Business Scenarios:**
- Quarterly vendor performance reviews
- Preferred vendor qualification
- Vendor contract renewals
- Vendor corrective action plans
- Vendor awards and recognition

**Services to Create:**

```
src/Services/Vendor/
├── VendorPerformanceCalculator.php     # Calculate performance scores
├── VendorScorecardService.php          # Generate vendor scorecards
└── PerformanceTrendAnalyzer.php        # Analyze performance trends
```

**DataProviders to Create:**

```
src/DataProviders/
└── VendorPerformanceDataProvider.php   # Aggregates performance data (may exist from Feature 2)
```

**DTOs to Create:**

```
src/DTOs/Vendor/
├── VendorPerformanceRequest.php        # Input for performance calculation
├── VendorPerformanceResult.php         # Performance scores
├── VendorScorecardData.php             # Scorecard details
├── PerformanceMetricData.php           # Individual metric data
└── PerformanceTrendData.php            # Performance trend analysis
```

**Events to Create:**

```
src/Events/Vendor/
├── VendorPerformanceCalculatedEvent.php     # Performance scored
├── VendorPerformanceDegradedEvent.php       # Performance below threshold
├── VendorPerformanceImprovedEvent.php       # Performance improved
└── VendorScorecardGeneratedEvent.php        # Scorecard generated
```

**Enums to Create:**

```
src/Enums/
├── PerformanceMetricType.php           # QUALITY, DELIVERY, PRICE, RESPONSIVENESS
├── PerformanceRating.php               # EXCELLENT, GOOD, SATISFACTORY, POOR, UNACCEPTABLE
└── PerformancePeriod.php               # MONTHLY, QUARTERLY, ANNUAL, TRAILING_12M
```

**ValueObjects to Create:**

```
src/ValueObjects/
├── PerformanceScore.php                # Immutable performance score (0-100)
└── PerformanceThreshold.php            # Threshold for performance levels
```

**Atomic Package Dependencies:**

| Package | Interfaces Used |
|---------|-----------------|
| `Nexus\Reporting` | `ReportGeneratorInterface` (scorecards) |
| `Nexus\Setting` | `SettingsManagerInterface` (metric weights) |

**Integration Points:**
- Feed from `GoodsReceiptCoordinator` (on-time delivery, quality metrics)
- Feed from `InvoiceMatchingCoordinator` (pricing accuracy)
- Feed from `QualityInspectionCoordinator` (quality scores)
- Link to `VendorSegmentationService` for preferred vendor classification

---

### Feature 10: ASN (Advance Ship Notice) Processing (2 days)

**Objective:** Process advance ship notices from vendors to pre-populate goods receipt documents and reduce manual data entry.

**Business Scenarios:**
- Vendor sends ASN via EDI or portal
- ASN pre-populates GR with expected items
- Receiving team validates against ASN
- Exception handling for ASN discrepancies

**Services to Create:**

```
src/Services/GoodsReceipt/
├── ASNProcessingService.php            # Process ASN documents
├── ASNMatchingService.php              # Match ASN to PO
└── ASNValidationService.php            # Validate ASN data
```

**Rules to Create:**

```
src/Rules/GoodsReceipt/
├── ASNValidityRule.php                 # Validate ASN is valid
├── ASNPOMatchRule.php                  # Validate ASN matches PO
└── ASNQuantityRule.php                 # Validate ASN quantities reasonable
```

**DTOs to Create:**

```
src/DTOs/GoodsReceipt/
├── ASNProcessingRequest.php            # Input for ASN processing
├── ASNProcessingResult.php             # ASN processing outcome
├── ASNData.php                         # ASN document data
├── ASNLineData.php                     # ASN line item data
└── ASNDiscrepancy.php                  # ASN vs PO discrepancy
```

**Events to Create:**

```
src/Events/GoodsReceipt/
├── ASNReceivedEvent.php                # ASN received from vendor
├── ASNProcessedEvent.php               # ASN processed successfully
├── ASNMatchedToPOEvent.php             # ASN matched to PO
├── ASNDiscrepancyFoundEvent.php        # ASN discrepancy detected
└── ASNRejectedEvent.php                # ASN rejected (invalid)
```

**Enums to Create:**

```
src/Enums/
├── ASNStatus.php                       # RECEIVED, VALIDATED, MATCHED, DISCREPANT, REJECTED
└── ASNSource.php                       # EDI, VENDOR_PORTAL, EMAIL, MANUAL
```

**Atomic Package Dependencies:**

| Package | Interfaces Used |
|---------|-----------------|
| `Nexus\Connector` | `IntegrationInterface` (EDI ASN) |
| `Nexus\Document` | `DocumentManagerInterface` (ASN documents) |

**Integration Points:**
- Hook into `GoodsReceiptCoordinator` to pre-fill receipt from ASN
- Flag discrepancies for manual review before receipt
- Link to vendor performance for ASN accuracy tracking

---

## Deliverables Summary

| Category | Count | Details |
|----------|-------|---------|
| **Coordinators** | 8 | SourceToContract, RFQManagement, VendorOnboarding, QualityInspection, ServiceReceipt, ReturnToVendor, PaymentVoid, PaymentReconciliation |
| **Workflows** | 3 | RFQWorkflow, VendorOnboardingWorkflow, PaymentRunWorkflow |
| **Workflow Steps** | 20+ | Complete workflow steps for all 3 workflows |
| **Services** | 25+ | Sourcing, Vendor, Quality, Service, Returns, Payment, Performance, ASN |
| **Rules** | 35+ | Sourcing, Vendor, Quality, Service, Returns, Payment, Performance, ASN |
| **DTOs** | 50+ | Complete DTO coverage for all features |
| **DataProviders** | 8 | Sourcing, Vendor, Performance, Discount, Cash, ASN |
| **Events** | 50+ | Complete event coverage for all features |
| **Enums** | 25+ | Status, types, and classification enums |
| **ValueObjects** | 8+ | Immutable value objects for domain concepts |

---

## Atomic Package Dependencies

| Package | New Interfaces Used (Phase C) |
|---------|-------------------------------|
| `Nexus\Workflow` | `WorkflowInterface`, `WorkflowStepInterface`, `TaskInterface` |
| `Nexus\Inventory` | `InventoryManagerInterface`, `StockReservationInterface` |
| `Nexus\Warehouse` | `ShipmentManagerInterface` |
| `Nexus\Product` | `ProductRepositoryInterface` |
| `Nexus\Procurement` | `ServiceOrderRepositoryInterface` |
| `Nexus\Finance` | `BankAccountRepositoryInterface`, `CashManagerInterface`, `GeneralLedgerInterface` |
| `Nexus\Scheduler` | `SchedulerInterface` |
| `Nexus\Compliance` | `SanctionsScreeningInterface`, `BackgroundCheckInterface` |
| `Nexus\Connector` | `IntegrationInterface` |
| `Nexus\Reporting` | `ReportGeneratorInterface` |
| `Nexus\Party` | `VendorRepositoryInterface`, `VendorManagerInterface` |

**Note:** All packages from Phase A and Phase B remain as dependencies.

---

## Testing Strategy

### Unit Testing

**Coverage Target:** 100% of new services, rules, and coordinators

**Test Categories:**
1. **Service Tests** - Mock all repository interfaces
2. **Rule Tests** - Validate pass/fail scenarios
3. **Coordinator Tests** - Mock all service dependencies
4. **DTO Tests** - Validate factory methods and getters
5. **Enum Tests** - Validate behavior methods if any

**Example Test Files:**

```
tests/Unit/
├── Services/
│   ├── Sourcing/QuoteEvaluationServiceTest.php
│   ├── Vendor/VendorRiskAssessmentServiceTest.php
│   ├── Payment/EarlyPaymentDiscountServiceTest.php
│   └── Returns/ReturnAuthorizationServiceTest.php
├── Rules/
│   ├── Sourcing/MinimumQuotesRuleTest.php
│   ├── Vendor/VendorRiskThresholdRuleTest.php
│   └── Payment/DiscountEligibilityRuleTest.php
├── Coordinators/
│   ├── SourceToContractCoordinatorTest.php
│   ├── VendorOnboardingCoordinatorTest.php
│   └── ReturnToVendorCoordinatorTest.php
└── DTOs/
    └── Sourcing/QuoteEvaluationResultTest.php
```

### Integration Testing

**Test Scenarios:**

1. **End-to-End: RFQ to PO**
   - Create RFQ → Invite vendors → Receive quotes → Evaluate → Award → Create PO

2. **End-to-End: Vendor Onboarding**
   - Submit vendor → Risk assessment → Compliance check → Approve → Activate → First PO

3. **End-to-End: Quality Inspection Flow**
   - Receive goods → QC hold → Inspection → Pass → Release to stock
   - Receive goods → QC hold → Inspection → Fail → RTV

4. **End-to-End: Service Receipt to Payment**
   - Service completion → Service receipt → Invoice → Three-way match → Payment

5. **End-to-End: Payment Run with Discounts**
   - Identify discount opportunities → Select invoices → Apply discounts → Approve batch → Execute payments → Send remittance

6. **End-to-End: Return to Vendor**
   - QC failure → Initiate RTV → RMA approval → Ship return → Receive credit memo

**Integration Test Files:**

```
tests/Integration/
├── RFQToContractFlowTest.php
├── VendorOnboardingFlowTest.php
├── QualityInspectionFlowTest.php
├── ServiceReceiptToPaymentFlowTest.php
├── PaymentRunWithDiscountsTest.php
└── ReturnToVendorFlowTest.php
```

### Performance Testing

**Critical Paths to Test:**

1. **Payment Run Performance**
   - Test with 1,000+ invoices in single run
   - Validate batch generation performance

2. **Vendor Search/Deduplication**
   - Test with 10,000+ vendor records
   - Validate deduplication algorithm performance

3. **Quote Evaluation**
   - Test with 100+ quotes in single RFQ
   - Validate evaluation service performance

---

## Configuration Requirements

### Settings Keys

Phase C introduces new settings for tenant-specific configuration:

**RFQ Configuration:**

```php
'procurement.rfq.settings' => [
    'minimum_quotes_required' => 3,
    'quote_validity_days' => 30,
    'auto_award_enabled' => false,
    'evaluation_criteria' => [
        'price' => ['weight' => 0.60, 'enabled' => true],
        'delivery_time' => ['weight' => 0.20, 'enabled' => true],
        'quality_score' => ['weight' => 0.15, 'enabled' => true],
        'past_performance' => ['weight' => 0.05, 'enabled' => true],
    ],
];
```

**Vendor Onboarding Configuration:**

```php
'procurement.vendor.onboarding' => [
    'require_compliance_check' => true,
    'require_risk_assessment' => true,
    'require_sanctions_screening' => true,
    'require_background_check' => false,
    'auto_activate_low_risk' => false,
    'risk_thresholds' => [
        'low' => 30,      // Score 0-30
        'medium' => 60,   // Score 31-60
        'high' => 85,     // Score 61-85
        'critical' => 100, // Score 86-100
    ],
    'required_documents' => ['w9', 'insurance', 'bank_details'],
];
```

**Quality Inspection Configuration:**

```php
'procurement.quality.inspection' => [
    'auto_schedule_enabled' => true,
    'sample_size_percentage' => 10, // 10% sampling
    'hold_stock_pending_qc' => true,
    'inspection_categories_requiring_qc' => ['raw_materials', 'electronics', 'food', 'pharma'],
];
```

**Payment Run Configuration:**

```php
'procurement.payment.run' => [
    'default_frequency' => 'WEEKLY',
    'default_payment_day' => 'FRIDAY',
    'require_approval' => true,
    'approval_threshold_cents' => 10000000, // $100,000
    'enable_early_payment_discount' => true,
    'minimum_discount_percentage' => 1.0, // Skip if < 1%
    'auto_generate_remittance' => true,
    'remittance_method' => 'EMAIL',
];
```

**Vendor Performance Configuration:**

```php
'procurement.vendor.performance' => [
    'calculate_frequency' => 'MONTHLY',
    'metrics' => [
        'quality_score' => ['weight' => 0.40, 'threshold' => 95],
        'on_time_delivery' => ['weight' => 0.30, 'threshold' => 98],
        'price_competitiveness' => ['weight' => 0.20, 'threshold' => 90],
        'responsiveness' => ['weight' => 0.10, 'threshold' => 85],
    ],
    'performance_levels' => [
        'excellent' => 95,
        'good' => 85,
        'satisfactory' => 75,
        'poor' => 60,
        'unacceptable' => 0,
    ],
];
```

---

## Risk Assessment & Mitigation

| Risk | Impact | Probability | Mitigation Strategy |
|------|--------|-------------|---------------------|
| **Vendor onboarding complexity** | High | Medium | Start with core workflow; defer advanced risk scoring to Phase D |
| **RFQ evaluation subjectivity** | Medium | High | Provide configurable evaluation criteria; allow manual override with justification |
| **Quality inspection integration** | High | Medium | Define clear interface contracts; mock for testing without QC system |
| **Payment run performance** | High | Medium | Implement pagination; batch processing; background jobs for large runs |
| **Service receipt complexity** | Medium | Medium | Focus on time-based and milestone-based; defer complex deliverables to Phase D |
| **Early discount calculation** | Low | Low | Use battle-tested financial calculation libraries; comprehensive unit tests |
| **ASN format variations** | Medium | High | Support minimal ASN format initially; extend in Phase E for EDI |
| **Vendor performance metrics** | Medium | Medium | Provide sensible defaults; allow tenant customization via settings |

---

## Prerequisites & Dependencies

### Must Be Completed Before Phase C

**Phase B Completion Checklist:**

- [ ] Spend Policy Engine fully implemented and tested
- [ ] Approval Limits Configuration operational
- [ ] SOX Control Points implemented
- [ ] Withholding Tax Calculation operational
- [ ] Tax Validation Service complete
- [ ] Document Retention Policies configured
- [ ] Audit Trail Enhancement complete
- [ ] All Phase B coordinator updates integrated
- [ ] Phase B test suite passing at 100%

**Atomic Package Readiness:**

- [ ] `Nexus\Workflow` - Workflow engine operational
- [ ] `Nexus\Inventory` - Stock reservation/hold capability
- [ ] `Nexus\Finance` - Cash management and GL interfaces
- [ ] `Nexus\Scheduler` - Job scheduling capability
- [ ] `Nexus\Compliance` - Sanctions screening interface

---

## Implementation Sequence

### Week 1: Sourcing & Vendor Foundation
- Days 1-3: RFQ/Sourcing Workflow implementation
- Days 4-5: Vendor Onboarding Workflow (core workflow only)

### Week 2: Vendor Completion & Quality
- Days 1-2: Vendor Onboarding Workflow (risk assessment, compliance)
- Days 3-5: Quality Inspection Integration

### Week 3: Service & Returns
- Days 1-2: Service Receipt Processing
- Days 3-5: Return to Vendor (RTV)

### Week 4: Payment Optimization
- Days 1-2: Early Payment Discount Capture
- Days 3-5: Payment Run Workflow (core workflow)

### Week 5: Payment Completion & Performance
- Days 1-2: Payment Run Workflow (bank files preparation for Phase E)
- Day 3: Payment Void/Cancellation
- Days 4-5: Vendor Performance Tracking

### Week 6: ASN & Integration Testing
- Days 1-2: ASN Processing
- Days 3-5: Integration testing and bug fixes

---

## Success Criteria

### Functional Criteria

- [ ] RFQ workflow creates, evaluates, and awards quotes
- [ ] Vendor onboarding workflow approves/rejects vendors with risk scoring
- [ ] Quality inspection holds stock and triggers RTV on failure
- [ ] Service receipt enables three-way matching for services
- [ ] RTV workflow generates RMA and credit memos
- [ ] Early payment discounts are identified and captured automatically
- [ ] Payment run workflow batches and approves payments
- [ ] Payment void workflow cancels payments with accounting reversal
- [ ] Vendor performance is calculated and scored
- [ ] ASN pre-populates goods receipt documents

### Non-Functional Criteria

- [ ] All unit tests passing at 100% coverage
- [ ] Integration tests cover all end-to-end flows
- [ ] Performance tests validate payment run with 1,000+ invoices
- [ ] All new code follows CODING_GUIDELINES.md
- [ ] All new code follows Advanced Orchestrator Pattern v1.1
- [ ] Documentation updated (README, IMPLEMENTATION_SUMMARY)
- [ ] No security vulnerabilities introduced (CodeQL passing)

---

## Post-Phase C State

After completing Phase C, ProcurementOperations will have:

- ✅ **Procurement Coverage:** ~75% (up from ~50% post-Phase B)
- ✅ **Vendor Lifecycle:** Onboarding, risk assessment, performance tracking
- ✅ **Advanced Workflows:** RFQ/sourcing, quality inspection, service receipt, RTV
- ✅ **Payment Optimization:** Early discounts, payment runs, void/cancel
- ✅ **Operational Maturity:** Production-ready for most enterprise P2P scenarios

**Ready for:** Phase D (Analytics & Optimization) for spend analytics, cash flow forecasting, and maverick spend reporting.

---

## Deferred to Future Phases

### Phase D: Analytics & Optimization (2-3 weeks)
- Spend Analytics Dashboard
- AP Aging Reports
- Cash Flow Forecasting
- Vendor Spend Concentration Analysis
- Cycle Time Analysis
- Exception Reports

### Phase E: Integrations (3-4 weeks)
- Bank File Generation (NACHA, Positive Pay, SWIFT)
- EDI Support (850/810/856)
- External Tax Engine Integration (Avalara, Vertex)
- Vendor Portal Foundation
- Punch-out Catalog Integration (cXML/OCI)

### Beyond Phase E: Advanced Capabilities
- Multi-currency PO support (header/line level)
- Foreign currency payment processing
- Contract pricing engine with tiered pricing
- Consignment inventory receipt
- Subcontracting receipt flows
- Evaluated Receipt Settlement (ERS)
- Payment netting (intercompany)
- 1099 reporting and filing
- Recurring invoice templates

---

## References

### Related Documents
- `GAP_ANALYSIS_PROCUREMENT_OPERATIONS.md` - Complete gap analysis
- `PHASEA_GAP_IMPLEMENTATION.md` - Phase A completed features
- `PHASE_B_GAP_IMPLEMENTATION_PLAN.md` - Phase B implementation plan
- `CODING_GUIDELINES.md` - Mandatory coding standards
- `ARCHITECTURE.md` - Three-layer architecture principles

### Industry Benchmarks
- SAP Ariba Procurement
- Oracle Procurement Cloud
- Microsoft Dynamics 365 F&O
- NetSuite Procurement
- Coupa Procurement

---

**Document Status:** Complete  
**Next Review:** Before Phase C implementation kickoff  
**Owner:** Nexus Architecture Team  
**Last Updated:** December 10, 2025
