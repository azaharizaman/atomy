<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use Nexus\Common\Exceptions\InvalidValueException;
use Nexus\Common\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_of_creates_valid_email(): void
    {
        $email = new Email('test@example.com');
        
        $this->assertSame('test@example.com', $email->getValue());
    }

    public function test_email_is_normalized_to_lowercase(): void
    {
        $email = new Email('Test@EXAMPLE.COM');
        
        $this->assertSame('test@example.com', $email->getValue());
    }

    public function test_throws_exception_for_invalid_email(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid email address');
        
        new Email('invalid-email');
    }

    public function test_throws_exception_for_empty_email(): void
    {
        $this->expectException(InvalidValueException::class);
        
        new Email('');
    }

    public function test_get_domain_returns_domain_part(): void
    {
        $email = new Email('user@example.com');
        
        $this->assertSame('example.com', $email->getDomain());
    }

    public function test_get_local_part_returns_local_part(): void
    {
        $email = new Email('john.doe@example.com');
        
        $this->assertSame('john.doe', $email->getLocalPart());
    }

    public function test_compare_to(): void
    {
        $email1 = new Email('alice@example.com');
        $email2 = new Email('bob@example.com');
        $email3 = new Email('alice@example.com');
        
        $this->assertSame(-1, $email1->compareTo($email2));
        $this->assertSame(1, $email2->compareTo($email1));
        $this->assertSame(0, $email1->compareTo($email3));
    }

    public function test_equals(): void
    {
        $email1 = new Email('test@example.com');
        $email2 = new Email('TEST@example.com'); // Case insensitive
        $email3 = new Email('other@example.com');
        
        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    public function test_greater_than(): void
    {
        $email1 = new Email('bob@example.com');
        $email2 = new Email('alice@example.com');
        
        $this->assertTrue($email1->greaterThan($email2));
        $this->assertFalse($email2->greaterThan($email1));
    }

    public function test_less_than(): void
    {
        $email1 = new Email('alice@example.com');
        $email2 = new Email('bob@example.com');
        
        $this->assertTrue($email1->lessThan($email2));
        $this->assertFalse($email2->lessThan($email1));
    }

    public function test_to_array(): void
    {
        $email = new Email('test@example.com');
        
        $expected = ['value' => 'test@example.com'];
        $this->assertSame($expected, $email->toArray());
    }

    public function test_to_string(): void
    {
        $email = new Email('test@example.com');
        
        $this->assertSame('test@example.com', $email->toString());
    }

    public function test_from_array(): void
    {
        $data = ['value' => 'user@domain.com'];
        
        $email = Email::fromArray($data);
        
        $this->assertSame('user@domain.com', $email->getValue());
    }
}
