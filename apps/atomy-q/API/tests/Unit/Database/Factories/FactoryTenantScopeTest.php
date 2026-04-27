<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Approval;
use App\Models\Award;
use App\Models\ComparisonRun;
use App\Models\Project;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\Tenant;
use App\Models\VendorInvitation;
use Tests\TestCase;

final class FactoryTenantScopeTest extends TestCase
{
    public function test_approval_factory_uses_same_tenant(): void
    {
        $approval = Approval::factory()->create();
        $rfq = Rfq::find($approval->rfq_id);
        $comparisonRun = ComparisonRun::find($approval->comparison_run_id);

        $this->assertNotNull($rfq);
        $this->assertNotNull($comparisonRun);
        $this->assertSame($approval->tenant_id, $rfq->tenant_id);
        $this->assertSame($approval->tenant_id, $comparisonRun->tenant_id);
    }

    public function test_approval_approved_state_uses_same_tenant(): void
    {
        $approval = Approval::factory()->approved()->create();
        if ($approval->approved_by) {
            $approvedBy = \App\Models\User::find($approval->approved_by);
            $this->assertNotNull($approvedBy);
            $this->assertSame($approval->tenant_id, $approvedBy->tenant_id);
        }
    }

    public function test_award_factory_uses_same_tenant(): void
    {
        $award = Award::factory()->create();
        $rfq = Rfq::find($award->rfq_id);
        $comparisonRun = ComparisonRun::find($award->comparison_run_id);

        $this->assertNotNull($rfq);
        $this->assertNotNull($comparisonRun);
        $this->assertSame($award->tenant_id, $rfq->tenant_id);
        $this->assertSame($award->tenant_id, $comparisonRun->tenant_id);
    }

    public function test_award_protested_state_uses_ulid(): void
    {
        $award = Award::factory()->protested()->create();

        $this->assertNotNull($award->protest_id);
        $this->assertEquals(26, strlen($award->protest_id));
    }

    public function test_comparison_run_factory_uses_same_tenant(): void
    {
        $comparisonRun = ComparisonRun::factory()->create();
        $rfq = Rfq::find($comparisonRun->rfq_id);

        $this->assertNotNull($rfq);
        $this->assertSame($comparisonRun->tenant_id, $rfq->tenant_id);
    }

    public function test_project_factory_uses_same_tenant_for_manager(): void
    {
        $project = Project::factory()->create();
        $projectManager = \App\Models\User::find($project->project_manager_id);

        $this->assertNotNull($projectManager);
        $this->assertSame($project->tenant_id, $projectManager->tenant_id);
    }

    public function test_rfq_line_item_factory_uses_same_tenant(): void
    {
        $lineItem = RfqLineItem::factory()->create();
        $rfq = Rfq::find($lineItem->rfq_id);

        $this->assertNotNull($rfq);
        $this->assertSame($lineItem->tenant_id, $rfq->tenant_id);
    }

    public function test_rfq_factory_dates_are_ordered_correctly(): void
    {
        $rfq = Rfq::factory()->create();

        $this->assertTrue($rfq->submission_deadline <= $rfq->closing_date);
        $this->assertTrue($rfq->submission_deadline <= $rfq->technical_review_due_at);
        $this->assertTrue($rfq->submission_deadline <= $rfq->financial_review_due_at);
        $this->assertTrue($rfq->submission_deadline <= $rfq->expected_award_at);
    }

    public function test_tenant_factory_code_has_sufficient_entropy(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertGreaterThanOrEqual(7, strlen($tenant->code));
    }
}
