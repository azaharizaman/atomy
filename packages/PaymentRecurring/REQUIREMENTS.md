# Nexus\PaymentRecurring Requirements Specification

**Package:** `azaharizaman/nexus-payment-recurring`  
**Version:** 0.1.0  
**Status:** Draft  
**Last Updated:** December 18, 2025  
**Author:** Nexus Architecture Team

---

## 1. Executive Summary

The `Nexus\PaymentRecurring` package provides subscription management, usage-based billing, recurring payment processing, and dunning management for failed payment recovery.

### 1.1 Purpose

- Manage subscription lifecycle (create, upgrade, downgrade, cancel)
- Support multiple billing models (fixed, usage-based, tiered, metered)
- Process recurring payments automatically
- Handle failed payment recovery (dunning)
- Calculate proration for plan changes

### 1.2 Scope

**In Scope:**
- Subscription creation and management
- Billing plan definitions
- Usage tracking and metering
- Recurring payment scheduling
- Dunning management and retry strategies
- Proration calculations
- Invoice generation for subscriptions
- Trial period management
- Coupon and discount application

**Out of Scope:**
- Payment execution → `Payment` core
- Gateway processing → `PaymentGateway`
- Bank account debits → `PaymentBank`
- One-time payments → `Payment` core

---

## 2. Functional Requirements

### 2.1 Subscription Management

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-001 | System shall create subscriptions with payment method | P0 | 🔴 |
| REC-002 | System shall support subscription status lifecycle | P0 | 🔴 |
| REC-003 | System shall support subscription upgrades | P0 | 🔴 |
| REC-004 | System shall support subscription downgrades | P0 | 🔴 |
| REC-005 | System shall support subscription pausing | P1 | 🔴 |
| REC-006 | System shall support subscription resumption | P1 | 🔴 |
| REC-007 | System shall support immediate cancellation | P0 | 🔴 |
| REC-008 | System shall support end-of-period cancellation | P0 | 🔴 |
| REC-009 | System shall support subscription renewal | P0 | 🔴 |
| REC-010 | System shall track subscription history | P1 | 🔴 |

### 2.2 Trial Period Management

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-015 | System shall support trial periods | P0 | 🔴 |
| REC-016 | System shall track trial end dates | P0 | 🔴 |
| REC-017 | System shall notify before trial expiration | P1 | 🔴 |
| REC-018 | System shall convert trial to paid automatically | P0 | 🔴 |
| REC-019 | System shall prevent multiple trials per customer | P2 | 🔴 |
| REC-020 | System shall support trial extension | P2 | 🔴 |

### 2.3 Billing Plan Management

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-025 | System shall define billing plans | P0 | 🔴 |
| REC-026 | System shall support monthly billing cycle | P0 | 🔴 |
| REC-027 | System shall support annual billing cycle | P0 | 🔴 |
| REC-028 | System shall support weekly billing cycle | P2 | 🔴 |
| REC-029 | System shall support custom billing intervals | P2 | 🔴 |
| REC-030 | System shall support plan versioning | P1 | 🔴 |
| REC-031 | System shall grandfather existing subscribers on plan changes | P1 | 🔴 |
| REC-032 | System shall support plan migration | P1 | 🔴 |

### 2.4 Pricing Models

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-035 | System shall support flat-rate pricing | P0 | 🔴 |
| REC-036 | System shall support per-seat pricing | P0 | 🔴 |
| REC-037 | System shall support tiered pricing | P1 | 🔴 |
| REC-038 | System shall support volume pricing | P1 | 🔴 |
| REC-039 | System shall support stair-step pricing | P2 | 🔴 |
| REC-040 | System shall support package pricing | P2 | 🔴 |
| REC-041 | System shall support metered billing | P1 | 🔴 |
| REC-042 | System shall support hybrid pricing (base + usage) | P1 | 🔴 |

