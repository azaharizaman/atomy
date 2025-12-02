<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use Nexus\Common\ValueObjects\CustomerId;
use PHPUnit\Framework\TestCase;

final class CustomerIdTest extends TestCase
{
    public function test_generate_creates_valid_id(): void
    {
        $id = CustomerId::generate();
        
        $this->assertInstanceOf(CustomerId::class, $id);
        $this->assertSame(26, strlen($id->toString()));
    }

    public function test_from_string_creates_id(): void
    {
        $ulid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $id = CustomerId::fromString($ulid);
        
        $this->assertSame($ulid, $id->toString());
    }

    public function test_equals(): void
    {
        $id1 = CustomerId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $id2 = CustomerId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $id3 = CustomerId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAW');
        
        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }

    public function test_to_array(): void
    {
        $id = CustomerId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $array = $id->toArray();
        
        $this->assertArrayHasKey('value', $array);
        $this->assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $array['value']);
    }

    public function test_from_array(): void
    {
        $data = ['value' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'];
        $id = CustomerId::fromArray($data);
        
        $this->assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $id->toString());
    }
}
