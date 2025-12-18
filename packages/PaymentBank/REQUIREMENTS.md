# Nexus\PaymentBank Requirements Specification

**Package:** `nexus/payment-bank`  
**Version:** 0.1.0  
**Status:** Draft  
**Last Updated:** December 18, 2025  
**Author:** Nexus Architecture Team

---

## 1. Executive Summary

The `Nexus\PaymentBank` package provides bank integration capabilities including Open Banking (PSD2), Plaid connectivity, real-time payment initiation, and bank account verification. It enables direct bank-to-bank payments and account data access.

### 1.1 Purpose

- Integrate with Open Banking APIs (PSD2, FDX)
- Support Plaid for account linking and verification
- Enable real-time payment initiation
- Provide bank account verification services
- Support account balance and transaction retrieval

### 1.2 Scope

**In Scope:**
- Open Banking (PSD2) integration
- Plaid integration
- Yodlee integration interface
- Account verification (micro-deposits, instant)
- Bank account linking
- Payment initiation via bank APIs
- Account balance retrieval
- Transaction history retrieval

**Out of Scope:**
- ACH/Wire file generation → `PaymentRails`
- Card payments → `PaymentGateway`
- Digital wallets → `PaymentWallet`
- Recurring billing → `PaymentRecurring`

---

## 2. Functional Requirements

### 2.1 Open Banking Integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BANK-001 | System shall support PSD2 PISP (Payment Initiation) | P0 | 🔴 |
| BANK-002 | System shall support PSD2 AISP (Account Information) | P0 | 🔴 |
| BANK-003 | System shall manage Strong Customer Authentication (SCA) | P0 | 🔴 |
| BANK-004 | System shall support consent management lifecycle | P0 | 🔴 |
| BANK-005 | System shall support consent expiry and renewal | P1 | 🔴 |
| BANK-006 | System shall support multiple Open Banking providers | P1 | 🔴 |
| BANK-007 | System shall handle redirect-based authorization flows | P0 | 🔴 |
| BANK-008 | System shall support embedded authorization flows | P2 | 🔴 |

### 2.2 Plaid Integration

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BANK-010 | System shall support Plaid Link for account connection | P0 | 🔴 |
| BANK-011 | System shall support Plaid Auth for account numbers | P0 | 🔴 |
| BANK-012 | System shall support Plaid Balance for balance checks | P0 | 🔴 |
| BANK-013 | System shall support Plaid Transactions | P1 | 🔴 |
| BANK-014 | System shall support Plaid Identity for account holder verification | P1 | 🔴 |
| BANK-015 | System shall support Plaid Payment Initiation | P2 | 🔴 |
| BANK-016 | System shall handle Plaid webhook events | P0 | 🔴 |
| BANK-017 | System shall support Plaid item status tracking | P1 | 🔴 |

### 2.3 Account Verification

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BANK-020 | System shall support instant account verification (Plaid) | P0 | 🔴 |
| BANK-021 | System shall support micro-deposit verification | P0 | 🔴 |
| BANK-022 | System shall generate micro-deposit amounts | P0 | 🔴 |
| BANK-023 | System shall verify micro-deposit amount confirmation | P0 | 🔴 |
| BANK-024 | System shall support verification attempt limits | P1 | 🔴 |
| BANK-025 | System shall track verification status | P0 | 🔴 |
| BANK-026 | System shall support account ownership verification | P1 | 🔴 |
| BANK-027 | System shall support bank statement verification interface | P2 | 🔴 |

### 2.4 Account Linking

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BANK-030 | System shall support linking multiple bank accounts | P0 | 🔴 |
| BANK-031 | System shall store account metadata (bank name, type, last4) | P0 | 🔴 |
| BANK-032 | System shall support account nickname management | P2 | 🔴 |
| BANK-033 | System shall support account unlinking | P0 | 🔴 |
| BANK-034 | System shall track account link status | P0 | 🔴 |
| BANK-035 | System shall support re-authentication flows | P1 | 🔴 |

### 2.5 Payment Initiation

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BANK-040 | System shall support payment initiation via Open Banking | P0 | 🔴 |
| BANK-041 | System shall support instant payments (where available) | P1 | 🔴 |
| BANK-042 | System shall support scheduled payments | P2 | 🔴 |
| BANK-043 | System shall track payment initiation status | P0 | 🔴 |
| BANK-044 | System shall support payment confirmation callbacks | P0 | 🔴 |
| BANK-045 | System shall validate beneficiary details | P1 | 🔴 |

### 2.6 Balance and Transaction Retrieval

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BANK-050 | System shall retrieve current account balance | P0 | 🔴 |
| BANK-051 | System shall retrieve available balance | P0 | 🔴 |
| BANK-052 | System shall retrieve transaction history | P1 | 🔴 |
| BANK-053 | System shall support transaction date range filtering | P1 | 🔴 |
| BANK-054 | System shall normalize transaction categories | P2 | 🔴 |
| BANK-055 | System shall cache balance data with TTL | P1 | 🔴 |

