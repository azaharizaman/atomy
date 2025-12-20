<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;

/**
 * Contract for gateway credentials storage.
 *
 * Stores encrypted gateway credentials per tenant.
 * SECURITY: Credentials should be encrypted at rest.
 */
interface CredentialStorageInterface
{
    /**
     * Store credentials for a tenant.
     *
     * @param string $tenantId Tenant identifier
     * @param GatewayCredentials $credentials Gateway credentials
     */
    public function store(string $tenantId, GatewayCredentials $credentials): void;

    /**
     * Retrieve credentials for a tenant and provider.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\CredentialsNotFoundException
     */
    public function retrieve(string $tenantId, GatewayProvider $provider): GatewayCredentials;

    /**
     * Get all configured providers for a tenant.
     *
     * @return array<GatewayProvider>
     */
    public function getProviders(string $tenantId): array;

    /**
     * Delete credentials.
     */
    public function delete(string $tenantId, GatewayProvider $provider): void;

    /**
     * Check if credentials exist.
     */
    public function exists(string $tenantId, GatewayProvider $provider): bool;
}
