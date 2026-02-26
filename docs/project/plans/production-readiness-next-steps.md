# Plan: Production Readiness Next Steps for IdentityOperations

This document details the technical requirements, external services, and implementation guides for the remaining gaps in the `IdentityOperations` orchestrator.

---

## 1. CI/CD Pipeline for IdentityOperations
Automate test execution and coverage reporting to ensure regression safety.

### **Prerequisites**
*   GitHub Repository access.
*   PHP 8.3 Runner environment.

### **Step-by-Step Guide**
1.  **Define Workflow**: Create a YAML file in `.github/workflows/identity-ops.yml`.
2.  **Configure Environment**: Set up PHP 8.3 with the `xdebug` extension enabled (required for coverage).
3.  **Dependency Resolution**: Run `composer install` within the `orchestrators/IdentityOperations` directory.
4.  **Execute Tests**: Run `vendor/bin/phpunit --coverage-text`.

### **Installation Steps**
```bash
# Ensure Xdebug is installed locally for manual checks
pecl install xdebug

# Run tests with coverage locally
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text
```

### **External Resources**
*   [GitHub Actions for PHP](https://github.com/shivammathur/setup-php)
*   [PHPUnit Documentation](https://phpunit.de/documentation.html)

---

## 2. SAML 2.0 & OIDC Integration (SSO)
Enable enterprise-grade Single Sign-On capabilities.

### **Prerequisites**
*   **SAML 2.0 Library**: `onelogin/php-saml`
*   **OIDC Library**: `league/oauth2-client`
*   **Identity Provider (IdP)**: A sandbox account for testing.

### **Step-by-Step Guide**
1.  **Interface Definition**: Define `SsoProviderInterface` in `packages/Identity/src/Contracts`.
2.  **Implementation**: Create `SAMLServiceProvider` and `OidcServiceProvider` in the `Identity` package.
3.  **Orchestration**: Update `UserAuthenticationCoordinator` to handle SSO redirection and callback logic.
4.  **Mapping**: Implement attribute mapping (e.g., mapping IdP "Groups" to Nexus "Roles").

### **External Services**
*   **Okta Developer**: [https://developer.okta.com/](https://developer.okta.com/) (Recommended for SAML/OIDC)
*   **Auth0**: [https://auth0.com/](https://auth0.com/) (Great for OIDC)
*   **Keycloak**: [https://www.keycloak.org/](https://www.keycloak.org/) (Open Source self-hosted IdP)

### **Installation Steps**
```bash
# Install SAML support in Identity package
composer require onelogin/php-saml

# Install OIDC support
composer require league/oauth2-client
```

---

## 3. Real Email & SMS Delivery via Notifier
Transition from logged notifications to real-world delivery.

### **Prerequisites**
*   **Email Provider**: Postmark or SendGrid.
*   **SMS Provider**: Twilio.
*   **Laravel Queue**: Redis or Database driver configured.

### **Step-by-Step Guide**
1.  **Provider Setup**: Obtain API keys from Twilio and Postmark.
2.  **Adapter Implementation**: Create `TwilioSmsAdapter` and `PostmarkEmailAdapter` in `adapters/Laravel/Notifier/src/Adapters`.
3.  **Template Creation**: Define Blade views for "Welcome" and "MFA Code" notifications.
4.  **Queue Configuration**: Enable asynchronous sending in `IdentityOperationsAdapter` using Laravel's `Queue` facade or `Notification::send()`.

### **External Services**
*   **Twilio**: [https://www.twilio.com/](https://www.twilio.com/) (SMS)
*   **Postmark**: [https://postmarkapp.com/](https://postmarkapp.com/) (High-reliability transactional email)
*   **SendGrid**: [https://sendgrid.com/](https://sendgrid.com/) (Email alternative)

### **Installation Steps**
```bash
# Twilio SDK
composer require twilio/sdk

# Postmark Transport for Laravel
composer require wildbit/swiftmailer-postmark
```

---

**Prepared By:** Gemini CLI  
**Document Location:** `plans/production-readiness-next-steps.md`
