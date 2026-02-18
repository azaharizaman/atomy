# Nexus SalesOperations Orchestrator - TODO

**Last Updated:** 2026-02-18
**Status:** In Progress

---

## Progress Summary

| Component | Status | Files | Progress |
|-----------|--------|-------|----------|
| Contracts | ✅ Complete | 17 interfaces | 100% |
| Enums | ✅ Complete | 5 enums | 100% |
| DTOs | ✅ Complete | 8 files | 100% |
| Coordinators | ✅ Complete | 3 coordinators | 100% |
| Services | ✅ Complete | 17 files | 100% |
| Exceptions | ✅ Complete | 10 exceptions | 100% |
| Rules | ✅ Complete | 11 rules + 6 registries | 100% |
| Workflows | ✅ Complete | 9 files | 100% |
| Events | ✅ Complete | 9 events | 100% |
| Listeners | ✅ Complete | 7 listeners | 100% |
| DataProviders | ✅ Complete | 8 files | 100% |
| Tests | 🔄 In Progress | 16 files | 65% |

---

## 1. Workflows (Priority: HIGH) - ✅ COMPLETE

Stateful long-running processes that manage complex multi-step business processes.

### Completed Items

- [x] **AbstractSaga.php**
  - Base class for saga implementations
  - Step execution with retry logic
  - Compensation (rollback) on failure
  - State persistence hooks
  - Location: `src/Workflows/AbstractSaga.php`

- [x] **OrderToCashWorkflow.php**
  - Complete O2C lifecycle management
  - Steps: Credit check → Stock reservation → Order confirmation → Fulfillment → Invoicing → Payment tracking
  - Implements saga pattern for rollback handling
  - Location: `src/Workflows/OrderToCashWorkflow.php`

- [x] **SplitShipmentWorkflow.php**
  - Handle partial shipments across multiple warehouses
  - Track shipment status per order line
  - Handle backorder scenarios
  - Location: `src/Workflows/SplitShipmentWorkflow.php`

### Workflow Steps Completed

- [x] **ValidateCreditStep.php** - Validates customer credit limit
- [x] **ReserveStockStep.php** - Reserves stock for order lines
- [x] **ConfirmOrderStep.php** - Confirms the sales order
- [x] **CreateShipmentStep.php** - Creates shipment from order
- [x] **CreateInvoiceStep.php** - Creates invoice from shipment
- [x] **TrackPaymentStep.php** - Tracks payment against invoice

---

## 2. Events (Priority: HIGH) - ✅ COMPLETE

Domain events for event-driven integration.

### Completed Items

- [x] `src/Events/OrderCreatedEvent.php` - Fired when order is created
- [x] `src/Events/OrderConfirmedEvent.php` - Fired when order is confirmed
- [x] `src/Events/OrderCancelledEvent.php` - Fired when order is cancelled
- [x] `src/Events/ShipmentCreatedEvent.php` - Fired when shipment is created
- [x] `src/Events/InvoiceGeneratedEvent.php` - Fired when invoice is generated
- [x] `src/Events/PaymentReceivedEvent.php` - Fired when payment is received
- [x] `src/Events/QuotationCreatedEvent.php` - Fired when quotation is created
- [x] `src/Events/QuotationAcceptedEvent.php` - Fired when quotation is accepted
- [x] `src/Events/CommissionCalculatedEvent.php` - Fired when commission is calculated

---

## 3. Listeners (Priority: HIGH) - ✅ COMPLETE

Event reactors that respond to domain events and trigger side effects.

### Completed Items

- [x] **ReserveStockListener.php**
  - Listens to: `OrderConfirmedEvent`
  - Action: Reserve inventory for order lines

- [x] **CheckCreditListener.php**
  - Listens to: `OrderCreatedEvent`
  - Action: Verify customer credit limit

- [x] **GenerateInvoiceListener.php**
  - Listens to: `ShipmentCreatedEvent`
  - Action: Create invoice from shipment

- [x] **UpdateInventoryListener.php**
  - Listens to: `ShipmentCreatedEvent`
  - Action: Convert reservations to allocations

