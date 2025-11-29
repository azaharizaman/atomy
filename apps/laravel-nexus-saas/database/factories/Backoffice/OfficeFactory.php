<?php

namespace Database\Factories\Backoffice;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Backoffice\Office;
use App\Models\Backoffice\Company;
use Illuminate\Support\Str;

class OfficeFactory extends Factory
{
    protected $model = Office::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'tenant_id' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'name' => $this->faker->city . ' Office',
            'code' => strtoupper($this->faker->lexify('OFF-???')),
            'type' => 'branch',
            'status' => 'active',
            'address_line_1' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'country' => $this->faker->countryCode,
            'postal_code' => $this->faker->postcode,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->companyEmail,
            'is_head_office' => false,
        ];
    }
}
