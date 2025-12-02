<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use Nexus\Common\Exceptions\InvalidValueException;
use Nexus\Common\ValueObjects\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    public function test_of_creates_valid_phone_number(): void
    {
        $phone = new PhoneNumber('+60123456789');
        
        $this->assertSame('+60123456789', $phone->getValue());
    }

    public function test_throws_exception_for_invalid_format(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Phone number must be in E.164 format');
        
        new PhoneNumber('123456');
    }

    public function test_throws_exception_for_missing_plus(): void
    {
        $this->expectException(InvalidValueException::class);
        
        new PhoneNumber('60123456789');
    }

    public function test_get_country_code_returns_country_code(): void
    {
        $phone = new PhoneNumber('+60123456789');
        
        $this->assertSame('+60', $phone->getCountryCode());
    }

    public function test_get_country_code_for_single_digit(): void
    {
        $phone = new PhoneNumber('+12125551234');
        
        $this->assertSame('+1', $phone->getCountryCode());
    }

    public function test_format_returns_formatted_number(): void
    {
        $phone = new PhoneNumber('+60123456789');
        
        $formatted = $phone->format();
        
        $this->assertStringContainsString('+60', $formatted);
    }

    public function test_compare_to(): void
    {
        $phone1 = new PhoneNumber('+60123456789');
        $phone2 = new PhoneNumber('+60123456790');
        $phone3 = new PhoneNumber('+60123456789');
        
        $this->assertSame(-1, $phone1->compareTo($phone2));
        $this->assertSame(1, $phone2->compareTo($phone1));
        $this->assertSame(0, $phone1->compareTo($phone3));
    }

    public function test_equals(): void
    {
        $phone1 = new PhoneNumber('+60123456789');
        $phone2 = new PhoneNumber('+60123456789');
        $phone3 = new PhoneNumber('+60987654321');
        
        $this->assertTrue($phone1->equals($phone2));
        $this->assertFalse($phone1->equals($phone3));
    }

    public function test_greater_than(): void
    {
        $phone1 = new PhoneNumber('+60123456790');
        $phone2 = new PhoneNumber('+60123456789');
        
        $this->assertTrue($phone1->greaterThan($phone2));
        $this->assertFalse($phone2->greaterThan($phone1));
    }

    public function test_less_than(): void
    {
        $phone1 = new PhoneNumber('+60123456789');
        $phone2 = new PhoneNumber('+60123456790');
        
        $this->assertTrue($phone1->lessThan($phone2));
        $this->assertFalse($phone2->lessThan($phone1));
    }

    public function test_to_array(): void
    {
        $phone = new PhoneNumber('+60123456789');
        
        $expected = ['value' => '+60123456789'];
        $this->assertSame($expected, $phone->toArray());
    }

    public function test_to_string(): void
    {
        $phone = new PhoneNumber('+60123456789');
        
        $this->assertSame('+60123456789', $phone->toString());
    }

    public function test_from_array(): void
    {
        $data = ['value' => '+60123456789'];
        
        $phone = PhoneNumber::fromArray($data);
        
        $this->assertSame('+60123456789', $phone->getValue());
    }
}