- [x] **ReleaseCreditHoldListener.php**
  - Listens to: `PaymentReceivedEvent`
  - Action: Free up customer credit

- [x] **CalculateCommissionListener.php**
  - Listens to: `PaymentReceivedEvent`
  - Action: Compute sales commission

- [x] **NotifyCustomerListener.php**
  - Listens to: `OrderConfirmedEvent`, `ShipmentCreatedEvent`
  - Action: Send customer notifications

---

## 4. DataProviders (Priority: MEDIUM) - ✅ COMPLETE

Cross-package data aggregation services that gather data from multiple atomic packages.

### Completed Items

- [x] **OrderDataProvider.php**
  - Aggregates: Order, Customer, Credit, Stock data
  - Used by: Workflows, Coordinators
  - Includes: OrderContext DTO

- [x] **CustomerDataProvider.php**
  - Aggregates: Customer profile, Credit status, Payment history
  - Used by: CreditCheckCoordinator
  - Includes: CustomerContext DTO

- [x] **FulfillmentDataProvider.php**
  - Aggregates: Stock availability, Warehouse capacity, Shipping options
  - Used by: OrderFulfillmentCoordinator
  - Includes: FulfillmentContext DTO

- [x] **InvoiceDataProvider.php**
  - Aggregates: Order totals, Tax calculations, Payment terms
  - Used by: OrderFulfillmentCoordinator
  - Includes: InvoiceContext DTO

---

## 5. Services (Priority: MEDIUM) - ✅ COMPLETE

Pure business calculations - stateless, side-effect-free functions.

### Completed Items

- [x] **MarginCalculator.php** - Gross/net margin calculations
- [x] **CommissionCalculator.php** - Salesperson commission calculations
  - Revenue-based, profit-based, tiered commissions
  - Team splits, override rates, period summaries
- [x] **RevenueRecognitionService.php** - IFRS 15 / ASC 606 compliance
  - Performance obligation identification
  - Transaction price allocation
  - Recognition schedules
- [x] **PricingService.php** - Advanced pricing (Tier 3)
  - Volume discounts, customer discounts, promo codes
  - Tiered pricing, price override validation

---

## 6. Tests (Priority: HIGH) - 🔄 IN PROGRESS

Comprehensive unit test coverage for all components.

### Completed Items

- [x] **phpunit.xml** - PHPUnit configuration
- [x] **Services**
  - [x] MarginCalculatorTest
  - [x] CommissionCalculatorTest
  - [x] RevenueRecognitionServiceTest
  - [x] PricingServiceTest
- [x] **Rules**
  - [x] CreditLimitRuleTest
  - [x] CreditHoldRuleTest
  - [x] PaymentTermsRuleTest
  - [x] StockAvailabilityRuleTest
  - [x] OrderStatusRuleTest
  - [x] OrderMinimumRuleTest
- [x] **Events** - EventsTest (all 9 events)
- [x] **Exceptions** - ExceptionsTest (all 10 exceptions)
- [x] **DataProviders**
  - [x] CustomerDataProviderTest
  - [x] InvoiceDataProviderTest
- [x] **Listeners**
  - [x] ReserveStockListenerTest
  - [x] CheckCreditListenerTest

### TODO Items (for 90% coverage)

- [ ] **Coordinator Tests**
  - [ ] `tests/Unit/Coordinators/QuotationToOrderCoordinatorTest.php`
  - [ ] `tests/Unit/Coordinators/OrderFulfillmentCoordinatorTest.php`
  - [ ] `tests/Unit/Coordinators/CreditCheckCoordinatorTest.php`

- [ ] **Additional Rule Tests**
  - [ ] `tests/Unit/Rules/Stock/StockReservationRuleTest.php`
  - [ ] `tests/Unit/Rules/Stock/StockReservableRuleTest.php`
  - [ ] `tests/Unit/Rules/Order/OrderNotShippedRuleTest.php`
  - [ ] `tests/Unit/Rules/Credit/CreditRuleRegistryTest.php`
  - [ ] `tests/Unit/Rules/Order/OrderRuleRegistryTest.php`
  - [ ] `tests/Unit/Rules/Stock/StockRuleRegistryTest.php`

