<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\Integration\CashManagementProviderInterface;
use Nexus\Treasury\Contracts\LiquidityPoolInterface;
use Nexus\Treasury\Contracts\LiquidityPoolQueryInterface;
use Nexus\Treasury\Contracts\LiquidityPoolPersistInterface;
use Nexus\Treasury\Entities\LiquidityPool;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Treasury\Exceptions\LiquidityPoolNotFoundException;
use Nexus\Treasury\ValueObjects\LiquidityPoolBalance;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class LiquidityPoolService
{
    public function __construct(
        private LiquidityPoolQueryInterface $query,
        private LiquidityPoolPersistInterface $persist,
        private ?CashManagementProviderInterface $cashManagementProvider = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function create(
        string $tenantId,
        string $name,
        string $currency,
        array $bankAccountIds,
        ?string $description = null
    ): LiquidityPoolInterface {
        $now = new DateTimeImmutable();
        $zeroBalance = Money::of(0, $currency);

        $pool = new LiquidityPool(
            id: $this->generateId(),
            tenantId: $tenantId,
            name: $name,
            description: $description,
            currency: $currency,
            totalBalance: $zeroBalance,
            availableBalance: $zeroBalance,
            reservedBalance: $zeroBalance,
            status: TreasuryStatus::PENDING,
            bankAccountIds: $bankAccountIds,
            createdAt: $now,
            updatedAt: $now
        );

        $this->persist->save($pool);

        $this->logger->info('Liquidity pool created', [
            'pool_id' => $pool->getId(),
            'tenant_id' => $tenantId,
            'name' => $name,
        ]);

        return $pool;
    }

    public function activate(string $poolId): LiquidityPoolInterface
    {
        $pool = $this->query->findOrFail($poolId);

        $activated = $this->reconstructWithStatus($pool, TreasuryStatus::ACTIVE);
        $this->persist->save($activated);

        $this->logger->info('Liquidity pool activated', ['pool_id' => $poolId]);

        return $activated;
    }

    public function deactivate(string $poolId): LiquidityPoolInterface
    {
        $pool = $this->query->findOrFail($poolId);

        $deactivated = $this->reconstructWithStatus($pool, TreasuryStatus::INACTIVE);
        $this->persist->save($deactivated);

        $this->logger->info('Liquidity pool deactivated', ['pool_id' => $poolId]);

        return $deactivated;
    }

    public function get(string $poolId): LiquidityPoolInterface
    {
        return $this->query->findOrFail($poolId);
    }

    public function getByTenant(string $tenantId): array
    {
        return $this->query->findByTenantId($tenantId);
    }

    public function getActive(string $tenantId): array
    {
        return $this->query->findActiveByTenantId($tenantId);
    }

    public function calculateBalance(string $poolId): LiquidityPoolBalance
    {
        $pool = $this->query->findOrFail($poolId);

        if ($this->cashManagementProvider === null) {
            return new LiquidityPoolBalance(
                poolId: $poolId,
                totalBalance: $pool->getTotalBalance(),
                availableBalance: $pool->getAvailableBalance(),
                reservedBalance: $pool->getReservedBalance(),
                calculatedAt: new DateTimeImmutable()
            );
        }

        $totalBalance = Money::of(0, $pool->getCurrency());
        $availableBalance = Money::of(0, $pool->getCurrency());

        foreach ($pool->getBankAccountIds() as $bankAccountId) {
            $balance = $this->cashManagementProvider->getCurrentBalance($bankAccountId);
            $currency = $this->cashManagementProvider->getCurrency($bankAccountId);

            if ($currency === $pool->getCurrency()) {
                $totalBalance = $totalBalance->add(Money::of($balance, $currency));
                $availableBalance = $availableBalance->add(Money::of($balance, $currency));
            }
        }

        $reservedBalance = $pool->getReservedBalance();
        $availableBalance = $availableBalance->subtract($reservedBalance);

        if ($availableBalance->getAmount() < 0) {
            $availableBalance = Money::of(0, $pool->getCurrency());
        }

        return new LiquidityPoolBalance(
            poolId: $poolId,
            totalBalance: $totalBalance,
            availableBalance: $availableBalance,
            reservedBalance: $reservedBalance,
            calculatedAt: new DateTimeImmutable()
        );
    }

    public function refreshBalances(string $poolId): LiquidityPoolInterface
    {
        $balance = $this->calculateBalance($poolId);
        $pool = $this->query->findOrFail($poolId);

        $updated = new LiquidityPool(
            id: $pool->getId(),
            tenantId: $pool->getTenantId(),
            name: $pool->getName(),
            description: $pool->getDescription(),
            currency: $pool->getCurrency(),
            totalBalance: $balance->totalBalance,
            availableBalance: $balance->availableBalance,
            reservedBalance: $balance->reservedBalance,
            status: $pool->getStatus(),
            bankAccountIds: $pool->getBankAccountIds(),
            createdAt: $pool->getCreatedAt(),
            updatedAt: new DateTimeImmutable()
        );

        $this->persist->save($updated);

        $this->logger->info('Liquidity pool balances refreshed', [
            'pool_id' => $poolId,
            'total_balance' => $balance->totalBalance->format(),
        ]);

        return $updated;
    }

    public function reserveFunds(string $poolId, Money $amount): LiquidityPoolInterface
    {
        $pool = $this->query->findOrFail($poolId);

        if (!$pool->hasSufficientLiquidity($amount)) {
            throw LiquidityPoolNotFoundException::forId($poolId);
        }

        $newReserved = $pool->getReservedBalance()->add($amount);
        $newAvailable = $pool->getAvailableBalance()->subtract($amount);

        $updated = new LiquidityPool(
            id: $pool->getId(),
            tenantId: $pool->getTenantId(),
            name: $pool->getName(),
            description: $pool->getDescription(),
            currency: $pool->getCurrency(),
            totalBalance: $pool->getTotalBalance(),
            availableBalance: $newAvailable,
            reservedBalance: $newReserved,
            status: $pool->getStatus(),
            bankAccountIds: $pool->getBankAccountIds(),
            createdAt: $pool->getCreatedAt(),
            updatedAt: new DateTimeImmutable()
        );

        $this->persist->save($updated);

        $this->logger->info('Funds reserved in liquidity pool', [
            'pool_id' => $poolId,
            'amount' => $amount->format(),
        ]);

        return $updated;
    }

    public function releaseFunds(string $poolId, Money $amount): LiquidityPoolInterface
    {
        $pool = $this->query->findOrFail($poolId);

        $releaseAmount = $amount;
        if ($amount->greaterThan($pool->getReservedBalance())) {
            $releaseAmount = $pool->getReservedBalance();
        }

        $newReserved = $pool->getReservedBalance()->subtract($releaseAmount);
        $newAvailable = $pool->getAvailableBalance()->add($releaseAmount);

        $updated = new LiquidityPool(
            id: $pool->getId(),
            tenantId: $pool->getTenantId(),
            name: $pool->getName(),
            description: $pool->getDescription(),
            currency: $pool->getCurrency(),
            totalBalance: $pool->getTotalBalance(),
            availableBalance: $newAvailable,
            reservedBalance: $newReserved,
            status: $pool->getStatus(),
            bankAccountIds: $pool->getBankAccountIds(),
            createdAt: $pool->getCreatedAt(),
            updatedAt: new DateTimeImmutable()
        );

        $this->persist->save($updated);

        $this->logger->info('Funds released from liquidity pool', [
            'pool_id' => $poolId,
            'amount' => $releaseAmount->format(),
        ]);

        return $updated;
    }

    public function addBankAccount(string $poolId, string $bankAccountId): LiquidityPoolInterface
    {
        $pool = $this->query->findOrFail($poolId);

        $bankAccountIds = $pool->getBankAccountIds();
        if (!in_array($bankAccountId, $bankAccountIds, true)) {
            $bankAccountIds[] = $bankAccountId;
        }

        $updated = new LiquidityPool(
            id: $pool->getId(),
            tenantId: $pool->getTenantId(),
            name: $pool->getName(),
            description: $pool->getDescription(),
            currency: $pool->getCurrency(),
            totalBalance: $pool->getTotalBalance(),
            availableBalance: $pool->getAvailableBalance(),
            reservedBalance: $pool->getReservedBalance(),
            status: $pool->getStatus(),
            bankAccountIds: $bankAccountIds,
            createdAt: $pool->getCreatedAt(),
            updatedAt: new DateTimeImmutable()
        );

        $this->persist->save($updated);

        return $updated;
    }

    public function removeBankAccount(string $poolId, string $bankAccountId): LiquidityPoolInterface
    {
        $pool = $this->query->findOrFail($poolId);

        $bankAccountIds = array_filter(
            $pool->getBankAccountIds(),
            fn($id) => $id !== $bankAccountId
        );

        $updated = new LiquidityPool(
            id: $pool->getId(),
            tenantId: $pool->getTenantId(),
            name: $pool->getName(),
            description: $pool->getDescription(),
            currency: $pool->getCurrency(),
            totalBalance: $pool->getTotalBalance(),
            availableBalance: $pool->getAvailableBalance(),
            reservedBalance: $pool->getReservedBalance(),
            status: $pool->getStatus(),
            bankAccountIds: array_values($bankAccountIds),
            createdAt: $pool->getCreatedAt(),
            updatedAt: new DateTimeImmutable()
        );

        $this->persist->save($updated);

        return $updated;
    }

    public function delete(string $poolId): void
    {
        $pool = $this->query->find($poolId);

        if ($pool === null) {
            throw LiquidityPoolNotFoundException::forId($poolId);
        }

        $this->persist->delete($poolId);

        $this->logger->info('Liquidity pool deleted', ['pool_id' => $poolId]);
    }

    private function reconstructWithStatus(LiquidityPoolInterface $pool, TreasuryStatus $status): LiquidityPool
    {
        return new LiquidityPool(
            id: $pool->getId(),
            tenantId: $pool->getTenantId(),
            name: $pool->getName(),
            description: $pool->getDescription(),
            currency: $pool->getCurrency(),
            totalBalance: $pool->getTotalBalance(),
            availableBalance: $pool->getAvailableBalance(),
            reservedBalance: $pool->getReservedBalance(),
            status: $status,
            bankAccountIds: $pool->getBankAccountIds(),
            createdAt: $pool->getCreatedAt(),
            updatedAt: new DateTimeImmutable()
        );
    }

    private function generateId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'TRE-LIQ-' . $uuid;
    }
}
