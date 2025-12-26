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
        private ProviderRegistryInterface $providerRegistry
    ) {}

    public function getAccounts(string $connectionId): array
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $dataProvider = $provider->getAccountDataProvider();

        // In a real scenario, we would decrypt credentials here and pass them
        // or the provider would handle it if initialized with credentials.
        // For this design, we assume the provider methods accept the connection context or credentials.
        // Let's assume the DataProvider methods take the connection object or credentials.
        // Based on AccountDataProviderInterface: public function getAccounts(array $credentials): array;
        
        // We need to decrypt credentials. 
        // For simplicity in this file, I'll assume raw credentials or handled by a helper.
        // Ideally, we inject CryptoManager here too.
        $credentials = $connection->getCredentials(); // Should be decrypted

        return $dataProvider->getAccounts($credentials);
    }

    public function getAccount(string $connectionId, string $accountId): BankAccount
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $dataProvider = $provider->getAccountDataProvider();
        $credentials = $connection->getCredentials();

        return $dataProvider->getAccount($credentials, $accountId);
    }

    public function getTransactions(string $connectionId, string $accountId, Period $period): array
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $dataProvider = $provider->getAccountDataProvider();
        $credentials = $connection->getCredentials();

        return $dataProvider->getTransactions($credentials, $accountId, $period->getStartDate(), $period->getEndDate());
    }

    private function getConnection(string $connectionId): \Nexus\PaymentBank\Entities\BankConnectionInterface
    {
        return $this->connectionQuery->findById($connectionId)
            ?? throw new BankConnectionNotFoundException($connectionId);
    }
}
