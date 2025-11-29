<?php

namespace Tests\Feature\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Backoffice\Department;
use App\Models\Backoffice\Company;

class DepartmentFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_can_list_departments_by_company(): void
    {
        $company = Company::factory()->create();
        Department::factory()->count(3)->create(['company_id' => $company->id]);
        // Create another department for a different company
        Department::factory()->create();

        $response = $this->getJson("/api/backoffice/departments?company_id={$company->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_department(): void
    {
        $company = Company::factory()->create();
        $data = [
            'company_id' => $company->id,
            'name' => 'Engineering',
            'code' => 'ENG',
            'type' => 'core',
            'metadata' => ['key' => 'value'],
        ];

        $response = $this->postJson('/api/backoffice/departments', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => $data['name']]);

        $this->assertDatabaseHas('backoffice_departments', ['name' => $data['name']]);
    }

    public function test_can_show_department(): void
    {
        $department = Department::factory()->create();

        $response = $this->getJson("/api/backoffice/departments/{$department->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $department->id]);
    }

    public function test_can_update_department(): void
    {
        $department = Department::factory()->create();
        $newData = ['name' => 'Updated Department Name'];

        $response = $this->putJson("/api/backoffice/departments/{$department->id}", $newData);

        $response->assertStatus(200)
            ->assertJsonFragment($newData);

        $this->assertDatabaseHas('backoffice_departments', ['id' => $department->id, 'name' => 'Updated Department Name']);
    }

    public function test_can_delete_department(): void
    {
        $department = Department::factory()->create();

        $response = $this->deleteJson("/api/backoffice/departments/{$department->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('backoffice_departments', ['id' => $department->id]);
    }
}
