<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Contract for spend query operations.
 * Consumer application must implement this interface.
 */
interface SpendQueryInterface
{
    public function getCategorySpendYtd(string $tenantId, string $categoryId, int $year): Money;
    public function getVendorSpendYtd(string $tenantId, string $vendorId, int $year): Money;
    public function getDepartmentSpendYtd(string $tenantId, string $departmentId, int $year): Money;
}
