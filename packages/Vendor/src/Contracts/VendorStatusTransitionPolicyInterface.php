<?php

declare(strict_types=1);

namespace Nexus\Vendor\Contracts;

use Nexus\Vendor\Enums\VendorStatus;

interface VendorStatusTransitionPolicyInterface
{
    public function assertCanTransition(VendorStatus $from, VendorStatus $to): void;
}
