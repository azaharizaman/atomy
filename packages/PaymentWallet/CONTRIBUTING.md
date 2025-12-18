# Contributing to Nexus\PaymentWallet

Please refer to the [CONTRIBUTING.md](../Payment/CONTRIBUTING.md) in the core Payment package.

## Adding a New Wallet Provider

1. Create a new class implementing `WalletProviderInterface`
2. Implement charge flow (redirect-based or token-based)
3. Add refund support
4. Add webhook handler
5. Write integration tests with sandbox credentials
