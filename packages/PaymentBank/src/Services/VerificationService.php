<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\Contracts\VerificationServiceInterface;
use Nexus\PaymentBank\DTOs\AccountVerificationResult;
use Nexus\PaymentBank\Enums\VerificationMethod;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;

final readonly class VerificationService implements VerificationServiceInterface
{
    public function __construct(
        private BankConnectionQueryInterface $connectionQuery,
        private ProviderRegistryInterface $providerRegistry,
        private CredentialDecryptionHelper $credentialDecryptor
    ) {}

    public function verifyOwnership(string $connectionId, string $accountId, array $identityData): AccountVerificationResult
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $verifier = $provider->getAccountVerification();
        
        // Initiate instant verification with identity data
        // Note: This adapts the service interface (verifyOwnership) to the provider interface (initiateVerification)
        $verificationId = $verifier->initiateVerification($connection, $accountId, VerificationMethod::INSTANT);
        
        // Complete verification with identity data
        $verified = $verifier->completeVerification($connection, $accountId, $identityData);
        
        $status = $verifier->getVerificationStatus($connection, $accountId);
        
        return new AccountVerificationResult(
            $accountId,
            $status,
            $verificationId
        );
    }

    public function initiateMicroDeposits(string $connectionId, string $accountId): string
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $verifier = $provider->getAccountVerification();
        
        // Initiate micro-deposit verification
        return $verifier->initiateVerification($connection, $accountId, VerificationMethod::MICRO_DEPOSIT);
    }

    public function verifyMicroDeposits(string $connectionId, string $verificationId, array $amounts): bool
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $verifier = $provider->getAccountVerification();
        
        // Complete micro-deposit verification using the verification identifier and the provided amounts
        return $verifier->completeVerification($connection, $verificationId, $amounts);
    }

    private function getConnection(string $connectionId): \Nexus\PaymentBank\Entities\BankConnectionInterface
    {
        return $this->connectionQuery->findById($connectionId)
            ?? throw new BankConnectionNotFoundException($connectionId);
    }
}
