# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| 0.x.x   | :warning: Development only |

## Reporting a Vulnerability

If you discover a security vulnerability within Nexus\Payment, please follow these steps:

1. **Do NOT** open a public issue
2. Email security@nexus.dev with:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Any suggested fixes

## Response Timeline

- **Acknowledgment**: Within 48 hours
- **Initial Assessment**: Within 7 days
- **Resolution Target**: Within 30 days for critical issues

## Security Best Practices

When using this package:

### PCI Compliance

- **NEVER** store full card numbers (PAN) - use tokenization
- **NEVER** log sensitive payment data
- Use `Nexus\Crypto` for encryption of bank account details

### Bank Account Security

- Store bank account numbers encrypted at rest
- Mask account numbers in UI and logs: `****7890`
- Implement access controls for payment instrument management

### Audit Trail

- All payment operations should be logged via `Nexus\AuditLogger`
- Payment status changes should emit domain events

## Secure Defaults

This package is designed with security in mind:

- No plaintext storage of sensitive data
- Immutable value objects prevent accidental modification
- Event sourcing enables full audit trail reconstruction
