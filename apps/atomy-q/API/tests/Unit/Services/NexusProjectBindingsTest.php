<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Nexus\Project\Contracts\ProjectManagerInterface;
use Nexus\Project\Contracts\ProjectPersistInterface;
use Nexus\Project\Contracts\ProjectQueryInterface;
use Tests\TestCase;

class NexusProjectBindingsTest extends TestCase
{
    public function test_project_contracts_are_bound_in_container(): void
    {
        $this->assertInstanceOf(
            ProjectManagerInterface::class,
            $this->app->make(ProjectManagerInterface::class)
        );

        $this->assertInstanceOf(
            ProjectQueryInterface::class,
            $this->app->make(ProjectQueryInterface::class)
        );

        $this->assertInstanceOf(
            ProjectPersistInterface::class,
            $this->app->make(ProjectPersistInterface::class)
        );
    }
}
