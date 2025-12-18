# Contributing to Nexus\PaymentGateway

Please refer to the [CONTRIBUTING.md](../Payment/CONTRIBUTING.md) in the core Payment package.

## Adding a New Gateway

1. Create a new class implementing `GatewayProcessorInterface`
2. Implement all required methods (charge, authorize, capture, refund)
3. Add webhook handler implementing `WebhookHandlerInterface`
4. Write integration tests with sandbox credentials
5. Document gateway-specific configuration
