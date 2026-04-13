<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Repository interface for vendor bill persistence operations.
 */
interface VendorBillRepositoryInterface extends VendorBillQueryInterface
{
    /**
     * Save bill (create or update).
     *
     * @param VendorBillInterface $bill Bill entity
     * @return VendorBillInterface
     */
    public function save(VendorBillInterface $bill): VendorBillInterface;

    /**
     * Delete bill.
     *
     * @param string $id Bill ULID
     * @return bool
     */
    public function delete(string $id): bool;
}
