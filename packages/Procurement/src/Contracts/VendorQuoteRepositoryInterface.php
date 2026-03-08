<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Vendor quote repository interface.
 */
interface VendorQuoteRepositoryInterface
{
    /**
     * Find quote by ID and tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param string $id Quote ULID
     * @return VendorQuoteInterface|null
     */
    public function findById(string $tenantId, string $id): ?VendorQuoteInterface;

    /**
     * Find all quotes for an RFQ.
     *
     * @param string $tenantId Tenant ULID
     * @param string $rfqNumber RFQ number
     * @return array<VendorQuoteInterface>
     */
    public function findByRfqNumber(string $tenantId, string $rfqNumber): array;

    /**
     * Find quotes for a requisition.
     *
     * @param string $tenantId Tenant ULID
     * @param string $requisitionId Requisition ULID
     * @return array<VendorQuoteInterface>
     */
    public function findByRequisitionId(string $tenantId, string $requisitionId): array;

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
     * @param string $tenantId Tenant ULID
     * @param string $requisitionId Requisition ULID
     * @param array<string, mixed> $data
     * @return VendorQuoteInterface
     */
    public function create(string $tenantId, string $requisitionId, array $data): VendorQuoteInterface;

    /**
     * Accept a vendor quote.
     *
     * @param string $tenantId Tenant ULID
     * @param string $quoteId Quote ULID
     * @param string $acceptorId User ULID
     * @return VendorQuoteInterface
     */
    public function accept(string $tenantId, string $quoteId, string $acceptorId): VendorQuoteInterface;

    /**
     * Reject a vendor quote.
     *
     * @param string $tenantId Tenant ULID
     * @param string $quoteId Quote ULID
     * @param string $reason Rejection reason
     * @return VendorQuoteInterface
     */
    public function reject(string $tenantId, string $quoteId, string $reason): VendorQuoteInterface;

    /**
     * Save quote.
     *
     * @param VendorQuoteInterface $quote
     * @return void
     */
    public function save(VendorQuoteInterface $quote): void;

    /**
     * Acquire a lock on the quote for a comparison run.
     *
     * @param string $tenantId Tenant ULID
     * @param string $quoteId Quote ULID
     * @param string $comparisonRunId The run that holds the lock
     * @param string $lockedBy User ULID who initiated the lock
     * @return VendorQuoteInterface The updated quote
     */
    public function lock(string $tenantId, string $quoteId, string $comparisonRunId, string $lockedBy): VendorQuoteInterface;

    /**
     * Release a lock on the quote.
     *
     * @param string $tenantId Tenant ULID
     * @param string $quoteId Quote ULID
     * @param string $comparisonRunId The run that held the lock (must match)
     * @return VendorQuoteInterface The updated quote
     */
    public function unlock(string $tenantId, string $quoteId, string $comparisonRunId): VendorQuoteInterface;

    /**
     * Find all quotes locked by a specific comparison run.
     *
     * @param string $tenantId Tenant ULID
     * @param string $comparisonRunId
     * @return array<VendorQuoteInterface>
     */
    public function findLockedByRun(string $tenantId, string $comparisonRunId): array;
}
