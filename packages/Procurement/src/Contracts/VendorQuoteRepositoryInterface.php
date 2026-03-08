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

    /**
     * Acquire a lock on the quote for a comparison run.
     *
     * @param string $quoteId Quote ULID
     * @param string $comparisonRunId The run that holds the lock
     * @param string $lockedBy User ULID who initiated the lock
     * @return VendorQuoteInterface The updated quote
     */
    public function lock(string $quoteId, string $comparisonRunId, string $lockedBy): VendorQuoteInterface;

    /**
     * Release a lock on the quote.
     *
     * @param string $quoteId Quote ULID
     * @param string $comparisonRunId The run that held the lock (must match)
     * @return VendorQuoteInterface The updated quote
     */
    public function unlock(string $quoteId, string $comparisonRunId): VendorQuoteInterface;

    /**
     * Find all quotes locked by a specific comparison run.
     *
     * @param string $comparisonRunId
     * @return array<VendorQuoteInterface>
     */
    public function findLockedByRun(string $comparisonRunId): array;
}
