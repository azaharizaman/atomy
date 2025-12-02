<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use Nexus\Common\ValueObjects\VendorId;
use PHPUnit\Framework\TestCase;

final class VendorIdTest extends TestCase
{
    public function test_generate_and_basic_operations(): void
    {
        $id = VendorId::generate();
        $this->assertInstanceOf(VendorId::class, $id);
        $this->assertSame(26, strlen($id->toString()));
        
        $id2 = VendorId::fromString($id->toString());
        $this->assertTrue($id->equals($id2));
    }

    public function test_serialization(): void
    {
        $id = VendorId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $array = $id->toArray();
        $restored = VendorId::fromArray($array);
        
        $this->assertTrue($id->equals($restored));
    }
}
