<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ConnectorCredential;
use Nexus\Connector\Contracts\CredentialProviderInterface;
use Nexus\Connector\Exceptions\{CredentialNotFoundException, CredentialRefreshException};
use Nexus\Connector\ValueObjects\{AuthMethod, Credentials};

/**
 * Laravel implementation of credential provider using encrypted database storage.
 */
final readonly class LaravelCredentialProvider implements CredentialProviderInterface
{
    /**
     * Retrieve credentials for a specific service endpoint.
     */
    public function getCredentials(string $serviceName, ?string $tenantId = null): Credentials
    {
        $model = ConnectorCredential::forService($serviceName, $tenantId)
            ->active()
            ->first();

        if ($model === null) {
            throw CredentialNotFoundException::forService($serviceName, $tenantId);
        }

        // Check if expired
        if ($model->isExpired()) {
            // Attempt to refresh
            try {
                return $this->refreshCredentials($serviceName, $tenantId);
            } catch (\Throwable $e) {
                throw CredentialNotFoundException::forService($serviceName, $tenantId);
            }
        }

        return new Credentials(
            authMethod: AuthMethod::from($model->auth_method),
            data: $model->credential_data,
            expiresAt: $model->expires_at?->toDateTimeImmutable(),
            refreshToken: $model->refresh_token
        );
    }

    /**
     * Check if credentials exist for a service.
     */
    public function hasCredentials(string $serviceName, ?string $tenantId = null): bool
    {
        return ConnectorCredential::forService($serviceName, $tenantId)
            ->active()
            ->exists();
    }

    /**
     * Refresh OAuth tokens if they have expired.
     */
    public function refreshCredentials(string $serviceName, ?string $tenantId = null): Credentials
    {
        $model = ConnectorCredential::forService($serviceName, $tenantId)
            ->active()
            ->first();

        if ($model === null) {
            throw CredentialNotFoundException::forService($serviceName, $tenantId);
        }

        if ($model->auth_method !== AuthMethod::OAUTH2->value) {
            // Non-OAuth credentials don't need refresh
            return $this->getCredentials($serviceName, $tenantId);
        }

        if ($model->refresh_token === null) {
            throw new CredentialRefreshException(
                message: "No refresh token available for {$serviceName}",
                serviceName: $serviceName
            );
        }

        // TODO: Implement actual OAuth refresh logic based on service
        // This is a placeholder that should be implemented per service
        throw new CredentialRefreshException(
            message: "OAuth refresh not implemented for {$serviceName}",
            serviceName: $serviceName
        );
    }

    /**
     * Store or update credentials (helper method for application use).
     */
    public function storeCredentials(
        string $serviceName,
        AuthMethod $authMethod,
        array $credentialData,
        ?string $tenantId = null,
        ?\DateTimeInterface $expiresAt = null,
        ?string $refreshToken = null
    ): void {
        ConnectorCredential::updateOrCreate(
            [
                'service_name' => $serviceName,
                'tenant_id' => $tenantId,
            ],
            [
                'auth_method' => $authMethod->value,
                'credential_data' => $credentialData,
                'expires_at' => $expiresAt,
                'refresh_token' => $refreshToken,
                'is_active' => true,
            ]
        );
    }

    /**
     * Deactivate credentials (helper method for application use).
     */
    public function deactivateCredentials(string $serviceName, ?string $tenantId = null): void
    {
        ConnectorCredential::forService($serviceName, $tenantId)
            ->update(['is_active' => false]);
    }
}
