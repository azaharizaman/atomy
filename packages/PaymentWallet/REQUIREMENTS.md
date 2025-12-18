# Nexus\PaymentWallet Requirements Specification

**Package:** `nexus/payment-wallet`  
**Version:** 0.1.0  
**Status:** Draft  
**Last Updated:** December 18, 2025  
**Author:** Nexus Architecture Team

---

## 1. Executive Summary

The `Nexus\PaymentWallet` package provides integrations with digital wallets (Apple Pay, Google Pay, Samsung Pay), regional wallets (GrabPay, Touch 'n Go, Alipay), and Buy Now Pay Later (BNPL) providers (Klarna, Afterpay, Affirm, Atome).

### 1.1 Purpose

- Integrate with major digital wallet platforms
- Support regional payment wallets for Southeast Asia
- Enable Buy Now Pay Later payment options
- Handle wallet-specific payment flows
- Manage wallet tokenization and payment sessions

### 1.2 Scope

**In Scope:**
- Apple Pay integration
- Google Pay integration
- Samsung Pay integration
- GrabPay integration
- Touch 'n Go eWallet integration
- Alipay integration
- Klarna BNPL integration
- Afterpay/Clearpay integration
- Affirm integration
- Atome integration
- Wallet payment session management
- BNPL order management

**Out of Scope:**
- Traditional bank rails → `PaymentRails`
- Card gateway processing → `PaymentGateway`
- Bank account verification → `PaymentBank`
- Subscription billing → `PaymentRecurring`

---

## 2. Functional Requirements

### 2.1 Digital Wallet Abstraction

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-001 | System shall define WalletProviderInterface for all wallet implementations | P0 | 🔴 |
| WALLET-002 | System shall support multiple concurrent wallet providers | P0 | 🔴 |
| WALLET-003 | System shall normalize wallet responses to common format | P0 | 🔴 |
| WALLET-004 | System shall track wallet-specific transaction IDs | P0 | 🔴 |
| WALLET-005 | System shall support wallet availability checking | P1 | 🔴 |

### 2.2 Apple Pay Integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-010 | System shall support Apple Pay payment sessions | P0 | 🔴 |
| WALLET-011 | System shall validate Apple Pay merchant certificates | P0 | 🔴 |
| WALLET-012 | System shall decrypt Apple Pay payment tokens | P0 | 🔴 |
| WALLET-013 | System shall support Apple Pay on the web | P0 | 🔴 |
| WALLET-014 | System shall support Apple Pay in-app | P1 | 🔴 |
| WALLET-015 | System shall validate domain association files | P1 | 🔴 |

### 2.3 Google Pay Integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-020 | System shall support Google Pay payment sessions | P0 | 🔴 |
| WALLET-021 | System shall validate Google Pay signatures | P0 | 🔴 |
| WALLET-022 | System shall decrypt Google Pay payment tokens | P0 | 🔴 |
| WALLET-023 | System shall support Google Pay on the web | P0 | 🔴 |
| WALLET-024 | System shall support Google Pay in-app | P1 | 🔴 |
| WALLET-025 | System shall support tokenized card payment method | P1 | 🔴 |

### 2.4 Samsung Pay Integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-030 | System shall support Samsung Pay integration | P2 | 🔴 |
| WALLET-031 | System shall validate Samsung Pay certificates | P2 | 🔴 |
| WALLET-032 | System shall decrypt Samsung Pay tokens | P2 | 🔴 |

### 2.5 Regional Wallets - Southeast Asia

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-040 | System shall support GrabPay integration | P1 | 🔴 |
| WALLET-041 | System shall support GrabPay OAuth flow | P1 | 🔴 |
| WALLET-042 | System shall support GrabPay refunds | P1 | 🔴 |
| WALLET-043 | System shall support Touch 'n Go eWallet | P2 | 🔴 |
| WALLET-044 | System shall support Boost (Malaysia) | P3 | 🔴 |
| WALLET-045 | System shall support ShopeePay interface | P2 | 🔴 |

### 2.6 Regional Wallets - China

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-050 | System shall support Alipay integration | P2 | 🔴 |
| WALLET-051 | System shall support Alipay QR code generation | P2 | 🔴 |
| WALLET-052 | System shall support WeChat Pay interface | P3 | 🔴 |
| WALLET-053 | System shall support UnionPay SecurePay | P3 | 🔴 |

### 2.7 BNPL - Klarna Integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-060 | System shall support Klarna Pay Later | P0 | 🔴 |
| WALLET-061 | System shall support Klarna Pay in 3/4 | P0 | 🔴 |
| WALLET-062 | System shall support Klarna Financing | P1 | 🔴 |
| WALLET-063 | System shall create Klarna sessions | P0 | 🔴 |
| WALLET-064 | System shall capture Klarna orders | P0 | 🔴 |
| WALLET-065 | System shall refund Klarna orders | P0 | 🔴 |
| WALLET-066 | System shall handle Klarna webhooks | P0 | 🔴 |
| WALLET-067 | System shall support Klarna order management | P0 | 🔴 |

### 2.8 BNPL - Afterpay/Clearpay Integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-070 | System shall support Afterpay checkout flow | P1 | 🔴 |
| WALLET-071 | System shall capture Afterpay orders | P1 | 🔴 |
| WALLET-072 | System shall refund Afterpay orders | P1 | 🔴 |
| WALLET-073 | System shall support Afterpay order limits | P1 | 🔴 |
| WALLET-074 | System shall handle Afterpay webhooks | P1 | 🔴 |

### 2.9 BNPL - Affirm Integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-080 | System shall support Affirm checkout flow | P2 | 🔴 |
| WALLET-081 | System shall capture Affirm charges | P2 | 🔴 |
| WALLET-082 | System shall refund Affirm charges | P2 | 🔴 |
| WALLET-083 | System shall void Affirm charges | P2 | 🔴 |

### 2.10 BNPL - Atome Integration (SEA)

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-090 | System shall support Atome checkout flow | P1 | 🔴 |
| WALLET-091 | System shall capture Atome payments | P1 | 🔴 |
| WALLET-092 | System shall refund Atome payments | P1 | 🔴 |
| WALLET-093 | System shall handle Atome webhooks | P1 | 🔴 |

### 2.11 BNPL Order Management

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-100 | System shall track BNPL order lifecycle | P0 | 🔴 |
| WALLET-101 | System shall support partial capture | P1 | 🔴 |
| WALLET-102 | System shall support partial refund | P1 | 🔴 |
| WALLET-103 | System shall support order extension | P2 | 🔴 |
| WALLET-104 | System shall support shipping info update | P1 | 🔴 |
| WALLET-105 | System shall track BNPL payment schedule | P1 | 🔴 |

### 2.12 Events

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-110 | System shall emit WalletPaymentInitiatedEvent | P0 | 🔴 |
| WALLET-111 | System shall emit WalletPaymentCompletedEvent | P0 | 🔴 |
| WALLET-112 | System shall emit WalletPaymentFailedEvent | P0 | 🔴 |
| WALLET-113 | System shall emit BnplOrderCreatedEvent | P0 | 🔴 |
| WALLET-114 | System shall emit BnplOrderCapturedEvent | P0 | 🔴 |
| WALLET-115 | System shall emit BnplOrderRefundedEvent | P0 | 🔴 |
| WALLET-116 | System shall emit BnplPaymentScheduleEvent | P1 | 🔴 |

---

## 3. Non-Functional Requirements

### 3.1 Security

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-SEC-001 | Package shall never log decrypted payment tokens | P0 | 🔴 |
| WALLET-SEC-002 | Package shall validate all wallet signatures | P0 | 🔴 |
| WALLET-SEC-003 | Package shall use TLS 1.2+ for all API calls | P0 | 🔴 |
| WALLET-SEC-004 | Package shall store merchant certificates securely | P0 | 🔴 |
| WALLET-SEC-005 | Package shall support certificate rotation | P1 | 🔴 |

### 3.2 Performance

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-PERF-001 | Payment session creation shall complete in < 2s | P1 | 🔴 |
| WALLET-PERF-002 | Token decryption shall complete in < 100ms | P1 | 🔴 |

### 3.3 Reliability

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| WALLET-REL-001 | Package shall implement retry logic for wallet APIs | P0 | 🔴 |
| WALLET-REL-002 | Package shall handle wallet downtime gracefully | P0 | 🔴 |
| WALLET-REL-003 | Package shall implement idempotency | P0 | 🔴 |

---

## 4. Interface Specifications

### 4.1 Core Interfaces

```
WalletProviderInterface
├── createSession(SessionRequest $request): WalletSession
├── processPayment(PaymentData $data): WalletPaymentResult
├── refund(RefundRequest $request): RefundResult
├── getTransaction(string $transactionId): WalletTransaction
├── getName(): string
└── isAvailable(): bool

DigitalWalletInterface extends WalletProviderInterface
├── validateMerchantSession(MerchantValidation $validation): MerchantSession
├── decryptPaymentToken(EncryptedToken $token): DecryptedPaymentData
└── getPaymentMethodData(): PaymentMethodData

BnplProviderInterface extends WalletProviderInterface
├── createOrder(BnplOrderRequest $request): BnplOrder
├── captureOrder(string $orderId, ?Money $amount = null): CaptureResult
├── refundOrder(string $orderId, RefundRequest $request): RefundResult
├── getOrder(string $orderId): BnplOrder
├── extendOrder(string $orderId, int $days): void
└── updateShipping(string $orderId, ShippingInfo $shipping): void
```

### 4.2 Provider-Specific Interfaces

```
ApplePayProviderInterface extends DigitalWalletInterface
├── validateMerchant(AppleMerchantValidation $validation): AppleMerchantSession
├── decryptPaymentData(ApplePaymentToken $token): ApplePaymentData
└── getDomainAssociationFile(): string

GooglePayProviderInterface extends DigitalWalletInterface
├── validatePaymentData(GooglePaymentData $data): bool
├── getPaymentDataRequest(): array
└── decryptPaymentToken(GooglePaymentToken $token): PaymentCredential

KlarnaProviderInterface extends BnplProviderInterface
├── createKlarnaSession(KlarnaSessionRequest $request): KlarnaSession
├── authorizeKlarnaSession(string $sessionId, string $authorizationToken): KlarnaOrder
└── acknowledgeOrder(string $orderId): void
```

### 4.3 Value Objects

| Value Object | Purpose | Properties |
|--------------|---------|------------|
| `WalletSession` | Payment session | `sessionId`, `provider`, `expiresAt` |
| `WalletPaymentResult` | Payment result | `transactionId`, `status`, `amount` |
| `EncryptedToken` | Encrypted payment data | `token`, `signature`, `version` |
| `DecryptedPaymentData` | Decrypted card data | `pan`, `expiry`, `cryptogram` |
| `BnplOrder` | BNPL order | `orderId`, `status`, `amount`, `paymentSchedule` |
| `PaymentSchedule` | BNPL payment plan | `installments`, `firstPayment`, `recurringAmount` |
| `ShippingInfo` | Order shipping | `carrier`, `trackingNumber`, `shippedAt` |

### 4.4 Enums

```
WalletProvider
├── APPLE_PAY
├── GOOGLE_PAY
├── SAMSUNG_PAY
├── GRABPAY
├── TOUCH_N_GO
├── ALIPAY
├── WECHAT_PAY
├── KLARNA
├── AFTERPAY
├── AFFIRM
└── ATOME

WalletPaymentStatus
├── PENDING
├── AUTHORIZED
├── CAPTURED
├── FAILED
├── CANCELLED
└── REFUNDED

BnplOrderStatus
├── CREATED
├── AUTHORIZED
├── CAPTURED
├── PARTIALLY_CAPTURED
├── REFUNDED
├── PARTIALLY_REFUNDED
├── EXPIRED
└── CANCELLED

KlarnaPaymentOption
├── PAY_LATER (Pay in 30 days)
├── PAY_IN_3 (3 installments)
├── PAY_IN_4 (4 installments)
├── FINANCING (6-36 months)
└── DIRECT_DEBIT

ApplePayNetwork
├── VISA
├── MASTERCARD
├── AMEX
├── DISCOVER
├── JCB
└── MADA
```

---

## 5. Events

| Event | Trigger | Payload |
|-------|---------|---------|
| `WalletPaymentInitiatedEvent` | Payment started | sessionId, provider, amount |
| `WalletPaymentCompletedEvent` | Payment successful | transactionId, provider, amount |
| `WalletPaymentFailedEvent` | Payment failed | sessionId, provider, reason |
| `WalletRefundedEvent` | Refund processed | transactionId, refundAmount |
| `BnplOrderCreatedEvent` | BNPL order created | orderId, provider, amount |
| `BnplOrderAuthorizedEvent` | Customer authorized | orderId, authorizationToken |
| `BnplOrderCapturedEvent` | Order captured | orderId, capturedAmount |
| `BnplOrderRefundedEvent` | Order refunded | orderId, refundedAmount |
| `BnplInstallmentDueEvent` | Installment due | orderId, installmentNumber, amount |

---

## 6. Dependencies

### 6.1 Required Dependencies

| Package | Purpose |
|---------|---------|
| `nexus/payment` | Core payment interfaces |
| `nexus/common` | Money VO, common interfaces |

### 6.2 Optional Dependencies

| Package | Purpose |
|---------|---------|
| `nexus/crypto` | Token decryption, certificate handling |
| `nexus/connector` | HTTP client for API calls |

---

## 7. Provider Configuration

### 7.1 Apple Pay Configuration

```php
[
    'merchant_id' => 'merchant.com.example',
    'merchant_certificate' => '/path/to/merchant.pem',
    'merchant_key' => '/path/to/merchant.key',
    'domain_verification_file' => '/path/to/.well-known/apple-developer-merchantid-domain-association',
    'supported_networks' => ['visa', 'mastercard', 'amex'],
    'merchant_capabilities' => ['supports3DS'],
]
```

### 7.2 Klarna Configuration

```php
[
    'api_username' => 'xxx',
    'api_password' => 'xxx',
    'region' => 'eu', // or 'us', 'oc'
    'environment' => 'production', // or 'playground'
    'merchant_urls' => [
        'confirmation' => 'https://.../confirmation',
        'notification' => 'https://.../webhook',
    ],
]
```

### 7.3 GrabPay Configuration

```php
[
    'partner_id' => 'xxx',
    'partner_secret' => 'xxx',
    'merchant_id' => 'xxx',
    'environment' => 'production', // or 'staging'
    'country' => 'MY', // or 'SG', 'PH', etc.
]
```

---

## 8. Acceptance Criteria

1. Apple Pay integration must pass Apple Pay certification
2. Google Pay integration must pass Google Pay certification
3. BNPL providers must support full order lifecycle
4. All payment tokens must be handled securely
5. Regional wallets must support respective country requirements

---

## 9. Glossary

| Term | Definition |
|------|------------|
| **BNPL** | Buy Now Pay Later |
| **Payment Token** | Encrypted payment credentials from wallet |
| **Merchant Session** | Apple Pay session for merchant validation |
| **Payment Sheet** | Wallet UI for payment confirmation |
| **Cryptogram** | One-time dynamic security code for tokenized payments |
| **Installment** | One payment in a BNPL schedule |
| **Domain Association** | Apple Pay domain verification file |

---

## 10. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1.0 | 2025-12-18 | Nexus Team | Initial draft |
