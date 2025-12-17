<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\ValueObjects;

use Nexus\DataPrivacy\Exceptions\InvalidDataSubjectIdException;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataSubjectId::class)]
final class DataSubjectIdTest extends TestCase
{
    public function testConstructorWithValidValue(): void
    {
        $id = new DataSubjectId('user-123');
        
        $this->assertSame('user-123', $id->value);
    }

    public function testConstructorThrowsOnEmptyValue(): void
    {
        $this->expectException(InvalidDataSubjectIdException::class);
        
        new DataSubjectId('');
    }

    public function testConstructorThrowsOnWhitespaceOnly(): void
    {
        $this->expectException(InvalidDataSubjectIdException::class);
        
        new DataSubjectId('   ');
    }

    public function testConstructorThrowsOnTooLongValue(): void
    {
        $this->expectException(InvalidDataSubjectIdException::class);
        
        new DataSubjectId(str_repeat('a', 256));
    }

    public function testFromPartyIdCreatesCorrectFormat(): void
    {
        $id = DataSubjectId::fromPartyId('party-abc-123');
        
        $this->assertSame('party:party-abc-123', $id->value);
    }

    public function testFromEmailCreatesHashedFormat(): void
    {
        $id = DataSubjectId::fromEmail('test@example.com');
        
        $this->assertStringStartsWith('email:', $id->value);
        $this->assertSame(64 + 6, strlen($id->value)); // 6 for 'email:' + 64 for sha256
    }

    public function testFromEmailNormalizesCase(): void
    {
        $id1 = DataSubjectId::fromEmail('Test@Example.COM');
        $id2 = DataSubjectId::fromEmail('test@example.com');
        
        $this->assertSame($id1->value, $id2->value);
    }

    public function testFromExternalCreatesCorrectFormat(): void
    {
        $id = DataSubjectId::fromExternal('crm', 'customer-456');
        
        $this->assertSame('crm:customer-456', $id->value);
    }

    public function testIsPartyIdReturnsTrueForPartyPrefix(): void
    {
        $id = DataSubjectId::fromPartyId('abc');
        
        $this->assertTrue($id->isPartyId());
    }

    public function testIsPartyIdReturnsFalseForEmailPrefix(): void
    {
        $id = DataSubjectId::fromEmail('test@example.com');
        
        $this->assertFalse($id->isPartyId());
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $id1 = new DataSubjectId('test-123');
        $id2 = new DataSubjectId('test-123');
        
        $this->assertTrue($id1->equals($id2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $id1 = new DataSubjectId('test-123');
        $id2 = new DataSubjectId('test-456');
        
        $this->assertFalse($id1->equals($id2));
    }

    public function testToStringReturnsValue(): void
    {
        $id = new DataSubjectId('test-123');
        
        $this->assertSame('test-123', (string) $id);
    }
}
