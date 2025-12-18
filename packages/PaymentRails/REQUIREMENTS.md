# Nexus\PaymentRails Requirements Specification

**Package:** `nexus/payment-rails`  
**Version:** 0.1.0  
**Status:** Draft  
**Last Updated:** December 18, 2025  
**Author:** Nexus Architecture Team

---

## 1. Executive Summary

The `Nexus\PaymentRails` package provides implementations for traditional payment rails including ACH, Wire Transfer, Check, RTGS, and Virtual Cards. It extends `Nexus\Payment` to provide file generation, validation, and processing for bank-based payment methods.

### 1.1 Purpose

- Generate bank-compliant payment files (NACHA, SWIFT, ISO 20022)
- Support ACH credit/debit transactions
- Support domestic and international wire transfers
- Support check printing and positive pay
- Support virtual card issuance and management

### 1.2 Scope

**In Scope:**
- ACH file generation (NACHA format)
- Wire transfer file generation (SWIFT MT, ISO 20022)
- RTGS/GIRO file generation
- Check file generation and positive pay
- Virtual card issuance interfaces
- Bank account validation (routing/account numbers)
- Pre-notification handling

**Out of Scope:**
- Payment gateway integrations → `PaymentGateway`
- Open Banking/Plaid → `PaymentBank`
- Digital wallets → `PaymentWallet`
- Recurring billing logic → `PaymentRecurring`

---

## 2. Functional Requirements

### 2.1 ACH Processing

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| RAIL-001 | System shall generate NACHA-compliant ACH files | P0 | 🔴 |
| RAIL-002 | System shall support ACH credit transactions | P0 | 🔴 |
| RAIL-003 | System shall support ACH debit transactions | P0 | 🔴 |
| RAIL-004 | System shall support Standard Entry Class (SEC) codes: PPD, CCD, WEB, TEL | P0 | 🔴 |
| RAIL-005 | System shall generate batch headers and control records | P0 | 🔴 |
| RAIL-006 | System shall generate file headers and control records | P0 | 🔴 |
| RAIL-007 | System shall support addenda records | P1 | 🔴 |
| RAIL-008 | System shall validate routing numbers (ABA checksum) | P0 | 🔴 |
| RAIL-009 | System shall support pre-notification (prenote) entries | P1 | 🔴 |
| RAIL-010 | System shall support NOC (Notification of Change) handling | P1 | 🔴 |
| RAIL-011 | System shall support ACH return processing | P0 | 🔴 |
| RAIL-012 | System shall enforce ACH processing cutoff times | P1 | 🔴 |
| RAIL-013 | System shall support same-day ACH | P2 | 🔴 |
| RAIL-014 | System shall generate balanced ACH batches | P0 | 🔴 |

### 2.2 Wire Transfer Processing

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| RAIL-020 | System shall support domestic wire transfers | P0 | 🔴 |
| RAIL-021 | System shall support international wire transfers (SWIFT) | P1 | 🔴 |
| RAIL-022 | System shall generate SWIFT MT103 messages | P1 | 🔴 |
| RAIL-023 | System shall generate ISO 20022 pain.001 messages | P2 | 🔴 |
| RAIL-024 | System shall validate SWIFT/BIC codes | P1 | 🔴 |
| RAIL-025 | System shall validate IBAN format | P1 | 🔴 |
| RAIL-026 | System shall support intermediary bank information | P1 | 🔴 |
| RAIL-027 | System shall track wire transfer status | P0 | 🔴 |
| RAIL-028 | System shall support wire transfer fee handling | P1 | 🔴 |
| RAIL-029 | System shall support beneficiary reference information | P0 | 🔴 |

### 2.3 RTGS/Real-Time Gross Settlement

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| RAIL-030 | System shall support RTGS transaction creation | P1 | 🔴 |
| RAIL-031 | System shall support DuitNow (Malaysia) format | P2 | 🔴 |
| RAIL-032 | System shall support GIRO file generation | P2 | 🔴 |
| RAIL-033 | System shall validate local clearing codes | P1 | 🔴 |
| RAIL-034 | System shall support instant payment rails | P2 | 🔴 |

### 2.4 Check Processing

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| RAIL-040 | System shall generate check print files | P1 | 🔴 |
| RAIL-041 | System shall support positive pay file generation | P1 | 🔴 |
| RAIL-042 | System shall track check numbers and sequences | P1 | 🔴 |
| RAIL-043 | System shall support check void/stop payment | P1 | 🔴 |
| RAIL-044 | System shall support check clearing status | P1 | 🔴 |
| RAIL-045 | System shall support MICR line generation | P2 | 🔴 |
| RAIL-046 | System shall support remote deposit capture interface | P2 | 🔴 |

