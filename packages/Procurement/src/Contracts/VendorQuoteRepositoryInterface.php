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
     * Find all quotes for a requisition.
     *
     * @param string $requisitionId Requisition ULID
     * @return array<VendorQuoteInterface>
     */
    public function findByRequisitionId(string $requisitionId): array;

    /**
     * Find quotes by vendor.
     *
     * @param string $tenantId Tenant ULID
     * @param string $vendorId Vendor ULID
     * @return array<VendorQuoteInterface>
     */
    public function findByVendorId(string $tenantId, string $vendorId): array;

    /**
     * Create a new vendor quote.
     *
     * @param string $tenantId
     * @param string $requisitionId
     * @param array<string, mixed> $data
     * @return VendorQuoteInterface
     */
    public function create(string $tenantId, string $requisitionId, array $data): VendorQuoteInterface;

    /**
     * Accept a quote.
     *
     * @param string $quoteId
     * @param string $acceptorId
     * @return VendorQuoteInterface
     */
    public function accept(string $quoteId, string $acceptorId): VendorQuoteInterface;

    /**
     * Reject a quote.
     *
     * @param string $quoteId
     * @param string $reason
     * @return VendorQuoteInterface
     */
    public function reject(string $quoteId, string $reason): VendorQuoteInterface;

    /**
     * Save quote.
     *
     * @param VendorQuoteInterface $quote
     * @return void
     */
    public function save(VendorQuoteInterface $quote): void;
}