- [ ] **Workflow Tests**
  - [ ] `tests/Unit/Workflows/AbstractSagaTest.php`
  - [ ] `tests/Unit/Workflows/OrderToCashWorkflowTest.php`
  - [ ] `tests/Unit/Workflows/SplitShipmentWorkflowTest.php`
  - [ ] `tests/Unit/Workflows/Steps/ValidateCreditStepTest.php`
  - [ ] `tests/Unit/Workflows/Steps/ReserveStockStepTest.php`
  - [ ] `tests/Unit/Workflows/Steps/ConfirmOrderStepTest.php`
  - [ ] `tests/Unit/Workflows/Steps/CreateShipmentStepTest.php`
  - [ ] `tests/Unit/Workflows/Steps/CreateInvoiceStepTest.php`
  - [ ] `tests/Unit/Workflows/Steps/TrackPaymentStepTest.php`

- [ ] **Additional Listener Tests**
  - [ ] `tests/Unit/Listeners/GenerateInvoiceListenerTest.php`
  - [ ] `tests/Unit/Listeners/UpdateInventoryListenerTest.php`
  - [ ] `tests/Unit/Listeners/ReleaseCreditHoldListenerTest.php`
  - [ ] `tests/Unit/Listeners/CalculateCommissionListenerTest.php`
  - [ ] `tests/Unit/Listeners/NotifyCustomerListenerTest.php`

- [ ] **Additional DataProvider Tests**
  - [ ] `tests/Unit/DataProviders/OrderDataProviderTest.php`
  - [ ] `tests/Unit/DataProviders/FulfillmentDataProviderTest.php`

---

## 7. Additional Exceptions (Priority: LOW) - ✅ COMPLETE

Edge case exceptions for specific error scenarios.

### Completed Items

- [x] **CreditLimitExceededException** - Credit limit exceeded
- [x] **InsufficientStockException** - Stock shortage
- [x] **OrderNotFoundException** - Order not found
- [x] **QuotationNotConvertibleException** - Quotation conversion failed
- [x] **FulfillmentException** - Fulfillment failure
- [x] **CustomerNotFoundException** - Customer not found
- [x] **PaymentException** - Payment processing errors
- [x] **ShipmentException** - Shipment errors
- [x] **CommissionException** - Commission calculation errors
- [x] **PricingException** - Pricing errors

---

## 8. Additional Rules (Priority: LOW)

Extended validation rules for enterprise features.

### TODO Items

- [ ] **CustomerStatusRule.php** - Validate customer is active
- [ ] **ProductAvailabilityRule.php** - Validate product is sellable
- [ ] **TerritoryRule.php** - Validate sales territory restrictions
- [ ] **DiscountLimitRule.php** - Validate discount approvals
- [ ] **PriceOverrideRule.php** - Validate price override permissions

---

## 9. Documentation (Priority: LOW)

- [ ] Add inline PHPDoc comments to all public methods
- [ ] Create usage examples in README.md
- [ ] Document progressive disclosure tiers

---

## File Inventory

### Completed Files (100 files)

