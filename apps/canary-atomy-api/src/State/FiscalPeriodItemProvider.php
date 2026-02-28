<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\FiscalPeriod as FiscalPeriodResource;
use App\Service\TenantContext;
use Nexus\SettingsManagement\Contracts\FiscalPeriodCoordinatorInterface;

/**
 * Item provider for FiscalPeriod resource.
 */
final class FiscalPeriodItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly FiscalPeriodCoordinatorInterface $periodCoordinator,
        private readonly TenantContext $tenantContext
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?FiscalPeriodResource
    {
        $id = $uriVariables['id'] ?? null;
        if (!$id) {
            return null;
        }

        $tenantId = $this->tenantContext->getCurrentTenantId();
        if (!$tenantId) {
            return null;
        }

        $periods = $this->periodCoordinator->getAllPeriods($tenantId);
        $period = null;
        foreach ($periods as $p) {
            if (($p['id'] ?? '') === $id) {
                $period = $p;
                break;
            }
        }

        if (!$period) {
            return null;
        }

        $currentPeriod = $this->periodCoordinator->getCurrentPeriod($tenantId);

        $resource = new FiscalPeriodResource();
        $resource->id = $period['id'] ?? '';
        $resource->name = $period['name'] ?? '';
        $resource->startDate = $period['start_date'] ?? '';
        $resource->endDate = $period['end_date'] ?? '';
        $resource->status = $period['status'] ?? '';
        $resource->isCurrent = ($currentPeriod && $currentPeriod['id'] === $resource->id);

        return $resource;
    }
}