### 2.5 Usage Tracking

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-050 | System shall record usage events | P0 | 🔴 |
| REC-051 | System shall aggregate usage by period | P0 | 🔴 |
| REC-052 | System shall support multiple usage metrics | P1 | 🔴 |
| REC-053 | System shall calculate usage-based charges | P0 | 🔴 |
| REC-054 | System shall support usage thresholds with alerts | P1 | 🔴 |
| REC-055 | System shall support prepaid usage credits | P2 | 🔴 |
| REC-056 | System shall support rollover credits | P2 | 🔴 |
| REC-057 | System shall support usage overage charges | P1 | 🔴 |

### 2.6 Billing Cycle Processing

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-060 | System shall calculate billing cycle dates | P0 | 🔴 |
| REC-061 | System shall handle month-end billing edge cases | P0 | 🔴 |
| REC-062 | System shall generate invoices at cycle end | P0 | 🔴 |
| REC-063 | System shall schedule automatic payment collection | P0 | 🔴 |
| REC-064 | System shall support billing date anchoring | P1 | 🔴 |
| REC-065 | System shall handle timezone differences | P1 | 🔴 |

### 2.7 Proration Calculation

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-070 | System shall prorate on mid-cycle upgrade | P0 | 🔴 |
| REC-071 | System shall prorate on mid-cycle downgrade | P0 | 🔴 |
| REC-072 | System shall support proration credits | P0 | 🔴 |
| REC-073 | System shall support proration debits | P0 | 🔴 |
| REC-074 | System shall calculate daily proration | P0 | 🔴 |
| REC-075 | System shall support "no proration" option | P1 | 🔴 |
| REC-076 | System shall support immediate charge on upgrade | P1 | 🔴 |
| REC-077 | System shall support credit on downgrade | P1 | 🔴 |

### 2.8 Discounts and Coupons

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-080 | System shall support percentage discounts | P0 | 🔴 |
| REC-081 | System shall support fixed amount discounts | P0 | 🔴 |
| REC-082 | System shall support coupon codes | P0 | 🔴 |
| REC-083 | System shall support discount duration (forever, once, repeating) | P0 | 🔴 |
| REC-084 | System shall validate coupon applicability | P0 | 🔴 |
| REC-085 | System shall track coupon redemptions | P1 | 🔴 |
| REC-086 | System shall support coupon limits (max uses, date range) | P1 | 🔴 |

### 2.9 Dunning Management

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-090 | System shall define dunning strategies | P0 | 🔴 |
| REC-091 | System shall retry failed payments automatically | P0 | 🔴 |
| REC-092 | System shall support configurable retry schedules | P0 | 🔴 |
| REC-093 | System shall update payment method on retry | P1 | 🔴 |
| REC-094 | System shall send dunning notifications | P0 | 🔴 |
| REC-095 | System shall mark subscription as past_due | P0 | 🔴 |
| REC-096 | System shall cancel subscription after max retries | P0 | 🔴 |
| REC-097 | System shall support grace period before cancellation | P1 | 🔴 |
| REC-098 | System shall recover subscription on successful payment | P0 | 🔴 |

### 2.10 Retry Strategies

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-100 | System shall support exponential backoff retry | P0 | 🔴 |
| REC-101 | System shall support fixed interval retry | P1 | 🔴 |
| REC-102 | System shall support smart retry (optimal time detection) | P2 | 🔴 |
| REC-103 | System shall limit retry attempts per period | P0 | 🔴 |
| REC-104 | System shall track retry history | P1 | 🔴 |

### 2.11 Subscription Metrics

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-110 | System shall calculate MRR (Monthly Recurring Revenue) | P1 | 🔴 |
| REC-111 | System shall calculate ARR (Annual Recurring Revenue) | P1 | 🔴 |
| REC-112 | System shall track churn rate | P1 | 🔴 |
| REC-113 | System shall track expansion revenue | P2 | 🔴 |
| REC-114 | System shall track contraction revenue | P2 | 🔴 |
| REC-115 | System shall calculate ARPU (Average Revenue Per User) | P2 | 🔴 |

