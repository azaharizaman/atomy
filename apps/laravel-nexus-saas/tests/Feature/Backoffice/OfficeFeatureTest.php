<?php

namespace Tests\Feature\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Backoffice\Office;
use App\Models\Backoffice\Company;

class OfficeFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_can_list_offices(): void
    {
        Office::factory()->count(3)->create();

        $response = $this->getJson('/api/backoffice/offices');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_office(): void
    {
        $company = Company::factory()->create();
        $data = [
            'company_id' => $company->id,
            'name' => $this->faker->city . ' Office',
            'code' => 'OFF-TEST',
            'type' => 'branch',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'postal_code' => '10001',
            'is_head_office' => true,
        ];

        $response = $this->postJson('/api/backoffice/offices', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => $data['name']]);

        $this->assertDatabaseHas('backoffice_offices', ['name' => $data['name']]);
    }

    public function test_can_show_office(): void
    {
        $office = Office::factory()->create();

        $response = $this->getJson("/api/backoffice/offices/{$office->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $office->id]);
    }

    public function test_can_update_office(): void
    {
        $office = Office::factory()->create();
        $newData = ['name' => 'Updated Office Name'];

        $response = $this->putJson("/api/backoffice/offices/{$office->id}", $newData);

        $response->assertStatus(200)
            ->assertJsonFragment($newData);

        $this->assertDatabaseHas('backoffice_offices', ['id' => $office->id, 'name' => 'Updated Office Name']);
    }

    public function test_can_delete_office(): void
    {
        $office = Office::factory()->create();

        $response = $this->deleteJson("/api/backoffice/offices/{$office->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('backoffice_offices', ['id' => $office->id]);
    }
}
