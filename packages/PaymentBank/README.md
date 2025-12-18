# Nexus\PaymentBank

**Version:** 0.1.0  
**Status:** In Development  
**PHP:** ^8.3  
**Extends:** `nexus/payment`

## Overview

`Nexus\PaymentBank` is an extension package for `Nexus\Payment` providing direct bank integrations for open banking (PSD2/Open Banking UK), real-time payments (RTP, FAST, SEPA Instant), and bank account verification services (Plaid, Yodlee, MX).

## Installation

```bash
composer require nexus/payment-bank
```

## Features

- **Open Banking** - PSD2 (EU), Open Banking UK, CDR (Australia)
- **Real-Time Payments** - RTP (US), FAST (Singapore), SEPA Instant
- **Account Verification** - Micro-deposits, Instant verification (Plaid)
- **Balance Checks** - Pre-payment balance verification
- **Bank Account Linking** - Secure account linking flows

## Quick Start

```php
use Nexus\PaymentBank\Contracts\BankConnectionManagerInterface;

final readonly class BankAccountService
{
    public function __construct(
        private BankConnectionManagerInterface $bankManager,
    ) {}

    public function linkAccount(LinkAccountRequest $request): BankAccount
    {
        return $this->bankManager->initiateLink($request);
    }

    public function verifyAccount(string $accountId, VerifyRequest $request): VerificationResult
    {
        return $this->bankManager->verifyAccount($accountId, $request);
    }
}
```

## Supported Providers

| Provider | Type | Region |
|----------|------|--------|
| Plaid | Account Linking/Verification | US, CA, UK, EU |
| Yodlee | Account Aggregation | Global |
| TrueLayer | Open Banking | UK, EU |
| Finicity | Verification | US |

## Documentation

- [Requirements](REQUIREMENTS.md)
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Test Suite Summary](TEST_SUITE_SUMMARY.md)

## License

MIT License. See [LICENSE](LICENSE) for details.
