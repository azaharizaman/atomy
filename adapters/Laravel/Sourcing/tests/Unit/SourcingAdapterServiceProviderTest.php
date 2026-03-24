<?php

declare(strict_types=1);

namespace Nexus\Laravel\Sourcing\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Smoke test: provider source is present. Resolving `SourcingAdapterServiceProvider`
 * requires `illuminate/support` (see package `composer.json`); container integration
 * tests belong in the host app or with Orchestra Testbench if added as a dev dependency.
 */
final class SourcingAdapterServiceProviderTest extends TestCase
{
    public function test_provider_source_file_exists(): void
    {
        $path = dirname(__DIR__, 2) . '/src/Providers/SourcingAdapterServiceProvider.php';
        $this->assertFileExists($path);
        $contents = (string) file_get_contents($path);
        $this->assertStringContainsString('final class SourcingAdapterServiceProvider', $contents);
    }
}
