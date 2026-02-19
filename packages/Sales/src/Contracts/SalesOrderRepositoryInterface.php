<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

use Nexus\Sales\ValueObjects\SalesOrderData;

/**
 * Repository contract for sales orders.
 */
interface SalesOrderRepositoryInterface
{
    /**
     * @throws \Nexus\Sales\Exceptions\SalesOrderNotFoundException
     */
    public function findById(string $id): SalesOrderInterface;

    /**
     * @throws \Nexus\Sales\Exceptions\SalesOrderNotFoundException
     */
    public function findByNumber(string $tenantId, string $orderNumber): SalesOrderInterface;

    /**
     * @return SalesOrderInterface[]
     */
    public function findByCustomer(string $tenantId, string $customerId): array;

    /**
     * @return SalesOrderInterface[]
     */
    public function findByStatus(string $tenantId, string $status): array;

    /**
     * @throws \Nexus\Sales\Exceptions\DuplicateOrderNumberException
     */
    public function save(SalesOrderInterface $order): void;

    /**
     * Create a new sales order from data.
     *
     * @param SalesOrderData $data
     * @return SalesOrderInterface
     * @throws \Nexus\Sales\Exceptions\DuplicateOrderNumberException
     */
    public function create(SalesOrderData $data): SalesOrderInterface;

    public function delete(string $id): void;

    public function exists(string $tenantId, string $orderNumber): bool;
}
