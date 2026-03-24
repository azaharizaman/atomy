<?php
declare(strict_types=1);

namespace Nexus\Vendor\Contracts;

interface VendorQueryInterface {
    public function findById(string $tenantId, string $id): ?VendorProfileInterface;
}