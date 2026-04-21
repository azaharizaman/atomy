<?php

declare(strict_types=1);

namespace Nexus\Vendor\Contracts;

use Nexus\Vendor\Enums\VendorStatus;
use Nexus\Vendor\ValueObjects\RegistrationNumber;
use Nexus\Vendor\ValueObjects\VendorApprovalRecord;
use Nexus\Vendor\ValueObjects\VendorDisplayName;
use Nexus\Vendor\ValueObjects\VendorId;
use Nexus\Vendor\ValueObjects\VendorLegalName;

interface VendorInterface
{
    public function getId(): VendorId;

    public function getLegalName(): VendorLegalName;

    public function getDisplayName(): VendorDisplayName;

    public function getRegistrationNumber(): RegistrationNumber;

    public function getCountryOfRegistration(): string;

    public function getPrimaryContactName(): string;

    public function getPrimaryContactEmail(): string;

    public function getPrimaryContactPhone(): ?string;

    public function getStatus(): VendorStatus;

    public function getApprovalRecord(): ?VendorApprovalRecord;
}
