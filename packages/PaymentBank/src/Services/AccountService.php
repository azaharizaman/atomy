<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\Common\ValueObjects\Period;
use Nexus\PaymentBank\Contracts\AccountServiceInterface;
use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\DTOs\BankAccount;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;
use Nexus\PaymentBank\Exceptions\ProviderException;

final readonly class AccountService implements AccountServiceInterface
{
    public function __construct(
        private BankConnectionQueryInterface $connectionQuery,
        private ProviderRegistryInterface $providerRegistry,
        private CredentialDecryptionHelper $credentialDecryptor
    ) {}

    public function getAccounts(string $connectionId): array
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $dataProvider = $provider->getAccountDataProvider();

        // Decrypt credentials before passing to provider
        $credentials = $this->credentialDecryptor->decryptCredentials($connection->getCredentials());

        return $dataProvider->getAccounts($credentials);
    }

    public function getAccount(string $connectionId, string $accountId): BankAccount
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $dataProvider = $provider->getAccountDataProvider();
        
        // Decrypt credentials before passing to provider
        $credentials = $this->credentialDecryptor->decryptCredentials($connection->getCredentials());

        return $dataProvider->getAccount($credentials, $accountId);
    }

    public function getTransactions(string $connectionId, string $accountId, Period $period): array
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $dataProvider = $provider->getAccountDataProvider();
        
        // Decrypt credentials before passing to provider
        $credentials = $this->credentialDecryptor->decryptCredentials($connection->getCredentials());

        return $dataProvider->getTransactions($credentials, $accountId, $period->getStartDate(), $period->getEndDate());
    }

    private function getConnection(string $connectionId): \Nexus\PaymentBank\Entities\BankConnectionInterface
    {
        return $this->connectionQuery->findById($connectionId)
            ?? throw new BankConnectionNotFoundException($connectionId);
    }
}
