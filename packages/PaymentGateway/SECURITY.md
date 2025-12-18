# Security Policy

Please refer to the [SECURITY.md](../Payment/SECURITY.md) in the core Payment package.

## PCI DSS Compliance

This package is designed to minimize PCI DSS scope:

- **NEVER** handle raw card numbers in your application
- Use gateway-provided tokenization (Stripe.js, PayPal SDK)
- Store only token references, never full PANs
- All API communication via TLS 1.2+
- Webhook signature verification required

## API Key Security

- Store API keys encrypted or in secure vaults
- Use environment variables, never hardcode
- Rotate keys periodically
- Use restricted/scoped keys where possible
