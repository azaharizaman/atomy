<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

use DateTimeImmutable;

/**
 * Repository contract for price lists.
 */
interface PriceListRepositoryInterface
{
    public function findById(string $id): PriceListInterface;

    /**
     * @return PriceListInterface[]
     */
    public function findByTenant(string $tenantId): array;

    /**
     * @return PriceListInterface[]
     */
    public function findActiveByCustomer(string $tenantId, string $customerId, DateTimeImmutable $asOf): array;

    /**
     * @return PriceListInterface[]
     */
    public function findDefaultActive(string $tenantId, DateTimeImmutable $asOf): array;

    public function save(PriceListInterface $priceList): void;

    public function delete(string $id): void;
}
