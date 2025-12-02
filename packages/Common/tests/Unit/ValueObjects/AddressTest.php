<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use Nexus\Common\Exceptions\InvalidValueException;
use Nexus\Common\ValueObjects\Address;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    public function test_of_creates_address(): void
    {
        $address = new Address(
            street: '123 Main St',
            street2: 'Apt 4B',
            city: 'Kuala Lumpur',
            state: 'Federal Territory',
            postalCode: '50000',
            country: 'MY'
        );
        
        $this->assertSame('123 Main St', $address->getStreet());
        $this->assertSame('Apt 4B', $address->getStreet2());
        $this->assertSame('Kuala Lumpur', $address->getCity());
        $this->assertSame('Federal Territory', $address->getState());
        $this->assertSame('50000', $address->getPostalCode());
        $this->assertSame('MY', $address->getCountry());
    }

    public function test_of_creates_address_without_street2(): void
    {
        $address = new Address(
            street: '456 Oak Ave',
            street2: null,
            city: 'Singapore',
            state: 'Singapore',
            postalCode: '123456',
            country: 'SG'
        );
        
        $this->assertNull($address->getStreet2());
    }

    public function test_throws_exception_for_invalid_country_code(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Country code must be ISO 3166-1 alpha-2');
        
        new Address(
            street: '123 Main St',
            street2: null,
            city: 'City',
            state: 'State',
            postalCode: '12345',
            country: 'USA' // Should be 'US'
        );
    }

    public function test_get_full_address_with_street2(): void
    {
        $address = new Address(
            street: '123 Main St',
            street2: 'Suite 100',
            city: 'Kuala Lumpur',
            state: 'Federal Territory',
            postalCode: '50000',
            country: 'MY'
        );
        
        $full = $address->getFullAddress();
        
        $this->assertStringContainsString('123 Main St', $full);
        $this->assertStringContainsString('Suite 100', $full);
        $this->assertStringContainsString('Kuala Lumpur', $full);
        $this->assertStringContainsString('50000', $full);
        $this->assertStringContainsString('MY', $full);
    }

    public function test_get_full_address_without_street2(): void
    {
        $address = new Address(
            street: '456 Oak Ave',
            street2: null,
            city: 'Singapore',
            state: 'Singapore',
            postalCode: '123456',
            country: 'SG'
        );
        
        $full = $address->getFullAddress();
        
        $this->assertStringContainsString('456 Oak Ave', $full);
        $this->assertStringNotContainsString('null', $full);
    }

    public function test_compare_to(): void
    {
        $addr1 = new Address('123 Main St', null, 'City A', 'State', '12345', 'MY');
        $addr2 = new Address('456 Oak Ave', null, 'City B', 'State', '12345', 'MY');
        $addr3 = new Address('123 Main St', null, 'City A', 'State', '12345', 'MY');
        
        $this->assertLessThan(0, $addr1->compareTo($addr2));
        $this->assertGreaterThan(0, $addr2->compareTo($addr1));
        $this->assertSame(0, $addr1->compareTo($addr3));
    }

    public function test_equals(): void
    {
        $addr1 = new Address('123 Main St', null, 'Kuala Lumpur', 'FT', '50000', 'MY');
        $addr2 = new Address('123 Main St', null, 'Kuala Lumpur', 'FT', '50000', 'MY');
        $addr3 = new Address('456 Oak Ave', null, 'Singapore', 'SG', '123456', 'SG');
        
        $this->assertTrue($addr1->equals($addr2));
        $this->assertFalse($addr1->equals($addr3));
    }

    public function test_to_array(): void
    {
        $address = new Address(
            street: '123 Main St',
            street2: 'Apt 4B',
            city: 'Kuala Lumpur',
            state: 'Federal Territory',
            postalCode: '50000',
            country: 'MY'
        );
        
        $expected = [
            'street' => '123 Main St',
            'street2' => 'Apt 4B',
            'city' => 'Kuala Lumpur',
            'state' => 'Federal Territory',
            'postal_code' => '50000',
            'country' => 'MY',
        ];
        
        $this->assertSame($expected, $address->toArray());
    }

    public function test_to_string(): void
    {
        $address = new Address('123 Main St', null, 'Kuala Lumpur', 'FT', '50000', 'MY');
        
        $string = $address->toString();
        
        $this->assertStringContainsString('123 Main St', $string);
    }

    public function test_from_array(): void
    {
        $data = [
            'street' => '789 Pine Rd',
            'street2' => null,
            'city' => 'Penang',
            'state' => 'Penang',
            'postal_code' => '10000',
            'country' => 'MY',
        ];
        
        $address = Address::fromArray($data);
        
        $this->assertSame('789 Pine Rd', $address->getStreet());
        $this->assertSame('Penang', $address->getCity());
        $this->assertSame('MY', $address->getCountry());
    }
}
