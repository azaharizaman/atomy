# Nexus\PaymentGateway Requirements Specification

**Package:** `azaharizaman/nexus-payment-gateway`  
**Version:** 0.1.0  
**Status:** Draft  
**Last Updated:** December 18, 2025  
**Author:** Nexus Architecture Team

---

## 1. Executive Summary

The `Nexus\PaymentGateway` package provides integrations with third-party payment gateways including Stripe, PayPal, Square, Adyen, and other card processors. It handles tokenization, payment authorization, capture, refunds, and webhook processing.

### 1.1 Purpose

- Provide unified interface for multiple payment gateways
- Handle payment authorization and capture workflows
- Support card tokenization and secure storage
- Process gateway webhooks and status updates
- Support refunds and chargebacks

### 1.2 Scope

**In Scope:**
- Stripe integration
- PayPal integration
- Square integration
- Adyen integration
- Card tokenization
- Payment authorization/capture
- Refund processing
- Webhook handling
- Gateway health monitoring

**Out of Scope:**
- Traditional bank rails (ACH, Wire) → `PaymentRails`
- Open Banking/Plaid → `PaymentBank`
- Digital wallets (Apple Pay) → `PaymentWallet`
- Subscription billing → `PaymentRecurring`

---

## 2. Functional Requirements

### 2.1 Gateway Abstraction

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-001 | System shall define GatewayInterface for all gateway implementations | P0 | 🔴 |
| GW-002 | System shall support multiple concurrent gateway configurations | P0 | 🔴 |
| GW-003 | System shall support gateway selection strategies (primary/fallback) | P1 | 🔴 |
| GW-004 | System shall support gateway-specific configuration | P0 | 🔴 |
| GW-005 | System shall normalize gateway responses to common format | P0 | 🔴 |
| GW-006 | System shall track gateway transaction IDs | P0 | 🔴 |
| GW-007 | System shall support gateway health checking | P1 | 🔴 |

### 2.2 Payment Authorization

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-010 | System shall support payment authorization (hold funds) | P0 | 🔴 |
| GW-011 | System shall support authorization expiry tracking | P1 | 🔴 |
| GW-012 | System shall support authorization amount adjustment | P2 | 🔴 |
| GW-013 | System shall support authorization void/cancel | P0 | 🔴 |
| GW-014 | System shall track authorization status | P0 | 🔴 |

### 2.3 Payment Capture

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-020 | System shall support full capture of authorization | P0 | 🔴 |
| GW-021 | System shall support partial capture | P1 | 🔴 |
| GW-022 | System shall support multiple partial captures | P2 | 🔴 |
| GW-023 | System shall validate capture amount against authorization | P0 | 🔴 |
| GW-024 | System shall support auth-capture in single transaction | P0 | 🔴 |

### 2.4 Tokenization

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-030 | System shall support card tokenization via gateway | P0 | 🔴 |
| GW-031 | System shall NEVER store raw card numbers | P0 | 🔴 |
| GW-032 | System shall store gateway-provided tokens only | P0 | 🔴 |
| GW-033 | System shall support token reuse for repeat payments | P0 | 🔴 |
| GW-034 | System shall support token invalidation/deletion | P1 | 🔴 |
| GW-035 | System shall capture card metadata (last4, brand, expiry) | P0 | 🔴 |
| GW-036 | System shall track token expiration | P1 | 🔴 |

### 2.5 Refund Processing

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-040 | System shall support full refunds | P0 | 🔴 |
| GW-041 | System shall support partial refunds | P0 | 🔴 |
| GW-042 | System shall support multiple partial refunds | P1 | 🔴 |
| GW-043 | System shall validate refund amount against captured amount | P0 | 🔴 |
| GW-044 | System shall track refund status | P0 | 🔴 |
| GW-045 | System shall support refund reason tracking | P1 | 🔴 |

### 2.6 Chargeback Handling

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-050 | System shall receive chargeback notifications via webhook | P0 | 🔴 |
| GW-051 | System shall track chargeback status | P0 | 🔴 |
| GW-052 | System shall support chargeback evidence submission interface | P1 | ✅ |
| GW-053 | System shall track chargeback resolution | P1 | 🔴 |
| GW-054 | System shall emit chargeback events | P0 | 🔴 |

### 2.7 Webhook Processing

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-060 | System shall verify webhook signatures | P0 | 🔴 |
| GW-061 | System shall support Stripe webhook events | P0 | 🔴 |
| GW-062 | System shall support PayPal webhook events | P1 | ✅ |
| GW-063 | System shall support Square webhook events | P2 | ✅ |
| GW-064 | System shall support Adyen webhook events | P2 | ✅ |
| GW-065 | System shall handle duplicate webhook deliveries | P0 | 🔴 |
| GW-066 | System shall emit domain events from webhooks | P0 | 🔴 |
| GW-067 | System shall track webhook processing status | P1 | 🔴 |

