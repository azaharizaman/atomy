# Nexus\Payment

**Version:** 0.1.0  
**Status:** In Development  
**PHP:** ^8.3

## Overview

`Nexus\Payment` is the core payment domain package providing a unified payment model for handling monetary transfers across Accounts Receivable, Accounts Payable, and Payroll domains. This package follows the **Progressive Disclosure Pattern** - it covers 80% of payment use cases, with extension packages available for advanced functionality.

## Installation

```bash
composer require nexus/payment
```

## Features

- **Unified Payment Entity** - Single `PaymentInterface` spanning AR/AP/Payroll
- **Payment Instruments** - Bank accounts, cards, and wallet abstractions
- **Allocation Engine** - FIFO, LIFO, Proportional, Manual allocation strategies
- **Event-Driven** - Domain events for integration with other packages
- **Multi-Tenant** - Full tenant isolation
- **Framework-Agnostic** - Works with Laravel, Symfony, or any PHP framework

## Quick Start

```php
use Nexus\Payment\Contracts\PaymentPersistInterface;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethod;
use Nexus\Common\ValueObjects\Money;

final readonly class PaymentService
{
    public function __construct(
        private PaymentPersistInterface $paymentPersist,
    ) {}

    public function recordPayment(
        string $tenantId,
        string $payerId,
        Money $amount,
    ): string {
        // Implementation
    }
}
```

## Extension Packages

For advanced functionality, see these extension packages:

| Package | Purpose |
|---------|---------|
| `nexus/payment-rails` | ACH, Wire, Check, RTGS payment rails |
| `nexus/payment-gateway` | Stripe, PayPal, Square integrations |
| `nexus/payment-bank` | NACHA, SWIFT, ISO 20022 bank files |
| `nexus/payment-wallet` | Digital wallets (GrabPay, DuitNow) |
| `nexus/payment-recurring` | Subscriptions and standing orders |

## Documentation

- [Requirements](REQUIREMENTS.md)
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Test Suite Summary](TEST_SUITE_SUMMARY.md)

## License

MIT License. See [LICENSE](LICENSE) for details.
