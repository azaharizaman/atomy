<?php

declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Mappers;

use Nexus\Adapter\Laravel\Vendor\Models\EloquentVendor;
use Nexus\Vendor\Contracts\VendorInterface;
use Nexus\Vendor\Enums\VendorStatus;
use Nexus\Vendor\ValueObjects\RegistrationNumber;
use Nexus\Vendor\ValueObjects\VendorApprovalRecord;
use Nexus\Vendor\ValueObjects\VendorDisplayName;
use Nexus\Vendor\ValueObjects\VendorId;
use Nexus\Vendor\ValueObjects\VendorLegalName;

final readonly class LaravelVendorMapper
{
    public static function fromModel(EloquentVendor $vendor): VendorInterface
    {
        $id = $vendor->getId();
        $legalName = $vendor->getLegalName();
        $displayName = $vendor->getDisplayName();
        $registrationNumber = $vendor->getRegistrationNumber();
        $countryOfRegistration = $vendor->getCountryOfRegistration();
        $primaryContactName = $vendor->getPrimaryContactName();
        $primaryContactEmail = $vendor->getPrimaryContactEmail();
        $primaryContactPhone = $vendor->getPrimaryContactPhone();
        $status = $vendor->getStatus();
        $approvalRecord = $vendor->getApprovalRecord();

        return new class(
            $id,
            $legalName,
            $displayName,
            $registrationNumber,
            $countryOfRegistration,
            $primaryContactName,
            $primaryContactEmail,
            $primaryContactPhone,
            $status,
            $approvalRecord,
        ) implements VendorInterface {
            public function __construct(
                private readonly VendorId $id,
                private readonly VendorLegalName $legalName,
                private readonly VendorDisplayName $displayName,
                private readonly RegistrationNumber $registrationNumber,
                private readonly string $countryOfRegistration,
                private readonly string $primaryContactName,
                private readonly string $primaryContactEmail,
                private readonly ?string $primaryContactPhone,
                private readonly VendorStatus $status,
                private readonly ?VendorApprovalRecord $approvalRecord,
            ) {
            }

            public function getId(): VendorId
            {
                return $this->id;
            }

            public function getLegalName(): VendorLegalName
            {
                return $this->legalName;
            }

            public function getDisplayName(): VendorDisplayName
            {
                return $this->displayName;
            }

            public function getRegistrationNumber(): RegistrationNumber
            {
                return $this->registrationNumber;
            }

            public function getCountryOfRegistration(): string
            {
                return $this->countryOfRegistration;
            }

            public function getPrimaryContactName(): string
            {
                return $this->primaryContactName;
            }

            public function getPrimaryContactEmail(): string
            {
                return $this->primaryContactEmail;
            }

            public function getPrimaryContactPhone(): ?string
            {
                return $this->primaryContactPhone;
            }

            public function getStatus(): VendorStatus
            {
                return $this->status;
            }

            public function getApprovalRecord(): ?VendorApprovalRecord
            {
                return $this->approvalRecord;
            }
        };
    }

    public static function toModel(VendorInterface $vendor): EloquentVendor
    {
        $model = $vendor instanceof EloquentVendor ? $vendor : new EloquentVendor();
        $vendorId = (string) $vendor->getId();
        $legalName = $vendor->getLegalName()->getValue();
        $displayName = $vendor->getDisplayName()->getValue();
        $registrationNumber = $vendor->getRegistrationNumber()->getValue();
        $countryOfRegistration = $vendor->getCountryOfRegistration();
        $primaryContactName = $vendor->getPrimaryContactName();
        $primaryContactEmail = $vendor->getPrimaryContactEmail();
        $primaryContactPhone = $vendor->getPrimaryContactPhone();
        $status = $vendor->getStatus()->value;
        $approvalRecord = $vendor->getApprovalRecord();

        $model->forceFill([
            'id' => $vendorId,
            'legal_name' => $legalName,
            'display_name' => $displayName,
            'registration_number' => $registrationNumber,
            'country_of_registration' => $countryOfRegistration,
            'status' => $status,
            'primary_contact_name' => $primaryContactName,
            'primary_contact_email' => $primaryContactEmail,
            'primary_contact_phone' => $primaryContactPhone,
            'approved_by_user_id' => $approvalRecord?->getApprovedByUserId(),
            'approved_at' => $approvalRecord?->getApprovedAt(),
            'approval_note' => $approvalRecord?->getApprovalNote(),
            'name' => $legalName,
            'trading_name' => $displayName,
            'country_code' => $countryOfRegistration,
            'email' => $primaryContactEmail,
            'phone' => $primaryContactPhone,
        ]);

        return $model;
    }
}
