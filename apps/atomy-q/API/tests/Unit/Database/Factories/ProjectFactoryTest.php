<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

final class ProjectFactoryTest extends TestCase
{
    public function test_project_factory_creates_valid_instance(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(Project::class, $project);
        $this->assertNotEmpty($project->name);
    }

    public function test_project_active_state(): void
    {
        $project = Project::factory()->active()->create();

        $this->assertSame('active', $project->status);
    }

    public function test_project_planning_state(): void
    {
        $project = Project::factory()->planning()->create();

        $this->assertSame('planning', $project->status);
    }

    public function test_project_completed_state(): void
    {
        $project = Project::factory()->completed()->create();

        $this->assertSame('completed', $project->status);
    }

    public function test_project_for_tenant_relationship(): void
    {
        $tenant = Tenant::factory()->create();
        $project = Project::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertSame($tenant->id, $project->tenant_id);
    }
}