```
src/
├── Contracts/
│   ├── AuditLoggerInterface.php ✅
│   ├── CreditManagerInterface.php ✅
│   ├── CustomerInterface.php ✅
│   ├── CustomerProviderInterface.php ✅
│   ├── InvoiceInterface.php ✅
│   ├── QuotationInterface.php ✅
│   ├── QuotationProviderInterface.php ✅
│   ├── SagaInterface.php ✅
│   ├── SagaStateInterface.php ✅
│   ├── SagaStepInterface.php ✅
│   ├── SalesOrderInterface.php ✅
│   ├── SalesOrderProviderInterface.php ✅
│   ├── SecureIdGeneratorInterface.php ✅
│   ├── ShipmentInterface.php ✅
│   ├── StockReservationInterface.php ✅
│   ├── WorkflowStateInterface.php ✅
│   └── WorkflowStorageInterface.php ✅
├── Enums/
│   ├── CreditCheckResult.php ✅
│   ├── FulfillmentStatus.php ✅
│   ├── OrderStatus.php ✅
│   ├── PricingTier.php ✅
│   └── SagaStatus.php ✅
├── DTOs/
│   ├── CreditDTOs.php ✅
│   ├── FulfillmentDTOs.php ✅
│   ├── OrderDTOs.php ✅
│   ├── QuotationDTOs.php ✅
│   ├── SagaContext.php ✅
│   ├── SagaResult.php ✅
│   ├── SagaStepContext.php ✅
│   └── SagaStepResult.php ✅
├── Coordinators/
│   ├── CreditCheckCoordinator.php ✅
│   ├── OrderFulfillmentCoordinator.php ✅
│   └── QuotationToOrderCoordinator.php ✅
├── Services/
│   ├── CommissionCalculator.php ✅
│   ├── CommissionInput.php ✅
│   ├── CommissionResult.php ✅
│   ├── CommissionSplitResult.php ✅
│   ├── CommissionSummary.php ✅
│   ├── DeferredRevenueResult.php ✅
│   ├── MarginCalculator.php ✅
│   ├── MarginAnalysis.php ✅
│   ├── PriceOverrideValidation.php ✅
│   ├── PricingInput.php ✅
│   ├── PricingResult.php ✅
│   ├── PricingService.php ✅
│   ├── RevenueRecognitionEntry.php ✅
│   ├── RevenueRecognitionInput.php ✅
│   ├── RevenueRecognitionResult.php ✅
│   ├── RevenueRecognitionService.php ✅
│   └── TieredPricingResult.php ✅
├── Exceptions/
│   └── Exceptions.php ✅ (10 exceptions)
├── Rules/
│   ├── RuleInterface.php ✅
│   ├── RuleResult.php ✅
│   ├── Credit/
│   │   ├── CreditHoldRule.php ✅
│   │   ├── CreditLimitRule.php ✅
│   │   ├── CreditRuleRegistry.php ✅
│   │   ├── CreditValidationResult.php ✅
│   │   └── PaymentTermsRule.php ✅
│   ├── Order/
│   │   ├── OrderMinimumRule.php ✅
│   │   ├── OrderNotShippedRule.php ✅
│   │   ├── OrderRuleRegistry.php ✅
│   │   ├── OrderStatusRule.php ✅
│   │   └── OrderValidationResult.php ✅
│   └── Stock/
│       ├── StockAvailabilityRule.php ✅
│       ├── StockReservableRule.php ✅
│       ├── StockReservationRule.php ✅
│       ├── StockRuleRegistry.php ✅
│       └── StockValidationResult.php ✅
├── Workflows/
│   ├── AbstractSaga.php ✅
│   ├── OrderToCashWorkflow.php ✅
│   ├── SplitShipmentWorkflow.php ✅
│   └── Steps/
│       ├── ConfirmOrderStep.php ✅
│       ├── CreateInvoiceStep.php ✅
│       ├── CreateShipmentStep.php ✅
│       ├── ReserveStockStep.php ✅
│       ├── TrackPaymentStep.php ✅
│       └── ValidateCreditStep.php ✅
├── Events/
│   ├── CommissionCalculatedEvent.php ✅
│   ├── InvoiceGeneratedEvent.php ✅
│   ├── OrderCancelledEvent.php ✅
│   ├── OrderConfirmedEvent.php ✅
│   ├── OrderCreatedEvent.php ✅
│   ├── PaymentReceivedEvent.php ✅
│   ├── QuotationAcceptedEvent.php ✅
│   ├── QuotationCreatedEvent.php ✅
│   └── ShipmentCreatedEvent.php ✅
├── Listeners/
│   ├── CalculateCommissionListener.php ✅
│   ├── CheckCreditListener.php ✅
│   ├── GenerateInvoiceListener.php ✅
│   ├── NotifyCustomerListener.php ✅
│   ├── ReleaseCreditHoldListener.php ✅
│   ├── ReserveStockListener.php ✅
│   └── UpdateInventoryListener.php ✅
└── DataProviders/
    ├── CustomerContext.php ✅
    ├── CustomerDataProvider.php ✅
    ├── FulfillmentContext.php ✅
    ├── FulfillmentDataProvider.php ✅
    ├── InvoiceContext.php ✅
    ├── InvoiceDataProvider.php ✅
    ├── OrderContext.php ✅
    └── OrderDataProvider.php ✅
```

