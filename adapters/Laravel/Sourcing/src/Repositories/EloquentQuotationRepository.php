<?php

declare(strict_types=1);

namespace Nexus\Laravel\Sourcing\Repositories;

use Nexus\Laravel\Sourcing\Models\EloquentQuotation;
use Nexus\Sourcing\Contracts\QuotationInterface;
use Nexus\Sourcing\Contracts\QuotationRepositoryInterface;

final readonly class EloquentQuotationRepository implements QuotationRepositoryInterface
{
    /**
     * @return array<QuotationInterface>
     */
    public function findBySourcingEvent(string $tenantId, string $rfqId): array
    {
        return EloquentQuotation::query()
            ->where('tenant_id', $tenantId)
            ->where('sourcing_event_id', $rfqId)
            ->get()
            ->all();
    }

    public function findById(string $tenantId, string $id): ?QuotationInterface
    {
        /** @var EloquentQuotation|null $row */
        $row = EloquentQuotation::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($id)
            ->first();

        return $row;
    }
}