### 2.7 Consent Management

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BANK-060 | System shall store consent tokens securely | P0 | 🔴 |
| BANK-061 | System shall track consent scope and permissions | P0 | 🔴 |
| BANK-062 | System shall track consent expiry dates | P0 | 🔴 |
| BANK-063 | System shall support consent revocation | P0 | 🔴 |
| BANK-064 | System shall emit consent expiry warnings | P1 | 🔴 |
| BANK-065 | System shall support consent renewal flows | P1 | 🔴 |

### 2.8 Events

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BANK-070 | System shall emit AccountLinkedEvent | P0 | 🔴 |
| BANK-071 | System shall emit AccountUnlinkedEvent | P0 | 🔴 |
| BANK-072 | System shall emit AccountVerifiedEvent | P0 | 🔴 |
| BANK-073 | System shall emit VerificationFailedEvent | P0 | 🔴 |
| BANK-074 | System shall emit PaymentInitiatedEvent | P0 | 🔴 |
| BANK-075 | System shall emit PaymentConfirmedEvent | P0 | 🔴 |
| BANK-076 | System shall emit ConsentGrantedEvent | P0 | 🔴 |
| BANK-077 | System shall emit ConsentRevokedEvent | P0 | 🔴 |
| BANK-078 | System shall emit ConsentExpiringEvent | P1 | 🔴 |

---

## 3. Non-Functional Requirements

### 3.1 Security

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BANK-SEC-001 | Package shall encrypt consent tokens at rest | P0 | 🔴 |
| BANK-SEC-002 | Package shall use TLS 1.2+ for all API calls | P0 | 🔴 |
| BANK-SEC-003 | Package shall mask account numbers in logs | P0 | 🔴 |
| BANK-SEC-004 | Package shall support mTLS for Open Banking | P1 | 🔴 |
| BANK-SEC-005 | Package shall validate bank certificates | P1 | 🔴 |

### 3.2 Compliance

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BANK-COMP-001 | Package shall comply with PSD2 requirements | P0 | 🔴 |
| BANK-COMP-002 | Package shall support GDPR data deletion requests | P0 | 🔴 |
| BANK-COMP-003 | Package shall log consent changes for audit | P0 | 🔴 |

### 3.3 Reliability

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| BANK-REL-001 | Package shall implement retry logic for bank APIs | P0 | 🔴 |
| BANK-REL-002 | Package shall handle bank downtime gracefully | P0 | 🔴 |
| BANK-REL-003 | Package shall support webhook retry processing | P1 | 🔴 |

---

## 4. Interface Specifications

### 4.1 Core Interfaces

```
BankConnectionInterface
├── connect(ConnectionRequest $request): AuthorizationUrl
├── handleCallback(CallbackData $data): LinkedAccount
├── disconnect(string $accountId): void
├── refreshConnection(string $accountId): void
└── getConnectionStatus(string $accountId): ConnectionStatus

AccountVerificationInterface
├── initiateInstantVerification(string $accountId): VerificationResult
├── initiateMicroDeposits(BankAccountDetails $details): MicroDepositSession
├── verifyMicroDeposits(string $sessionId, array $amounts): VerificationResult
├── getVerificationStatus(string $verificationId): VerificationStatus
└── getRemainingAttempts(string $sessionId): int

PaymentInitiationInterface
├── initiate(PaymentRequest $request): PaymentInitiation
├── getStatus(string $initiationId): PaymentStatus
├── cancel(string $initiationId): void
└── confirm(string $initiationId, ConfirmationData $data): PaymentResult

AccountDataInterface
├── getBalance(string $accountId): AccountBalance
├── getBalances(array $accountIds): array
├── getTransactions(string $accountId, DateRange $range): array
└── getAccountHolder(string $accountId): AccountHolder

ConsentManagerInterface
├── getActiveConsents(string $userId): array
├── revokeConsent(string $consentId): void
├── isConsentValid(string $consentId): bool
├── getConsentExpiry(string $consentId): ?DateTimeImmutable
└── renewConsent(string $consentId): ConsentRenewalResult
```

### 4.2 Provider-Specific Interfaces

```
PlaidProviderInterface extends BankConnectionInterface
├── exchangePublicToken(string $publicToken): PlaidItem
├── getItem(string $itemId): PlaidItem
├── getAuth(string $accessToken): PlaidAuth
└── handleWebhook(PlaidWebhook $webhook): void

OpenBankingProviderInterface extends BankConnectionInterface
├── registerPayment(PaymentRequest $request): PaymentConsent
├── executePayment(string $consentId, AuthorizationData $auth): Payment
└── getAccountConsent(string $consentId): AccountConsent
```

### 4.3 Value Objects

