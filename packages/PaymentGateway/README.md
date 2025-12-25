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

## Security & PCI Compliance

**CRITICAL:** This package is designed to minimize PCI scope.

1.  **Never Store PAN/CVV:** You must **NEVER** store raw credit card numbers (PAN) or CVV codes in your database or logs.
2.  **Use Tokenization:** Always use client-side tokenization (e.g., Stripe Elements, PayPal JS SDK) to send card data directly to the gateway. Your server should only receive a `payment_method_id` or `token`.
3.  **Logging Redaction:** Ensure your logging configuration redacts sensitive fields. This package's DTOs are designed to carry tokens, not raw card data.

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
