<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Contracts;

interface QuotationQueryInterface
{
    /**
     * @return array<QuotationInterface>
     */
    public function findBySourcingEvent(string $tenantId, string $sourcingEventId): array;

    public function findById(string $tenantId, string $id): ?QuotationInterface;
}
