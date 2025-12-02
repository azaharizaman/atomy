<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Common\ValueObjects\TenantId;
use Nexus\Common\Exceptions\InvalidValueException;

final class TenantIdTest extends TestCase
{
    public function test_generate_creates_valid_ulid(): void
    {
        $id = TenantId::generate();
        
        $this->assertInstanceOf(TenantId::class, $id);
        $this->assertSame(26, strlen($id->toString()));
    }

    public function test_from_string_creates_tenant_id(): void
    {
        $ulid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $id = TenantId::fromString($ulid);
        
        $this->assertSame($ulid, $id->toString());
    }

    public function test_throws_exception_for_invalid_ulid_length(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessageMatches('/.* 26 characters.*/');
        
        TenantId::fromString('too-short');
    }

    public function test_throws_exception_for_invalid_ulid_characters(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessageMatches('/^TenantId must be valid ULID.*/');
        
        TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5F!!'); // Invalid chars
    }

    public function test_equals_returns_true_for_same_id(): void
    {
        $id1 = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $id2 = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        
        $this->assertTrue($id1->equals($id2));
    }

    public function test_equals_returns_false_for_different_ids(): void
    {
        $id1 = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $id2 = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAW');
        
        $this->assertFalse($id1->equals($id2));
    }

    public function test_compare_to(): void
    {
        $id1 = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $id2 = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAW');
        $id3 = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        
        $this->assertLessThan(0, $id1->compareTo($id2));
        $this->assertGreaterThan(0, $id2->compareTo($id1));
        $this->assertSame(0, $id1->compareTo($id3));
    }

    public function test_greater_than(): void
    {
        $id1 = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAW');
        $id2 = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        
        $this->assertTrue($id1->greaterThan($id2));
        $this->assertFalse($id2->greaterThan($id1));
    }

    public function test_less_than(): void
    {
        $id1 = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $id2 = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAW');
        
        $this->assertTrue($id1->lessThan($id2));
        $this->assertFalse($id2->lessThan($id1));
    }

    public function test_to_array(): void
    {
        $id = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $array = $id->toArray();
        
        $this->assertArrayHasKey('value', $array);
        $this->assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $array['value']);
    }

    public function test_from_array(): void
    {
        $data = ['value' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'];
        $id = TenantId::fromArray($data);
        
        $this->assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $id->toString());
    }

    public function test_multiple_generates_are_unique(): void
    {
        $id1 = TenantId::generate();
        $id2 = TenantId::generate();
        
        $this->assertNotEquals($id1->toString(), $id2->toString());
    }
}
