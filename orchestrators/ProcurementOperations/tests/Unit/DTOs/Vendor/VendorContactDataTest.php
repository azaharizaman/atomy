<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Vendor;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorContactData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorContactData::class)]
final class VendorContactDataTest extends TestCase
{
    #[Test]
    public function it_creates_primary_contact(): void
    {
        $contact = VendorContactData::primary(
            name: 'John Doe',
            email: 'john@acme.com',
            phone: '+60123456789',
            title: 'Managing Director',
        );

        $this->assertSame('John Doe', $contact->name);
        $this->assertSame('john@acme.com', $contact->email);
        $this->assertSame('+60123456789', $contact->phone);
        $this->assertSame('Managing Director', $contact->title);
        $this->assertSame('primary', $contact->contactType);
        $this->assertTrue($contact->isPrimary);
    }

    #[Test]
    public function it_creates_billing_contact(): void
    {
        $contact = VendorContactData::billing(
            name: 'Jane Smith',
            email: 'billing@acme.com',
        );

        $this->assertSame('Jane Smith', $contact->name);
        $this->assertSame('billing@acme.com', $contact->email);
        $this->assertSame('billing', $contact->contactType);
        $this->assertFalse($contact->isPrimary);
    }

    #[Test]
    public function it_creates_technical_contact(): void
    {
        $contact = VendorContactData::technical(
            name: 'Tech Support',
            email: 'support@acme.com',
            phone: '+60198765432',
        );

        $this->assertSame('technical', $contact->contactType);
        $this->assertFalse($contact->isPrimary);
    }

    #[Test]
    public function it_creates_sales_contact(): void
    {
        $contact = VendorContactData::sales(
            name: 'Sales Team',
            email: 'sales@acme.com',
        );

        $this->assertSame('sales', $contact->contactType);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $contact = VendorContactData::primary(
            name: 'John Doe',
            email: 'john@acme.com',
            phone: '+60123456789',
        );

        $array = $contact->toArray();

        $this->assertIsArray($array);
        $this->assertSame('John Doe', $array['name']);
        $this->assertSame('john@acme.com', $array['email']);
        $this->assertSame('+60123456789', $array['phone']);
        $this->assertSame('primary', $array['contact_type']);
        $this->assertTrue($array['is_primary']);
    }

    #[Test]
    public function it_handles_optional_fields(): void
    {
        $contact = VendorContactData::billing(
            name: 'Minimal Contact',
            email: 'minimal@test.com',
        );

        $this->assertNull($contact->phone);
        $this->assertNull($contact->title);
        $this->assertNull($contact->department);
    }

    #[Test]
    public function it_creates_contact_with_all_fields(): void
    {
        $contact = new VendorContactData(
            name: 'Full Contact',
            email: 'full@test.com',
            phone: '+60123456789',
            title: 'Director',
            department: 'Finance',
            contactType: 'primary',
            isPrimary: true,
            preferredLanguage: 'en',
            timezone: 'Asia/Kuala_Lumpur',
        );

        $this->assertSame('Full Contact', $contact->name);
        $this->assertSame('Director', $contact->title);
        $this->assertSame('Finance', $contact->department);
        $this->assertSame('en', $contact->preferredLanguage);
        $this->assertSame('Asia/Kuala_Lumpur', $contact->timezone);
    }
}
