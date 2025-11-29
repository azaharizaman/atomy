<?php

namespace Database\Factories\Backoffice;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Backoffice\Company;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'tenant_id' => (string) Str::ulid(),
            'code' => $this->faker->unique()->slug,
            'name' => $this->faker->company,
            'registration_number' => $this->faker->uuid,
            'registration_date' => $this->faker->date(),
            'jurisdiction' => $this->faker->countryCode,
            'status' => 'active',
            'tax_id' => $this->faker->uuid,
            'metadata' => [],
        ];
    }
}
