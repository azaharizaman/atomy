<?php

declare(strict_types=1);

namespace Nexus\Vendor\Tests\Unit\Contracts;

use DateTimeImmutable;
use Nexus\Vendor\Contracts\VendorInterface;
use Nexus\Vendor\Enums\VendorStatus;
use Nexus\Vendor\ValueObjects\RegistrationNumber;
use Nexus\Vendor\ValueObjects\VendorApprovalRecord;
use Nexus\Vendor\ValueObjects\VendorDisplayName;
use Nexus\Vendor\ValueObjects\VendorId;
use Nexus\Vendor\ValueObjects\VendorLegalName;
use PHPUnit\Framework\TestCase;

final class VendorInterfaceTest extends TestCase
{
    public function test_contract_includes_required_contact_fields(): void
    {
        $vendor = new class implements VendorInterface {
            public function getId(): VendorId
            {
                return new VendorId('01J8Z8V7K2M9C4A5B6D7E8F9G0');
            }

            public function getLegalName(): VendorLegalName
            {
                return new VendorLegalName('Acme Holdings Sdn Bhd');
            }

            public function getDisplayName(): VendorDisplayName
            {
                return new VendorDisplayName('Acme');
            }

            public function getRegistrationNumber(): RegistrationNumber
            {
                return new RegistrationNumber('201901234567');
            }

            public function getCountryOfRegistration(): string
            {
                return 'MY';
            }

            public function getPrimaryContactName(): string
            {
                return 'Amina Zain';
            }

            public function getPrimaryContactEmail(): string
            {
                return 'amina@example.com';
            }

            public function getPrimaryContactPhone(): ?string
            {
                return '+60123456789';
            }

            public function getStatus(): VendorStatus
            {
                return VendorStatus::Draft;
            }

            public function getApprovalRecord(): ?VendorApprovalRecord
            {
                return new VendorApprovalRecord('user-123', new DateTimeImmutable('2026-04-21T08:30:00+00:00'));
            }
        };

        self::assertSame('Amina Zain', $vendor->getPrimaryContactName());
        self::assertSame('amina@example.com', $vendor->getPrimaryContactEmail());
        self::assertSame('+60123456789', $vendor->getPrimaryContactPhone());
    }
}
