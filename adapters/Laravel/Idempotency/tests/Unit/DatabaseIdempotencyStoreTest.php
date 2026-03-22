<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nexus\Laravel\Idempotency\Adapters\DatabaseIdempotencyStore;
use Nexus\Laravel\Idempotency\Models\IdempotencyRecord as EloquentModel;

class DatabaseIdempotencyStoreTest extends TestCase
{
    public function test_implements_store_interface(): void
    {
        $store = new DatabaseIdempotencyStore(
            $this->createMock(EloquentModel::class)
        );
        
        $this->assertInstanceOf(
            \Nexus\Idempotency\Contracts\IdempotencyStoreInterface::class,
            $store
        );
    }
}