### 2.12 Events

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-120 | System shall emit SubscriptionCreatedEvent | P0 | 🔴 |
| REC-121 | System shall emit SubscriptionActivatedEvent | P0 | 🔴 |
| REC-122 | System shall emit SubscriptionUpgradedEvent | P0 | 🔴 |
| REC-123 | System shall emit SubscriptionDowngradedEvent | P0 | 🔴 |
| REC-124 | System shall emit SubscriptionCancelledEvent | P0 | 🔴 |
| REC-125 | System shall emit SubscriptionRenewedEvent | P0 | 🔴 |
| REC-126 | System shall emit TrialEndingEvent | P1 | 🔴 |
| REC-127 | System shall emit PaymentFailedEvent | P0 | 🔴 |
| REC-128 | System shall emit PaymentRecoveredEvent | P0 | 🔴 |
| REC-129 | System shall emit UsageRecordedEvent | P1 | 🔴 |
| REC-130 | System shall emit InvoiceGeneratedEvent | P0 | 🔴 |

---

## 3. Non-Functional Requirements

### 3.1 Performance

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-PERF-001 | Proration calculation shall complete in < 50ms | P1 | 🔴 |
| REC-PERF-002 | Usage aggregation shall handle 1M events/day | P1 | 🔴 |
| REC-PERF-003 | Billing cycle processing shall scale horizontally | P1 | 🔴 |

### 3.2 Reliability

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-REL-001 | Billing must be idempotent (no duplicate charges) | P0 | 🔴 |
| REC-REL-002 | Subscription state transitions must be atomic | P0 | 🔴 |
| REC-REL-003 | Usage recording must be durable | P0 | 🔴 |
| REC-REL-004 | Dunning retries must survive restarts | P0 | 🔴 |

### 3.3 Auditability

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| REC-AUD-001 | All subscription changes must be logged | P0 | 🔴 |
| REC-AUD-002 | Billing calculations must be reproducible | P0 | 🔴 |
| REC-AUD-003 | Usage events must have timestamps | P0 | 🔴 |

---

## 4. Interface Specifications

### 4.1 Core Interfaces

```
SubscriptionManagerInterface
├── create(SubscriptionRequest $request): Subscription
├── upgrade(string $subscriptionId, Plan $plan): Subscription
├── downgrade(string $subscriptionId, Plan $plan): Subscription
├── cancel(string $subscriptionId, CancellationType $type): Subscription
├── pause(string $subscriptionId, ?DateTimeImmutable $resumeAt): Subscription
├── resume(string $subscriptionId): Subscription
├── renew(string $subscriptionId): Subscription
├── getSubscription(string $id): Subscription
└── getSubscriptionsByCustomer(string $customerId): array

PlanManagerInterface
├── createPlan(PlanDefinition $definition): Plan
├── updatePlan(string $planId, PlanDefinition $definition): Plan
├── archivePlan(string $planId): void
├── getPlan(string $id): Plan
├── listPlans(?PlanFilter $filter = null): array
└── migratePlan(string $planId, string $newPlanId, MigrationOptions $options): void

UsageTrackerInterface
├── record(UsageEvent $event): void
├── recordBatch(array $events): void
├── getUsage(string $subscriptionId, DateTimeImmutable $start, DateTimeImmutable $end): UsageAggregate
├── getUsageByMetric(string $subscriptionId, string $metric, DateTimeImmutable $start, DateTimeImmutable $end): float
└── calculateCharges(string $subscriptionId, BillingPeriod $period): Money
```

### 4.2 Billing Interfaces