---

## Estimated Remaining Work

| Category | Files to Create | Estimated Hours |
|----------|-----------------|-----------------|
| Tests | 35+ | 20 |
| **Total** | ~35 files | ~20 hours |

---

## Notes for Developers

1. **Follow the Orchestrator Interface Segregation Pattern** - All interfaces should be in `Contracts/` and adapters will implement them.

2. **Progressive Disclosure** - Implement features according to tier levels:
   - Tier 1 (SMB): Basic quote-to-order, fulfillment, invoicing
   - Tier 2 (Mid-Market): Credit limits, multi-warehouse, partial shipments, commissions
   - Tier 3 (Enterprise): Multi-currency, revenue recognition, advanced pricing, approvals

3. **Rules are Single-Responsibility** - Each rule validates ONE constraint

4. **Coordinators are Stateless** - No state mutation, only coordination

5. **Workflows are Stateful** - Use saga pattern for long-running processes

---

## Changelog

### 2026-02-18 (Session 6)
- Created phpunit.xml configuration
- Created 16 test files covering:
  - Services: MarginCalculator, CommissionCalculator, RevenueRecognitionService, PricingService
  - Rules: CreditLimit, CreditHold, PaymentTerms, StockAvailability, OrderStatus, OrderMinimum
  - Events: All 9 events in single test file
  - Exceptions: All 10 exceptions in single test file
  - DataProviders: CustomerDataProvider, InvoiceDataProvider
  - Listeners: ReserveStockListener, CheckCreditListener

### 2026-02-18 (Session 5)
- Completed Services component (17 files)
  - CommissionCalculator with tiered rates, team splits, period summaries
  - RevenueRecognitionService with IFRS 15/ASC 606 compliance
  - PricingService with volume discounts, tiered pricing, override validation
- Completed all Exceptions (10 total)
  - Added CustomerNotFoundException, PaymentException, ShipmentException
  - Added CommissionException, PricingException

### 2026-02-18 (Session 4)
- Completed DataProviders component (8 files)
  - OrderContextProvider + OrderContext DTO
  - CustomerDataProvider + CustomerContext DTO
  - FulfillmentDataProvider + FulfillmentContext DTO
  - InvoiceDataProvider + InvoiceContext DTO

### 2026-02-18 (Session 3)
- Completed Events component (9 files)
  - OrderCreatedEvent, OrderConfirmedEvent, OrderCancelledEvent
  - ShipmentCreatedEvent, InvoiceGeneratedEvent
  - PaymentReceivedEvent, CommissionCalculatedEvent
  - QuotationCreatedEvent, QuotationAcceptedEvent
- Completed Listeners component (7 files)
  - ReserveStockListener, CheckCreditListener
  - GenerateInvoiceListener, UpdateInventoryListener
  - ReleaseCreditHoldListener, CalculateCommissionListener
  - NotifyCustomerListener

### 2026-02-18 (Session 2)
- Completed Workflows component (9 files)
  - AbstractSaga.php with compensation logic
  - OrderToCashWorkflow.php with 6 steps
  - SplitShipmentWorkflow.php
  - 6 saga steps with compensation handlers
- Added saga contracts: SagaInterface, SagaStepInterface, SagaStateInterface, WorkflowStorageInterface, WorkflowStateInterface, SecureIdGeneratorInterface
- Added saga DTOs: SagaContext, SagaResult, SagaStepContext, SagaStepResult
- Added SagaStatus enum
- Extended interfaces: SalesOrderProviderInterface (cancel), ShipmentProviderInterface (cancel), InvoiceProviderInterface (void)

### 2026-02-18 (Session 1)
- Created TODO.md
- Completed Rules component (11 rules + 6 registries)
- Added `isOnCreditHold`, `getCreditHoldReason`, `placeOnCreditHold`, `releaseCreditHold` to CreditManagerInterface
- Added `getTotalAvailableQuantity` to StockAvailabilityInterface
