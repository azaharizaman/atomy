<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

use Nexus\Uom\Contracts\QuantityInterface;

/**
 * Repository contract for sales quotations.
 */
interface QuotationRepositoryInterface
{
    /**
     * @throws \Nexus\Sales\Exceptions\QuotationNotFoundException
     */
    public function findById(string $id): QuotationInterface;

    /**
     * @throws \Nexus\Sales\Exceptions\QuotationNotFoundException
     */
    public function findByNumber(string $tenantId, string $quoteNumber): QuotationInterface;

    /**
     * @return QuotationInterface[]
     */
    public function findByCustomer(string $tenantId, string $customerId): array;

    /**
     * @return QuotationInterface[]
     */
    public function findByStatus(string $tenantId, string $status): array;

    /**
     * @throws \Nexus\Sales\Exceptions\DuplicateQuoteNumberException
     */
    public function save(QuotationInterface $quotation): void;

    /**
     * Mark quotation as converted to order.
     *
     * @param string $quotationId
     * @param string $orderId
     * @throws \Nexus\Sales\Exceptions\QuotationNotFoundException
     */
    public function markAsConvertedToOrder(string $quotationId, string $orderId): void;

    public function delete(string $id): void;

    public function exists(string $tenantId, string $quoteNumber): bool;
}