```
BillingEngineInterface
├── generateInvoice(string $subscriptionId): Invoice
├── calculateProration(Subscription $subscription, Plan $newPlan): ProrationResult
├── applyDiscount(string $subscriptionId, Discount $discount): void
├── removeDiscount(string $subscriptionId, string $discountId): void
└── getBillingSchedule(string $subscriptionId): BillingSchedule

DunningManagerInterface
├── setStrategy(string $subscriptionId, DunningStrategy $strategy): void
├── handleFailedPayment(string $subscriptionId, PaymentFailure $failure): void
├── scheduleRetry(string $subscriptionId, DateTimeImmutable $retryAt): void
├── markRecovered(string $subscriptionId): void
├── getRetryHistory(string $subscriptionId): array
└── getDunningStatus(string $subscriptionId): DunningStatus

ProrationCalculatorInterface
├── calculate(Subscription $subscription, Plan $newPlan, DateTimeImmutable $effectiveDate): ProrationResult
├── calculateDailyRate(Plan $plan): Money
└── getDaysRemaining(BillingPeriod $period, DateTimeImmutable $asOf): int
```

### 4.3 Discount Interfaces

```
CouponManagerInterface
├── create(CouponDefinition $definition): Coupon
├── validate(string $code, string $subscriptionId): ValidationResult
├── apply(string $code, string $subscriptionId): AppliedDiscount
├── redeem(string $code, string $customerId): void
├── getCoupon(string $code): Coupon
└── deactivate(string $code): void
```

### 4.4 Value Objects

| Value Object | Purpose | Properties |
|--------------|---------|------------|
| `Subscription` | Subscription entity | `id`, `customerId`, `planId`, `status`, `currentPeriodStart`, `currentPeriodEnd` |
| `Plan` | Billing plan | `id`, `name`, `interval`, `intervalCount`, `amount`, `currency` |
| `BillingPeriod` | Period dates | `start`, `end`, `billingDate` |
| `ProrationResult` | Proration details | `creditAmount`, `debitAmount`, `netAmount`, `daysRemaining` |
| `UsageEvent` | Usage record | `subscriptionId`, `metric`, `quantity`, `timestamp`, `idempotencyKey` |
| `UsageAggregate` | Aggregated usage | `total`, `metrics`, `periodStart`, `periodEnd` |
| `DunningStatus` | Dunning state | `attempts`, `nextRetry`, `status`, `lastError` |
| `Coupon` | Discount coupon | `code`, `discountType`, `amount`, `duration`, `maxRedemptions` |
| `AppliedDiscount` | Active discount | `couponId`, `subscriptionId`, `appliedAt`, `expiresAt` |

### 4.5 Enums

```
SubscriptionStatus
├── TRIALING
├── ACTIVE
├── PAST_DUE
├── PAUSED
├── CANCELED
├── EXPIRED
└── INCOMPLETE

BillingInterval
├── DAY
├── WEEK
├── MONTH
├── QUARTER
└── YEAR

CancellationType
├── IMMEDIATE
├── END_OF_PERIOD
└── AT_DATE

DunningStatus
├── NONE
├── PENDING_RETRY
├── RETRYING
├── RECOVERED
├── FAILED
└── EXHAUSTED

PricingModel
├── FLAT_RATE
├── PER_SEAT
├── TIERED
├── VOLUME
├── STAIR_STEP
├── METERED
└── HYBRID

DiscountDuration
├── ONCE
├── REPEATING
└── FOREVER

ProrationBehavior
├── CREATE_PRORATIONS
├── NONE
└── ALWAYS_INVOICE
```

---

## 5. Events

| Event | Trigger | Payload |
|-------|---------|---------|
| `SubscriptionCreatedEvent` | Subscription created | subscriptionId, customerId, planId |
| `SubscriptionActivatedEvent` | Trial ended/first payment | subscriptionId, activatedAt |
| `SubscriptionUpgradedEvent` | Plan upgraded | subscriptionId, oldPlanId, newPlanId, proration |
| `SubscriptionDowngradedEvent` | Plan downgraded | subscriptionId, oldPlanId, newPlanId, effectiveAt |
| `SubscriptionCancelledEvent` | Subscription cancelled | subscriptionId, cancelledAt, effectiveAt |
| `SubscriptionPausedEvent` | Subscription paused | subscriptionId, pausedAt, resumeAt |
| `SubscriptionRenewedEvent` | Subscription renewed | subscriptionId, periodStart, periodEnd |
| `TrialEndingEvent` | Trial about to end | subscriptionId, trialEnd, daysRemaining |
| `PaymentFailedEvent` | Payment failed | subscriptionId, invoiceId, reason, retryAt |
| `PaymentRecoveredEvent` | Payment recovered after retry | subscriptionId, invoiceId, attempt |
| `UsageRecordedEvent` | Usage event recorded | subscriptionId, metric, quantity |
| `InvoiceGeneratedEvent` | Invoice generated | subscriptionId, invoiceId, amount |
| `CouponAppliedEvent` | Coupon applied | subscriptionId, couponCode, discount |

