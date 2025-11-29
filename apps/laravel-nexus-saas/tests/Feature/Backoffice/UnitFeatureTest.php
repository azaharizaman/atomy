<?php

namespace Tests\Feature\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Backoffice\Unit;
use App\Models\Backoffice\Company;
use App\Models\Backoffice\Department;

class UnitFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_can_list_units_by_company(): void
    {
        $company = Company::factory()->create();
        Unit::factory()->count(3)->create(['company_id' => $company->id]);
        // Create another unit for a different company
        Unit::factory()->create();

        $response = $this->getJson("/api/backoffice/units?company_id={$company->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_unit(): void
    {
        $company = Company::factory()->create();
        
        $data = [
            'company_id' => $company->id,
            'name' => 'Backend Team',
            'code' => 'BE-TEAM',
            'type' => 'project_team',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/backoffice/units', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => $data['name']]);

        $this->assertDatabaseHas('backoffice_units', ['name' => $data['name']]);
    }

    public function test_can_show_unit(): void
    {
        $unit = Unit::factory()->create();

        $response = $this->getJson("/api/backoffice/units/{$unit->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $unit->id]);
    }

    public function test_can_update_unit(): void
    {
        $unit = Unit::factory()->create();
        $newData = ['name' => 'Updated Unit Name'];

        $response = $this->putJson("/api/backoffice/units/{$unit->id}", $newData);

        $response->assertStatus(200)
            ->assertJsonFragment($newData);

        $this->assertDatabaseHas('backoffice_units', ['id' => $unit->id, 'name' => 'Updated Unit Name']);
    }

    public function test_can_delete_unit(): void
    {
        $unit = Unit::factory()->create();

        $response = $this->deleteJson("/api/backoffice/units/{$unit->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('backoffice_units', ['id' => $unit->id]);
    }
}
