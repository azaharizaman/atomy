# Security Policy

Please refer to the [SECURITY.md](../Payment/SECURITY.md) in the core Payment package.

## Wallet-Specific Security

### Apple Pay / Google Pay
- Payment tokens are single-use
- Device-bound cryptography
- Never store raw tokens

### BNPL Providers
- Credit check data is not stored
- Only order references are retained
- Webhook signatures must be verified

### QR Payments
- QR codes expire after use/timeout
- Dynamic QR preferred over static