---

## 6. Dependencies

### 6.1 Required Dependencies

| Package | Purpose |
|---------|---------|
| `azaharizaman/nexus-payment` | Core payment interfaces for executing payments |
| `azaharizaman/nexus-common` | Money VO, common interfaces |
| `psr/event-dispatcher` | Domain event publishing |

### 6.2 Optional Dependencies

| Package | Purpose |
|---------|---------|
| `azaharizaman/nexus-scheduler` | Scheduling billing cycle jobs |
| `azaharizaman/nexus-notifier` | Dunning and trial notifications |
| `azaharizaman/nexus-receivable` | Invoice integration |

---

## 7. Retry Strategy Configuration

### 7.1 Default Dunning Schedule

```php
[
    'attempts' => [
        ['delay_days' => 1, 'notify' => false],
        ['delay_days' => 3, 'notify' => true],
        ['delay_days' => 5, 'notify' => true],
        ['delay_days' => 7, 'notify' => true],
    ],
    'grace_period_days' => 7,
    'final_action' => 'cancel',
    'notification_template' => 'dunning.payment_failed',
]
```

### 7.2 Smart Retry Configuration

```php
[
    'enabled' => true,
    'optimal_hours' => [9, 14, 18], // Local time
    'avoid_weekends' => true,
    'avoid_holidays' => true,
    'timezone_detection' => true,
]
```

---

## 8. Proration Examples

### 8.1 Mid-Cycle Upgrade

```
Current Plan: Basic ($10/month)
New Plan: Pro ($30/month)
Days used: 15 of 30
Days remaining: 15

Credit for unused Basic: $10 × (15/30) = $5.00
Charge for Pro period: $30 × (15/30) = $15.00
Net charge today: $15.00 - $5.00 = $10.00

Next full billing: $30.00 (full month)
```

### 8.2 Mid-Cycle Downgrade

```
Current Plan: Pro ($30/month)
New Plan: Basic ($10/month)
Days used: 20 of 30
Days remaining: 10

Credit for unused Pro: $30 × (10/30) = $10.00
Credit applied to next invoice
Effective immediately or at period end (configurable)
```

---

## 9. Acceptance Criteria

1. Subscriptions must transition through all lifecycle states correctly
2. Proration calculations must be accurate to the cent
3. Usage metering must not lose events
4. Dunning must recover at least 20% of failed payments
5. Billing must be idempotent (no duplicate charges)
6. All events must be emitted for integration
7. Trial conversion must happen automatically
8. Coupons must validate correctly

---

## 10. Glossary

| Term | Definition |
|------|------------|
| **MRR** | Monthly Recurring Revenue |
| **ARR** | Annual Recurring Revenue |
| **Dunning** | Process of recovering failed payments |
| **Proration** | Partial charge/credit for mid-cycle changes |
| **Churn** | Rate of subscription cancellations |
| **ARPU** | Average Revenue Per User |
| **Metered Billing** | Charging based on actual usage |
| **Seat** | Unit of subscription (e.g., user, license) |
| **Grace Period** | Time allowed after payment failure before cancellation |
| **Anchor Date** | Fixed billing date for all cycles |

---

## 11. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1.0 | 2025-12-18 | Nexus Team | Initial draft |
