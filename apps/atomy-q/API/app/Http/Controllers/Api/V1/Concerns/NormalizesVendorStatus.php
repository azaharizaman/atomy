<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Concerns;

use Nexus\Vendor\Enums\VendorStatus;

trait NormalizesVendorStatus
{
    protected function normalizeVendorStatus(string $status): VendorStatus
    {
        $normalized = trim(strtolower($status));

        if ($normalized === 'active') {
            $normalized = VendorStatus::Approved->value;
        } elseif ($normalized === 'inactive') {
            $normalized = VendorStatus::Suspended->value;
        }

        return VendorStatus::tryFrom($normalized)
            ?? throw new \InvalidArgumentException(sprintf('Unreadable vendor status: %s', $status));
    }
}
