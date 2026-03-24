<?php
declare(strict_types=1);

namespace Nexus\Vendor\Contracts;

use Nexus\Vendor\ValueObjects\VendorStatus;

interface VendorProfileInterface {
    public function getId(): string;
    public function getPartyId(): string;
    public function getStatus(): VendorStatus;
}
