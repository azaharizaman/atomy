<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\FiscalPeriod as FiscalPeriodResource;
use App\Service\TenantContext;
use Nexus\SettingsManagement\Contracts\FiscalPeriodCoordinatorInterface;

/**
 * Collection provider for FiscalPeriod resource.
 */
final class FiscalPeriodCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly FiscalPeriodCoordinatorInterface $periodCoordinator,
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<FiscalPeriodResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if (!$tenantId) {
            return [];
        }

        $periods = $this->periodCoordinator->getAllPeriods($tenantId);
        $currentPeriod = $this->periodCoordinator->getCurrentPeriod($tenantId);

        foreach ($periods as $period) {
            $resource = new FiscalPeriodResource();
            $resource->id = $period['id'] ?? '';
            $resource->name = $period['name'] ?? '';
            $resource->startDate = $period['start_date'] ?? '';
            $resource->endDate = $period['end_date'] ?? '';
            $resource->status = $period['status'] ?? '';
            $resource->isCurrent = ($currentPeriod && $currentPeriod['id'] === $resource->id);

            yield $resource;
        }
    }
}
