# Security Policy

Please refer to the [SECURITY.md](../Payment/SECURITY.md) in the core Payment package.

## Subscription-Specific Security

### Payment Method Storage
- Payment method tokens stored, never raw card data
- Support for payment method updates without re-entering full details

### Dunning & Retries
- Rate limiting on retry attempts
- Configurable retry intervals
- Customer notification before card expiry

### Data Protection
- Usage data aggregated, not individual transactions
- Subscription cancellation respects data retention policies
