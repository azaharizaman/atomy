<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface QuotationProviderInterface
{
    public function findById(string $tenantId, string $quotationId): ?QuotationInterface;

    public function findByNumber(string $tenantId, string $quotationNumber): ?QuotationInterface;

    public function markAsConverted(string $tenantId, string $quotationId, string $orderId): void;

    public function markAsExpired(string $tenantId, string $quotationId): void;
}
