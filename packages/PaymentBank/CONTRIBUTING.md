# Contributing to Nexus\PaymentBank

Please refer to the [CONTRIBUTING.md](../Payment/CONTRIBUTING.md) in the core Payment package.

## Adding a New Bank Integration

1. Create a new class implementing `BankProviderInterface`
2. Implement account linking flow
3. Implement verification methods
4. Add regional compliance notes (PSD2, etc.)
5. Write integration tests with sandbox credentials
