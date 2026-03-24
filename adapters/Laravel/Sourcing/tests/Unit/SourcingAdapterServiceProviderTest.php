<?php

declare(strict_types=1);

namespace Nexus\Laravel\Sourcing\Tests\Unit;

use Nexus\Laravel\Sourcing\Providers\SourcingAdapterServiceProvider;
use PHPUnit\Framework\TestCase;

final class SourcingAdapterServiceProviderTest extends TestCase
{
    public function test_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(SourcingAdapterServiceProvider::class));
    }
}
