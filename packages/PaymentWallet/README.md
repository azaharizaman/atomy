# Nexus\PaymentWallet

**Version:** 0.1.0  
**Status:** In Development  
**PHP:** ^8.3  
**Extends:** `nexus/payment`

## Overview

`Nexus\PaymentWallet` is an extension package for `Nexus\Payment` providing integrations with digital wallets (Apple Pay, Google Pay), regional wallets (GrabPay, Touch'n Go), and Buy Now Pay Later (BNPL) services (Klarna, Afterpay, Atome).

## Installation

```bash
composer require nexus/payment-wallet
```

## Features

- **Digital Wallets** - Apple Pay, Google Pay, Samsung Pay
- **Regional Wallets** - GrabPay, Touch'n Go, GCash, Boost
- **BNPL Services** - Klarna, Afterpay, Affirm, Atome
- **Cryptocurrency** - Bitcoin, Ethereum (optional extension)
- **QR Payments** - DuitNow QR, PayNow QR

## Quick Start

```php
use Nexus\PaymentWallet\Contracts\WalletManagerInterface;

final readonly class WalletPaymentService
{
    public function __construct(
        private WalletManagerInterface $walletManager,
    ) {}

    public function processWalletPayment(string $walletType, WalletPaymentRequest $request): PaymentResult
    {
        $wallet = $this->walletManager->getWallet($walletType);
        return $wallet->charge($request);
    }
}
```

## Supported Wallets

| Wallet | Type | Region |
|--------|------|--------|
| Apple Pay | Digital | Global |
| Google Pay | Digital | Global |
| GrabPay | Regional | SEA |
| Touch'n Go | Regional | Malaysia |
| Klarna | BNPL | EU, US |
| Afterpay | BNPL | US, AU |
| Atome | BNPL | SEA |

## Documentation

- [Requirements](REQUIREMENTS.md)
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Test Suite Summary](TEST_SUITE_SUMMARY.md)

## License

MIT License. See [LICENSE](LICENSE) for details.
