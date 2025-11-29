<?php

namespace Tests\Feature\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Backoffice\Company;

class CompanyFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_can_list_companies(): void
    {
        Company::factory()->count(3)->create();

        $response = $this->getJson('/api/backoffice/companies');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_company(): void
    {
        $data = [
            'name' => $this->faker->company,
            'code' => 'COMP-001',
            'registration_number' => $this->faker->uuid,
            'tax_id' => $this->faker->uuid,
            'country' => 'US',
            'currency' => 'USD',
        ];

        $response = $this->postJson('/api/backoffice/companies', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => $data['name']]);

        $this->assertDatabaseHas('backoffice_companies', ['name' => $data['name']]);
    }

    public function test_can_show_company(): void
    {
        $company = Company::factory()->create();

        $response = $this->getJson("/api/backoffice/companies/{$company->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $company->id]);
    }

    public function test_can_update_company(): void
    {
        $company = Company::factory()->create();
        $newData = ['name' => 'Updated Company Name'];

        $response = $this->putJson("/api/backoffice/companies/{$company->id}", $newData);

        $response->assertStatus(200)
            ->assertJsonFragment($newData);

        $this->assertDatabaseHas('backoffice_companies', ['id' => $company->id, 'name' => 'Updated Company Name']);
    }

    public function test_can_delete_company(): void
    {
        $company = Company::factory()->create();

        $response = $this->deleteJson("/api/backoffice/companies/{$company->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('backoffice_companies', ['id' => $company->id]);
    }
}
