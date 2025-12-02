<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DataProviders;

use Nexus\AccountConsolidation\Contracts\ConsolidationDataProviderInterface;
use Nexus\AccountConsolidation\ValueObjects\ConsolidationEntity;
use Nexus\AccountConsolidation\ValueObjects\OwnershipStructure;
use Nexus\AccountConsolidation\ValueObjects\IntercompanyBalance;

/**
 * Data provider for consolidation operations.
 */
final readonly class ConsolidationDataProvider implements ConsolidationDataProviderInterface
{
    public function __construct(
        // Injected dependencies from consuming application
    ) {}

    /**
     * @param array<string> $entityIds
     * @return array<ConsolidationEntity>
     */
    public function getConsolidationEntities(string $tenantId, array $entityIds): array
    {
        // Implementation provided by consuming application
        return [];
    }

    public function getOwnershipStructure(string $tenantId, string $parentEntityId): OwnershipStructure
    {
        // Implementation provided by consuming application
        return new OwnershipStructure(
            parentEntityId: $parentEntityId,
            subsidiaries: [],
            ownershipPercentages: [],
            effectiveDate: new \DateTimeImmutable(),
        );
    }

    /**
     * @return array<IntercompanyBalance>
     */
    public function getIntercompanyBalances(string $tenantId, string $periodId): array
    {
        // Implementation provided by consuming application
        return [];
    }

    /**
     * @return array<string, float>
     */
    public function getExchangeRates(string $reportingCurrency, \DateTimeImmutable $date): array
    {
        // Implementation provided by consuming application
        return [];
    }

    /**
     * @return array<string, array<string, float>>
     */
    public function getEntityTrialBalances(string $tenantId, array $entityIds, string $periodId): array
    {
        // Implementation provided by consuming application
        return [];
    }
}
