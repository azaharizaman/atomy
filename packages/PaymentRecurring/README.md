# Nexus\PaymentRecurring

**Version:** 0.1.0  
**Status:** In Development  
**PHP:** ^8.3  
**Extends:** `nexus/payment`

## Overview

`Nexus\PaymentRecurring` is an extension package for `Nexus\Payment` providing subscription billing, usage-based billing, and recurring payment management. It supports payment schedules, billing cycles, proration, and dunning management.

## Installation

```bash
composer require nexus/payment-recurring
```

## Features

- **Subscription Billing** - Fixed, tiered, and per-seat pricing
- **Usage-Based Billing** - Metered billing with thresholds
- **Payment Schedules** - Weekly, monthly, quarterly, annual
- **Proration** - Mid-cycle upgrades/downgrades
- **Dunning Management** - Retry logic for failed payments
- **Trial Periods** - Free trial with auto-conversion

## Quick Start

```php
use Nexus\PaymentRecurring\Contracts\SubscriptionManagerInterface;

final readonly class BillingService
{
    public function __construct(
        private SubscriptionManagerInterface $subscriptionManager,
    ) {}

    public function createSubscription(CreateSubscriptionRequest $request): Subscription
    {
        return $this->subscriptionManager->create($request);
    }

    public function processRenewals(): RenewalResult
    {
        return $this->subscriptionManager->processRenewals();
    }
}
```

## Billing Models

| Model | Description | Use Case |
|-------|-------------|----------|
| Fixed | Same amount each period | SaaS plans |
| Tiered | Volume-based pricing | API calls |
| Per-Seat | Per user/license | Enterprise software |
| Usage | Metered consumption | Utilities, Cloud |

## Documentation

- [Requirements](REQUIREMENTS.md)
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Test Suite Summary](TEST_SUITE_SUMMARY.md)

## License

MIT License. See [LICENSE](LICENSE) for details.
