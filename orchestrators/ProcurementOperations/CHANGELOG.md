# Changelog

All notable changes to the ProcurementOperations orchestrator will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0-phase-c] - 2025-01-15

### Added

#### SOX 404 Compliance Framework (STEP 1)
- Full SOX 404 compliance infrastructure with COSO framework mapping
- `ControlTestingService` - PCAOB-compliant control testing with sample size calculation
- `Sox404EvidenceService` - Evidence package generation for external audits
- `SodValidationService` - Segregation of duties validation and conflict detection
- `DeficiencyTrackingService` - Material weakness and significant deficiency tracking
- 15 new DTOs for SOX compliance data management
- 10 enums covering all SOX compliance states and classifications
- `SoxComplianceCoordinator` for compliance workflow orchestration

#### Vendor Portal Ecosystem (STEP 2)
- `VendorOnboardingWorkflow` - Complete vendor onboarding saga (7 steps)
- `VendorComplianceWorkflow` - Vendor compliance monitoring workflow
- `VendorOnboardingService` - Self-service vendor registration
- `PerformanceScoreService` - Vendor performance scoring and tiering
- `RiskAssessmentService` - Vendor risk evaluation
- `SupplierQualificationService` - Supplier qualification validation
- 11 vendor-specific DTOs for registration, compliance, and performance data

#### Financial Operations Core (STEP 3)
- `EarlyPaymentDiscountService` - Early payment discount capture optimization
- `PaymentMethodRouter` - Intelligent payment method selection
- `PaymentBatchOptimizer` - Payment batch optimization for cash flow
- `TaxWithholdingService` - Withholding tax calculation (1099, W-8BEN)
- Support for multiple payment methods: ACH, Wire, Check, Virtual Card
- 6 new financial rules for discount, tax, and currency management

#### Integration Services (STEP 4)
- `BankFileIntegrationService` - Bank file format generation (ACH, SWIFT, ISO20022)
- `EdiIntegrationService` - EDI 810/850/856 message handling
- `TaxReportingService` - Tax reporting and filing preparation
- Format mapping DTOs for bank and EDI integrations

#### Advanced Coordinators (STEP 5)
- `SpendPolicyCoordinator` - Spend policy enforcement with override handling
- `ComplianceMonitorCoordinator` - Real-time compliance monitoring
- `SpendAnalyticsCoordinator` - Spend analysis and maverick detection
- `SoxComplianceDataProvider` - SOX data aggregation
- `SpendAnalyticsDataProvider` - Spend analytics data aggregation

#### Multi-Entity Operations (STEP 6)
- `SharedServicesCenter` - Centralized shared services for multi-entity procurement
- `IntercompanySettlementService` - Intercompany transaction settlement
- `GlobalProcurementService` - Global procurement coordination
- `GlobalProcurementWorkflow` - Multi-entity procurement workflow
- `IntercompanySettlementWorkflow` - Settlement workflow with GL integration
- Cost allocation methods: EVEN, VOLUME_BASED, HEADCOUNT, CUSTOM

#### Audit & Retention (STEP 7)
- `DocumentRetentionServiceInterface` - Document retention management
- `ProcurementAuditServiceInterface` - SOX 404 audit operations
- `AuditTrailDataProvider` - Comprehensive audit data aggregation
- 10 retention categories with regulatory-compliant retention periods
- Legal hold management with matter tracking
- `RetentionPolicyRule` - Retention compliance validation
- `AuditComplianceRule` - SOX audit compliance checking
- `ControlTestingRule` - PCAOB control test validation
- `DisposalAuthorizationRule` - Document disposal authorization
- `SpendPolicyViolatedEvent` - Policy breach notifications
- `SpendPolicyOverrideRequestedEvent` - Override request tracking
- `MaverickSpendDetectedEvent` - Off-contract spend alerts

### Changed
- Updated README.md with comprehensive Phase C documentation
- Updated IMPLEMENTATION_SUMMARY.md with full Phase C metrics

### Security
- Enhanced segregation of duties validation across all workflows
- Legal hold enforcement prevents unauthorized document disposal
- Material weakness tracking for SOX 404 compliance

## [1.0.0] - 2025-12-07

### Added

#### Core Architecture
- Advanced Orchestrator Pattern v1.1 implementation
- Saga pattern for distributed transaction handling with compensation
- Abstract base classes for workflows and rules

#### Contracts Layer
- `SagaInterface`, `SagaStepInterface`, `SagaStateInterface`
- `WorkflowStorageInterface` for state persistence
- `ThreeWayMatchCoordinatorInterface`
- `PaymentProcessingCoordinatorInterface`
- `ProcurementCoordinatorInterface`
- `RuleInterface` for business rule abstraction

#### DataProviders
- `PaymentDataProvider` - Payment batch context aggregation
- `ProcurementDataProvider` - PO and GR data aggregation
- `ThreeWayMatchDataProvider` - Matching context aggregation
- `VendorSpendContextProvider` - Vendor analytics

#### Business Rules
- Procurement rules: PurchaseOrderExistsRule, GoodsReceiptExistsRule
- Matching rules: PriceVarianceRule, QuantityVarianceRule, ToleranceThresholdRule
- Payment rules: PaymentTermsMetRule, DuplicatePaymentRule, BankDetailsVerifiedRule
- Rule registries: ThreeWayMatchRuleRegistry, PaymentRuleRegistry

#### Services
- `PaymentBatchBuilder` - Build and optimize payment batches
- `ThreeWayMatchingService` - Execute 3-way matching logic
- `AccrualCalculator` - Calculate accrual entries
- `VarianceAnalyzer` - Analyze price/quantity variances

#### Coordinators
- `ThreeWayMatchCoordinator` - PO-GR-Invoice matching orchestration
- `PaymentProcessingCoordinator` - Payment scheduling and execution
- `ProcurementCoordinator` - End-to-end procurement orchestration

#### Workflows
- `ProcureToPayWorkflow` - 6-step P2P saga with compensation
- `RequisitionApprovalWorkflow` - Multi-level approval chain
- `InvoiceToPaymentWorkflow` - Invoice processing with variance handling

#### Workflow Steps
- CreateRequisitionStep, CreatePurchaseOrderStep, ReceiveGoodsStep
- ThreeWayMatchStep, CreateAccrualStep, ProcessPaymentStep

#### Events
- P2P workflow events: Started, Completed, Failed, StepCompleted
- Payment events: PaymentScheduledEvent, PaymentExecutedEvent
- Approval events: Started, Completed, Rejected

#### Listeners
- `SchedulePaymentOnInvoiceApproved` - Auto-schedule payments
- `UpdateVendorLedgerOnPayment` - Update AP ledger
- `NotifyVendorOnPaymentExecuted` - Send vendor notifications

### Dependencies
- `psr/event-dispatcher` ^1.0
- `psr/log` ^3.0
- PHP 8.3+

## [Unreleased]

### Planned for Phase D
- RFQ/RFP bidding workflow
- Catalog management integration
- Advanced analytics with ML predictions
- Mobile approval workflows
