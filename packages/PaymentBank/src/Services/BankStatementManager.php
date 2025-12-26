<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\BankStatementPersistInterface;
use Nexus\PaymentBank\Contracts\BankStatementQueryInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\Entities\BankStatement;
use Nexus\PaymentBank\Entities\BankStatementInterface;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;
use Psr\Log\LoggerInterface;

final readonly class BankStatementManager
{
    public function __construct(
        private BankStatementPersistInterface $persist,
        private BankStatementQueryInterface $query,
        private BankConnectionQueryInterface $connectionQuery,
        private ProviderRegistryInterface $providerRegistry,
        private LoggerInterface $logger
    ) {}

    public function fetchStatements(string $connectionId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $connection = $this->connectionQuery->findById($connectionId);

        if (!$connection) {
            throw new BankConnectionNotFoundException("Connection not found: $connectionId");
        }

        $provider = $this->providerRegistry->get($connection->getProviderName());
        $rawStatements = $provider->fetchStatements($connection->getCredentials(), $start, $end);

        $statements = [];
        foreach ($rawStatements as $raw) {
            // Support both explicit start_date/end_date and single date field
            // If only 'date' is provided, both start and end will be set to the same value,
            // representing a single-day statement period
            $startDate = new \DateTimeImmutable($raw['start_date'] ?? $raw['date']);
            $endDate = new \DateTimeImmutable($raw['end_date'] ?? $raw['date']);
            $statement = new BankStatement(
                id: $raw['id'],
                connectionId: $connectionId,
                startDate: $startDate,
                endDate: $endDate,
                amount: (float)$raw['amount']
            );

            $this->persist->save($statement);
            $statements[] = $statement;
        }

        $this->logger->info("Fetched " . count($statements) . " statements for connection $connectionId");

        return $statements;
    }

    public function getStatement(string $id): ?BankStatementInterface
    {
        return $this->query->findById($id);
    }
}
