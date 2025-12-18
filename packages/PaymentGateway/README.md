# Nexus\PaymentGateway

**Version:** 0.1.0  
**Status:** In Development  
**PHP:** ^8.3  
**Extends:** `nexus/payment`

## Overview

`Nexus\PaymentGateway` is an extension package for `Nexus\Payment` providing integrations with online payment processors like Stripe, PayPal, Square, Adyen, and regional gateways. This package handles tokenization, authorization, capture, refunds, and webhook processing.

## Installation

```bash
composer require nexus/payment-gateway
```

## Features

- **Multi-Gateway Support** - Stripe, PayPal, Square, Adyen, Braintree
- **Tokenization** - Secure card tokenization (PCI DSS compliant)
- **Payment Intents** - 3D Secure / SCA support
- **Webhooks** - Standardized webhook handling
- **Refunds** - Full and partial refunds
- **Disputes** - Chargeback handling

## Quick Start

```php
use Nexus\PaymentGateway\Contracts\GatewayManagerInterface;

final readonly class OnlinePaymentService
{
    public function __construct(
        private GatewayManagerInterface $gatewayManager,
    ) {}

    public function charge(string $gateway, ChargeRequest $request): ChargeResult
    {
        $processor = $this->gatewayManager->getProcessor($gateway);
        return $processor->charge($request);
    }
}
```

## Supported Gateways

| Gateway | Status | Features |
|---------|--------|----------|
| Stripe | Planned | Cards, ACH, SEPA, 3DS |
| PayPal | Planned | PayPal, Venmo |
| Square | Planned | Cards, Apple Pay |
| Adyen | Planned | Cards, Local Methods |
| Braintree | Planned | Cards, PayPal |

## Documentation

- [Requirements](REQUIREMENTS.md)
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Test Suite Summary](TEST_SUITE_SUMMARY.md)

## License

MIT License. See [LICENSE](LICENSE) for details.