| Value Object | Purpose | Properties |
|--------------|---------|------------|
| `LinkedAccount` | Linked bank account | `accountId`, `bankName`, `accountType`, `lastFour`, `routingNumber` |
| `AccountBalance` | Account balance data | `current`, `available`, `currency`, `asOf` |
| `BankTransaction` | Transaction data | `transactionId`, `amount`, `date`, `description`, `category` |
| `MicroDepositSession` | Verification session | `sessionId`, `status`, `attempts`, `expiresAt` |
| `PaymentInitiation` | Initiated payment | `initiationId`, `status`, `amount`, `reference` |
| `Consent` | User consent | `consentId`, `scope`, `grantedAt`, `expiresAt` |

### 4.4 Enums

```
ConnectionStatus
├── CONNECTED
├── PENDING_VERIFICATION
├── REQUIRES_REAUTH
├── DISCONNECTED
└── ERROR

VerificationStatus
├── PENDING
├── PENDING_MICRO_DEPOSITS
├── AWAITING_CONFIRMATION
├── VERIFIED
├── FAILED
└── EXPIRED

VerificationMethod
├── INSTANT (Plaid instant)
├── MICRO_DEPOSIT
├── MANUAL_STATEMENT
└── BANK_STATEMENT_UPLOAD

AccountType
├── CHECKING
├── SAVINGS
├── MONEY_MARKET
├── CREDIT_CARD
├── LOAN
└── OTHER

ConsentScope
├── BALANCE_READ
├── TRANSACTIONS_READ
├── PAYMENT_INITIATION
└── ACCOUNT_DETAILS_READ

PlaidProduct
├── AUTH
├── TRANSACTIONS
├── BALANCE
├── IDENTITY
├── INVESTMENTS
└── LIABILITIES
```

---

## 5. Events

| Event | Trigger | Payload |
|-------|---------|---------|
| `AccountLinkedEvent` | Bank account connected | accountId, bankName, lastFour |
| `AccountUnlinkedEvent` | Bank account disconnected | accountId, reason |
| `AccountVerifiedEvent` | Account verified | accountId, method |
| `VerificationFailedEvent` | Verification failed | accountId, reason, attemptsLeft |
| `MicroDepositsInitiatedEvent` | Micro-deposits sent | sessionId, accountId |
| `PaymentInitiatedEvent` | Payment initiated | initiationId, amount |
| `PaymentConfirmedEvent` | Payment confirmed by bank | initiationId, confirmationId |
| `PaymentFailedEvent` | Payment failed | initiationId, reason |
| `ConsentGrantedEvent` | User granted consent | consentId, scope |
| `ConsentRevokedEvent` | User revoked consent | consentId, revokedBy |
| `ConsentExpiringEvent` | Consent expiring soon | consentId, expiresAt |
| `AccountReauthRequiredEvent` | Reauth needed | accountId, reason |

---

## 6. Dependencies

### 6.1 Required Dependencies

| Package | Purpose |
|---------|---------|
| `nexus/payment` | Core payment interfaces |
| `nexus/common` | Money VO, common interfaces |
| `nexus/connector` | HTTP client, OAuth handling |

### 6.2 Optional Dependencies

| Package | Purpose |
|---------|---------|
| `nexus/payment-rails` | ACH/Wire file generation from bank data |
| `nexus/crypto` | Consent token encryption |

---

## 7. Provider Configuration

### 7.1 Plaid Configuration

```php
[
    'client_id' => 'xxx',
    'secret' => 'xxx',
    'environment' => 'production', // or 'sandbox', 'development'
    'products' => ['auth', 'transactions', 'balance'],
    'country_codes' => ['US', 'CA'],
    'webhook_url' => 'https://...',
]
```

### 7.2 Open Banking Configuration

```php
[
    'provider' => 'truelayer', // or 'yapily', 'token.io'
    'client_id' => 'xxx',
    'client_secret' => 'xxx',
    'redirect_uri' => 'https://...',
    'signing_key' => '/path/to/key.pem',
    'certificate' => '/path/to/cert.pem',
]
```

---

## 8. Acceptance Criteria

1. Plaid integration must support Link flow with all error handling
2. Micro-deposit verification must prevent brute-force attempts
3. Open Banking flows must handle SCA properly
4. All consent tokens must be encrypted at rest
5. Account unlinking must trigger proper cleanup

---

## 9. Glossary

| Term | Definition |
|------|------------|
| **PSD2** | Payment Services Directive 2 (EU regulation) |
| **PISP** | Payment Initiation Service Provider |
| **AISP** | Account Information Service Provider |
| **SCA** | Strong Customer Authentication |
| **Plaid Link** | Plaid's UI component for bank connection |
| **Access Token** | Plaid token for accessing account data |
| **Public Token** | Temporary token from Plaid Link |
| **Micro-deposits** | Small test deposits for account verification |
| **FDX** | Financial Data Exchange (US open banking standard) |

---

## 10. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1.0 | 2025-12-18 | Nexus Team | Initial draft |
