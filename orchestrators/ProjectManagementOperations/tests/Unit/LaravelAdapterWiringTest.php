<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Tests\Unit;

use Nexus\ProjectManagementOperations\Contracts\ProjectTaskIdsQueryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Asserts app contract interfaces required by the Laravel adapter exist and have the expected API.
 * Full resolution tests run in the API app (ProjectManagementOperationsLaravelWiringTest).
 */
final class LaravelAdapterWiringTest extends TestCase
{
    public function test_project_task_ids_query_interface_has_required_method(): void
    {
        $this->assertTrue(interface_exists(ProjectTaskIdsQueryInterface::class));
        $method = (new \ReflectionClass(ProjectTaskIdsQueryInterface::class))->getMethod('getTaskIdsForProject');
        $this->assertSame(2, $method->getNumberOfRequiredParameters());
        $params = $method->getParameters();
        $this->assertSame('tenantId', $params[0]->getName());
        $this->assertSame('projectId', $params[1]->getName());
    }
}
