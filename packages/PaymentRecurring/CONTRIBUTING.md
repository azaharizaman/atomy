# Contributing to Nexus\PaymentRecurring

Please refer to the [CONTRIBUTING.md](../Payment/CONTRIBUTING.md) in the core Payment package.

## Adding a New Billing Model

1. Create a new class implementing `BillingModelInterface`
2. Implement `calculateAmount()` for the billing period
3. Add proration support if applicable
4. Write comprehensive tests with edge cases
5. Document pricing tier configurations
