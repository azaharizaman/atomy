<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\Common\Contracts\UlidInterface;
use Nexus\Crypto\Contracts\CryptoManagerInterface;
use Nexus\Crypto\ValueObjects\EncryptedData;
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
        private LoggerInterface $logger,
        private UlidInterface $ulid
    ) {}

    public function initiateConnection(string $providerName, string $tenantId, array $config = []): array
    {
        $providerType = ProviderType::from($providerName);
        $provider = $this->providerRegistry->getProvider($providerType);
        
        // PLACEHOLDER: Mock authorization URL - replace with actual provider API call
        // when provider adapters are implemented
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
        
        // PLACEHOLDER: Mock credentials exchange - replace with actual provider OAuth token exchange
        // when provider adapters (Plaid, TrueLayer, Yodlee) are implemented
        $accessToken = 'mock_access_token';
        $refreshToken = 'mock_refresh_token';
        $expiresIn = 3600;
        
        // Encrypt tokens before storing - store as JSON-serialized EncryptedData
        $encryptedAccessToken = $this->crypto->encrypt($accessToken)->toJson();
        $encryptedRefreshToken = $this->crypto->encrypt($refreshToken)->toJson();

        $connection = new BankConnection(
            id: $this->ulid->generate(),
            tenantId: $tenantId,
            providerType: $providerType,
            providerConnectionId: $callbackData['institution_id'] ?? 'unknown',
            accessToken: $encryptedAccessToken,
            refreshToken: $encryptedRefreshToken,
            expiresAt: (new \DateTimeImmutable())->modify("+{$expiresIn} seconds"),
            consentStatus: ConsentStatus::ACTIVE,
            metadata: ['connected_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)],
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
        
        // Decrypt tokens for refresh
        $accessTokenJson = $connection->getAccessToken();
        $accessToken = $this->crypto->decrypt(EncryptedData::fromJson($accessTokenJson));
        
        $refreshTokenJson = $connection->getRefreshToken();
        $refreshToken = $refreshTokenJson ? $this->crypto->decrypt(EncryptedData::fromJson($refreshTokenJson)) : null;
        
        // PLACEHOLDER: Mock token refresh - replace with actual provider OAuth refresh token flow
        // when provider adapters are implemented
        $newAccessToken = 'new_mock_access_token';
        $expiresIn = 3600;

        $updatedConnection = $connection->withAccessToken(
            $this->crypto->encrypt($newAccessToken)->toJson(),
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
            // TODO: Implement token revocation when provider adapters are ready
            // $provider->revokeToken($connection->getAccessToken());
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
}
