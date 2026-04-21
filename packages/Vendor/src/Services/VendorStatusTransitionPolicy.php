<?php

declare(strict_types=1);

namespace Nexus\Vendor\Services;

use Nexus\Vendor\Contracts\VendorStatusTransitionPolicyInterface;
use Nexus\Vendor\Enums\VendorStatus;
use Nexus\Vendor\Exceptions\InvalidVendorStatusTransition;

final readonly class VendorStatusTransitionPolicy implements VendorStatusTransitionPolicyInterface
{
    public function assertCanTransition(VendorStatus $from, VendorStatus $to): void
    {
        if (in_array($to, match ($from) {
            VendorStatus::Draft => [VendorStatus::UnderReview],
            VendorStatus::UnderReview => [VendorStatus::Approved],
            VendorStatus::Approved => [
                VendorStatus::Restricted,
                VendorStatus::Suspended,
                VendorStatus::Archived,
            ],
            VendorStatus::Restricted => [VendorStatus::Approved],
            VendorStatus::Suspended => [VendorStatus::Approved],
            VendorStatus::Archived => [],
        }, true)) {
            return;
        }

        throw new InvalidVendorStatusTransition($from, $to);
    }
}
