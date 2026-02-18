<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Workflows\WarehouseBalancing;

final readonly class BalancingResult
{
    public function __construct(
        public string $tenantId,
        public ?string $regionId,
        public int $warehousesAnalyzed,
        public int $imbalancesFound,
        public array $transfersCreated,
        public ?\DateTimeImmutable $analyzedAt = null
    ) {
        $this->analyzedAt = $analyzedAt ?? new \DateTimeImmutable();
    }

    public function hasTransfers(): bool
    {
        return !empty($this->transfersCreated);
    }
}
