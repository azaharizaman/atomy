<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Tests\Unit;

use Nexus\Sourcing\ValueObjects\Conflict;
use PHPUnit\Framework\TestCase;

final class ConflictTest extends TestCase
{
    public function test_holds_type_and_message(): void
    {
        $c = new Conflict('uom_mismatch', 'Unit of measure differs from RFQ');

        $this->assertSame('uom_mismatch', $c->type);
        $this->assertSame('Unit of measure differs from RFQ', $c->message);
    }
}