### 2.8 Stripe Integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-070 | System shall support Stripe Payment Intents | P0 | 🔴 |
| GW-071 | System shall support Stripe Setup Intents | P0 | 🔴 |
| GW-072 | System shall support Stripe Customers | P0 | 🔴 |
| GW-073 | System shall support Stripe Payment Methods | P0 | 🔴 |
| GW-074 | System shall support Stripe Connected Accounts | P2 | 🔴 |
| GW-075 | System shall support Stripe Checkout Sessions | P2 | 🔴 |
| GW-076 | System shall support Stripe Radar fraud detection | P1 | 🔴 |

### 2.9 PayPal Integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-080 | System shall support PayPal Orders API | P1 | ✅ |
| GW-081 | System shall support PayPal Capture | P1 | ✅ |
| GW-082 | System shall support PayPal Refunds | P1 | ✅ |
| GW-083 | System shall support PayPal Vault (tokenization) | P1 | 🔴 |
| GW-084 | System shall support PayPal Express Checkout | P2 | 🔴 |

### 2.10 Square Integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-085 | System shall support Square Payments API | P2 | ✅ |
| GW-086 | System shall support Square Refunds | P2 | ✅ |
| GW-087 | System shall support Square Void | P2 | ✅ |

### 2.11 Adyen Integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-088 | System shall support Adyen Checkout API | P2 | ✅ |
| GW-089 | System shall support Adyen Capture | P2 | ✅ |
| GW-090 | System shall support Adyen Refunds | P2 | ✅ |
| GW-091 | System shall support Adyen Void | P2 | ✅ |

### 2.12 Events

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-100 | System shall emit PaymentAuthorizedEvent | P0 | 🔴 |
| GW-101 | System shall emit PaymentCapturedEvent | P0 | 🔴 |
| GW-102 | System shall emit PaymentRefundedEvent | P0 | 🔴 |
| GW-103 | System shall emit ChargebackReceivedEvent | P0 | 🔴 |
| GW-104 | System shall emit TokenCreatedEvent | P0 | 🔴 |
| GW-105 | System shall emit WebhookReceivedEvent | P1 | 🔴 |
| GW-106 | System shall emit GatewayErrorEvent | P0 | 🔴 |

---

## 3. Non-Functional Requirements

### 3.1 Security

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-SEC-001 | Package shall NEVER log raw card numbers | P0 | 🔴 |
| GW-SEC-002 | Package shall NEVER store raw card numbers | P0 | 🔴 |
| GW-SEC-003 | Package shall validate webhook signatures | P0 | 🔴 |
| GW-SEC-004 | Package shall use TLS 1.2+ for all gateway communication | P0 | 🔴 |
| GW-SEC-005 | Package shall support API key encryption at rest | P0 | 🔴 |
| GW-SEC-006 | Package shall support PCI DSS SAQ A compliance | P0 | 🔴 |

### 3.2 Reliability

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-REL-001 | Package shall implement idempotency for gateway calls | P0 | 🔴 |
| GW-REL-002 | Package shall support automatic retry with backoff | P0 | 🔴 |
| GW-REL-003 | Package shall implement circuit breaker pattern | P1 | 🔴 |
| GW-REL-004 | Package shall support gateway failover | P1 | 🔴 |

### 3.3 Performance

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| GW-PERF-001 | Gateway timeout shall be configurable (default 30s) | P0 | 🔴 |
| GW-PERF-002 | Webhook processing shall complete in < 5s | P1 | 🔴 |

---

## 4. Interface Specifications

### 4.1 Core Interfaces

```
GatewayInterface
├── authorize(AuthorizationRequest $request): AuthorizationResult
├── capture(CaptureRequest $request): CaptureResult
├── refund(RefundRequest $request): RefundResult
├── void(VoidRequest $request): VoidResult
├── getTransaction(string $transactionId): GatewayTransaction
└── getName(): string

TokenizerInterface
├── createToken(TokenizationRequest $request): PaymentToken
├── getToken(string $tokenId): ?PaymentToken
├── deleteToken(string $tokenId): void
└── listTokens(string $customerId): array

WebhookHandlerInterface
├── verifySignature(string $payload, string $signature, string $secret): bool
├── parse(string $payload): WebhookEvent
├── process(WebhookEvent $event): void
└── getEventType(string $payload): string

GatewayFactoryInterface
├── create(string $gatewayName, array $config): GatewayInterface
├── supports(string $gatewayName): bool
└── getAvailableGateways(): array
```

### 4.2 Gateway-Specific Interfaces

```
StripeGatewayInterface extends GatewayInterface
├── createPaymentIntent(array $params): PaymentIntent
├── confirmPaymentIntent(string $intentId): PaymentIntent
├── createSetupIntent(array $params): SetupIntent
└── createCustomer(array $params): Customer

PayPalGatewayInterface extends GatewayInterface
├── createOrder(array $params): Order
├── captureOrder(string $orderId): CaptureResult
└── vaultPaymentMethod(array $params): VaultedPaymentMethod
```

