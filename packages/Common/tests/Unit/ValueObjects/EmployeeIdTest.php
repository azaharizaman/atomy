<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use Nexus\Common\ValueObjects\EmployeeId;
use PHPUnit\Framework\TestCase;

final class EmployeeIdTest extends TestCase
{
    public function test_generate_and_basic_operations(): void
    {
        $id = EmployeeId::generate();
        $this->assertInstanceOf(EmployeeId::class, $id);
        $this->assertSame(26, strlen($id->toString()));
        
        $id2 = EmployeeId::fromString($id->toString());
        $this->assertTrue($id->equals($id2));
    }

    public function test_serialization(): void
    {
        $id = EmployeeId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $array = $id->toArray();
        $restored = EmployeeId::fromArray($array);
        
        $this->assertTrue($id->equals($restored));
    }
}
