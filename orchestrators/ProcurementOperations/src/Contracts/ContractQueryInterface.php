<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Contract for contract query operations.
 * Consumer application must implement this interface.
 */
interface ContractQueryInterface
{
    /**
     * @return array{id: string, remaining: Money}|null
     */
    public function getActiveContract(string $tenantId, string $categoryId, ?string $vendorId): ?array;
}