### 2.5 Virtual Card Processing

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| RAIL-050 | System shall support virtual card issuance interface | P1 | 🔴 |
| RAIL-051 | System shall support single-use virtual cards | P1 | 🔴 |
| RAIL-052 | System shall support multi-use virtual cards with limits | P2 | 🔴 |
| RAIL-053 | System shall track virtual card usage | P1 | 🔴 |
| RAIL-054 | System shall support virtual card cancellation | P1 | 🔴 |
| RAIL-055 | System shall support virtual card rebate tracking | P2 | 🔴 |

### 2.6 Bank Account Validation

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| RAIL-060 | System shall validate US routing numbers (ABA) | P0 | 🔴 |
| RAIL-061 | System shall validate account number format | P0 | 🔴 |
| RAIL-062 | System shall validate SWIFT/BIC codes | P1 | 🔴 |
| RAIL-063 | System shall validate IBAN format and checksum | P1 | 🔴 |
| RAIL-064 | System shall validate Malaysian bank codes | P2 | 🔴 |
| RAIL-065 | System shall support bank lookup by routing number | P1 | 🔴 |

### 2.7 File Management

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| RAIL-070 | System shall track generated files and their status | P0 | 🔴 |
| RAIL-071 | System shall support file transmission status tracking | P1 | 🔴 |
| RAIL-072 | System shall support file acknowledgment processing | P1 | 🔴 |
| RAIL-073 | System shall generate unique file identifiers | P0 | 🔴 |
| RAIL-074 | System shall support file regeneration | P1 | 🔴 |

### 2.8 Events

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| RAIL-080 | System shall emit AchFileGeneratedEvent | P0 | 🔴 |
| RAIL-081 | System shall emit AchBatchCreatedEvent | P0 | 🔴 |
| RAIL-082 | System shall emit AchReturnReceivedEvent | P0 | 🔴 |
| RAIL-083 | System shall emit WireTransferInitiatedEvent | P0 | 🔴 |
| RAIL-084 | System shall emit WireTransferCompletedEvent | P0 | 🔴 |
| RAIL-085 | System shall emit CheckIssuedEvent | P1 | 🔴 |
| RAIL-086 | System shall emit VirtualCardIssuedEvent | P1 | 🔴 |

---

## 3. Non-Functional Requirements

### 3.1 Compliance

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| RAIL-COMP-001 | ACH files shall comply with NACHA Operating Rules | P0 | 🔴 |
| RAIL-COMP-002 | Wire files shall comply with SWIFT standards | P1 | 🔴 |
| RAIL-COMP-003 | ISO 20022 messages shall pass XSD validation | P1 | 🔴 |
| RAIL-COMP-004 | Package shall support OFAC screening interface | P1 | 🔴 |

### 3.2 Security

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| RAIL-SEC-001 | Bank account numbers shall be masked in logs | P0 | 🔴 |
| RAIL-SEC-002 | Generated files shall not contain sensitive data beyond minimum required | P0 | 🔴 |
| RAIL-SEC-003 | File checksums shall be generated for integrity | P1 | 🔴 |

### 3.3 Performance

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| RAIL-PERF-001 | ACH file generation shall handle 10,000+ entries per batch | P0 | 🔴 |
| RAIL-PERF-002 | Routing number validation shall complete in < 1ms | P1 | 🔴 |

---

## 4. Interface Specifications

### 4.1 Core Interfaces

```
AchFileGeneratorInterface
├── generate(AchBatch $batch): AchFile
├── addEntry(AchEntry $entry): void
├── createBatch(AchBatchHeader $header): AchBatch
└── finalize(): string (file content)

WireTransferInterface
├── initiate(WireRequest $request): WireTransfer
├── getStatus(string $wireId): WireStatus
└── cancel(string $wireId): bool

CheckManagerInterface
├── issue(CheckRequest $request): Check
├── void(string $checkNumber): void
├── stopPayment(string $checkNumber, string $reason): void
└── generatePositivePayFile(array $checks): string

VirtualCardIssuerInterface
├── issue(VirtualCardRequest $request): VirtualCard
├── getDetails(string $cardId): VirtualCard
├── updateLimit(string $cardId, Money $limit): void
└── cancel(string $cardId): void

BankAccountValidatorInterface
├── validateRoutingNumber(string $routingNumber): ValidationResult
├── validateAccountNumber(string $accountNumber): ValidationResult
├── validateIban(string $iban): ValidationResult
├── validateSwiftCode(string $swift): ValidationResult
└── lookupBank(string $routingNumber): ?BankInfo
```

### 4.2 Value Objects

