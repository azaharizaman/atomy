<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Rfq;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

final class RfqFactoryTest extends TestCase
{
    public function test_rfq_factory_creates_valid_instance(): void
    {
        $rfq = Rfq::factory()->create();

        $this->assertInstanceOf(Rfq::class, $rfq);
        $this->assertNotEmpty($rfq->rfq_number);
    }

    public function test_rfq_draft_state(): void
    {
        $rfq = Rfq::factory()->draft()->create();

        $this->assertSame('draft', $rfq->status);
    }

    public function test_rfq_published_state(): void
    {
        $rfq = Rfq::factory()->published()->create();

        $this->assertSame('published', $rfq->status);
    }

    public function test_rfq_closed_state(): void
    {
        $rfq = Rfq::factory()->closed()->create();

        $this->assertSame('closed', $rfq->status);
    }

    public function test_rfq_for_project_relationship(): void
    {
        $project = Project::factory()->create();
        $rfq = Rfq::factory()->create(['project_id' => $project->id, 'tenant_id' => $project->tenant_id]);

        $this->assertSame($project->id, $rfq->project_id);
    }

    public function test_rfq_owned_by_relationship(): void
    {
        $user = User::factory()->create();
        $rfq = Rfq::factory()->create(['owner_id' => $user->id, 'tenant_id' => $user->tenant_id]);

        $this->assertSame($user->id, $rfq->owner_id);
    }
}