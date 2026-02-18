<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface ProcurementManagerInterface
{
    public function createDirectPO(string $tenantId, string $creatorId, array $poData): PurchaseOrderInterface;

    public function createRequisition(string $tenantId, string $requesterId, array $requisitionData): RequisitionInterface;
}
