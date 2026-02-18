<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface SalesOrderProviderInterface
{
    public function findById(string $tenantId, string $orderId): ?SalesOrderInterface;

    public function findByNumber(string $tenantId, string $orderNumber): ?SalesOrderInterface;

    public function findByStatus(string $tenantId, string $status): array;

    public function findByCustomer(string $tenantId, string $customerId): array;

    public function create(string $tenantId, array $data): SalesOrderInterface;

    public function updateStatus(string $tenantId, string $orderId, string $status): void;

    public function confirm(string $tenantId, string $orderId, string $confirmedBy): void;

    public function cancel(string $tenantId, string $orderId, string $reason, ?string $cancelledBy = null): void;
}
