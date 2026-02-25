# Nexus\SSO - Implementation Plan

**Version:** 1.0  
**Created:** November 23, 2025  
**Status:** Planning Phase  
**Package Type:** Atomic, Framework-Agnostic  
**Target PHP Version:** 8.3+

---

## 🎯 Executive Summary

The `Nexus\SSO` package provides a comprehensive Single Sign-On (SSO) solution for the Nexus ERP monorepo. It enables enterprise authentication via SAML 2.0, OAuth2/OIDC, and custom identity providers while maintaining strict separation from the `Nexus\Identity` package.

### Key Architectural Principles

1. **Separation of Concerns**: SSO is authentication orchestration, Identity is user management
2. **Framework Agnostic**: Pure PHP 8.3+ with zero Laravel dependencies in package layer
3. **Pluggable Providers**: Support multiple SSO providers (SAML, OAuth2, OIDC, Azure AD, Google Workspace)
4. **Multi-Tenant Aware**: Per-tenant SSO configuration with tenant isolation
5. **Optional Feature**: SSO can be disabled; local authentication remains fully functional

---

## 📋 Table of Contents

1. [Architecture & Design](#architecture--design)
2. [Package Structure](#package-structure)
3. [Core Contracts (Interfaces)](#core-contracts-interfaces)
4. [Services Layer](#services-layer)
5. [Value Objects](#value-objects)
6. [Exceptions](#exceptions)
7. [Application Layer (consuming application)](#application-layer-atomy)
8. [Integration Points](#integration-points)
9. [Security Considerations](#security-considerations)
10. [Implementation Phases](#implementation-phases)
11. [Testing Strategy](#testing-strategy)
12. [Configuration](#configuration)
13. [Requirements Traceability](#requirements-traceability)

---

## 🏗️ Architecture & Design

### The Separation Principle: SSO vs. Identity

| Package | Responsibility | Analogy |
|---------|---------------|----------|
| **`Nexus\SSO`** | **Authentication Orchestration** | "The bouncer at the club entrance" - verifies credentials with external IdP |
| **`Nexus\Identity`** | **User Management** | "The membership database" - stores user records, roles, permissions |

**Critical Rule:** `Nexus\SSO` **MUST NOT** directly depend on `Nexus\Identity`. Instead:
- SSO defines `UserProvisioningInterface` (contract)
- Identity implements the contract in consuming application (via `UserManager`)
- consuming application wires them together via dependency injection

### Integration Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                     1. User clicks "Login with SSO"              │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  2. consuming application Controller calls SsoManager::initiateLogin()          │
│     (Nexus\SSO)                                                  │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  3. SsoManager generates authorization URL and redirects        │
│     User to external IdP (e.g., Azure AD, Google)               │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  4. User authenticates at IdP (SAML/OAuth2/OIDC)                │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  5. IdP redirects back to consuming application callback URL with token/SAML    │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  6. consuming application Controller calls SsoManager::handleCallback()         │
│     (Nexus\SSO)                                                  │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  7. SsoManager validates token/assertion and extracts profile   │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  8. SsoManager calls UserProvisioningInterface::findOrCreate()  │
│     (Implemented by Nexus\Identity in consuming application)                    │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  9. Identity creates/updates user, assigns roles                │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  10. SessionManager creates session, returns token to user      │
└─────────────────────────────────────────────────────────────────┘
```

### Why SSO is a Separate Package

**Decision Rationale:**

1. **Single Responsibility**: Identity manages users, SSO orchestrates federated authentication
2. **Optional Feature**: Some deployments don't need SSO (SMBs use local auth only)
3. **Provider Agnostic**: SSO providers can be swapped without touching Identity
4. **Vendor Independence**: Switching from SAML to OAuth2 doesn't affect user management
5. **Testing Isolation**: SSO integration tests don't require Identity database

**Alternative Considered (Rejected):**
- ❌ **Embed SSO in `Nexus\Identity`**: Violates SRP, makes Identity too heavyweight
- ❌ **Embed SSO in `Nexus\Connector`**: Connector is for API integration, not user auth

---

## 📦 Package Structure

```
packages/SSO/
├── composer.json                # Package definition
├── README.md                    # Package documentation
├── LICENSE                      # MIT License
└── src/
    ├── Contracts/               # 12 Interfaces
    │   ├── SsoManagerInterface.php
    │   ├── SsoProviderInterface.php
    │   ├── SamlProviderInterface.php
    │   ├── OAuthProviderInterface.php
    │   ├── OidcProviderInterface.php
    │   ├── UserProvisioningInterface.php
    │   ├── AttributeMapperInterface.php
    │   ├── SsoConfigRepositoryInterface.php
    │   ├── SsoSessionRepositoryInterface.php
    │   ├── CallbackStateValidatorInterface.php
    │   ├── TokenValidatorInterface.php
    │   └── SamlMetadataInterface.php
    ├── Services/                # Core Business Logic
    │   ├── SsoManager.php
    │   ├── AttributeMapper.php
    │   ├── CallbackStateValidator.php
    │   └── SsoSessionManager.php
    ├── Providers/               # Concrete SSO Provider Implementations
    │   ├── Saml2Provider.php
    │   ├── OAuth2Provider.php
    │   ├── OidcProvider.php
    │   ├── AzureAdProvider.php
    │   ├── GoogleWorkspaceProvider.php
    │   └── OktaProvider.php
    ├── ValueObjects/            # Immutable Domain Objects
    │   ├── SsoProtocol.php      # enum (SAML2, OAuth2, OIDC)
    │   ├── SsoProviderConfig.php
    │   ├── SsoSession.php
    │   ├── SamlAssertion.php
    │   ├── OAuthToken.php
    │   ├── AttributeMap.php
    │   ├── CallbackState.php
    │   └── UserProfile.php
    └── Exceptions/              # Domain Exceptions
        ├── SsoException.php (base)
        ├── InvalidSsoConfigException.php
        ├── SsoProviderNotFoundException.php
        ├── InvalidCallbackStateException.php
        ├── InvalidSamlAssertionException.php
        ├── InvalidOAuthTokenException.php
        ├── SsoSessionExpiredException.php
        ├── AttributeMappingException.php
        ├── ProvisioningException.php
        └── SsoDisabledException.php
```

### Dependency Tree

```
Nexus\SSO
├── psr/log (logging interface)
├── symfony/http-foundation (for request/response objects)
├── onelogin/php-saml (SAML 2.0 implementation)
├── league/oauth2-client (OAuth2/OIDC client)
└── lcobucci/jwt (JWT validation)

NO dependencies on:
❌ Nexus\Identity
❌ Illuminate\* (Laravel)
❌ Any Eloquent models
```

---

## 🔌 Core Contracts (Interfaces)

### 1. `SsoManagerInterface` (Main Orchestrator)

**Responsibility:** High-level SSO orchestration (initiate login, handle callback, provision user)

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\SsoSession;
use Nexus\SSO\ValueObjects\UserProfile;

/**
 * Main SSO orchestration interface
 * 
 * Coordinates SSO authentication flow across providers
 */
interface SsoManagerInterface
{
    /**
     * Initiate SSO login flow
     * 
     * @param string $providerName SSO provider identifier (e.g., 'azure', 'google')
     * @param string $tenantId Current tenant context
     * @param array<string, mixed> $parameters Additional parameters (returnUrl, etc.)
     * @return array{authUrl: string, state: string} Authorization URL and state token
     * @throws \Nexus\SSO\Exceptions\SsoProviderNotFoundException
     * @throws \Nexus\SSO\Exceptions\SsoDisabledException
     */
    public function initiateLogin(
        string $providerName,
        string $tenantId,
        array $parameters = []
    ): array;

    /**
     * Handle SSO callback and provision user
     * 
     * @param string $providerName SSO provider identifier
     * @param array<string, mixed> $callbackData Data from IdP callback (code, SAML response, etc.)
     * @param string $state State token from initiateLogin()
     * @return SsoSession Authenticated SSO session with user profile
     * @throws \Nexus\SSO\Exceptions\InvalidCallbackStateException
     * @throws \Nexus\SSO\Exceptions\InvalidSamlAssertionException
     * @throws \Nexus\SSO\Exceptions\InvalidOAuthTokenException
     * @throws \Nexus\SSO\Exceptions\ProvisioningException
     */
    public function handleCallback(
        string $providerName,
        array $callbackData,
        string $state
    ): SsoSession;

    /**
     * Get user profile from SSO session
     * 
     * @param string $sessionId SSO session identifier
     * @return UserProfile User profile with mapped attributes
     * @throws \Nexus\SSO\Exceptions\SsoSessionExpiredException
     */
    public function getUserProfile(string $sessionId): UserProfile;

    /**
     * Initiate SSO logout (Single Logout - SLO)
     * 
     * @param string $sessionId SSO session identifier
     * @param string $providerName SSO provider identifier
     * @return string|null Logout URL (null if provider doesn't support SLO)
     */
    public function initiateLogout(string $sessionId, string $providerName): ?string;

    /**
     * Check if SSO is enabled for tenant
     */
    public function isSsoEnabled(string $tenantId): bool;

    /**
     * Get available SSO providers for tenant
     * 
     * @return array<string, array{name: string, protocol: string, enabled: bool}>
     */
    public function getAvailableProviders(string $tenantId): array;
}
```

---

### 2. `SsoProviderInterface` (Base Provider Contract)

**Responsibility:** Common interface for all SSO providers (SAML, OAuth2, OIDC)

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\SsoProviderConfig;
use Nexus\SSO\ValueObjects\UserProfile;

/**
 * Base SSO provider interface
 * 
 * All concrete SSO providers must implement this contract
 */
interface SsoProviderInterface
{
    /**
     * Get provider name (e.g., 'azure', 'google', 'okta', 'saml-generic')
     */
    public function getName(): string;

    /**
     * Get SSO protocol (SAML2, OAuth2, OIDC)
     */
    public function getProtocol(): \Nexus\SSO\ValueObjects\SsoProtocol;

    /**
     * Generate authorization URL for SSO login
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @param string $state Random state token for CSRF protection
     * @param array<string, mixed> $parameters Additional parameters
     * @return string Authorization URL to redirect user
     */
    public function getAuthorizationUrl(
        SsoProviderConfig $config,
        string $state,
        array $parameters = []
    ): string;

    /**
     * Handle SSO callback and extract user profile
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @param array<string, mixed> $callbackData Data from IdP callback
     * @return UserProfile Extracted user profile with attributes
     * @throws \Nexus\SSO\Exceptions\InvalidSamlAssertionException
     * @throws \Nexus\SSO\Exceptions\InvalidOAuthTokenException
     */
    public function handleCallback(
        SsoProviderConfig $config,
        array $callbackData
    ): UserProfile;

    /**
     * Get logout URL for Single Logout (SLO)
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @param string $sessionId SSO session identifier
     * @return string|null Logout URL (null if provider doesn't support SLO)
     */
    public function getLogoutUrl(SsoProviderConfig $config, string $sessionId): ?string;

    /**
     * Validate configuration
     * 
     * @throws \Nexus\SSO\Exceptions\InvalidSsoConfigException
     */
    public function validateConfig(SsoProviderConfig $config): void;
}
```

---

### 3. `SamlProviderInterface` (SAML-specific operations)

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\SamlAssertion;
use Nexus\SSO\ValueObjects\SsoProviderConfig;

/**
 * SAML 2.0 provider interface
 * 
 * Extends base provider with SAML-specific operations
 */
interface SamlProviderInterface extends SsoProviderInterface
{
    /**
     * Get Service Provider (SP) metadata XML
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @return string SAML metadata XML
     */
    public function getSpMetadata(SsoProviderConfig $config): string;

    /**
     * Parse SAML assertion from callback
     * 
     * @param array<string, mixed> $callbackData SAML response data
     * @return SamlAssertion Validated SAML assertion
     * @throws \Nexus\SSO\Exceptions\InvalidSamlAssertionException
     */
    public function parseSamlAssertion(array $callbackData): SamlAssertion;

    /**
     * Validate SAML signature
     * 
     * @param string $samlResponse Base64-encoded SAML response
     * @param string $certificate IdP X.509 certificate
     * @throws \Nexus\SSO\Exceptions\InvalidSamlAssertionException
     */
    public function validateSignature(string $samlResponse, string $certificate): void;
}
```

---

### 4. `OAuthProviderInterface` (OAuth2/OIDC operations)

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\OAuthToken;
use Nexus\SSO\ValueObjects\SsoProviderConfig;

/**
 * OAuth2/OIDC provider interface
 * 
 * Extends base provider with OAuth-specific operations
 */
interface OAuthProviderInterface extends SsoProviderInterface
{
    /**
     * Exchange authorization code for access token
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @param string $code Authorization code from callback
     * @return OAuthToken Access token and metadata
     * @throws \Nexus\SSO\Exceptions\InvalidOAuthTokenException
     */
    public function getAccessToken(SsoProviderConfig $config, string $code): OAuthToken;

    /**
     * Get user info from OAuth/OIDC userinfo endpoint
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @param string $accessToken Access token
     * @return array<string, mixed> User attributes from IdP
     */
    public function getUserInfo(SsoProviderConfig $config, string $accessToken): array;

    /**
     * Validate ID token (OIDC only)
     * 
     * @param string $idToken JWT ID token
     * @param SsoProviderConfig $config Provider configuration
     * @throws \Nexus\SSO\Exceptions\InvalidOAuthTokenException
     */
    public function validateIdToken(string $idToken, SsoProviderConfig $config): void;

    /**
     * Refresh access token
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @param string $refreshToken Refresh token
     * @return OAuthToken New access token
     */
    public function refreshToken(SsoProviderConfig $config, string $refreshToken): OAuthToken;
}
```

---

### 5. `UserProvisioningInterface` (Bridge to Identity)

**Responsibility:** Find or create user from SSO profile (implemented by `Nexus\Identity` in consuming application)

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\UserProfile;

/**
 * User provisioning interface
 * 
 * This contract is defined in Nexus\SSO but IMPLEMENTED by Nexus\Identity in consuming application.
 * It decouples SSO from Identity package.
 */
interface UserProvisioningInterface
{
    /**
     * Find existing user by SSO identifier or create new user (JIT provisioning)
     * 
     * @param UserProfile $profile SSO user profile with mapped attributes
     * @param string $providerName SSO provider identifier (e.g., 'azure')
     * @param string $tenantId Current tenant context
     * @return string User ID (local system user ID)
     * @throws \Nexus\SSO\Exceptions\ProvisioningException
     */
    public function findOrCreateUser(
        UserProfile $profile,
        string $providerName,
        string $tenantId
    ): string;

    /**
     * Update user attributes from SSO profile
     * 
     * @param string $userId Local user ID
     * @param UserProfile $profile Updated SSO profile
     */
    public function updateUserFromProfile(string $userId, UserProfile $profile): void;

    /**
     * Check if Just-In-Time (JIT) provisioning is enabled
     * 
     * @param string $providerName SSO provider identifier
     * @param string $tenantId Current tenant context
     */
    public function isJitProvisioningEnabled(string $providerName, string $tenantId): bool;

    /**
     * Link SSO identity to existing user
     * 
     * @param string $userId Local user ID
     * @param string $ssoUserId SSO user identifier (e.g., Azure AD Object ID)
     * @param string $providerName SSO provider identifier
     */
    public function linkSsoIdentity(string $userId, string $ssoUserId, string $providerName): void;

    /**
     * Unlink SSO identity from user
     * 
     * @param string $userId Local user ID
     * @param string $providerName SSO provider identifier
     */
    public function unlinkSsoIdentity(string $userId, string $providerName): void;
}
```

---

### 6. `AttributeMapperInterface` (Attribute Mapping)

**Responsibility:** Map IdP attributes to local user attributes

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\AttributeMap;
use Nexus\SSO\ValueObjects\UserProfile;

/**
 * Attribute mapper interface
 * 
 * Maps SSO provider attributes to local user attributes
 */
interface AttributeMapperInterface
{
    /**
     * Map SSO attributes to local user profile
     * 
     * @param array<string, mixed> $ssoAttributes Attributes from IdP
     * @param AttributeMap $mapping Attribute mapping configuration
     * @return UserProfile Mapped user profile
     * @throws \Nexus\SSO\Exceptions\AttributeMappingException
     */
    public function map(array $ssoAttributes, AttributeMap $mapping): UserProfile;

    /**
     * Extract required attributes from SSO response
     * 
     * @param array<string, mixed> $ssoAttributes Attributes from IdP
     * @param array<string> $requiredFields List of required field names
     * @throws \Nexus\SSO\Exceptions\AttributeMappingException If required field is missing
     */
    public function validateRequiredAttributes(array $ssoAttributes, array $requiredFields): void;
}
```

---

### 7. `SsoConfigRepositoryInterface` (Configuration Persistence)

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\SsoProviderConfig;

/**
 * SSO configuration repository
 * 
 * Stores and retrieves SSO provider configurations
 */
interface SsoConfigRepositoryInterface
{
    /**
     * Get SSO provider configuration
     * 
     * @param string $providerName Provider identifier
     * @param string $tenantId Tenant context
     * @return SsoProviderConfig Provider configuration
     * @throws \Nexus\SSO\Exceptions\SsoProviderNotFoundException
     */
    public function getConfig(string $providerName, string $tenantId): SsoProviderConfig;

    /**
     * Save SSO provider configuration
     */
    public function saveConfig(SsoProviderConfig $config): void;

    /**
     * Check if provider is enabled for tenant
     */
    public function isProviderEnabled(string $providerName, string $tenantId): bool;

    /**
     * Get all enabled providers for tenant
     * 
     * @return array<SsoProviderConfig>
     */
    public function getEnabledProviders(string $tenantId): array;

    /**
     * Delete provider configuration
     */
    public function deleteConfig(string $providerName, string $tenantId): void;
}
```

---

### 8. `CallbackStateValidatorInterface` (CSRF Protection)

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\CallbackState;

/**
 * Callback state validator
 * 
 * Prevents CSRF attacks during SSO callback
 */
interface CallbackStateValidatorInterface
{
    /**
     * Generate random state token
     * 
     * @param array<string, mixed> $metadata Additional metadata to store with state
     * @return CallbackState State token with metadata
     */
    public function generateState(array $metadata = []): CallbackState;

    /**
     * Validate state token from callback
     * 
     * @param string $state State token from callback
     * @return CallbackState Validated state with metadata
     * @throws \Nexus\SSO\Exceptions\InvalidCallbackStateException
     */
    public function validateState(string $state): CallbackState;

    /**
     * Invalidate state token (one-time use)
     */
    public function invalidateState(string $state): void;
}
```

---

## 🛠️ Services Layer

### 1. `SsoManager` (Main Orchestrator)

**File:** `packages/SSO/src/Services/SsoManager.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\Services;

use Nexus\SSO\Contracts\SsoManagerInterface;
use Nexus\SSO\Contracts\SsoProviderInterface;
use Nexus\SSO\Contracts\SsoConfigRepositoryInterface;
use Nexus\SSO\Contracts\UserProvisioningInterface;
use Nexus\SSO\Contracts\AttributeMapperInterface;
use Nexus\SSO\Contracts\CallbackStateValidatorInterface;
use Nexus\SSO\Contracts\SsoSessionRepositoryInterface;
use Nexus\SSO\ValueObjects\SsoSession;
use Nexus\SSO\ValueObjects\UserProfile;
use Nexus\SSO\Exceptions\SsoProviderNotFoundException;
use Nexus\SSO\Exceptions\SsoDisabledException;
use Psr\Log\LoggerInterface;

/**
 * Main SSO orchestration service
 */
final readonly class SsoManager implements SsoManagerInterface
{
    /**
     * @param array<string, SsoProviderInterface> $providers Registered SSO providers
     */
    public function __construct(
        private array $providers,
        private SsoConfigRepositoryInterface $configRepository,
        private UserProvisioningInterface $userProvisioning,
        private AttributeMapperInterface $attributeMapper,
        private CallbackStateValidatorInterface $stateValidator,
        private SsoSessionRepositoryInterface $sessionRepository,
        private LoggerInterface $logger
    ) {}

    public function initiateLogin(
        string $providerName,
        string $tenantId,
        array $parameters = []
    ): array {
        // 1. Check if SSO is enabled
        if (!$this->isSsoEnabled($tenantId)) {
            throw new SsoDisabledException($tenantId);
        }

        // 2. Get provider configuration
        $config = $this->configRepository->getConfig($providerName, $tenantId);

        // 3. Get provider instance
        $provider = $this->getProvider($providerName);

        // 4. Generate state token for CSRF protection
        $state = $this->stateValidator->generateState([
            'provider' => $providerName,
            'tenant_id' => $tenantId,
            'return_url' => $parameters['returnUrl'] ?? null,
        ]);

        // 5. Generate authorization URL
        $authUrl = $provider->getAuthorizationUrl($config, $state->getToken(), $parameters);

        $this->logger->info('SSO login initiated', [
            'provider' => $providerName,
            'tenant_id' => $tenantId,
        ]);

        return [
            'authUrl' => $authUrl,
            'state' => $state->getToken(),
        ];
    }

    public function handleCallback(
        string $providerName,
        array $callbackData,
        string $state
    ): SsoSession {
        // 1. Validate state token
        $validatedState = $this->stateValidator->validateState($state);
        
        // 2. Get tenant from state metadata
        $tenantId = $validatedState->getMetadata()['tenant_id'];

        // 3. Get provider configuration
        $config = $this->configRepository->getConfig($providerName, $tenantId);

        // 4. Get provider instance
        $provider = $this->getProvider($providerName);

        // 5. Handle callback and extract user profile
        $userProfile = $provider->handleCallback($config, $callbackData);

        // 6. Map attributes
        $mappedProfile = $this->attributeMapper->map(
            $userProfile->getAttributes(),
            $config->getAttributeMap()
        );

        // 7. Provision user (find or create)
        $userId = $this->userProvisioning->findOrCreateUser(
            $mappedProfile,
            $providerName,
            $tenantId
        );

        // 8. Create SSO session
        $session = new SsoSession(
            sessionId: bin2hex(random_bytes(32)),
            userId: $userId,
            providerName: $providerName,
            tenantId: $tenantId,
            profile: $mappedProfile,
            createdAt: new \DateTimeImmutable(),
            expiresAt: new \DateTimeImmutable('+1 hour')
        );

        // 9. Store session
        $this->sessionRepository->save($session);

        // 10. Invalidate state (one-time use)
        $this->stateValidator->invalidateState($state);

        $this->logger->info('SSO login completed', [
            'provider' => $providerName,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
        ]);

        return $session;
    }

    public function getUserProfile(string $sessionId): UserProfile
    {
        $session = $this->sessionRepository->findById($sessionId);
        return $session->getProfile();
    }

    public function initiateLogout(string $sessionId, string $providerName): ?string
    {
        $session = $this->sessionRepository->findById($sessionId);
        $config = $this->configRepository->getConfig($providerName, $session->getTenantId());
        $provider = $this->getProvider($providerName);

        $logoutUrl = $provider->getLogoutUrl($config, $sessionId);

        // Delete SSO session
        $this->sessionRepository->delete($sessionId);

        return $logoutUrl;
    }

    public function isSsoEnabled(string $tenantId): bool
    {
        return $this->configRepository->isProviderEnabled('*', $tenantId);
    }

    public function getAvailableProviders(string $tenantId): array
    {
        $configs = $this->configRepository->getEnabledProviders($tenantId);
        
        return array_map(
            fn($config) => [
                'name' => $config->getProviderName(),
                'protocol' => $config->getProtocol()->value,
                'enabled' => true,
            ],
            $configs
        );
    }

    private function getProvider(string $providerName): SsoProviderInterface
    {
        if (!isset($this->providers[$providerName])) {
            throw new SsoProviderNotFoundException($providerName);
        }

        return $this->providers[$providerName];
    }
}
```

---

### 2. `AttributeMapper` (Attribute Mapping Service)

**File:** `packages/SSO/src/Services/AttributeMapper.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\Services;

use Nexus\SSO\Contracts\AttributeMapperInterface;
use Nexus\SSO\ValueObjects\AttributeMap;
use Nexus\SSO\ValueObjects\UserProfile;
use Nexus\SSO\Exceptions\AttributeMappingException;

/**
 * SSO attribute mapper
 * 
 * Maps IdP attributes to local user attributes
 */
final readonly class AttributeMapper implements AttributeMapperInterface
{
    public function map(array $ssoAttributes, AttributeMap $mapping): UserProfile
    {
        $mappedAttributes = [];

        foreach ($mapping->getMappings() as $localField => $idpField) {
            // Handle dot notation (e.g., 'user.email' -> 'email')
            $value = $this->extractValue($ssoAttributes, $idpField);
            
            if ($value !== null) {
                $mappedAttributes[$localField] = $value;
            }
        }

        // Validate required fields
        $this->validateRequiredAttributes($mappedAttributes, $mapping->getRequiredFields());

        return new UserProfile(
            ssoUserId: $mappedAttributes['sso_user_id'] ?? throw new AttributeMappingException('sso_user_id is required'),
            email: $mappedAttributes['email'] ?? throw new AttributeMappingException('email is required'),
            firstName: $mappedAttributes['first_name'] ?? null,
            lastName: $mappedAttributes['last_name'] ?? null,
            displayName: $mappedAttributes['display_name'] ?? null,
            attributes: $mappedAttributes
        );
    }

    public function validateRequiredAttributes(array $attributes, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($attributes[$field]) || $attributes[$field] === null) {
                throw new AttributeMappingException("Required attribute '{$field}' is missing");
            }
        }
    }

    /**
     * Extract value using dot notation
     * 
     * @param array<string, mixed> $data
     */
    private function extractValue(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }
}
```

---

### 3. `CallbackStateValidator` (CSRF Protection)

**File:** `packages/SSO/src/Services/CallbackStateValidator.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\Services;

use Nexus\SSO\Contracts\CallbackStateValidatorInterface;
use Nexus\SSO\ValueObjects\CallbackState;
use Nexus\SSO\Exceptions\InvalidCallbackStateException;

/**
 * Callback state validator
 * 
 * Prevents CSRF attacks during SSO callback
 */
final class CallbackStateValidator implements CallbackStateValidatorInterface
{
    /** @var array<string, CallbackState> In-memory state storage (use Redis/Cache in production) */
    private array $states = [];

    private const STATE_LIFETIME_SECONDS = 600; // 10 minutes

    public function generateState(array $metadata = []): CallbackState
    {
        $token = bin2hex(random_bytes(32));
        
        $state = new CallbackState(
            token: $token,
            metadata: $metadata,
            createdAt: new \DateTimeImmutable(),
            expiresAt: new \DateTimeImmutable('+' . self::STATE_LIFETIME_SECONDS . ' seconds')
        );

        $this->states[$token] = $state;

        return $state;
    }

    public function validateState(string $state): CallbackState
    {
        if (!isset($this->states[$state])) {
            throw new InvalidCallbackStateException('Invalid or expired state token');
        }

        $callbackState = $this->states[$state];

        // Check expiration
        if ($callbackState->getExpiresAt() < new \DateTimeImmutable()) {
            unset($this->states[$state]);
            throw new InvalidCallbackStateException('State token has expired');
        }

        return $callbackState;
    }

    public function invalidateState(string $state): void
    {
        unset($this->states[$state]);
    }
}
```

---

## 💎 Value Objects

### 1. `SsoProtocol` (Enum)

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

/**
 * SSO protocol enum
 */
enum SsoProtocol: string
{
    case SAML2 = 'saml2';
    case OAuth2 = 'oauth2';
    case OIDC = 'oidc';
}
```

---

### 2. `SsoProviderConfig` (Provider Configuration)

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

/**
 * SSO provider configuration
 */
final readonly class SsoProviderConfig
{
    public function __construct(
        private string $providerName,
        private string $tenantId,
        private SsoProtocol $protocol,
        private bool $enabled,
        private array $settings, // Provider-specific settings (clientId, secret, entityId, etc.)
        private AttributeMap $attributeMap,
        private bool $jitProvisioningEnabled,
        private array $defaultRoles = [],
    ) {}

    public function getProviderName(): string { return $this->providerName; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getProtocol(): SsoProtocol { return $this->protocol; }
    public function isEnabled(): bool { return $this->enabled; }
    public function getSettings(): array { return $this->settings; }
    public function getSetting(string $key): mixed { return $this->settings[$key] ?? null; }
    public function getAttributeMap(): AttributeMap { return $this->attributeMap; }
    public function isJitProvisioningEnabled(): bool { return $this->jitProvisioningEnabled; }
    public function getDefaultRoles(): array { return $this->defaultRoles; }
}
```

---

### 3. `UserProfile` (SSO User Profile)

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

/**
 * SSO user profile
 */
final readonly class UserProfile
{
    public function __construct(
        private string $ssoUserId,       // Unique identifier from IdP
        private string $email,
        private ?string $firstName = null,
        private ?string $lastName = null,
        private ?string $displayName = null,
        private array $attributes = [],  // All mapped attributes
    ) {}

    public function getSsoUserId(): string { return $this->ssoUserId; }
    public function getEmail(): string { return $this->email; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    public function getDisplayName(): ?string { return $this->displayName; }
    public function getAttributes(): array { return $this->attributes; }
    public function getAttribute(string $key): mixed { return $this->attributes[$key] ?? null; }
}
```

---

### 4. `AttributeMap` (Attribute Mapping Configuration)

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

/**
 * Attribute mapping configuration
 */
final readonly class AttributeMap
{
    /**
     * @param array<string, string> $mappings Local field => IdP field mapping
     * @param array<string> $requiredFields Required local fields
     */
    public function __construct(
        private array $mappings,
        private array $requiredFields = ['email', 'sso_user_id'],
    ) {}

    public function getMappings(): array { return $this->mappings; }
    public function getRequiredFields(): array { return $this->requiredFields; }
}
```

---

### 5. `SsoSession` (SSO Session)

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

/**
 * SSO session
 */
final readonly class SsoSession
{
    public function __construct(
        private string $sessionId,
        private string $userId,
        private string $providerName,
        private string $tenantId,
        private UserProfile $profile,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $expiresAt,
    ) {}

    public function getSessionId(): string { return $this->sessionId; }
    public function getUserId(): string { return $this->userId; }
    public function getProviderName(): string { return $this->providerName; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getProfile(): UserProfile { return $this->profile; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    
    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }
}
```

---

## 🚨 Exceptions

All exceptions extend `SsoException` (base exception).

```php
<?php

declare(strict_types=1);

namespace Nexus\SSO\Exceptions;

/**
 * Base SSO exception
 */
class SsoException extends \Exception
{
}

/**
 * SSO provider not found
 */
class SsoProviderNotFoundException extends SsoException
{
    public function __construct(string $providerName)
    {
        parent::__construct("SSO provider '{$providerName}' not found");
    }
}

/**
 * SSO is disabled
 */
class SsoDisabledException extends SsoException
{
    public function __construct(string $tenantId)
    {
        parent::__construct("SSO is disabled for tenant '{$tenantId}'");
    }
}

/**
 * Invalid callback state (CSRF protection)
 */
class InvalidCallbackStateException extends SsoException
{
}

/**
 * Invalid SAML assertion
 */
class InvalidSamlAssertionException extends SsoException
{
}

/**
 * Invalid OAuth token
 */
class InvalidOAuthTokenException extends SsoException
{
}

/**
 * SSO session expired
 */
class SsoSessionExpiredException extends SsoException
{
}

/**
 * Attribute mapping failed
 */
class AttributeMappingException extends SsoException
{
}

/**
 * User provisioning failed
 */
class ProvisioningException extends SsoException
{
}

/**
 * Invalid SSO configuration
 */
class InvalidSsoConfigException extends SsoException
{
}
```

---

## 🚀 Application Layer (consuming application)

### Eloquent Models

```
consuming application (e.g., Laravel app)database/migrations/
├── 2025_11_23_000001_create_sso_providers_table.php
├── 2025_11_23_000002_create_sso_sessions_table.php
└── 2025_11_23_000003_create_sso_user_mappings_table.php

consuming application (e.g., Laravel app)app/Models/
├── SsoProvider.php
├── SsoSession.php
└── SsoUserMapping.php
```

### Repositories

```
consuming application (e.g., Laravel app)app/Repositories/
├── DbSsoConfigRepository.php (implements SsoConfigRepositoryInterface)
└── DbSsoSessionRepository.php (implements SsoSessionRepositoryInterface)
```

### Service Implementations

```
consuming application (e.g., Laravel app)app/Services/SSO/
├── IdentityUserProvisioner.php (implements UserProvisioningInterface)
└── RedisSsoStateStore.php (Redis-backed state validator)
```

### Controllers

```
consuming application (e.g., Laravel app)app/Http/Controllers/
└── SsoController.php
    - GET /sso/login/{provider}
    - GET /sso/callback/{provider}
    - POST /sso/logout
    - GET /sso/metadata/{provider} (SAML metadata)
```

### Service Provider Bindings

```php
// consuming application (e.g., Laravel app)app/Providers/SsoServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\SSO\Contracts\SsoManagerInterface;
use Nexus\SSO\Contracts\SsoConfigRepositoryInterface;
use Nexus\SSO\Contracts\UserProvisioningInterface;
use Nexus\SSO\Contracts\AttributeMapperInterface;
use Nexus\SSO\Contracts\CallbackStateValidatorInterface;
use Nexus\SSO\Contracts\SsoSessionRepositoryInterface;
use Nexus\SSO\Services\SsoManager;
use Nexus\SSO\Services\AttributeMapper;
use Nexus\SSO\Providers\AzureAdProvider;
use Nexus\SSO\Providers\GoogleWorkspaceProvider;
use Nexus\SSO\Providers\Saml2Provider;
use App\Repositories\DbSsoConfigRepository;
use App\Repositories\DbSsoSessionRepository;
use App\Services\SSO\IdentityUserProvisioner;
use App\Services\SSO\RedisSsoStateStore;

class SsoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->singleton(SsoConfigRepositoryInterface::class, DbSsoConfigRepository::class);
        $this->app->singleton(SsoSessionRepositoryInterface::class, DbSsoSessionRepository::class);

        // Service bindings
        $this->app->singleton(AttributeMapperInterface::class, AttributeMapper::class);
        $this->app->singleton(CallbackStateValidatorInterface::class, RedisSsoStateStore::class);
        
        // User provisioning (bridge to Nexus\Identity)
        $this->app->singleton(UserProvisioningInterface::class, IdentityUserProvisioner::class);

        // Register SSO providers
        $this->app->singleton(SsoManagerInterface::class, function ($app) {
            return new SsoManager(
                providers: [
                    'azure' => new AzureAdProvider(),
                    'google' => new GoogleWorkspaceProvider(),
                    'saml' => new Saml2Provider(),
                ],
                configRepository: $app->make(SsoConfigRepositoryInterface::class),
                userProvisioning: $app->make(UserProvisioningInterface::class),
                attributeMapper: $app->make(AttributeMapperInterface::class),
                stateValidator: $app->make(CallbackStateValidatorInterface::class),
                sessionRepository: $app->make(SsoSessionRepositoryInterface::class),
                logger: $app->make(\Psr\Log\LoggerInterface::class),
            );
        });
    }
}
```

---

## 🔗 Integration Points

### Integration with `Nexus\Identity`

**File:** `consuming application (e.g., Laravel app)app/Services/SSO/IdentityUserProvisioner.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\SSO;

use Nexus\SSO\Contracts\UserProvisioningInterface;
use Nexus\SSO\ValueObjects\UserProfile;
use Nexus\SSO\Exceptions\ProvisioningException;
use Nexus\Identity\Contracts\UserManagerInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Contracts\RoleManagerInterface;
use App\Models\SsoUserMapping;

/**
 * Identity-based user provisioner
 * 
 * Bridges Nexus\SSO to Nexus\Identity
 */
final readonly class IdentityUserProvisioner implements UserProvisioningInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserManagerInterface $userManager,
        private RoleManagerInterface $roleManager,
    ) {}

    public function findOrCreateUser(
        UserProfile $profile,
        string $providerName,
        string $tenantId
    ): string {
        // 1. Check if SSO mapping exists
        $mapping = SsoUserMapping::where('sso_user_id', $profile->getSsoUserId())
            ->where('provider_name', $providerName)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($mapping) {
            // User already exists
            $this->updateUserFromProfile($mapping->user_id, $profile);
            return $mapping->user_id;
        }

        // 2. Check if user exists by email
        $existingUser = $this->userRepository->findByEmail($profile->getEmail(), $tenantId);

        if ($existingUser) {
            // Link existing user to SSO
            $this->linkSsoIdentity($existingUser->getId(), $profile->getSsoUserId(), $providerName);
            $this->updateUserFromProfile($existingUser->getId(), $profile);
            return $existingUser->getId();
        }

        // 3. JIT provisioning - create new user
        if (!$this->isJitProvisioningEnabled($providerName, $tenantId)) {
            throw new ProvisioningException('JIT provisioning is disabled');
        }

        $userId = $this->userManager->createUser([
            'email' => $profile->getEmail(),
            'first_name' => $profile->getFirstName(),
            'last_name' => $profile->getLastName(),
            'display_name' => $profile->getDisplayName(),
            'tenant_id' => $tenantId,
            'is_sso_user' => true,
        ]);

        // 4. Link SSO identity
        $this->linkSsoIdentity($userId, $profile->getSsoUserId(), $providerName);

        // 5. Assign default roles (from SSO config)
        // TODO: Fetch default roles from SsoProviderConfig

        return $userId;
    }

    public function updateUserFromProfile(string $userId, UserProfile $profile): void
    {
        $this->userManager->updateUser($userId, [
            'first_name' => $profile->getFirstName(),
            'last_name' => $profile->getLastName(),
            'display_name' => $profile->getDisplayName(),
        ]);
    }

    public function isJitProvisioningEnabled(string $providerName, string $tenantId): bool
    {
        // Check SSO provider configuration
        return true; // Placeholder
    }

    public function linkSsoIdentity(string $userId, string $ssoUserId, string $providerName): void
    {
        SsoUserMapping::create([
            'user_id' => $userId,
            'sso_user_id' => $ssoUserId,
            'provider_name' => $providerName,
            'tenant_id' => auth()->user()->tenant_id, // Get from context
        ]);
    }

    public function unlinkSsoIdentity(string $userId, string $providerName): void
    {
        SsoUserMapping::where('user_id', $userId)
            ->where('provider_name', $providerName)
            ->delete();
    }
}
```

---

## 🔒 Security Considerations

1. **CSRF Protection**: State token validation prevents callback hijacking
2. **Token Validation**: SAML signatures and JWT validation prevent tampering
3. **HTTPS Only**: All SSO flows must use HTTPS
4. **Tenant Isolation**: SSO configs are isolated per tenant
5. **Session Expiry**: SSO sessions have configurable TTL
6. **Audit Logging**: All SSO events logged to `Nexus\AuditLogger`
7. **Rate Limiting**: SSO callback endpoints should be rate-limited
8. **JIT Provisioning Control**: Configurable per provider/tenant
9. **Attribute Validation**: Enforce required attributes before provisioning

---

## 📅 Implementation Phases

### Phase 1: Core Infrastructure (Week 1-2)
- [ ] Create package structure and composer.json
- [ ] Define all contracts (12 interfaces)
- [ ] Implement core value objects (8 classes)
- [ ] Implement all exceptions (10 classes)
- [ ] Create `SsoManager` service
- [ ] Create `AttributeMapper` service
- [ ] Create `CallbackStateValidator` service
- [ ] Unit tests for services

### Phase 2: SAML 2.0 Provider (Week 3)
- [ ] Implement `Saml2Provider`
- [ ] SAML signature validation
- [ ] SP metadata generation
- [ ] Integration with `onelogin/php-saml`
- [ ] SAML-specific tests

### Phase 3: OAuth2/OIDC Provider (Week 4)
- [ ] Implement `OAuth2Provider` (generic)
- [ ] Implement `OidcProvider` (OpenID Connect)
- [ ] JWT ID token validation
- [ ] Integration with `league/oauth2-client`
- [ ] OAuth-specific tests

### Phase 4: Vendor-Specific Providers (Week 5)
- [ ] Implement `AzureAdProvider` (Azure AD/Entra ID)
- [ ] Implement `GoogleWorkspaceProvider` (Google OAuth2)
- [ ] Implement `OktaProvider` (Okta OIDC)
- [ ] Vendor-specific attribute mapping

### Phase 5: Application Layer (Week 6)
- [ ] Create Eloquent models (SsoProvider, SsoSession, SsoUserMapping)
- [ ] Create migrations
- [ ] Implement `DbSsoConfigRepository`
- [ ] Implement `DbSsoSessionRepository`
- [ ] Implement `IdentityUserProvisioner` (bridge to Identity)
- [ ] Implement `RedisSsoStateStore`
- [ ] Service provider bindings

### Phase 6: API & Controllers (Week 7)
- [ ] Create `SsoController` (login, callback, logout, metadata)
- [ ] API routes
- [ ] Middleware for SSO session validation
- [ ] Integration with `Nexus\AuditLogger`
- [ ] Integration with `Nexus\Tenant` (tenant context)

### Phase 7: Testing & Documentation (Week 8)
- [ ] End-to-end SSO flow tests
- [ ] Multi-tenant SSO tests
- [ ] JIT provisioning tests
- [ ] Attribute mapping tests
- [ ] README.md with usage examples
- [ ] API documentation
- [ ] Update `NEXUS_PACKAGES_REFERENCE.md`

---

## 🧪 Testing Strategy

### Unit Tests (Package Layer)

```
packages/SSO/tests/Unit/
├── Services/
│   ├── SsoManagerTest.php
│   ├── AttributeMapperTest.php
│   └── CallbackStateValidatorTest.php
├── Providers/
│   ├── Saml2ProviderTest.php
│   ├── OAuth2ProviderTest.php
│   └── AzureAdProviderTest.php
└── ValueObjects/
    ├── AttributeMapTest.php
    └── UserProfileTest.php
```

### Integration Tests (Application Layer)

```
consuming application (e.g., Laravel app)tests/Feature/SSO/
├── SamlAuthenticationTest.php
├── OAuth2AuthenticationTest.php
├── JitProvisioningTest.php
├── AttributeMappingTest.php
└── MultiTenantSsoTest.php
```

---

## ⚙️ Configuration

### `consuming application (e.g., Laravel app)config/sso.php`

```php
<?php

return [
    'enabled' => env('SSO_ENABLED', false),

    'providers' => [
        'azure' => [
            'enabled' => env('SSO_AZURE_ENABLED', false),
            'client_id' => env('SSO_AZURE_CLIENT_ID'),
            'client_secret' => env('SSO_AZURE_CLIENT_SECRET'),
            'tenant_id' => env('SSO_AZURE_TENANT_ID'),
            'redirect_uri' => env('APP_URL') . '/sso/callback/azure',
        ],

        'google' => [
            'enabled' => env('SSO_GOOGLE_ENABLED', false),
            'client_id' => env('SSO_GOOGLE_CLIENT_ID'),
            'client_secret' => env('SSO_GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('APP_URL') . '/sso/callback/google',
        ],

        'saml' => [
            'enabled' => env('SSO_SAML_ENABLED', false),
            'entity_id' => env('SSO_SAML_ENTITY_ID'),
            'idp_entity_id' => env('SSO_SAML_IDP_ENTITY_ID'),
            'idp_sso_url' => env('SSO_SAML_IDP_SSO_URL'),
            'idp_slo_url' => env('SSO_SAML_IDP_SLO_URL'),
            'idp_x509_cert' => env('SSO_SAML_IDP_CERT'),
            'sp_acs_url' => env('APP_URL') . '/sso/callback/saml',
            'sp_slo_url' => env('APP_URL') . '/sso/logout/saml',
        ],
    ],

    'jit_provisioning' => [
        'enabled' => env('SSO_JIT_PROVISIONING_ENABLED', true),
        'default_roles' => ['user'],
    ],

    'session' => [
        'lifetime_minutes' => 60,
    ],

    'attribute_mapping' => [
        'default' => [
            'sso_user_id' => 'sub',
            'email' => 'email',
            'first_name' => 'given_name',
            'last_name' => 'family_name',
            'display_name' => 'name',
        ],
        'azure' => [
            'sso_user_id' => 'oid',
            'email' => 'email',
            'first_name' => 'given_name',
            'last_name' => 'family_name',
            'display_name' => 'name',
        ],
        'google' => [
            'sso_user_id' => 'sub',
            'email' => 'email',
            'first_name' => 'given_name',
            'last_name' => 'family_name',
            'display_name' => 'name',
        ],
    ],
];
```

---

## 📊 Requirements Traceability

| Requirement ID | Description | Implemented By |
|---------------|-------------|----------------|
| ARC-IDE-1310 | MFA and SSO contracts MUST be optional and pluggable | `SsoProviderInterface`, `SsoManagerInterface` |
| BUS-IDE-1341 | SSO providers (SAML, OAuth2, OIDC) are configured per tenant | `SsoConfigRepositoryInterface`, multi-tenant config |
| BUS-IDE-1342 | SSO user provisioning can be automatic (JIT) or manual approval | `UserProvisioningInterface::isJitProvisioningEnabled()` |
| BUS-IDE-1343 | SSO attribute mapping is configurable | `AttributeMapperInterface`, `AttributeMap` VO |
| BUS-IDE-1344 | Local password authentication can be disabled when SSO is enforced | Config flag, enforced in `Nexus\Identity` |
| FUN-IDE-1375 | Provide SsoProviderInterface for single sign-on integration | `SsoProviderInterface` (contract) |
| FUN-IDE-1399 | Support SSO authentication with SAML 2.0 | `Saml2Provider`, `SamlProviderInterface` |
| FUN-IDE-1400 | Support SSO authentication with OAuth2/OIDC | `OAuth2Provider`, `OidcProvider` |
| FUN-IDE-1401 | Support JIT (Just-In-Time) user provisioning from SSO | `UserProvisioningInterface::findOrCreateUser()` |
| FUN-IDE-1402 | Support SSO attribute mapping configuration | `AttributeMapper`, `AttributeMap` |
| FUN-IDE-1403 | Support user impersonation with audit trail | (Handled by `Nexus\Identity` + `AuditLogger`) |

---

## 📝 Next Steps

1. **Review & Approve Plan**: Stakeholder sign-off on architecture
2. **Create Package Scaffold**: Initialize `packages/SSO/` structure
3. **Begin Phase 1**: Implement core contracts and services
4. **Set Up CI/CD**: Automated testing for SSO package
5. **Documentation**: Create README.md and integration guide

---

## 🔗 Related Documents

- [`docs/IDENTITY_IMPLEMENTATION.md`](./IDENTITY_IMPLEMENTATION.md) - Identity package documentation
- [`docs/NEXUS_PACKAGES_REFERENCE.md`](./NEXUS_PACKAGES_REFERENCE.md) - All packages reference
- [`docs/REQUIREMENTS_IDENTITY.md`](./REQUIREMENTS_IDENTITY.md) - Identity requirements
- [`.github/copilot-instructions.md`](../.github/copilot-instructions.md) - Architecture guidelines

---

**Document Version:** 1.0  
**Last Updated:** November 23, 2025  
**Author:** Nexus Development Team  
**Status:** ✅ Ready for Implementation
