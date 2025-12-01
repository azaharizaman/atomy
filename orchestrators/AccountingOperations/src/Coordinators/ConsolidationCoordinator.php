<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Coordinators;

use Nexus\AccountingOperations\Contracts\AccountingCoordinatorInterface;
use Nexus\AccountingOperations\DTOs\ConsolidationRequest;
use Nexus\AccountingOperations\DataProviders\ConsolidationDataProvider;
use Nexus\AccountConsolidation\Services\ConsolidationCalculator;
use Nexus\AccountConsolidation\Services\IntercompanyEliminator;
use Nexus\AccountConsolidation\Services\CurrencyTranslator;
use Nexus\AccountConsolidation\ValueObjects\ConsolidationResult;

/**
 * Coordinator for consolidation operations.
 */
final readonly class ConsolidationCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private ConsolidationDataProvider $dataProvider,
        private ConsolidationCalculator $calculator,
        private IntercompanyEliminator $eliminator,
        private CurrencyTranslator $translator,
    ) {}

    public function getName(): string
    {
        return 'consolidation';
    }

    public function hasRequiredData(string $tenantId, string $periodId): bool
    {
        return true;
    }

    /**
     * @return array<string>
     */
    public function getSupportedOperations(): array
    {
        return ['consolidate', 'eliminate', 'translate'];
    }

    public function consolidate(ConsolidationRequest $request): ConsolidationResult
    {
        // 1. Get entity data
        $entities = $this->dataProvider->getConsolidationEntities(
            $request->tenantId,
            $request->entityIds
        );

        // 2. Get ownership structure
        $ownership = $this->dataProvider->getOwnershipStructure(
            $request->tenantId,
            $request->parentEntityId
        );

        // 3. Get trial balances
        $trialBalances = $this->dataProvider->getEntityTrialBalances(
            $request->tenantId,
            $request->entityIds,
            $request->periodId
        );

        // 4. Translate currencies
        $exchangeRates = $this->dataProvider->getExchangeRates(
            $request->reportingCurrency,
            new \DateTimeImmutable()
        );

        // 5. Calculate consolidation
        $result = $this->calculator->calculate(
            entities: $entities,
            ownership: $ownership,
            trialBalances: $trialBalances,
            method: $request->method
        );

        // 6. Eliminate intercompany if requested
        if ($request->eliminateIntercompany) {
            $intercompanyBalances = $this->dataProvider->getIntercompanyBalances(
                $request->tenantId,
                $request->periodId
            );
            // Apply eliminations
        }

        return $result;
    }
}
