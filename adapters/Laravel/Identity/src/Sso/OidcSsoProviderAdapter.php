<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Sso;

use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\Contracts\SsoProviderInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserPersistInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Identity\Exceptions\SsoAuthenticationException;
use Nexus\SSO\Providers\OidcProvider;
use Nexus\SSO\ValueObjects\AttributeMap;
use Nexus\SSO\ValueObjects\SsoProtocol;
use Nexus\SSO\ValueObjects\SsoProviderConfig;

final readonly class OidcSsoProviderAdapter implements SsoProviderInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private UserQueryInterface $userQuery,
        private UserPersistInterface $userPersist,
        private PasswordHasherInterface $passwordHasher,
        private array $config,
        private OidcProvider $oidc = new OidcProvider(),
    ) {
    }

    public function getName(): string
    {
        return 'oidc';
    }

    public function getAuthorizationUrl(array $parameters = []): string
    {
        $tenantId = (string) ($parameters['tenant_id'] ?? '');
        $state = (string) ($parameters['state'] ?? '');
        $redirectUriOverride = isset($parameters['redirect_uri']) ? (string) $parameters['redirect_uri'] : null;

        if ($tenantId === '' || $state === '') {
            throw new \InvalidArgumentException('tenant_id and state are required');
        }

        $cfg = $this->providerConfig($redirectUriOverride);
        $discovery = $this->oidc->getDiscoveryDocument($cfg->discoveryUrl);

        $cfg = $this->withDiscoveryEndpoints($cfg, $discovery);
        $this->oidc->validateConfig($cfg);

        return $this->oidc->getAuthorizationUrl($cfg, $state, ['scopes' => $cfg->scopes]);
    }

    public function handleCallback(array $callbackData): UserInterface
    {
        $tenantId = (string) ($callbackData['tenant_id'] ?? '');
        $code = (string) ($callbackData['code'] ?? '');
        $redirectUriOverride = isset($callbackData['redirect_uri']) ? (string) $callbackData['redirect_uri'] : null;

        if ($tenantId === '' || $code === '') {
            throw new SsoAuthenticationException($this->getName(), 'Missing tenant_id or code');
        }

        $cfg = $this->providerConfig($redirectUriOverride);
        $discovery = $this->oidc->getDiscoveryDocument($cfg->discoveryUrl);
        $cfg = $this->withDiscoveryEndpoints($cfg, $discovery);

        try {
            $profile = $this->oidc->handleCallback($cfg, ['code' => $code]);
        } catch (\Throwable $e) {
            throw new SsoAuthenticationException($this->getName(), $e->getMessage(), previous: $e);
        }

        $mapped = $this->mapAttributes($profile->attributes);
        $email = strtolower(trim((string) ($mapped['email'] ?? '')));
        if ($email === '') {
            throw new SsoAuthenticationException($this->getName(), 'Missing email claim');
        }

        $existing = $this->userQuery->findByEmailOrNull($email);
        if ($existing !== null) {
            return $existing;
        }

        if (! $this->isJitProvisioningEnabled()) {
            throw new SsoAuthenticationException($this->getName(), 'JIT provisioning disabled');
        }

        return $this->provisionUser(array_merge($mapped, [
            'tenant_id' => $tenantId,
        ]));
    }

    public function getUserProfile(string $accessToken): array
    {
        $cfg = $this->providerConfig(null);
        return $this->oidc->getUserInfo($cfg, $accessToken);
    }

    public function mapAttributes(array $ssoAttributes): array
    {
        $out = [
            'email' => $ssoAttributes['email'] ?? null,
            'name' => $ssoAttributes['name'] ?? null,
        ];

        return $out;
    }

    public function isJitProvisioningEnabled(): bool
    {
        return (bool) ($this->config['jit'] ?? true);
    }

    public function provisionUser(array $ssoAttributes): UserInterface
    {
        $email = strtolower(trim((string) ($ssoAttributes['email'] ?? '')));
        $tenantId = (string) ($ssoAttributes['tenant_id'] ?? '');
        $name = (string) ($ssoAttributes['name'] ?? '');

        if ($email === '' || $tenantId === '') {
            throw new SsoAuthenticationException($this->getName(), 'Missing email or tenant context');
        }

        $password = bin2hex(random_bytes(24));
        $passwordHash = $this->passwordHasher->hash($password);

        return $this->userPersist->create([
            'tenant_id' => $tenantId,
            'email' => $email,
            'password_hash' => $passwordHash,
            'first_name' => $name !== '' ? $name : 'User',
            'status' => 'active',
        ]);
    }

    private function providerConfig(?string $redirectUriOverride): SsoProviderConfig
    {
        $issuerUrl = (string) ($this->config['issuer_url'] ?? '');
        $clientId = (string) ($this->config['client_id'] ?? '');
        $clientSecret = (string) ($this->config['client_secret'] ?? '');
        $redirectUri = $redirectUriOverride ?: (string) ($this->config['redirect_uri'] ?? '');

        if ($issuerUrl === '' || $clientId === '' || $clientSecret === '' || $redirectUri === '') {
            throw new \RuntimeException('OIDC is not configured');
        }

        $attributeMap = new AttributeMap([
            'sso_user_id' => 'sub',
            'email' => 'email',
            'display_name' => 'name',
        ]);

        $metadata = [];
        if (isset($this->config['mock_id_token_claims']) && is_array($this->config['mock_id_token_claims'])) {
            $metadata['mock_id_token_claims'] = $this->config['mock_id_token_claims'];
        }

        return new SsoProviderConfig(
            providerName: 'oidc',
            protocol: SsoProtocol::OIDC,
            clientId: $clientId,
            clientSecret: $clientSecret,
            discoveryUrl: $issuerUrl,
            redirectUri: $redirectUri,
            attributeMap: $attributeMap,
            scopes: ['openid', 'email', 'profile'],
            metadata: $metadata,
        );
    }

    /**
     * @param array<string, mixed> $discovery
     */
    private function withDiscoveryEndpoints(SsoProviderConfig $cfg, array $discovery): SsoProviderConfig
    {
        return new SsoProviderConfig(
            providerName: $cfg->providerName,
            protocol: $cfg->protocol,
            clientId: $cfg->clientId,
            clientSecret: $cfg->clientSecret,
            discoveryUrl: $cfg->discoveryUrl,
            redirectUri: $cfg->redirectUri,
            attributeMap: $cfg->attributeMap,
            enabled: $cfg->enabled,
            scopes: $cfg->scopes,
            metadata: array_merge($cfg->metadata, [
                'authorization_endpoint' => $discovery['authorization_endpoint'] ?? null,
                'token_endpoint' => $discovery['token_endpoint'] ?? null,
                'userinfo_endpoint' => $discovery['userinfo_endpoint'] ?? null,
            ]),
        );
    }
}

