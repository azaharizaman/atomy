<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\ReturnToVendorRequest;
use Nexus\ProcurementOperations\DTOs\ReturnToVendorResult;

/**
 * Contract for return to vendor (RTV) workflow coordination.
 */
interface ReturnToVendorCoordinatorInterface
{
    /**
     * Initiate a return to vendor.
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\GoodsReceiptException
     */
    public function initiateReturn(ReturnToVendorRequest $request): ReturnToVendorResult;

    /**
     * Confirm a return has been shipped back to the vendor.
     */
    public function confirmShipment(
        string $tenantId,
        string $returnId,
        string $shippedBy,
        ?string $trackingNumber = null
    ): ReturnToVendorResult;

    /**
     * Record a credit memo received for a return.
     */
    public function recordCreditMemo(
        string $tenantId,
        string $returnId,
        string $creditMemoId
    ): ReturnToVendorResult;
}
