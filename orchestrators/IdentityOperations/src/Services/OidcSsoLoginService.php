<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\Identity\Contracts\SsoProviderInterface as IdentitySsoProviderInterface;
use Nexus\Identity\Exceptions\SsoAuthenticationException;
use Nexus\IdentityOperations\DTOs\UserContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Orchestrates OIDC SSO flow using an Identity SSO provider and a state store.
 */
final readonly class OidcSsoLoginService implements SsoLoginServiceInterface
{
    private const STATE_TTL_SECONDS = 600;

    public function __construct(
        private IdentitySsoProviderInterface $provider,
        private SsoStateStoreInterface $stateStore,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function initiate(string $tenantId, ?string $redirectUriOverride = null): array
    {
        $state = bin2hex(random_bytes(20));

        $this->stateStore->put($state, [
            'tenant_id' => $tenantId,
            'redirect_uri' => $redirectUriOverride,
        ], self::STATE_TTL_SECONDS);

        $url = $this->provider->getAuthorizationUrl([
            'tenant_id' => $tenantId,
            'state' => $state,
            'redirect_uri' => $redirectUriOverride,
        ]);

        return ['authorization_url' => $url, 'state' => $state];
    }

    public function callback(string $tenantId, string $code, string $state): UserContext
    {
        $stateData = $this->stateStore->pull($state);
        if ($stateData === null || (string) ($stateData['tenant_id'] ?? '') !== $tenantId) {
            throw new \InvalidArgumentException('Invalid or expired SSO state');
        }

        try {
            $user = $this->provider->handleCallback([
                'code' => $code,
                'state' => $state,
                'tenant_id' => $tenantId,
                'redirect_uri' => $stateData['redirect_uri'] ?? null,
            ]);
        } catch (SsoAuthenticationException $e) {
            $this->logger->warning('SSO auth failed', ['tenant_id' => $tenantId, 'reason' => $e->getMessage()]);
            throw $e;
        }

        return new UserContext(
            userId: $user->getId(),
            email: $user->getEmail(),
            firstName: $user->getName(),
            lastName: null,
            tenantId: $tenantId,
            status: $user->getStatus(),
            permissions: [],
            roles: [],
        );
    }
}

