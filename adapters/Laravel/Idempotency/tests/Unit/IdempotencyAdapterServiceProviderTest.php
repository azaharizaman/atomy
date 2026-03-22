<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nexus\Laravel\Idempotency\Providers\IdempotencyAdapterServiceProvider;

class IdempotencyAdapterServiceProviderTest extends TestCase
{
    public function test_service_provider_exists(): void
    {
        $this->assertTrue(class_exists(IdempotencyAdapterServiceProvider::class));
    }
}
