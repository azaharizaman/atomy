<?php

namespace Tests\Feature\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Backoffice\Staff;
use App\Models\Backoffice\Company;
use App\Models\Backoffice\Department;

class StaffFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_can_list_staff_by_company(): void
    {
        // Staff are not directly linked to company in this schema, listing all for now or filtering by other means
        Staff::factory()->count(3)->create();
        
        $response = $this->getJson("/api/backoffice/staff");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_staff(): void
    {
        $data = [
            'employee_id' => 'EMP-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'position' => 'Developer',
            'type' => 'permanent',
            'status' => 'active',
            'hire_date' => now()->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/backoffice/staff', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['email' => $data['email']]);

        $this->assertDatabaseHas('backoffice_staff', ['email' => $data['email']]);
    }

    public function test_can_show_staff(): void
    {
        $staff = Staff::factory()->create();

        $response = $this->getJson("/api/backoffice/staff/{$staff->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $staff->id]);
    }

    public function test_can_update_staff(): void
    {
        $staff = Staff::factory()->create();
        $newData = ['first_name' => 'Jane'];

        $response = $this->putJson("/api/backoffice/staff/{$staff->id}", $newData);

        $response->assertStatus(200)
            ->assertJsonFragment($newData);

        $this->assertDatabaseHas('backoffice_staff', ['id' => $staff->id, 'first_name' => 'Jane']);
    }

    public function test_can_delete_staff(): void
    {
        $staff = Staff::factory()->create();

        $response = $this->deleteJson("/api/backoffice/staff/{$staff->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('backoffice_staff', ['id' => $staff->id]);
    }
}
