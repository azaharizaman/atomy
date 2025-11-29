<?php

namespace Tests\Feature\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Backoffice\Transfer;
use App\Models\Backoffice\Staff;
use App\Models\Backoffice\Department;
use App\Models\Backoffice\Company;

class TransferFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_can_request_transfer(): void
    {
        $staff = Staff::factory()->create();
        $department = Department::factory()->create(['company_id' => Company::factory()->create()->id]);

        $response = $this->postJson('/api/backoffice/transfers', [
            'staff_id' => $staff->id,
            'to_department_id' => $department->id,
            'effective_date' => now()->addWeek()->toDateString(),
            'reason' => 'Promotion',
            'transfer_type' => 'promotion',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'pending']);

        $this->assertDatabaseHas('transfers', ['staff_id' => $staff->id, 'status' => 'pending']);
    }

    public function test_can_approve_transfer(): void
    {
        $transfer = Transfer::factory()->create(['status' => 'pending']);

        $response = $this->postJson("/api/backoffice/transfers/{$transfer->id}/approve", [
            'approved_by' => 'Manager',
            'comment' => 'Approved',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'approved']);

        $this->assertDatabaseHas('transfers', ['id' => $transfer->id, 'status' => 'approved']);
    }

    public function test_can_reject_transfer(): void
    {
        $transfer = Transfer::factory()->create(['status' => 'pending']);

        $response = $this->postJson("/api/backoffice/transfers/{$transfer->id}/reject", [
            'reason' => 'Not needed',
            'rejected_by' => 'Manager',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'rejected']);

        $this->assertDatabaseHas('transfers', ['id' => $transfer->id, 'status' => 'rejected']);
    }

    public function test_can_cancel_transfer(): void
    {
        $transfer = Transfer::factory()->create(['status' => 'pending']);

        $response = $this->postJson("/api/backoffice/transfers/{$transfer->id}/cancel");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('transfers', ['id' => $transfer->id]);
    }

    public function test_can_complete_transfer(): void
    {
        $transfer = Transfer::factory()->create([
            'status' => 'approved',
            'effective_date' => now()->subDay(), // Effective date in the past
        ]);

        $response = $this->postJson("/api/backoffice/transfers/{$transfer->id}/complete");

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'completed']);

        $this->assertDatabaseHas('transfers', ['id' => $transfer->id, 'status' => 'completed']);
    }
}
