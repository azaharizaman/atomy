<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\BankTransactionPersistInterface;
use Nexus\PaymentBank\Contracts\BankTransactionQueryInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\Entities\BankTransaction;
use Nexus\PaymentBank\Entities\BankTransactionInterface;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;
use Psr\Log\LoggerInterface;

final readonly class BankTransactionManager
{
    public function __construct(
        private BankTransactionPersistInterface $persist,
        private BankTransactionQueryInterface $query,
        private BankConnectionQueryInterface $connectionQuery,
        private ProviderRegistryInterface $providerRegistry,
        private LoggerInterface $logger
    ) {}

    public function fetchTransactions(string $connectionId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $connection = $this->connectionQuery->findById($connectionId);

        if (!$connection) {
            throw new BankConnectionNotFoundException("Connection not found: $connectionId");
        }

        $provider = $this->providerRegistry->get($connection->getProviderName());
        $rawTransactions = $provider->fetchTransactions($connection->getCredentials(), $start, $end);

        $transactions = [];
        foreach ($rawTransactions as $raw) {
            $transaction = new BankTransaction(
                id: $raw['id'],
                date: new \DateTimeImmutable($raw['date']),
                amount: (float)$raw['amount'],
                description: $raw['description'] ?? ''
            );

            $this->persist->save($transaction);
            $transactions[] = $transaction;
        }

        $this->logger->info("Fetched " . count($transactions) . " transactions for connection $connectionId");

        return $transactions;
    }

    public function getTransaction(string $id): ?BankTransactionInterface
    {
        return $this->query->findById($id);
    }
}
