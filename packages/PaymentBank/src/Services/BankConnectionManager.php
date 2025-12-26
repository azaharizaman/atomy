<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\Crypto\Contracts\CryptoManagerInterface;
use Nexus\PaymentBank\Contracts\BankConnectionManagerInterface;
use Nexus\PaymentBank\Contracts\BankConnectionPersistInterface;
use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\Entities\BankConnection;
use Nexus\PaymentBank\Entities\BankConnectionInterface;
use Nexus\PaymentBank\Enums\ConsentStatus;
use Nexus\PaymentBank\Enums\ProviderType;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;
use Psr\Log\LoggerInterface;

final readonly class BankConnectionManager implements BankConnectionManagerInterface
{
    public function __construct(
        private BankConnectionPersistInterface $persist,
        private BankConnectionQueryInterface $query,
        private ProviderRegistryInterface $providerRegistry,
        private CryptoManagerInterface $crypto,
        private LoggerInterface $logger
    ) {}

    public function initiateConnection(string $providerName, string $tenantId, array $parameters = []): array
    {
        $providerType = ProviderType::from($providerName);
        $provider = $this->providerRegistry->getProvider($providerType);
        
        return [
            'provider' => $providerName,
            'tenant_id' => $tenantId,
            'action' => 'redirect',
            'url' => "https://auth.{$providerName}.com/connect?tenant={$tenantId}", 
        ];
    }

    public function completeConnection(string $providerName, string $tenantId, array $callbackData): BankConnectionInterface
    {
        $providerType = ProviderType::from($providerName);
        $provider = $this->providerRegistry->getProvider($providerType);
        
        // Mock credentials exchange
        $accessToken = 'mock_access_token';
        $refreshToken = 'mock_refresh_token';
        $expiresIn = 3600;
        
        $encryptedAccessToken = $this->encryptString($accessToken);
        $encryptedRefreshToken = $this->encryptString($refreshToken);

        $connection = new BankConnection(
            id: uniqid('conn_'),
            tenantId: $tenantId,
            providerType: $providerType,
            providerConnectionId: $callbackData['institution_id'] ?? 'unknown',
            accessToken: $encryptedAccessToken,
            refreshToken: $encryptedRefreshToken,
            expiresAt: (new \DateTimeImmutable())->modify("+{$expiresIn} seconds"),
            consentStatus: ConsentStatus::ACTIVE,
            metadata: ['connected_at' => date('c')],
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );

        $saved = $this->persist->save($connection);
        
        $this->logger->info('Bank connection established', [
            'connection_id' => $saved->getId(),
            'provider' => $providerName,
            'tenant_id' => $tenantId
        ]);

        return $saved;
    }

    public function refreshConnection(string $connectionId): BankConnectionInterface
    {
        $connection = $this->query->findById($connectionId) 
            ?? throw new BankConnectionNotFoundException($connectionId);

        $providerType = $connection->getProviderType();
        $provider = $this->providerRegistry->getProvider($providerType);
        
        $accessToken = $this->decryptString($connection->getAccessToken());
        $refreshToken = $connection->getRefreshToken() ? $this->decryptString($connection->getRefreshToken()) : null;
        
        // Mock refresh
        $newAccessToken = 'new_mock_access_token';
        $expiresIn = 3600;

        $updatedConnection = $connection->withAccessToken(
            $this->encryptString($newAccessToken),
            (new \DateTimeImmutable())->modify("+{$expiresIn} seconds")
        );
        
        $saved = $this->persist->save($updatedConnection);
        
        $this->logger->info('Bank connection refreshed', ['connection_id' => $connectionId]);

        return $saved;
    }

    public function disconnect(string $connectionId): void
    {
        $connection = $this->query->findById($connectionId)
            ?? throw new BankConnectionNotFoundException($connectionId);

        try {
            $providerType = $connection->getProviderType();
            $provider = $this->providerRegistry->getProvider($providerType);
            // Revoke token logic here
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to revoke token on provider', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage()
            ]);
        }

        $this->persist->delete($connectionId);
        
        $this->logger->info('Bank connection disconnected', ['connection_id' => $connectionId]);
    }

    public function updateStatus(string $connectionId, ConsentStatus $status, ?string $errorMessage = null): BankConnectionInterface
    {
        $connection = $this->query->findById($connectionId)
            ?? throw new BankConnectionNotFoundException($connectionId);

        $updated = $connection->withConsentStatus($status);
        
        if ($errorMessage) {
            $updated = $updated->withMetadata(['error_message' => $errorMessage]);
        }
        
        return $this->persist->save($updated);
    }

    private function encryptString(string $value): string
    {
        return 'encrypted_' . $value;
    }

    private function decryptString(string $value): string
    {
        return str_replace('encrypted_', '', $value);
    }
}
