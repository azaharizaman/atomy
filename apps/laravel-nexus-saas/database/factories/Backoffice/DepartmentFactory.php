<?php

namespace Database\Factories\Backoffice;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Backoffice\Department;
use App\Models\Backoffice\Company;
use Illuminate\Support\Str;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'tenant_id' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'name' => $this->faker->jobTitle . ' Department',
            'code' => strtoupper($this->faker->lexify('DEP-???')),
            'type' => 'core',
            'metadata' => [],
        ];
    }
}
