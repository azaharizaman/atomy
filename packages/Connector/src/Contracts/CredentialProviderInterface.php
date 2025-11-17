<?php

declare(strict_types=1);

namespace Nexus\Connector\Contracts;

use Nexus\Connector\ValueObjects\Credentials;

/**
 * Interface for retrieving secure credentials from storage.
 *
 * Implementations should retrieve API keys, tokens, and secrets
 * from secure storage (e.g., encrypted database, vault, or settings service).
 */
interface CredentialProviderInterface
{
    /**
     * Retrieve credentials for a specific service endpoint.
     *
     * @param string $serviceName The name of the external service (e.g., 'mailchimp', 'twilio')
     * @param string|null $tenantId Optional tenant identifier for multi-tenant credentials
     * @return Credentials The credentials object containing authentication data
     * @throws \Nexus\Connector\Exceptions\CredentialNotFoundException
     */
    public function getCredentials(string $serviceName, ?string $tenantId = null): Credentials;

    /**
     * Check if credentials exist for a service.
     *
     * @param string $serviceName The name of the external service
     * @param string|null $tenantId Optional tenant identifier
     * @return bool True if credentials exist
     */
    public function hasCredentials(string $serviceName, ?string $tenantId = null): bool;

    /**
     * Refresh OAuth tokens if they have expired.
     *
     * @param string $serviceName The name of the external service
     * @param string|null $tenantId Optional tenant identifier
     * @return Credentials Updated credentials with refreshed tokens
     * @throws \Nexus\Connector\Exceptions\CredentialRefreshException
     */
    public function refreshCredentials(string $serviceName, ?string $tenantId = null): Credentials;
}
