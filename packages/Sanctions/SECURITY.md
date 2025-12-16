# Security Policy

## Supported Versions

The following versions of `nexus/sanctions` are currently supported with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

---

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

If you discover a security vulnerability in this package, please report it responsibly:

### Reporting Process

1. **Email Security Team:**
   Send details to: **security@nexus-erp.local** (replace with actual email)
   
2. **Include in Your Report:**
   - Description of the vulnerability
   - Steps to reproduce the issue
   - Potential impact
   - Suggested fix (if available)
   - Your contact information

3. **Response Timeline:**
   - **Acknowledgment:** Within 48 hours
   - **Initial Assessment:** Within 1 week
   - **Fix Timeline:** Depends on severity (see below)

### Severity Levels

| Severity | Response Time | Description |
|----------|---------------|-------------|
| **Critical** | 24-48 hours | Remote code execution, data breach, authentication bypass |
| **High** | 1 week | Privilege escalation, sensitive data exposure |
| **Medium** | 2 weeks | Denial of service, information disclosure |
| **Low** | 1 month | Minor security concerns |

---

## Security Best Practices for Users

When using `nexus/sanctions` in production:

### 1. Keep Dependencies Updated

```bash
composer update nexus/sanctions
composer update  # Update all dependencies
```

### 2. Validate Input Data

Always validate party data before screening:

```php
// ✅ CORRECT: Validate before screening
if (empty($party->getId()) || empty($party->getName())) {
    throw new InvalidPartyException('Party ID and name are required');
}

$result = $screener->screen($party, [SanctionsList::OFAC_SDN]);
```

### 3. Secure Logging

Ensure sensitive data is not logged:

```php
// ❌ WRONG: Logging full party details
$logger->info('Screening party', ['party' => $party->toArray()]);

// ✅ CORRECT: Log only non-sensitive identifiers
$logger->info('Screening party', ['party_id' => $party->getId()]);
```

### 4. Access Control

Implement proper access control for screening operations:

- Restrict who can perform sanctions screening
- Log all screening activities
- Implement rate limiting for batch operations

### 5. Data Protection

- Encrypt sanctions data at rest
- Use HTTPS for API communications
- Implement proper authentication/authorization
- Follow GDPR/data protection regulations

### 6. Regular Security Audits

- Review screening logs regularly
- Monitor for anomalies in match patterns
- Update sanctions lists frequently
- Audit access to screening services

---

## Known Security Considerations

### 1. False Positives

The fuzzy matching algorithms may produce false positives. Always:
- Review STRONG and POSSIBLE matches manually
- Implement human verification workflows
- Document decision rationale

### 2. Data Sensitivity

Sanctions screening data is highly sensitive:
- Store screening results securely
- Implement retention policies
- Comply with data protection regulations
- Restrict access to authorized personnel only

### 3. API Rate Limits

When integrating with external sanctions APIs:
- Implement rate limiting
- Handle API failures gracefully
- Cache results appropriately
- Monitor API usage

---

## Disclosure Policy

### Coordinated Disclosure

We follow a coordinated disclosure process:

1. **Private Disclosure:** Report received and acknowledged privately
2. **Investigation:** Security team investigates and develops fix
3. **Testing:** Fix is tested and verified
4. **Release:** Security patch released
5. **Public Disclosure:** Vulnerability details published after fix is available

### Credit

We will credit security researchers who responsibly disclose vulnerabilities (with their permission).

---

## Security Updates

Security updates will be:
- Released as patch versions (e.g., 1.0.1)
- Documented in CHANGELOG.md with `[SECURITY]` tag
- Announced via repository security advisories

---

## Compliance

This package implements security measures to comply with:

- **FATF Recommendations** (Financial Action Task Force)
- **OFAC Guidelines** (Office of Foreign Assets Control)
- **EU Sanctions Regulations**
- **GDPR** (General Data Protection Regulation)

---

## Questions?

For security-related questions (not vulnerabilities):
- Open a GitHub issue with `security-question` label
- Consult the documentation

For vulnerability reports, use the private email channel above.

---

**Last Updated:** December 16, 2025  
**Maintained By:** Nexus Security Team
