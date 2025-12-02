<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use Nexus\Common\ValueObjects\ProductId;
use PHPUnit\Framework\TestCase;

final class ProductIdTest extends TestCase
{
    public function test_generate_and_basic_operations(): void
    {
        $id = ProductId::generate();
        $this->assertInstanceOf(ProductId::class, $id);
        $this->assertSame(26, strlen($id->toString()));
        
        $id2 = ProductId::fromString($id->toString());
        $this->assertTrue($id->equals($id2));
    }

    public function test_serialization(): void
    {
        $id = ProductId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $array = $id->toArray();
        $restored = ProductId::fromArray($array);
        
        $this->assertTrue($id->equals($restored));
    }
}
