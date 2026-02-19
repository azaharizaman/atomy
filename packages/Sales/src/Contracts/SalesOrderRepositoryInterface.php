<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

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
     * @param array $data Order data including lines
     * @return SalesOrderInterface
     */
    public function create(array $data): SalesOrderInterface;

    public function delete(string $id): void;

    public function exists(string $tenantId, string $orderNumber): bool;
}
