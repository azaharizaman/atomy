<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\Crypto\Contracts\CryptoManagerInterface;
use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\Contracts\VerificationServiceInterface;
use Nexus\PaymentBank\DTOs\AccountVerificationResult;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;

final readonly class VerificationService implements VerificationServiceInterface
{
    public function __construct(
        private BankConnectionQueryInterface $connectionQuery,
        private ProviderRegistryInterface $providerRegistry,
        private CryptoManagerInterface $crypto
    ) {}

    public function verifyOwnership(string $connectionId, string $accountId, array $identityData): AccountVerificationResult
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $verifier = $provider->getAccountVerification();
        
        // Decrypt credentials before passing to provider
        $credentials = $this->decryptCredentials($connection->getCredentials());
        
        return $verifier->verifyOwnership($credentials, $accountId, $identityData);
    }

    public function initiateMicroDeposits(string $connectionId, string $accountId): string
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $verifier = $provider->getAccountVerification();
        
        // Decrypt credentials before passing to provider
        $credentials = $this->decryptCredentials($connection->getCredentials());
        
        return $verifier->initiateMicroDeposits($credentials, $accountId);
    }

    public function verifyMicroDeposits(string $connectionId, string $verificationId, array $amounts): bool
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $verifier = $provider->getAccountVerification();
        
        // Decrypt credentials before passing to provider
        $credentials = $this->decryptCredentials($connection->getCredentials());
        
        return $verifier->verifyMicroDeposits($credentials, $verificationId, $amounts);
    }

    private function getConnection(string $connectionId): \Nexus\PaymentBank\Entities\BankConnectionInterface
    {
        return $this->connectionQuery->findById($connectionId)
            ?? throw new BankConnectionNotFoundException($connectionId);
    }

    /**
     * Decrypt encrypted credentials from BankConnection.
     *
     * @param array<string, mixed> $encryptedCredentials
     * @return array<string, mixed>
     */
    private function decryptCredentials(array $encryptedCredentials): array
    {
        $decrypted = $encryptedCredentials;
        
        if (isset($encryptedCredentials['access_token'])) {
            $decrypted['access_token'] = $this->crypto->decryptString($encryptedCredentials['access_token']);
        }
        
        if (isset($encryptedCredentials['refresh_token']) && $encryptedCredentials['refresh_token'] !== null) {
            $decrypted['refresh_token'] = $this->crypto->decryptString($encryptedCredentials['refresh_token']);
        }
        
        return $decrypted;
    }
}
