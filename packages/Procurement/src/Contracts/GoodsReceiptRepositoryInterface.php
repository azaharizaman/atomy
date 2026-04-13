<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Goods receipt note repository interface.
 *
 * Provides methods for both internal procurement operations
 * and external 3-way matching requirements from Nexus\Payable.
 */
interface GoodsReceiptRepositoryInterface extends GoodsReceiptQueryInterface, GoodsReceiptPersistInterface
{
}
