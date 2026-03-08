<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Purchase order repository interface.
 *
 * This interface provides methods for both internal procurement operations
 * and external 3-way matching requirements from Nexus\Payable.
 */
interface PurchaseOrderRepositoryInterface extends PurchaseOrderQueryInterface, PurchaseOrderPersistInterface
{
}
