<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\JwtServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Nexus\SSO\Providers\OidcProvider;
use Nexus\SSO\ValueObjects\AttributeMap;
use Nexus\SSO\ValueObjects\SsoProtocol;
use Nexus\SSO\ValueObjects\SsoProviderConfig;

final readonly class OidcSsoService
{
    private const STATE_TTL_SECONDS = 600;

    public function __construct(
        private JwtServiceInterface $jwt,
        private OidcProvider $provider = new OidcProvider(),
    ) {
    }

    /**
     * @return array{authorization_url: string, state: string}
     */
    public function initiate(string $tenantId, ?string $redirectUriOverride = null): array
    {
        $config = $this->configForTenant($tenantId, $redirectUriOverride);

        $discovery = $this->provider->getDiscoveryDocument($config->discoveryUrl);

        $state = Str::random(40);
        $cacheKey = $this->stateCacheKey($state);

        Cache::put($cacheKey, [
            'tenant_id' => $tenantId,
            'redirect_uri' => $config->redirectUri,
            'issuer_url' => $config->discoveryUrl,
        ], self::STATE_TTL_SECONDS);

        $config = new SsoProviderConfig(
            providerName: $config->providerName,
            protocol: $config->protocol,
            clientId: $config->clientId,
            clientSecret: $config->clientSecret,
            discoveryUrl: $config->discoveryUrl,
            redirectUri: $config->redirectUri,
            attributeMap: $config->attributeMap,
            enabled: $config->enabled,
            scopes: $config->scopes,
            metadata: array_merge($config->metadata, [
                'authorization_endpoint' => $discovery['authorization_endpoint'] ?? null,
                'token_endpoint' => $discovery['token_endpoint'] ?? null,
                'userinfo_endpoint' => $discovery['userinfo_endpoint'] ?? null,
            ]),
        );

        $this->provider->validateConfig($config);

        $authorizationUrl = $this->provider->getAuthorizationUrl($config, $state, [
            'scopes' => $config->scopes,
        ]);

        return [
            'authorization_url' => $authorizationUrl,
            'state' => $state,
        ];
    }

    /**
     * @param array{code: string, state: string} $callback
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int, user: array<string, mixed>}
     */
    public function callback(array $callback): array
    {
        $state = $callback['state'];
        $cacheKey = $this->stateCacheKey($state);
        /** @var array{tenant_id: string, redirect_uri: string, issuer_url: string}|null $stateData */
        $stateData = Cache::pull($cacheKey);
        if ($stateData === null) {
            throw new \InvalidArgumentException('Invalid or expired SSO state');
        }

        $tenantId = $stateData['tenant_id'];
        $config = $this->configForTenant($tenantId, $stateData['redirect_uri']);
        $discovery = $this->provider->getDiscoveryDocument($config->discoveryUrl);

        $config = new SsoProviderConfig(
            providerName: $config->providerName,
            protocol: $config->protocol,
            clientId: $config->clientId,
            clientSecret: $config->clientSecret,
            discoveryUrl: $config->discoveryUrl,
            redirectUri: $stateData['redirect_uri'],
            attributeMap: $config->attributeMap,
            enabled: $config->enabled,
            scopes: $config->scopes,
            metadata: array_merge($config->metadata, [
                'authorization_endpoint' => $discovery['authorization_endpoint'] ?? null,
                'token_endpoint' => $discovery['token_endpoint'] ?? null,
                'userinfo_endpoint' => $discovery['userinfo_endpoint'] ?? null,
                // Test-only hooks supported by Nexus\\SSO provider (kept empty in prod)
                'mock_id_token_claims' => $config->metadata['mock_id_token_claims'] ?? null,
            ]),
        );

        $this->provider->validateConfig($config);

        $profile = $this->provider->handleCallback($config, [
            'code' => $callback['code'],
        ]);

        $email = strtolower(trim($profile->email));
        if ($email === '') {
            throw new \RuntimeException('SSO profile is missing email');
        }

        /** @var User|null $user */
        $user = User::query()
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();

        if ($user === null) {
            $user = User::query()->create([
                'tenant_id' => $tenantId,
                'email' => $email,
                'name' => $profile->displayName ?? $profile->firstName ?? 'User',
                'password_hash' => Hash::make(Str::random(48)),
                'role' => $this->mapRoleFromClaims($profile->attributes),
                'status' => 'active',
                'timezone' => 'UTC',
                'locale' => 'en',
                'email_verified_at' => now(),
                'last_login_at' => now(),
            ]);
        } else {
            $user->last_login_at = now();
            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
            }
            $user->save();
        }

        $accessToken = $this->jwt->issueAccessToken((string) $user->id, $tenantId);
        $refreshToken = $this->jwt->issueRefreshToken((string) $user->id, $tenantId);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwt->getTtlMinutes() * 60,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'tenantId' => $user->tenant_id,
            ],
        ];
    }

    private function stateCacheKey(string $state): string
    {
        return 'oidc_sso_state:' . $state;
    }

    private function configForTenant(string $tenantId, ?string $redirectUriOverride): SsoProviderConfig
    {
        $issuerUrl = (string) (config('services.oidc.issuer_url') ?? '');
        $clientId = (string) (config('services.oidc.client_id') ?? '');
        $clientSecret = (string) (config('services.oidc.client_secret') ?? '');
        $redirectUri = $redirectUriOverride ?: (string) (config('services.oidc.redirect_uri') ?? '');
        $mockIdTokenClaims = config('services.oidc.mock_id_token_claims');

        if ($issuerUrl === '' || $clientId === '' || $clientSecret === '' || $redirectUri === '') {
            throw new \RuntimeException('OIDC is not configured');
        }

        return new SsoProviderConfig(
            providerName: 'oidc',
            protocol: SsoProtocol::OIDC,
            clientId: $clientId,
            clientSecret: $clientSecret,
            discoveryUrl: $issuerUrl,
            redirectUri: $redirectUri,
            attributeMap: new AttributeMap([
                'sso_user_id' => 'sub',
                'email' => 'email',
                'first_name' => 'given_name',
                'last_name' => 'family_name',
                'display_name' => 'name',
            ]),
            scopes: ['openid', 'email', 'profile'],
            metadata: [
                // Test-mode hook used by Nexus\\SSO\\Providers\\OidcProvider.
                'mock_id_token_claims' => is_array($mockIdTokenClaims) ? $mockIdTokenClaims : null,
            ],
        );
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function mapRoleFromClaims(array $claims): string
    {
        $groups = $claims['groups'] ?? $claims['roles'] ?? null;
        $asList = is_array($groups) ? $groups : (is_string($groups) ? [$groups] : []);
        $normalized = array_map(static fn ($v): string => strtolower(trim((string) $v)), $asList);

        if (in_array('admin', $normalized, true) || in_array('atomy_admin', $normalized, true)) {
            return 'admin';
        }

        return 'user';
    }
}

