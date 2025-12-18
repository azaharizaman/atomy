# Security Policy

## Reporting a Vulnerability

Please refer to the [SECURITY.md](../Payment/SECURITY.md) in the core Payment package.

## Package-Specific Security Notes

### ACH Security

- Store routing numbers and account numbers encrypted
- Validate routing numbers using checksum algorithm
- Never log full account numbers

### Wire Transfer Security

- SWIFT BIC codes must be validated
- IBAN validation required for international transfers
- Implement velocity limits for wire transfers

### Check Security

- Positive pay file generation for fraud prevention
- Check stock security recommendations
- MICR line security
