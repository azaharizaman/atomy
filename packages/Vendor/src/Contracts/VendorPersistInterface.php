<?php
declare(strict_types=1);

namespace Nexus\Vendor\Contracts;

interface VendorPersistInterface {
    public function save(string $tenantId, VendorProfileInterface $vendor): void;
}