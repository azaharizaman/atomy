# Security Policy

Please refer to the [SECURITY.md](../Payment/SECURITY.md) in the core Payment package.

## Open Banking Security

- All API tokens are short-lived (OAuth 2.0)
- Bank credentials NEVER pass through your servers
- Consent management required (PSD2)
- Strong Customer Authentication (SCA) enforced

## Data Protection

- Bank account numbers are masked (only last 4 digits stored)
- Routing numbers are verified against official databases
- All data encrypted at rest and in transit
- GDPR/CCPA data subject rights supported
