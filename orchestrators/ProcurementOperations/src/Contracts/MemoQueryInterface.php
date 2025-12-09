<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\Enums\MemoStatus;
use Nexus\ProcurementOperations\Enums\MemoType;

/**
 * Query interface for credit/debit memos.
 */
interface MemoQueryInterface
{
    /**
     * Find memo by ID.
     */
    public function findById(string $id): ?MemoInterface;

    /**
     * Find memo by number.
     */
    public function findByNumber(string $number): ?MemoInterface;

    /**
     * Find memos by vendor.
     *
     * @return array<MemoInterface>
     */
    public function findByVendor(string $vendorId): array;

    /**
     * Find unapplied memos by vendor.
     *
     * @return array<MemoInterface>
     */
    public function findUnappliedByVendor(string $vendorId): array;

    /**
     * Find memos by type and status.
     *
     * @return array<MemoInterface>
     */
    public function findByTypeAndStatus(MemoType $type, MemoStatus $status): array;

    /**
     * Find memos for an invoice.
     *
     * @return array<MemoInterface>
     */
    public function findByInvoice(string $invoiceId): array;
}
