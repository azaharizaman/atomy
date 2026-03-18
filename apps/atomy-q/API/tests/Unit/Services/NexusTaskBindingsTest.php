<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Nexus\Task\Contracts\TaskManagerInterface;
use Nexus\Task\Contracts\TaskPersistInterface;
use Nexus\Task\Contracts\TaskQueryInterface;
use Tests\TestCase;

class NexusTaskBindingsTest extends TestCase
{
    public function test_task_contracts_are_bound_in_container(): void
    {
        $this->assertInstanceOf(
            TaskManagerInterface::class,
            $this->app->make(TaskManagerInterface::class)
        );

        $this->assertInstanceOf(
            TaskQueryInterface::class,
            $this->app->make(TaskQueryInterface::class)
        );

        $this->assertInstanceOf(
            TaskPersistInterface::class,
            $this->app->make(TaskPersistInterface::class)
        );
    }
}
