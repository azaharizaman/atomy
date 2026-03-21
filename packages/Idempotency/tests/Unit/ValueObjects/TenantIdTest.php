<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Tests\Unit\ValueObjects;

use Nexus\Idempotency\Exceptions\IdempotencyKeyInvalidException;
use Nexus\Idempotency\ValueObjects\TenantId;
use PHPUnit\Framework\TestCase;

final class TenantIdTest extends TestCase
{
    public function testTrimsValue(): void
    {
        $id = new TenantId('  t1  ');
        $this->assertSame('t1', $id->value);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(IdempotencyKeyInvalidException::class);
        new TenantId('   ');
    }
}
