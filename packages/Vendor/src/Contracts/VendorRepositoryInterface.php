<?php
declare(strict_types=1);

namespace Nexus\Vendor\Contracts;

interface VendorRepositoryInterface {
    public function findById(string $tenantId, string $id): ?VendorProfileInterface;
    public function save(string $tenantId, VendorProfileInterface $vendor): void;
}
