<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\Common\ValueObjects\Period;
use Nexus\Crypto\Contracts\CryptoManagerInterface;
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
        private CryptoManagerInterface $crypto
    ) {}

    public function getAccounts(string $connectionId): array
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $dataProvider = $provider->getAccountDataProvider();

        // Decrypt credentials before passing to provider
        $credentials = $this->decryptCredentials($connection->getCredentials());

        return $dataProvider->getAccounts($credentials);
    }

    public function getAccount(string $connectionId, string $accountId): BankAccount
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $dataProvider = $provider->getAccountDataProvider();
        
        // Decrypt credentials before passing to provider
        $credentials = $this->decryptCredentials($connection->getCredentials());

        return $dataProvider->getAccount($credentials, $accountId);
    }

    public function getTransactions(string $connectionId, string $accountId, Period $period): array
    {
        $connection = $this->getConnection($connectionId);
        $provider = $this->providerRegistry->get($connection->getProviderName());
        $dataProvider = $provider->getAccountDataProvider();
        
        // Decrypt credentials before passing to provider
        $credentials = $this->decryptCredentials($connection->getCredentials());

        return $dataProvider->getTransactions($credentials, $accountId, $period->getStartDate(), $period->getEndDate());
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
