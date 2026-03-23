<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Tests\Unit;

use Nexus\Laravel\Idempotency\Providers\IdempotencyAdapterServiceProvider;
use PHPUnit\Framework\TestCase;

final class ServiceProviderTest extends TestCase
{
    public function test_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(IdempotencyAdapterServiceProvider::class));
    }
}
