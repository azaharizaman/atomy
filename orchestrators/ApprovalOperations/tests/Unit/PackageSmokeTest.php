<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PackageSmokeTest extends TestCase
{
    public function testPackageAutoloadRegisters(): void
    {
        self::assertTrue(class_exists(\Nexus\Workflow\Core\ApprovalEngine::class));
        self::assertTrue(interface_exists(\Nexus\PolicyEngine\Contracts\PolicyEngineInterface::class));
    }
}
