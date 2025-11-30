<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Vendor quote repository interface.
 */
interface VendorQuoteRepositoryInterface
{
    /**
     * Find quote by ID.
     *
     * @param string $id Quote ULID
     * @return VendorQuoteInterface|null
     */
    public function findById(string $id): ?VendorQuoteInterface;

    /**
     * Find all quotes for an RFQ.
     *
     * @param string $rfqNumber RFQ number
     * @return array<VendorQuoteInterface>
     */
    public function findByRfqNumber(string $rfqNumber): array;

    /**
     * Save quote.
     *
     * @param VendorQuoteInterface $quote
     * @return void
     */
    public function save(VendorQuoteInterface $quote): void;
}
