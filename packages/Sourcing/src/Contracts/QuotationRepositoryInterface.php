<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Contracts;

interface QuotationRepositoryInterface
{
    /**
     * @return array<QuotationInterface>
     */
    public function findBySourcingEvent(string $tenantId, string $rfqId): array;

    public function findById(string $tenantId, string $id): ?QuotationInterface;
}
