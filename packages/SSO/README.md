# Nexus\SSO - Single Sign-On Package

[![Tests](https://img.shields.io/badge/tests-51%20passing-success)](tests/)
[![PHP Version](https://img.shields.io/badge/php-8.3%2B-blue)](composer.json)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Framework-agnostic Single Sign-On (SSO) package for Nexus ERP monorepo. Supports SAML 2.0, OAuth2/OIDC, Azure AD, Google Workspace, and custom identity providers.

## 🎯 Features

- **Multi-Protocol Support**: SAML 2.0, OAuth2, OpenID Connect (OIDC)
- **Vendor Integrations**: Azure AD (Entra ID), Google Workspace, Okta (planned)
- **Just-In-Time Provisioning**: Auto-create users from SSO profiles
- **Attribute Mapping**: Flexible mapping from IdP attributes to local user fields
- **Multi-Tenant Ready**: Per-tenant SSO configuration
- **CSRF Protection**: Secure state validation for callbacks
- **Framework Agnostic**: Pure PHP 8.3+ with zero Laravel dependencies

## 📦 Installation

```bash
composer require nexus/sso
```

## 🏗️ Architecture

The `Nexus\SSO` package is designed to be **completely decoupled** from `Nexus\Identity`. It defines **contracts** (interfaces) that your application implements using the Identity package.

### The Separation Principle

| Package | Responsibility | Analogy |
|---------|---------------|----------|
| **`Nexus\SSO`** | **Authentication Orchestration** | "The bouncer" - verifies credentials with external IdP |
| **`Nexus\Identity`** | **User Management** | "The membership database" - stores users, roles, permissions |

## 🚀 Quick Start

### 1. Install Package Dependencies

```bash
cd packages/SSO
composer install
```

### 2. Define Core Interfaces (Already Done)

The package provides these core contracts:

- `SsoManagerInterface` - Main SSO orchestration
- `SsoProviderInterface` - Base provider contract
- `UserProvisioningInterface` - Bridge to Identity (you implement this)
- `AttributeMapperInterface` - Attribute mapping service
- `SsoConfigRepositoryInterface` - Configuration storage
- `CallbackStateValidatorInterface` - CSRF protection
- `StateStorageInterface` - Temporary state storage
- `SsoSessionRepositoryInterface` - Session management

### 3. Implement User Provisioning (Your Application)

In your consuming application, implement the `UserProvisioningInterface`:

```php
namespace App\Services\SSO;

use Nexus\SSO\Contracts\UserProvisioningInterface;
use Nexus\SSO\ValueObjects\UserProfile;
use Nexus\Identity\Contracts\UserManagerInterface;

final readonly class IdentityUserProvisioner implements UserProvisioningInterface
{
    public function __construct(
        private UserManagerInterface $userManager
    ) {}

    public function findOrCreateUser(
        UserProfile $profile,
        string $providerName,
        string $tenantId
    ): string {
        // Check if user exists by SSO ID or email
        $existingUser = $this->findBySsoId($profile->ssoUserId, $providerName);
        
        if ($existingUser) {
            return $existingUser->id;
        }

        // JIT provisioning - create new user
        return $this->userManager->createUser([
            'email' => $profile->email,
            'first_name' => $profile->firstName,
            'last_name' => $profile->lastName,
            'display_name' => $profile->displayName,
        ]);
    }
    
    // ... implement other methods
}
```

### 4. Configure SSO Provider

```php
use Nexus\SSO\ValueObjects\SsoProviderConfig;
use Nexus\SSO\ValueObjects\SsoProtocol;
use Nexus\SSO\ValueObjects\AttributeMap;

$azureConfig = new SsoProviderConfig(
    providerName: 'azure',
    protocol: SsoProtocol::OIDC,
    clientId: 'your-azure-client-id',
    clientSecret: 'your-azure-client-secret',
    discoveryUrl: 'https://login.microsoftonline.com/common/v2.0/.well-known/openid-configuration',
    redirectUri: 'https://your-app.com/sso/callback/azure',
    attributeMap: new AttributeMap(
        mappings: [
            'sso_user_id' => 'oid',
            'email' => 'email',
            'first_name' => 'given_name',
            'last_name' => 'family_name',
        ],
        requiredFields: ['email', 'sso_user_id']
    ),
    enabled: true
);
```

## 📚 Usage Examples

### Initiate SSO Login

```php
use Nexus\SSO\Contracts\SsoManagerInterface;

class LoginController
{
    public function __construct(
        private readonly SsoManagerInterface $ssoManager
    ) {}

    public function redirectToAzure()
    {
        $result = $this->ssoManager->initiateLogin(
            providerName: 'azure',
            tenantId: 'tenant-123',
            parameters: ['returnUrl' => '/dashboard']
        );

        return redirect($result['authUrl']);
    }
}
```

### Handle SSO Callback

```php
public function handleCallback(Request $request)
{
    $session = $this->ssoManager->handleCallback(
        providerName: 'azure',
        callbackData: [
            'code' => $request->get('code'),
        ],
        state: $request->get('state')
    );

    // Session contains authenticated user profile
    $userProfile = $session->userProfile;

    // Log user in locally
    auth()->loginUsingId($userProfile->ssoUserId);

    return redirect('/dashboard');
}
```

## 🧪 Testing

Run tests:

```bash
./vendor/bin/phpunit
```

Run tests with coverage:

```bash
./vendor/bin/phpunit --coverage-html coverage
```

Current test coverage: **51 tests, 139 assertions, 100% passing**

## 🏗️ Package Structure

```
packages/SSO/
├── composer.json
├── phpunit.xml
├── README.md
├── src/
│   ├── Contracts/              # Interfaces (framework agnostic)
│   │   ├── SsoManagerInterface.php
│   │   ├── SsoProviderInterface.php
│   │   ├── UserProvisioningInterface.php
│   │   ├── AttributeMapperInterface.php
│   │   ├── SsoConfigRepositoryInterface.php
│   │   ├── CallbackStateValidatorInterface.php
│   │   ├── StateStorageInterface.php
│   │   └── SsoSessionRepositoryInterface.php
│   ├── Services/               # Core services
│   │   ├── AttributeMapper.php
│   │   └── CallbackStateValidator.php
│   ├── ValueObjects/           # Immutable domain data
│   │   ├── SsoProtocol.php (enum)
│   │   ├── UserProfile.php
│   │   ├── CallbackState.php
│   │   ├── AttributeMap.php
│   │   ├── SsoProviderConfig.php
│   │   └── SsoSession.php
│   └── Exceptions/             # Domain exceptions
│       ├── SsoException.php
│       ├── SsoProviderNotFoundException.php
│       ├── InvalidCallbackStateException.php
│       ├── AttributeMappingException.php
│       ├── SsoAuthenticationException.php
│       ├── SsoConfigurationException.php
│       ├── SsoProviderException.php
│       ├── SsoSessionExpiredException.php
│       ├── TokenRefreshException.php
│       └── UserProvisioningException.php
└── tests/
    └── Unit/
        ├── Services/
        ├── ValueObjects/
        └── Exceptions/
```

## 📋 Implementation Status

### ✅ Phase 1: Core Infrastructure (COMPLETED)
- [x] Package structure
- [x] Core contracts (8 interfaces)
- [x] Value objects (6 classes)
- [x] Exceptions (10 classes)
- [x] AttributeMapper service
- [x] CallbackStateValidator service
- [x] Unit tests (51 tests passing)

### ⏳ Phase 2: SAML 2.0 Provider (PLANNED)
- [ ] Saml2Provider implementation
- [ ] SAML signature validation
- [ ] SP metadata generation
- [ ] SAML-specific tests

### ⏳ Phase 3: OAuth2/OIDC Provider (PLANNED)
- [ ] OAuth2Provider implementation
- [ ] OidcProvider implementation
- [ ] JWT ID token validation
- [ ] OAuth-specific tests

### ⏳ Phase 4: Vendor-Specific Providers (PLANNED)
- [ ] AzureAdProvider (Azure AD/Entra ID)
- [ ] GoogleWorkspaceProvider
- [ ] OktaProvider

## 🔗 Integration with Other Packages

- **Nexus\Identity**: User management, roles, permissions (via `UserProvisioningInterface`)
- **Nexus\Tenant**: Multi-tenancy support (SSO configs scoped by tenant)
- **Nexus\AuditLogger**: Audit trail for SSO events
- **Nexus\Monitoring**: Telemetry for SSO metrics

## 📖 Documentation

- [Implementation Plan](../../docs/SSO_IMPLEMENTATION_PLAN.md)
- [Requirements](../../docs/REQUIREMENTS_SSO.md)
- [Executive Summary](../../docs/SSO_EXECUTIVE_SUMMARY.md)
- [Architecture Diagrams](../../docs/SSO_ARCHITECTURE_DIAGRAMS.md)

## 🤝 Contributing

This package follows strict architectural guidelines:

1. **Framework Agnostic**: No Laravel dependencies in package layer
2. **Contract-Driven**: Define interfaces first, implement later
3. **Immutability**: Use `readonly` properties for all value objects
4. **PHP 8.3+**: Native enums, constructor property promotion, strict types
5. **TDD**: Red-Green-Refactor methodology

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

**Package Version**: 0.1.0 (Development)  
**PHP Version**: 8.3+  
**Status**: 🟡 In Development (Phase 1 Complete)
