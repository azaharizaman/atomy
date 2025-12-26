<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

final readonly class ConnectionRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $providerName Provider name (e.g., 'plaid', 'truelayer')
     * @param string $redirectUrl Callback URL after authorization
     * @param array<string> $scopes Requested permissions (e.g., ['transactions', 'auth'])
     * @param array<string, mixed> $options Additional provider-specific options
     */
    public function __construct(
        private string $tenantId,
        private string $providerName,
        private string $redirectUrl,
        private array $scopes = [],
        private array $options = []
    ) {}

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
