<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Tests\Unit;

use Nexus\Idempotency\Contracts\IdempotencyStoreInterface;
use Nexus\Laravel\Idempotency\Adapters\DatabaseIdempotencyStore;
use PHPUnit\Framework\TestCase;

final class DatabaseIdempotencyStoreTest extends TestCase
{
    public function test_implements_store_interface(): void
    {
        $store = new DatabaseIdempotencyStore();

        $this->assertInstanceOf(IdempotencyStoreInterface::class, $store);
    }
}
