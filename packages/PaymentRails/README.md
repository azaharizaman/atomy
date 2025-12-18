# Nexus\PaymentRails

**Version:** 0.1.0  
**Status:** In Development  
**PHP:** ^8.3  
**Extends:** `nexus/payment`

## Overview

`Nexus\PaymentRails` is an extension package for `Nexus\Payment` providing payment rail implementations for ACH, Wire Transfer, Check, RTGS, and other payment networks. This package handles the specifics of each payment method including validation, fee calculation, processing time, and execution.

## Installation

```bash
composer require nexus/payment-rails
```

## Features

- **ACH Payments** - Automated Clearing House (US banking network)
- **Wire Transfers** - SWIFT/Fedwire/CHIPS
- **Check Processing** - Physical check generation and positive pay
- **RTGS** - Real-Time Gross Settlement
- **Virtual Cards** - Single-use virtual card generation
- **Rail Selection** - Automatic optimal rail selection based on criteria

## Quick Start

```php
use Nexus\PaymentRails\Contracts\PaymentRailSelectorInterface;
use Nexus\PaymentRails\Contracts\PaymentRailInterface;

final readonly class DisbursementService
{
    public function __construct(
        private PaymentRailSelectorInterface $railSelector,
    ) {}

    public function processPayment(PaymentRequest $request): PaymentResult
    {
        // Auto-select optimal rail based on amount, urgency, cost
        $rail = $this->railSelector->selectOptimalRail($request);
        
        // Execute via selected rail
        return $rail->execute($request);
    }
}
```

## Available Rails

| Rail | Class | Use Case |
|------|-------|----------|
| ACH | `AchPaymentRail` | US domestic, low-cost, 1-3 days |
| Wire | `WirePaymentRail` | Urgent, same-day, international |
| Check | `CheckPaymentRail` | Legacy vendors, mail delivery |
| RTGS | `RtgsPaymentRail` | High-value, real-time settlement |
| Virtual Card | `VirtualCardPaymentRail` | Secure, rebate-earning |

## Documentation

- [Requirements](REQUIREMENTS.md)
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Test Suite Summary](TEST_SUITE_SUMMARY.md)

## License

MIT License. See [LICENSE](LICENSE) for details.