| Value Object | Purpose | Properties |
|--------------|---------|------------|
| `RoutingNumber` | US ABA routing number | `value`, `bankName`, `isValid` |
| `SwiftCode` | SWIFT/BIC code | `code`, `bankName`, `country` |
| `Iban` | International Bank Account Number | `iban`, `countryCode`, `checkDigits` |
| `AchEntry` | Single ACH transaction | `amount`, `accountNumber`, `routingNumber`, `secCode` |
| `AchBatch` | Collection of ACH entries | `batchId`, `entries[]`, `totals` |
| `WireRequest` | Wire transfer request | `amount`, `beneficiary`, `intermediaryBank` |
| `CheckRequest` | Check issuance request | `amount`, `payee`, `memo` |

### 4.3 Enums

```
SecCode (Standard Entry Class)
├── PPD (Prearranged Payment/Deposit)
├── CCD (Corporate Credit/Debit)
├── WEB (Internet-Initiated)
├── TEL (Telephone-Initiated)
├── CTX (Corporate Trade Exchange)
└── IAT (International ACH)

TransactionCode
├── CHECKING_CREDIT (22)
├── CHECKING_DEBIT (27)
├── SAVINGS_CREDIT (32)
├── SAVINGS_DEBIT (37)
├── PRENOTE_CHECKING_CREDIT (23)
└── PRENOTE_CHECKING_DEBIT (28)

WireType
├── DOMESTIC
├── INTERNATIONAL
└── BOOK_TRANSFER

CheckStatus
├── ISSUED
├── PRINTED
├── CLEARED
├── VOIDED
└── STOP_PAYMENT

AchReturnCode
├── R01 (Insufficient Funds)
├── R02 (Account Closed)
├── R03 (No Account)
├── R04 (Invalid Account Number)
├── R10 (Customer Advises Unauthorized)
└── ... (all standard return codes)
```

---

## 5. Events

| Event | Trigger | Payload |
|-------|---------|---------|
| `AchFileGeneratedEvent` | ACH file created | fileId, batchCount, totalAmount |
| `AchBatchCreatedEvent` | ACH batch finalized | batchId, entryCount, debitTotal, creditTotal |
| `AchReturnReceivedEvent` | ACH return processed | originalEntryId, returnCode, returnReason |
| `AchNocReceivedEvent` | NOC processed | entryId, changeCode, correctedData |
| `WireTransferInitiatedEvent` | Wire submitted | wireId, amount, beneficiary |
| `WireTransferCompletedEvent` | Wire confirmed | wireId, confirmationNumber |
| `WireTransferFailedEvent` | Wire failed | wireId, failureReason |
| `CheckIssuedEvent` | Check created | checkNumber, amount, payee |
| `CheckClearedEvent` | Check cleared | checkNumber, clearedDate |
| `VirtualCardIssuedEvent` | Virtual card created | cardId, lastFour, limit |

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
| `nexus/connector` | SFTP file transmission |
| `nexus/sanctions` | OFAC screening integration |

---

## 7. File Format Specifications

### 7.1 NACHA ACH File Structure

```
File Header Record (1 record)
├── Batch Header Record (1 per batch)
│   ├── Entry Detail Record (n entries)
│   │   └── Addenda Record (optional)
│   └── Batch Control Record
└── File Control Record
```

### 7.2 SWIFT MT103 Structure

```
Basic Header Block
├── Application Header Block
├── User Header Block
├── Text Block
│   ├── :20: Transaction Reference
│   ├── :23B: Bank Operation Code
│   ├── :32A: Value Date/Currency/Amount
│   ├── :50K: Ordering Customer
│   ├── :59: Beneficiary Customer
│   └── :71A: Details of Charges
└── Trailer Block
```

---

## 8. Acceptance Criteria

1. Generated ACH files must pass NACHA validation
2. Generated SWIFT messages must pass format validation
3. Routing number validation must be 100% accurate for valid numbers
4. IBAN validation must pass ISO 13616 compliance
5. All file formats must be configurable for bank-specific variations

---

## 9. Glossary

| Term | Definition |
|------|------------|
| **ACH** | Automated Clearing House - US electronic payment network |
| **NACHA** | National Automated Clearing House Association |
| **SWIFT** | Society for Worldwide Interbank Financial Telecommunication |
| **RTGS** | Real-Time Gross Settlement |
| **SEC Code** | Standard Entry Class code for ACH transactions |
| **NOC** | Notification of Change (ACH correction) |
| **Prenote** | Pre-notification zero-dollar test transaction |
| **OFAC** | Office of Foreign Assets Control |
| **IBAN** | International Bank Account Number |
| **BIC** | Bank Identifier Code (SWIFT code) |

---

## 10. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1.0 | 2025-12-18 | Nexus Team | Initial draft |
