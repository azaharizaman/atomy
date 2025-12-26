<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\Contracts\VerificationServiceInterface;
use Nexus\PaymentBank\DTOs\AccountVerificationResult;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;

final readonly class VerificationService implements VerificationServiceInterface
{
    public function __construct(
        private BankConnectionQueryInterface $connectionQuery,
        private ProviderRegistryInterface $providerRegistry
    ) {}

    public function verifyOwnership(string $connectionId, string $accountId, array $identityData): AccountVerificationResult
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $verifier = $provider->getAccountVerification();
        
        return $verifier->verifyOwnership($connection->getCredentials(), $accountId, $identityData);
    }

    public function initiateMicroDeposits(string $connectionId, string $accountId): string
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $verifier = $provider->getAccountVerification();
        
        return $verifier->initiateMicroDeposits($connection->getCredentials(), $accountId);
    }

    public function verifyMicroDeposits(string $connectionId, string $verificationId, array $amounts): bool
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $verifier = $provider->getAccountVerification();
        
        return $verifier->verifyMicroDeposits($connection->getCredentials(), $verificationId, $amounts);
    }

    private function getConnection(string $connectionId): \Nexus\PaymentBank\Entities\BankConnectionInterface
    {
        return $this->connectionQuery->findById($connectionId)
            ?? throw new BankConnectionNotFoundException($connectionId);
    }
}
