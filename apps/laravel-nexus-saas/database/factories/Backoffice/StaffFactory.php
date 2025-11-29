<?php

namespace Database\Factories\Backoffice;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Backoffice\Staff;
use App\Models\Backoffice\Company;
use App\Models\Backoffice\Department;
use Illuminate\Support\Str;

class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'tenant_id' => (string) Str::ulid(),
            'employee_id' => strtoupper($this->faker->lexify('EMP-???')),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'position' => $this->faker->jobTitle,
            'type' => 'permanent',
            'status' => 'active',
            'hire_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
