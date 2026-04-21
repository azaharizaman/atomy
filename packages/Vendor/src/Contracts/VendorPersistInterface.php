<?php

declare(strict_types=1);

namespace Nexus\Vendor\Contracts;

use Nexus\Vendor\Enums\VendorStatus;
use Nexus\Vendor\ValueObjects\VendorApprovalRecord;

interface VendorPersistInterface
{
    public function save(string $tenantId, VendorInterface $vendor): VendorInterface;

    public function updateStatus(
        string $tenantId,
        string $vendorId,
        VendorStatus $status,
        ?VendorApprovalRecord $approvalRecord = null,
    ): VendorInterface;
}
