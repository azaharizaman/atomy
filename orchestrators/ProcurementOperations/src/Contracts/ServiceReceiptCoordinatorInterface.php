<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\ServiceReceiptRequest;
use Nexus\ProcurementOperations\DTOs\ServiceReceiptResult;

/**
 * Contract for service receipt workflow coordination.
 */
interface ServiceReceiptCoordinatorInterface
{
    /**
     * Record a service receipt (acceptance of service deliverables).
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\PurchaseOrderException
     */
    public function record(ServiceReceiptRequest $request): ServiceReceiptResult;
}