### 4.3 Value Objects

| Value Object | Purpose | Properties |
|--------------|---------|------------|
| `PaymentToken` | Tokenized payment method | `tokenId`, `gateway`, `lastFour`, `brand`, `expiryMonth`, `expiryYear` |
| `CardMetadata` | Card information | `brand`, `lastFour`, `expiryMonth`, `expiryYear`, `funding` |
| `AuthorizationResult` | Auth response | `authorizationId`, `status`, `amount`, `expiresAt` |
| `CaptureResult` | Capture response | `captureId`, `status`, `amount`, `fee` |
| `RefundResult` | Refund response | `refundId`, `status`, `amount` |
| `GatewayError` | Error details | `code`, `message`, `declineCode`, `retryable` |
| `WebhookEvent` | Parsed webhook | `eventId`, `eventType`, `payload`, `receivedAt` |

### 4.4 Enums

```
GatewayName
├── STRIPE
├── PAYPAL
├── SQUARE
├── ADYEN
├── BRAINTREE
└── AUTHORIZE_NET

AuthorizationStatus
├── PENDING
├── AUTHORIZED
├── PARTIALLY_CAPTURED
├── CAPTURED
├── VOIDED
├── EXPIRED
└── FAILED

CardBrand
├── VISA
├── MASTERCARD
├── AMEX
├── DISCOVER
├── DINERS
├── JCB
└── UNIONPAY

ChargebackStatus
├── PENDING
├── UNDER_REVIEW
├── WON
├── LOST
└── ACCEPTED

DeclineCode
├── INSUFFICIENT_FUNDS
├── CARD_DECLINED
├── EXPIRED_CARD
├── INVALID_CVC
├── FRAUD_SUSPECTED
├── VELOCITY_EXCEEDED
└── DO_NOT_HONOR
```

---

## 5. Events

| Event | Trigger | Payload |
|-------|---------|---------|
| `PaymentAuthorizedEvent` | Authorization successful | paymentId, authorizationId, amount |
| `PaymentCapturedEvent` | Capture successful | paymentId, captureId, amount, fee |
| `PaymentRefundedEvent` | Refund successful | paymentId, refundId, amount |
| `PaymentDeclinedEvent` | Authorization declined | paymentId, declineCode, message |
| `ChargebackReceivedEvent` | Chargeback initiated | paymentId, chargebackId, amount, reason |
| `ChargebackResolvedEvent` | Chargeback resolved | chargebackId, resolution |
| `TokenCreatedEvent` | Token created | tokenId, customerId, lastFour |
| `TokenDeletedEvent` | Token removed | tokenId, customerId |
| `WebhookReceivedEvent` | Webhook processed | webhookId, eventType, gateway |
| `GatewayErrorEvent` | Gateway error occurred | gateway, errorCode, message |
| `GatewayTimeoutEvent` | Gateway timed out | gateway, requestId, timeout |

---

## 6. Dependencies

### 6.1 Required Dependencies

| Package | Purpose |
|---------|---------|
| `azaharizaman/nexus-payment` | Core payment interfaces |
| `azaharizaman/nexus-common` | Money VO, common interfaces |
| `azaharizaman/nexus-connector` | HTTP client, circuit breaker |
| `psr/http-client` | HTTP abstraction |

### 6.2 Optional Dependencies

| Package | Purpose |
|---------|---------|
| `azaharizaman/nexus-crypto` | API key encryption |

---

## 7. Gateway Configuration

### 7.1 Stripe Configuration

```php
[
    'api_key' => 'sk_live_xxx',
    'webhook_secret' => 'whsec_xxx',
    'api_version' => '2023-10-16',
    'connect_enabled' => false,
]
```

### 7.2 PayPal Configuration

```php
[
    'client_id' => 'xxx',
    'client_secret' => 'xxx',
    'environment' => 'production', // or 'sandbox'
    'webhook_id' => 'xxx',
]
```

---

## 8. Acceptance Criteria

1. Package shall pass PCI DSS SAQ A compliance review
2. All gateway integrations must have 100% test coverage with mocks
3. Webhook signature verification must be cryptographically secure
4. Idempotency must prevent duplicate charges in all scenarios
5. Gateway errors must be normalized to common error codes

---

## 9. Glossary

| Term | Definition |
|------|------------|
| **Authorization** | Holding funds on a card without capturing |
| **Capture** | Completing a previously authorized payment |
| **Tokenization** | Replacing card data with a secure token |
| **Chargeback** | Dispute initiated by cardholder |
| **SAQ A** | PCI DSS Self-Assessment Questionnaire for merchants using hosted payment pages |
| **Payment Intent** | Stripe's object for tracking payment lifecycle |
| **Webhook** | Server-to-server notification from gateway |

---

## 10. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1.0 | 2025-12-18 | Nexus Team | Initial draft |
