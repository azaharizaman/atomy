<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Workflows\WarehouseBalancing;

final readonly class BalancingResult
{
    public string $tenantId;
    public ?string $regionId;
    public int $warehousesAnalyzed;
    public int $imbalancesFound;
    public array $transfersCreated;
    public \DateTimeImmutable $analyzedAt;

    public function __construct(
        string $tenantId,
        ?string $regionId,
        int $warehousesAnalyzed,
        int $imbalancesFound,
        array $transfersCreated,
        ?\DateTimeImmutable $analyzedAt = null
    ) {
        $this->tenantId = $tenantId;
        $this->regionId = $regionId;
        $this->warehousesAnalyzed = $warehousesAnalyzed;
        $this->imbalancesFound = $imbalancesFound;
        $this->transfersCreated = $transfersCreated;
        $this->analyzedAt = $analyzedAt ?? new \DateTimeImmutable();
    }

    public function hasTransfers(): bool
    {
        return !empty($this->transfersCreated);
    }
}
